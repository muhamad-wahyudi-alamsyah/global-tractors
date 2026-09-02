<?php
/**
 * AJAX Handler — Customer Quotation Submission (public / not logged in)
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'wp_ajax_nopriv_gti_customer_submit_quotation', 'gti_customer_submit_quotation' );
add_action( 'wp_ajax_gti_customer_submit_quotation',       'gti_customer_submit_quotation' );

function gti_customer_submit_quotation() {

    // ── Verify nonce ────────────────────────────────────────────────────
    $nonce = isset( $_POST['gti_quot_nonce'] ) ? sanitize_text_field( $_POST['gti_quot_nonce'] ) : '';
    if ( ! $nonce || ! wp_verify_nonce( $nonce, 'gti_customer_quotation' ) ) {
        wp_send_json_error( [ 'message' => 'Security check failed.' ] );
        exit;
    }

    // ── Sanitize inputs ─────────────────────────────────────────────────
    $name    = sanitize_text_field( $_POST['name'] ?? '' );
    $company = sanitize_text_field( $_POST['company'] ?? '' );
    $phone   = sanitize_text_field( $_POST['phone'] ?? '' );
    $email   = sanitize_email( $_POST['email'] ?? '' );
    $message = sanitize_textarea_field( $_POST['message'] ?? '' );

    // Equipment context (from detail page)
    $equip_id = absint( $_POST['equipment_id'] ?? 0 );
    $equip_name = sanitize_text_field( $_POST['equipment_name'] ?? '' );

    // ── Validate required fields ────────────────────────────────────────
    $errors = [];
    if ( empty( $name ) )    $errors[] = 'Nama wajib diisi.';
    if ( empty( $phone ) )   $errors[] = 'Nomor telepon wajib diisi.';
    if ( empty( $email ) )   $errors[] = 'Email wajib diisi.';

    if ( ! empty( $email ) && ! is_email( $email ) ) {
        $errors[] = 'Format email tidak valid.';
    }

    if ( ! empty( $errors ) ) {
        wp_send_json_error( [ 'message' => implode( ' ', $errors ) ] );
        exit;
    }

    // ── Generate quotation ID ───────────────────────────────────────────
    global $wpdb;
    $table = $wpdb->prefix . 'gti_quotations';

    $prefix  = 'Q-' . date( 'Ym' ) . '-';
    $like    = $prefix . '%';
    $max_code = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT quotation_id FROM {$table} WHERE quotation_id LIKE %s ORDER BY id DESC LIMIT 1",
            $like
        )
    );

    $seq = 1;
    if ( $max_code ) {
        $last_num = (int) substr( $max_code, strrpos( $max_code, '-' ) + 1 );
        $seq = $last_num + 1;
    }
    $quotation_id = $prefix . str_pad( $seq, 4, '0', STR_PAD_LEFT );

    // ── Build items JSON ────────────────────────────────────────────────
    $items = [];
    if ( $equip_name ) {
        $items[] = [
            'name'     => $equip_name,
            'quantity' => 1,
            'notes'    => $message,
        ];
    }

    // ── Insert into database ────────────────────────────────────────────
    $data = [
        'quotation_id'      => $quotation_id,
        'customer_name'     => $name,
        'customer_company'  => $company,
        'customer_email'    => $email,
        'customer_phone'    => $phone,
        'customer_address'  => '',
        'items'             => wp_json_encode( $items ),
        'subtotal'          => 0,
        'discount'          => 0,
        'discount_type'     => 'amount',
        'tax_rate'          => 11,
        'tax_amount'        => 0,
        'total'             => 0,
        'sales_pic'         => '',
        'valid_until'       => '',
        'delivery_location' => '',
        'additional_notes'  => $message,
        'status'            => 'new',
        'request_date'      => date( 'Y-m-d' ),
    ];

    $result = $wpdb->insert( $table, $data );

    if ( $result ) {
        $insert_id = $wpdb->insert_id;

        // ── Send notification email to admin ─────────────────────────────
        $admin_email = get_option( 'admin_email' );
        $site_name   = get_bloginfo( 'name' );
        $subject     = "[{$site_name}] Quotation Request — {$quotation_id}";
        $body        = "Halo Admin,\n\n"
                     . "Ada permintaan quotation baru dari pelanggan:\n\n"
                     . "ID Quotation : {$quotation_id}\n"
                     . "Nama         : {$name}\n"
                     . "Perusahaan   : {$company}\n"
                     . "Telepon      : {$phone}\n"
                     . "Email        : {$email}\n"
                     . "Unit         : {$equip_name}\n"
                     . "Pesan        : {$message}\n\n"
                     . "Silakan cek dashboard untuk detail lengkap.\n";

        wp_mail( $admin_email, $subject, $body );

        wp_send_json_success( [
            'message'       => 'Permintaan quotation berhasil dikirim! Tim kami akan segera menghubungi Anda.',
            'quotation_id'  => $quotation_id,
            'id'            => $insert_id,
        ] );
    } else {
        wp_send_json_error( [ 'message' => 'Gagal menyimpan data. Silakan coba lagi.' ] );
    }

    exit;
}
