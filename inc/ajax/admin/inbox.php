<?php
/**
 * Inbox AJAX endpoints (PRD §8.2).
 *
 * Every handler follows the same order: nonce → capability → input validation →
 * transition check → write → history → hook → response. The email is a
 * consequence of gti_status_changed, so it can only follow a successful write
 * (this is the fix for A-04).
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Reject the request unless the current user owns this row, for users who
 * cannot see everything.
 */
function gti_guard_row_scope( $entity_type, array $row ) {
    if ( gti_can_view_all( $entity_type ) ) {
        return;
    }

    if ( (int) ( $row['assigned_to'] ?? 0 ) !== get_current_user_id() ) {
        gti_send_json_error( 'Pesanan ini bukan tanggung jawab Anda.', 'not_assigned', 403 );
    }
}

/**
 * Shared tail for a status transition: write, record, respond.
 *
 * @param array $extra Additional columns to write alongside the status.
 */
function gti_apply_status_change( $entity_type, $id, $to_status, array $extra = array(), array $context = array() ) {
    global $wpdb;

    $row = gti_entity_row( $entity_type, $id );
    if ( ! $row ) {
        gti_send_json_error( 'Data tidak ditemukan.', 'not_found', 404 );
    }

    gti_guard_row_scope( $entity_type, $row );

    $from = $row['status'];
    if ( ! gti_status_can_transition( $entity_type, $from, $to_status ) ) {
        gti_send_json_error(
            sprintf( 'Transisi status tidak valid: %s → %s.',
                gti_status_label( $entity_type, $from ),
                gti_status_label( $entity_type, $to_status ) ),
            'invalid_transition'
        );
    }

    $data = array_merge( $extra, array(
        'status'     => $to_status,
        'updated_by' => get_current_user_id() ?: null,
    ) );

    $columns = $wpdb->get_col( 'SHOW COLUMNS FROM ' . gti_entity_table( $entity_type ) );
    $data    = array_intersect_key( $data, array_flip( (array) $columns ) );

    $updated = $wpdb->update( gti_entity_table( $entity_type ), $data, array( 'id' => (int) $id ), null, array( '%d' ) );

    if ( $updated === false ) {
        gti_send_json_error( 'Gagal menyimpan perubahan status.', 'db_error' );
    }

    gti_record_status_change( $entity_type, $id, $from, $to_status, $context );

    gti_log_current_activity(
        'status_change',
        sprintf( '%s: %s → %s', gti_entity_ref( $entity_type, $row ),
            gti_status_label( $entity_type, $from ), gti_status_label( $entity_type, $to_status ) ),
        $entity_type,
        (int) $id,
        array( 'from' => $from, 'to' => $to_status )
    );

    return array( 'row' => $row, 'from' => $from );
}

// ─────────────────────────────────────────────────────────────────────────────
// C-01 — Send Proposal (Request Equipment)
// ─────────────────────────────────────────────────────────────────────────────

add_action( 'wp_ajax_gti_send_proposal', 'gti_ajax_send_proposal' );
function gti_ajax_send_proposal() {
    gti_ajax_guard( 'gti_manage_requests' );

    if ( ! current_user_can( 'gti_upload_documents' ) ) {
        gti_send_json_error( 'Anda tidak berhak mengunggah dokumen.', 'forbidden', 403 );
    }

    $id   = (int) ( $_POST['id'] ?? 0 );
    $note = wp_kses_post( wp_unslash( $_POST['note'] ?? '' ) );

    $row = gti_entity_row( 'request', $id );
    if ( ! $row ) {
        gti_send_json_error( 'Request tidak ditemukan.', 'not_found', 404 );
    }
    gti_guard_row_scope( 'request', $row );

    if ( ! gti_status_can_transition( 'request', $row['status'], 'proposal_sent' ) ) {
        gti_send_json_error( 'Proposal hanya bisa dikirim dari status Processing.', 'invalid_transition' );
    }

    // Upload first: a failed upload must not move the status.
    $attachment_id = gti_attach_document( array(
        'entity_type' => 'request',
        'entity_id'   => $id,
        'kind'        => 'proposal',
        'file_key'    => 'file',
        'note'        => $note,
    ) );

    if ( is_wp_error( $attachment_id ) ) {
        gti_send_json_error( $attachment_id->get_error_message(), $attachment_id->get_error_code() );
    }

    gti_apply_status_change( 'request', $id, 'proposal_sent', array(), array(
        'attachment_ids' => array( $attachment_id ),
        'note'           => $note,
    ) );

    gti_send_inbox_response( 'request', $id, 'proposal_sent', array( $attachment_id ) );
}

