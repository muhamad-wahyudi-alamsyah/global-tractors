<?php
/**
 * Customer quotation intake (public — PRD §7.3).
 *
 * Receives the catalogue inquiry form from the three shortcodes and writes a
 * row into wp_gti_quotations, which is what /dashboard/request-quotation lists.
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'wp_ajax_nopriv_gti_customer_submit_quotation', 'gti_customer_submit_quotation' );
add_action( 'wp_ajax_gti_customer_submit_quotation',        'gti_customer_submit_quotation' );

function gti_customer_submit_quotation() {
    global $wpdb;

    // ── Nonce ────────────────────────────────────────────────────────────
    $nonce = sanitize_text_field( wp_unslash( $_POST['gti_quot_nonce'] ?? '' ) );
    if ( ! $nonce || ! wp_verify_nonce( $nonce, 'gti_customer_quotation' ) ) {
        wp_send_json_error( array( 'message' => 'Sesi Anda sudah kedaluwarsa. Muat ulang halaman lalu coba lagi.', 'code' => 'bad_nonce' ) );
    }

    // ── Honeypot (§7.3 item 4) ───────────────────────────────────────────
    // A bot fills every field it finds. Answer as though it worked, and write
    // nothing — a visible rejection just tells the bot to try again.
    if ( ! empty( $_POST['ed_website'] ) ) {
        wp_send_json_success( array(
            'message'      => 'Permintaan quotation berhasil dikirim!',
            'quotation_id' => 'Q-' . date( 'Ym' ) . '-0000',
        ) );
    }

    // ── Rate limit (§7.3 item 3) ─────────────────────────────────────────
    $rate_key = 'quote_' . md5( gti_get_client_ip() );
    if ( ! gti_check_rate_limit( $rate_key, 3, 10 * MINUTE_IN_SECONDS ) ) {
        wp_send_json_error( array(
            'message' => 'Anda sudah mengirim beberapa permintaan. Mohon tunggu sekitar 10 menit sebelum mengirim lagi.',
            'code'    => 'rate_limited',
        ) );
    }

    // ── Input ────────────────────────────────────────────────────────────
    $text = function ( $key ) { return sanitize_text_field( wp_unslash( $_POST[ $key ] ?? '' ) ); };
    $area = function ( $key ) { return sanitize_textarea_field( wp_unslash( $_POST[ $key ] ?? '' ) ); };
    $date = function ( $key ) {
        $value = sanitize_text_field( wp_unslash( $_POST[ $key ] ?? '' ) );
        return ( $value && strtotime( $value ) ) ? date( 'Y-m-d', strtotime( $value ) ) : null;
    };

    $name     = $text( 'ed_name' );
    $company  = $text( 'ed_company' );
    $phone    = $text( 'ed_phone' );
    $email    = sanitize_email( wp_unslash( $_POST['ed_email'] ?? '' ) );
    $address  = $area( 'ed_address' );
    $message  = $area( 'ed_message' );
    $quantity = max( 1, (int) ( $_POST['ed_quantity'] ?? 1 ) );

    $needed_date       = $date( 'ed_needed_date' );
    $delivery_location = $text( 'ed_delivery_location' );
    $payment_terms     = $text( 'ed_payment_terms' );
    $budget            = (float) preg_replace( '/[^\d.]/', '', str_replace( ',', '', $text( 'ed_budget' ) ) );

    $rental_start    = $date( 'ed_rental_start' );
    $rental_end      = $date( 'ed_rental_end' );
    $rental_duration = $text( 'ed_rental_duration' );
    $operator_needed = $text( 'ed_operator_needed' );

    $part_number = $text( 'ed_part_number' );
    $unit_model  = $text( 'ed_unit_model' );
    $urgency     = $text( 'ed_urgency' );

    $equipment_id   = absint( $_POST['equipment_id'] ?? 0 );
    $equipment_name = $text( 'equipment_name' );
    $equipment_type = $text( 'equipment_type' );
    $source_url     = esc_url_raw( wp_unslash( $_POST['source_url'] ?? '' ) );

    if ( ! in_array( $equipment_type, array( 'used', 'rental', 'spare_part' ), true ) ) {
        $equipment_type = 'used';
    }
    if ( $payment_terms && ! array_key_exists( $payment_terms, gti_payment_terms_options() ) ) {
        $payment_terms = '';
    }

    // ── Validation ───────────────────────────────────────────────────────
    $errors = array();
    if ( $name === '' )  { $errors[] = 'Nama wajib diisi.'; }
    if ( $phone === '' ) { $errors[] = 'Nomor telepon wajib diisi.'; }
    if ( $email === '' ) {
        $errors[] = 'Email wajib diisi.';
    } elseif ( ! is_email( $email ) ) {
        $errors[] = 'Format email tidak valid.';
    }
    if ( empty( $_POST['ed_consent'] ) ) {
        $errors[] = 'Mohon setujui untuk dihubungi oleh tim kami.';
    }
    if ( $equipment_type === 'rental' && $rental_start && $rental_end && $rental_end <= $rental_start ) {
        $errors[] = 'Tanggal selesai sewa harus setelah tanggal mulai.';
    }

    if ( $errors ) {
        wp_send_json_error( array( 'message' => implode( ' ', $errors ), 'code' => 'validation' ) );
    }

    // Derive the end date when the visitor picked a preset duration (§7.2).
    if ( $equipment_type === 'rental' && $rental_start && ! $rental_end
         && $rental_duration && $rental_duration !== 'custom' ) {
        $months = (int) $rental_duration;
        if ( $months > 0 ) {
            $rental_end = date( 'Y-m-d', strtotime( $rental_start . ' +' . $months . ' months' ) );
        }
    }

    // ── Quotation ID ─────────────────────────────────────────────────────
    $table  = $wpdb->prefix . 'gti_quotations';
    $prefix = 'Q-' . date( 'Ym' ) . '-';

    $last = $wpdb->get_var( $wpdb->prepare(
        "SELECT quotation_id FROM {$table} WHERE quotation_id LIKE %s ORDER BY id DESC LIMIT 1",
        $wpdb->esc_like( $prefix ) . '%'
    ) );

    $sequence     = $last ? ( (int) substr( $last, strrpos( $last, '-' ) + 1 ) ) + 1 : 1;
    $quotation_id = $prefix . str_pad( $sequence, 4, '0', STR_PAD_LEFT );

    // ── Items JSON (§7.3 item 5) ─────────────────────────────────────────
    $notes = $message;
    if ( $operator_needed ) {
        $notes = trim( $notes . "\n" . 'Butuh operator: ' . $operator_needed );
    }

    $items = array( array(
        'equipment_id' => $equipment_id,
        'type'         => $equipment_type,
        'name'         => $equipment_name,
        'part_number'  => $part_number,
        'quantity'     => $quantity,
        'unit_model'   => $unit_model,
        'urgency'      => $urgency,
        'notes'        => $notes,
    ) );

    // ── Insert ───────────────────────────────────────────────────────────
    $data = array(
        'quotation_id'      => $quotation_id,
        'customer_name'     => $name,
        'customer_company'  => $company,
        'customer_email'    => $email,
        'customer_phone'    => $phone,
        'customer_address'  => $address,
        'equipment_id'      => $equipment_id ?: null,
        'equipment_type'    => $equipment_type,
        'quantity'          => $quantity,
        'needed_date'       => $needed_date,
        'rental_start_date' => $rental_start,
        'rental_end_date'   => $rental_end,
        'rental_duration'   => $rental_duration ?: null,
        'payment_terms'     => $payment_terms ?: null,
        'budget'            => $budget ?: null,
        'items'             => wp_json_encode( $items ),
        'subtotal'          => 0,
        'discount'          => 0,
        'discount_type'     => 'amount',
        'tax_rate'          => 11,
        'tax_amount'        => 0,
        'total'             => 0,
        // A DATE column rejects '' under MySQL strict mode (A-06).
        'valid_until'       => null,
        'delivery_location' => $delivery_location,
        'additional_notes'  => $notes,
        'status'            => 'new',
        'source_url'        => $source_url ?: null,
        'request_date'      => date( 'Y-m-d' ),
        'created_at'        => current_time( 'mysql' ),
    );

    // Auto-assign when a default PIC is configured (§7.3 item 8).
    $default_pic = (int) get_option( 'gti_default_sales_pic', 0 );
    if ( $default_pic && ( $user = get_userdata( $default_pic ) ) ) {
        $data['assigned_to'] = $default_pic;
        $data['assigned_at'] = current_time( 'mysql' );
        $data['sales_pic']   = $user->display_name;
    }

    // Drop anything the table does not have, so an older schema degrades
    // rather than failing the whole insert.
    $columns = $wpdb->get_col( "SHOW COLUMNS FROM {$table}" );
    $data    = array_intersect_key( $data, array_flip( (array) $columns ) );

    if ( false === $wpdb->insert( $table, $data ) ) {
        gti_log( 'Quotation insert failed', $wpdb->last_error );
        wp_send_json_error( array( 'message' => 'Gagal menyimpan data. Silakan coba lagi.', 'code' => 'db_error' ) );
    }

    $insert_id = (int) $wpdb->insert_id;

    gti_increment_rate_limit( $rate_key, 10 * MINUTE_IN_SECONDS );

    // Creates or refreshes the customer record behind /dashboard/customers.
    do_action( 'gti_submission_received', 'request-quotation', $data );

    gti_record_status_change( 'quotation', $insert_id, '', 'new', array( 'email' => false ) );

    // ── Emails ───────────────────────────────────────────────────────────
    // To the customer (§7.3 item 6 — previously only the admin was told).
    GTI_Mailer::send_status_email( 'quotation', $insert_id, 'new' );

    gti_notify_admin_new_quotation( $insert_id, $quotation_id, $data, $equipment_name );

    wp_send_json_success( array(
        'message'      => 'Permintaan quotation berhasil dikirim! Tim kami akan segera menghubungi Anda.',
        'quotation_id' => $quotation_id,
        'id'           => $insert_id,
    ) );
}

/**
 * Internal notification with a direct link to the new row (§7.3 item 7).
 */
