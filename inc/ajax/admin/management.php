<?php
/**
 * Management AJAX endpoints — customers and activity log (PRD §8.2).
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ─────────────────────────────────────────────────────────────────────────────
// Customers
// ─────────────────────────────────────────────────────────────────────────────

add_action( 'wp_ajax_gti_delete_customer', 'gti_ajax_delete_customer' );
function gti_ajax_delete_customer() {
    gti_ajax_guard( 'gti_manage_customers' );

    global $wpdb;

    $id  = (int) ( $_POST['id'] ?? 0 );
    $row = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}gti_customers WHERE id = %d", $id
    ), ARRAY_A );

    if ( ! $row ) {
        gti_send_json_error( 'Customer tidak ditemukan.', 'not_found', 404 );
    }

    if ( false === $wpdb->delete( $wpdb->prefix . 'gti_customers', array( 'id' => $id ), array( '%d' ) ) ) {
        gti_send_json_error( 'Gagal menghapus customer.', 'db_error' );
    }

    gti_log_current_activity( 'delete', 'Deleted customer: ' . $row['name'], 'customer', $id );

    wp_send_json_success( array(
        'deleted' => true,
        // Related rows are deliberately left in place — deleting a contact
        // record must not erase the order history attached to it.
        'message' => 'Customer dihapus. Request dan quotation terkait tetap tersimpan.',
    ) );
}

add_action( 'wp_ajax_gti_get_customer', 'gti_ajax_get_customer' );
function gti_ajax_get_customer() {
    gti_ajax_guard( 'gti_manage_customers' );

    global $wpdb;

    $id  = (int) ( $_POST['id'] ?? 0 );
    $row = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}gti_customers WHERE id = %d", $id
    ), ARRAY_A );

    if ( ! $row ) {
        gti_send_json_error( 'Customer tidak ditemukan.', 'not_found', 404 );
    }

    wp_send_json_success( array( 'customer' => $row ) );
}

// ─────────────────────────────────────────────────────────────────────────────
// Activity log
// ─────────────────────────────────────────────────────────────────────────────

add_action( 'wp_ajax_gti_export_activity_log', 'gti_ajax_export_activity_log' );
function gti_ajax_export_activity_log() {
    gti_ajax_guard( 'gti_manage_settings' );

    global $wpdb;

    $table = $wpdb->prefix . 'gti_activity_log';
    $where = array( '1=1' );
    $args  = array();

    $search = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
    if ( $search !== '' ) {
        $like    = '%' . $wpdb->esc_like( $search ) . '%';
        $where[] = '(description LIKE %s OR action LIKE %s OR entity_type LIKE %s)';
        array_push( $args, $like, $like, $like );
    }

    $action = sanitize_text_field( wp_unslash( $_POST['action_filter'] ?? '' ) );
    if ( $action !== '' ) {
        $where[] = 'action = %s';
        $args[]  = $action;
    }

    $user_id = (int) ( $_POST['user_id'] ?? 0 );
    if ( $user_id ) {
        $where[] = 'user_id = %d';
        $args[]  = $user_id;
    }

    foreach ( array( 'date_from' => '>=', 'date_to' => '<=' ) as $key => $op ) {
        $value = sanitize_text_field( wp_unslash( $_POST[ $key ] ?? '' ) );
        if ( $value ) {
            $where[] = "created_at {$op} %s";
            $args[]  = $value . ( $key === 'date_from' ? ' 00:00:00' : ' 23:59:59' );
        }
    }

    $sql = "SELECT l.*, u.display_name FROM {$table} l
            LEFT JOIN {$wpdb->users} u ON u.ID = l.user_id
            WHERE " . implode( ' AND ', $where ) . '
            ORDER BY l.created_at DESC LIMIT 10000';

    $rows = $args ? $wpdb->get_results( $wpdb->prepare( $sql, $args ), ARRAY_A )
                  : $wpdb->get_results( $sql, ARRAY_A );

    $csv = fopen( 'php://temp', 'r+' );
    fputcsv( $csv, array( 'Date', 'User', 'Action', 'Entity', 'Entity ID', 'Description', 'IP' ) );

    foreach ( (array) $rows as $row ) {
        fputcsv( $csv, array(
            $row['created_at'],
            $row['display_name'] ?: '—',
            $row['action'],
            $row['entity_type'],
            $row['entity_id'],
            $row['description'],
            $row['ip_address'],
        ) );
    }

    rewind( $csv );
    $content = stream_get_contents( $csv );
    fclose( $csv );

    gti_log_current_activity( 'export', sprintf( 'Exported %d activity log rows', count( (array) $rows ) ), 'system', 0 );

    wp_send_json_success( array(
        'filename' => 'gti-activity-log-' . date( 'Ymd-His' ) . '.csv',
        // Handed back as base64 so the browser can build the download itself;
        // admin-ajax has already emitted headers by this point.
        'content'  => base64_encode( "\xEF\xBB\xBF" . $content ),
        'rows'     => count( (array) $rows ),
    ) );
}

add_action( 'wp_ajax_gti_get_activity_detail', 'gti_ajax_get_activity_detail' );
function gti_ajax_get_activity_detail() {
    gti_ajax_guard( 'gti_manage_settings' );

    global $wpdb;

    $id  = (int) ( $_POST['id'] ?? 0 );
    $row = $wpdb->get_row( $wpdb->prepare(
        "SELECT l.*, u.display_name FROM {$wpdb->prefix}gti_activity_log l
         LEFT JOIN {$wpdb->users} u ON u.ID = l.user_id
         WHERE l.id = %d", $id
    ), ARRAY_A );

    if ( ! $row ) {
        gti_send_json_error( 'Entri tidak ditemukan.', 'not_found', 404 );
    }

    $row['details_parsed'] = $row['details'] ? json_decode( $row['details'], true ) : null;

    wp_send_json_success( array( 'entry' => $row ) );
}