// ─────────────────────────────────────────────────────────────────────────────
// C-02 — Create Quotation (Request Quotation)
// ─────────────────────────────────────────────────────────────────────────────

add_action( 'wp_ajax_gti_create_quotation', 'gti_ajax_create_quotation' );
function gti_ajax_create_quotation() {
    gti_ajax_guard( 'gti_manage_quotations' );

    if ( ! current_user_can( 'gti_upload_documents' ) ) {
        gti_send_json_error( 'Anda tidak berhak mengunggah dokumen.', 'forbidden', 403 );
    }

    $id     = (int) ( $_POST['id'] ?? 0 );
    $number = sanitize_text_field( wp_unslash( $_POST['quotation_number'] ?? '' ) );
    $total  = (float) ( $_POST['total'] ?? 0 );
    $valid  = sanitize_text_field( wp_unslash( $_POST['valid_until'] ?? '' ) );
    $terms  = sanitize_text_field( wp_unslash( $_POST['payment_terms'] ?? '' ) );
    $where  = sanitize_text_field( wp_unslash( $_POST['delivery_location'] ?? '' ) );
    $note   = wp_kses_post( wp_unslash( $_POST['note'] ?? '' ) );

    $row = gti_entity_row( 'quotation', $id );
    if ( ! $row ) {
        gti_send_json_error( 'Quotation tidak ditemukan.', 'not_found', 404 );
    }
    gti_guard_row_scope( 'quotation', $row );

    if ( ! gti_status_can_transition( 'quotation', $row['status'], 'waiting_customer' ) ) {
        gti_send_json_error( 'Quotation hanya bisa dikirim dari status Processing.', 'invalid_transition' );
    }

    if ( $number === '' ) {
        gti_send_json_error( 'Nomor quotation wajib diisi.', 'missing_number' );
    }
    if ( $total <= 0 ) {
        gti_send_json_error( 'Total nilai quotation wajib diisi.', 'missing_total' );
    }
    if ( ! $valid || ! strtotime( $valid ) ) {
        gti_send_json_error( 'Tanggal berlaku sampai wajib diisi.', 'missing_valid_until' );
    }

    $attachment_id = gti_attach_document( array(
        'entity_type' => 'quotation',
        'entity_id'   => $id,
        'kind'        => 'quotation',
        'file_key'    => 'file',
        'note'        => $note,
    ) );

    if ( is_wp_error( $attachment_id ) ) {
        gti_send_json_error( $attachment_id->get_error_message(), $attachment_id->get_error_code() );
    }

    gti_apply_status_change( 'quotation', $id, 'waiting_customer', array(
        'quotation_id'      => $number,
        'total'             => $total,
        'valid_until'       => date( 'Y-m-d', strtotime( $valid ) ),
        'payment_terms'     => $terms ?: null,
        'delivery_location' => $where ?: null,
    ), array(
        'attachment_ids' => array( $attachment_id ),
        'note'           => $note,
    ) );

    gti_send_inbox_response( 'quotation', $id, 'waiting_customer', array( $attachment_id ) );
}

// ─────────────────────────────────────────────────────────────────────────────
// C-05 — Request Invoice (Sell Equipment)
// ─────────────────────────────────────────────────────────────────────────────

add_action( 'wp_ajax_gti_request_invoice', 'gti_ajax_request_invoice' );
function gti_ajax_request_invoice() {
    gti_ajax_guard( 'gti_manage_requests' );

    $id       = (int) ( $_POST['id'] ?? 0 );
    $deadline = max( 1, min( 90, (int) ( $_POST['deadline_days'] ?? 7 ) ) );
    $note     = wp_kses_post( wp_unslash( $_POST['note'] ?? '' ) );
    $docs     = array_map( 'sanitize_text_field', (array) ( $_POST['documents'] ?? array() ) );

    // Feed the extras the template reads into the render call.
    add_filter( 'gti_mail_template_vars', function ( $vars, $template_key ) use ( $docs, $deadline ) {
        if ( $template_key === 'sell-invoice-requested' ) {
            $vars['invoice_documents']     = $docs;
            $vars['invoice_deadline_days'] = $deadline;
        }
        return $vars;
    }, 10, 2 );

    gti_apply_status_change( 'sell', $id, 'invoice_requested', array(
        'invoice_requested_at' => current_time( 'mysql' ),
    ), array( 'note' => $note ) );

    gti_send_inbox_response( 'sell', $id, 'invoice_requested' );
}