function gti_notify_admin_new_quotation( $id, $quotation_id, array $data, $equipment_name ) {
    $link = gti_dashboard_url( 'request-quotation' ) . '?highlight=' . (int) $id;

    $rows = array(
        'ID Quotation' => $quotation_id,
        'Nama'         => $data['customer_name'],
        'Perusahaan'   => $data['customer_company'] ?: '—',
        'Telepon'      => $data['customer_phone'],
        'Email'        => $data['customer_email'],
        'Unit'         => $equipment_name ?: '—',
        'Jumlah'       => $data['quantity'] ?? 1,
    );

    $html = '<table cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;margin-bottom:20px;">';
    foreach ( $rows as $label => $value ) {
        $html .= '<tr>'
              . '<td style="padding:8px 0;color:#6b7280;font-size:14px;width:40%;">' . esc_html( $label ) . '</td>'
              . '<td style="padding:8px 0;color:#1a1f36;font-size:14px;"><strong>' . esc_html( $value ) . '</strong></td>'
              . '</tr>';
    }
    $html .= '</table>';

    if ( ! empty( $data['additional_notes'] ) ) {
        $html .= '<p style="color:#374151;line-height:1.6;"><strong>Pesan:</strong><br>'
               . nl2br( esc_html( $data['additional_notes'] ) ) . '</p>';
    }

    $body = GTI_Mailer::render_layout( array(
        'title'     => 'Permintaan Quotation Baru',
        'body_html' => $html,
        'ref'       => $quotation_id,
        'cta'       => array( 'url' => $link, 'label' => 'Buka di dashboard' ),
    ) );

    wp_mail(
        get_option( 'gti_inbox_email', get_option( 'admin_email' ) ),
        sprintf( '[%s] Quotation Request — %s', get_bloginfo( 'name' ), $quotation_id ),
        $body,
        array( 'Content-Type: text/html; charset=UTF-8' )
    );
}
