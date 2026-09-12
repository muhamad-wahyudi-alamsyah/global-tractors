<?php
/**
 * Assignment & row-level scoping (PRD §5.4).
 *
 * A user without gti_view_all_requests sees only the rows assigned to them.
 * The scoping clause has to be applied to the list query, the count query and
 * the stat cards alike — if the stat cards skip it, they show totals that do
 * not match the table underneath.
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Users who can be made PIC of an order.
 *
 * @return WP_User[]
 */
function gti_assignable_users() {
    static $cache = null;

    if ( $cache !== null ) {
        return $cache;
    }

    $users = get_users( array(
        'orderby' => 'display_name',
        'order'   => 'ASC',
        'number'  => 200,
    ) );

    $cache = array();
    foreach ( $users as $user ) {
        if ( user_can( $user, 'gti_manage_quotations' ) || user_can( $user, 'gti_manage_requests' ) ) {
            $cache[] = $user;
        }
    }

    return $cache;
}

/**
 * Does the current user see every row, or only their own?
 */
function gti_can_view_all( $entity_type = '' ) {
    return current_user_can( 'gti_view_all_requests' ) || current_user_can( 'manage_options' );
}

/**
 * SQL fragment restricting a query to the current user's rows.
 *
 * Returns '' for users who may see everything, so it is safe to concatenate
 * unconditionally.
 *
 * @param string $entity_type request|quotation|sell
 * @param string $alias       Optional table alias, e.g. 'q'.
 */
function gti_scope_where_sql( $entity_type, $alias = '' ) {
    if ( gti_can_view_all( $entity_type ) ) {
        return '';
    }

    $prefix = $alias ? $alias . '.' : '';

    return ' AND ' . $prefix . 'assigned_to = ' . (int) get_current_user_id();
}

/**
 * Assign (or unassign, with $user_id = 0) an inbox row.
 *
 * @return array{ok: bool, message: string, sales_pic: string, notified: bool}
 */
function gti_assign_entity( $entity_type, $id, $user_id, $notify = true ) {
    global $wpdb;

    $table = gti_entity_table( $entity_type );
    $row   = gti_entity_row( $entity_type, $id );

    if ( ! $table || ! $row ) {
        return array( 'ok' => false, 'message' => 'Data tidak ditemukan.', 'sales_pic' => '', 'notified' => false );
    }

    $user_id = (int) $user_id;
    $user    = $user_id ? get_userdata( $user_id ) : null;

    if ( $user_id && ! $user ) {
        return array( 'ok' => false, 'message' => 'User tidak ditemukan.', 'sales_pic' => '', 'notified' => false );
    }

    $sales_pic = $user ? $user->display_name : '';

    $updated = $wpdb->update(
        $table,
        array(
            'assigned_to' => $user_id ?: null,
            'assigned_at' => $user_id ? current_time( 'mysql' ) : null,
            'sales_pic'   => $sales_pic ?: null,
            'updated_by'  => get_current_user_id() ?: null,
        ),
        array( 'id' => (int) $id ),
        array( '%d', '%s', '%s', '%d' ),
        array( '%d' )
    );

    if ( $updated === false ) {
        return array( 'ok' => false, 'message' => 'Gagal menyimpan perubahan PIC.', 'sales_pic' => '', 'notified' => false );
    }

    $note = $user_id ? sprintf( 'Assigned to %s', $sales_pic ) : 'Unassigned';

    // Recorded on the timeline at the row's current status, with no customer email.
    gti_record_status_change( $entity_type, $id, $row['status'], $row['status'], array(
        'note'  => $note,
        'email' => false,
    ) );

    gti_log_current_activity( 'assign', $note, $entity_type, (int) $id, array( 'user_id' => $user_id ) );

    $notified = false;
    if ( $notify && $user_id && is_email( $user->user_email ) ) {
        $notified = gti_notify_assignee( $entity_type, $id, $row, $user );
    }

    return array( 'ok' => true, 'message' => $note, 'sales_pic' => $sales_pic, 'notified' => $notified );
}

/**
 * Internal heads-up to the new PIC, with a deep link to the row.
 */
function gti_notify_assignee( $entity_type, $id, array $row, WP_User $user ) {
    $slug = array(
        'request'   => 'request-equipment',
        'quotation' => 'request-quotation',
        'sell'      => 'sell-equipment',
    );

    $page = isset( $slug[ $entity_type ] ) ? $slug[ $entity_type ] : '';
    $link = gti_dashboard_url( $page ) . '?highlight=' . (int) $id;
    $ref  = gti_entity_ref( $entity_type, $row );

    $body = GTI_Mailer::render_layout( array(
        'title'     => 'Pesanan baru ditugaskan kepada Anda',
        'body_html' => '<p style="color:#374151;line-height:1.6;margin:0 0 15px 0;">Pesanan <strong>' . esc_html( $ref )
                     . '</strong> dari <strong>' . esc_html( $row['customer_name'] ?? '—' )
                     . '</strong> kini menjadi tanggung jawab Anda.</p>',
        'ref'       => $ref,
        'cta'       => array( 'url' => $link, 'label' => 'Buka pesanan' ),
    ) );

    return (bool) wp_mail(
        $user->user_email,
        sprintf( '[GTI] Pesanan %s ditugaskan kepada Anda', $ref ),
        $body,
        array( 'Content-Type: text/html; charset=UTF-8' )
    );
}

/**
 * Count of rows currently assigned to a user, across the three inboxes.
 */
function gti_assigned_order_count( $user_id ) {
    global $wpdb;

    $user_id = (int) $user_id;
    $total   = 0;

    foreach ( array( 'gti_requests', 'gti_quotations', 'gti_sell_requests' ) as $table ) {
        $total += (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}{$table} WHERE assigned_to = %d", $user_id
        ) );
    }

    return $total;
}