// ─────────────────────────────────────────────────────────────────────────────
// Generic status update — replaces the three per-entity handlers' email hooks
// ─────────────────────────────────────────────────────────────────────────────

add_action( 'wp_ajax_gti_change_status', 'gti_ajax_change_status' );
function gti_ajax_change_status() {
    $entity_type = sanitize_key( $_POST['entity_type'] ?? '' );
    $caps        = array(
        'request'   => 'gti_manage_requests',
        'quotation' => 'gti_manage_quotations',
        'sell'      => 'gti_manage_requests',
    );

    if ( ! isset( $caps[ $entity_type ] ) ) {
        gti_send_json_error( 'Entity tidak dikenal.', 'bad_entity' );
    }

    gti_ajax_guard( $caps[ $entity_type ] );

    $id     = (int) ( $_POST['id'] ?? 0 );
    $status = sanitize_key( $_POST['status'] ?? '' );
    $note   = wp_kses_post( wp_unslash( $_POST['note'] ?? '' ) );

    // Statuses that require a document cannot be reached through this endpoint —
    // they have their own upload flow.
    if ( gti_status_requires_attachment( $entity_type, $status ) ) {
        gti_send_json_error( 'Status ini memerlukan dokumen. Gunakan tombol unggah dokumen.', 'requires_attachment' );
    }

    $extra = array();
    if ( $entity_type === 'sell' && $status === 'completed' ) {
        $extra['invoice_received_at'] = current_time( 'mysql' );
    }

    gti_apply_status_change( $entity_type, $id, $status, $extra, array( 'note' => $note ) );

    gti_send_inbox_response( $entity_type, $id, $status );
}

// ─────────────────────────────────────────────────────────────────────────────
// C-03 — Compose email
// ─────────────────────────────────────────────────────────────────────────────

add_action( 'wp_ajax_gti_send_customer_email', 'gti_ajax_send_customer_email' );
function gti_ajax_send_customer_email() {
    gti_ajax_guard( array( 'gti_manage_requests', 'gti_manage_quotations', 'gti_manage_customers' ) );

    if ( ! current_user_can( 'gti_send_email' ) ) {
        gti_send_json_error( 'Anda tidak berhak mengirim email.', 'forbidden', 403 );
    }

    $entity_type = sanitize_key( $_POST['entity_type'] ?? '' );
    $id          = (int) ( $_POST['id'] ?? 0 );
    $subject     = sanitize_text_field( wp_unslash( $_POST['subject'] ?? '' ) );
    $message     = wp_kses_post( wp_unslash( $_POST['message'] ?? '' ) );
    $cc          = sanitize_email( wp_unslash( $_POST['cc'] ?? '' ) );
    $signature   = ! empty( $_POST['signature'] );

    if ( ! in_array( $entity_type, array( 'request', 'quotation', 'sell', 'customer' ), true ) ) {
        gti_send_json_error( 'Entity tidak dikenal.', 'bad_entity' );
    }
    if ( $subject === '' ) {
        gti_send_json_error( 'Subject wajib diisi.', 'missing_subject' );
    }
    if ( trim( wp_strip_all_tags( $message ) ) === '' ) {
        gti_send_json_error( 'Isi pesan wajib diisi.', 'missing_message' );
    }
    if ( ! empty( $_POST['cc'] ) && ! $cc ) {
        gti_send_json_error( 'Alamat CC tidak valid.', 'bad_cc' );
    }

    $attachment_ids = gti_collect_uploaded_documents( $entity_type, $id, 'other' );
    if ( is_wp_error( $attachment_ids ) ) {
        gti_send_json_error( $attachment_ids->get_error_message(), $attachment_ids->get_error_code() );
    }

    $result = GTI_Mailer::send_custom_email( $entity_type, $id, $subject, $message, $attachment_ids, $cc, $signature );

    if ( ! $result['sent'] ) {
        gti_send_json_error( $result['message'], 'send_failed' );
    }

    wp_send_json_success( array(
        'message'    => $result['message'],
        'email_sent' => true,
        'log_id'     => $result['log_id'],
    ) );
}

/**
 * Store every file submitted under files[] and return their attachment row IDs.
 *
 * @return array|WP_Error
 */
function gti_collect_uploaded_documents( $entity_type, $entity_id, $kind ) {
    if ( empty( $_FILES['files'] ) || empty( $_FILES['files']['name'][0] ) ) {
        return array();
    }

    $files = $_FILES['files'];
    $ids   = array();

    foreach ( (array) $files['name'] as $index => $name ) {
        if ( $name === '' ) {
            continue;
        }

        // gti_attach_document() reads a single-file $_FILES entry.
        $_FILES['gti_tmp_file'] = array(
            'name'     => $name,
            'type'     => $files['type'][ $index ],
            'tmp_name' => $files['tmp_name'][ $index ],
            'error'    => $files['error'][ $index ],
            'size'     => $files['size'][ $index ],
        );

        $id = gti_attach_document( array(
            'entity_type' => $entity_type,
            'entity_id'   => $entity_id,
            'kind'        => $kind,
            'file_key'    => 'gti_tmp_file',
        ) );

        unset( $_FILES['gti_tmp_file'] );

        if ( is_wp_error( $id ) ) {
            return $id;
        }
        $ids[] = $id;
    }

    return $ids;
}

// ─────────────────────────────────────────────────────────────────────────────
// C-04 — Assign PIC
// ─────────────────────────────────────────────────────────────────────────────

add_action( 'wp_ajax_gti_assign_entity', 'gti_ajax_assign_entity' );
function gti_ajax_assign_entity() {
    gti_ajax_guard( array( 'gti_manage_requests', 'gti_manage_quotations' ) );

    $entity_type = sanitize_key( $_POST['entity_type'] ?? '' );
    $id          = (int) ( $_POST['id'] ?? 0 );
    $user_id     = (int) ( $_POST['user_id'] ?? 0 );
    $notify      = ! empty( $_POST['notify'] );

    if ( ! in_array( $entity_type, array( 'request', 'quotation', 'sell' ), true ) ) {
        gti_send_json_error( 'Entity tidak dikenal.', 'bad_entity' );
    }

    $row = gti_entity_row( $entity_type, $id );
    if ( ! $row ) {
        gti_send_json_error( 'Data tidak ditemukan.', 'not_found', 404 );
    }
    gti_guard_row_scope( $entity_type, $row );

    $result = gti_assign_entity( $entity_type, $id, $user_id, $notify );

    if ( ! $result['ok'] ) {
        gti_send_json_error( $result['message'], 'assign_failed' );
    }

    // A sales user who hands a row to someone else loses sight of it.
    $lost = ! gti_can_view_all( $entity_type ) && $user_id !== get_current_user_id();

    wp_send_json_success( array(
        'message'      => $user_id
            ? sprintf( 'Pesanan dialihkan ke %s', $result['sales_pic'] )
            : 'PIC dikosongkan.',
        'assigned_to'  => $user_id,
        'sales_pic'    => $result['sales_pic'],
        'notified'     => $result['notified'],
        'row_removed'  => $lost,
        'timeline'     => gti_timeline_payload( $entity_type, $id ),
    ) );
}

// ─────────────────────────────────────────────────────────────────────────────
// Read endpoints
// ─────────────────────────────────────────────────────────────────────────────

add_action( 'wp_ajax_gti_get_timeline', 'gti_ajax_get_timeline' );
function gti_ajax_get_timeline() {
    gti_ajax_guard( array( 'gti_manage_requests', 'gti_manage_quotations' ) );

    $entity_type = sanitize_key( $_POST['entity_type'] ?? '' );
    $id          = (int) ( $_POST['id'] ?? 0 );

    $documents = array();
    foreach ( gti_get_attachments( $entity_type, $id ) as $document ) {
        $documents[] = array(
            'id'   => (int) $document['id'],
            'name' => $document['original_name'],
            'url'  => $document['url'],
            'size' => $document['size_text'],
            'date' => $document['created_at'],
            'kind' => $document['kind'],
        );
    }

    wp_send_json_success( array(
        'items'       => gti_timeline_payload( $entity_type, $id ),
        'attachments' => $documents,
    ) );
}

add_action( 'wp_ajax_gti_get_email_history', 'gti_ajax_get_email_history' );
function gti_ajax_get_email_history() {
    gti_ajax_guard( array( 'gti_manage_requests', 'gti_manage_quotations' ) );

    $entity_type = sanitize_key( $_POST['entity_type'] ?? '' );
    $id          = (int) ( $_POST['id'] ?? 0 );
    $limit       = max( 1, min( 50, (int) ( $_POST['limit'] ?? 5 ) ) );

    wp_send_json_success( array( 'items' => GTI_Mailer::history( $entity_type, $id, $limit ) ) );
}

add_action( 'wp_ajax_gti_delete_attachment', 'gti_ajax_delete_attachment' );
function gti_ajax_delete_attachment() {
    gti_ajax_guard( array( 'gti_manage_requests', 'gti_manage_quotations' ) );

    $id  = (int) ( $_POST['id'] ?? 0 );
    $row = gti_get_attachment( $id );

    if ( ! $row ) {
        gti_send_json_error( 'Dokumen tidak ditemukan.', 'not_found', 404 );
    }

    $entity = gti_entity_row( $row['entity_type'], $row['entity_id'] );
    if ( $entity ) {
        gti_guard_row_scope( $row['entity_type'], $entity );
    }

    if ( ! gti_delete_attachment( $id ) ) {
        gti_send_json_error( 'Gagal menghapus dokumen.', 'db_error' );
    }

    gti_log_current_activity( 'delete', 'Deleted document: ' . $row['original_name'],
        $row['entity_type'], (int) $row['entity_id'] );

    wp_send_json_success( array( 'deleted' => true, 'message' => 'Dokumen dihapus.' ) );
}

// ─────────────────────────────────────────────────────────────────────────────
// Shared response builders
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Timeline as plain data for the client.
 */
function gti_timeline_payload( $entity_type, $id ) {
    $items = array();

    foreach ( gti_get_status_history( $entity_type, $id ) as $event ) {
        $items[] = array(
            'event' => gti_status_label( $entity_type, $event['to_status'] ),
            'date'  => $event['created_at'],
            'actor' => $event['actor'],
            'note'  => $event['note'],
        );
    }

    return $items;
}

/**
 * The response shape every inbox action returns (PRD §6.5.4 step 4).
 *
 * Includes everything the drawer needs to repaint in place, which is what lets
 * the client drop location.reload() and keep scroll position and drawer state.
 */
function gti_send_inbox_response( $entity_type, $id, $status, array $attachment_ids = array() ) {
    $sent = gti_last_email_result( $entity_type, $id );

    $documents = array();
    foreach ( gti_get_attachments( $entity_type, $id ) as $document ) {
        $documents[] = array(
            'id'   => (int) $document['id'],
            'name' => $document['original_name'],
            'url'  => $document['url'],
            'size' => $document['size_text'],
            'date' => $document['created_at'],
            'kind' => $document['kind'],
        );
    }

    $message = sprintf( 'Status diubah menjadi %s.', gti_status_label( $entity_type, $status ) );
    if ( $sent['exists'] ) {
        $message = $sent['ok']
            ? $sent['message']
            : $message . ' ' . $sent['message'];
    }

    wp_send_json_success( array(
        'message'      => $message,
        'status'       => $status,
        'status_label' => gti_status_label( $entity_type, $status ),
        'status_class' => gti_status_class( $entity_type, $status ),
        'next'         => gti_status_next( $entity_type, $status ),
        'email_sent'   => $sent['ok'],
        'attachments'  => $documents,
        'timeline'     => gti_timeline_payload( $entity_type, $id ),
        'toast_type'   => ( $sent['exists'] && ! $sent['ok'] ) ? 'warning' : 'success',
    ) );
}

/**
 * Outcome of the most recent email for a row, so the response can report a
 * partial success (status saved, email failed) as a warning rather than an error.
 */
function gti_last_email_result( $entity_type, $id ) {
    global $wpdb;

    $row = $wpdb->get_row( $wpdb->prepare(
        "SELECT send_result, recipient_email, error_message
           FROM {$wpdb->prefix}gti_email_logs
          WHERE type = %s AND related_id = %d
       ORDER BY id DESC LIMIT 1",
        $entity_type, (int) $id
    ), ARRAY_A );

    if ( ! $row ) {
        return array( 'exists' => false, 'ok' => false, 'message' => '' );
    }

    if ( $row['send_result'] ) {
        return array(
            'exists'  => true,
            'ok'      => true,
            'message' => sprintf( 'Email terkirim ke %s.', $row['recipient_email'] ),
        );
    }

    return array(
        'exists'  => true,
        'ok'      => false,
        'message' => $row['error_message'] === 'no recipient'
            ? 'Email tidak terkirim: alamat email pelanggan kosong.'
            : 'Email gagal terkirim. Periksa konfigurasi SMTP.',
    );
}
