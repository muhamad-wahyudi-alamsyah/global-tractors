<?php
/**
 * FluentForms intake — maps submissions into the GTI inbox tables.
 *
 * Form GTI_FLUENTFORM_REQUEST_FORM_ID  → wp_gti_requests      (Request Equipment)
 * Form GTI_FLUENTFORM_SELL_FORM_ID  → wp_gti_sell_requests (Sell Equipment)
 *
 * Both paths fire do_action('gti_submission_received', $source, $data) so the
 * customer record is created or refreshed, and now also hand off to GTI_Mailer
 * for the customer acknowledgement email.
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ── FluentForms → Request Equipment sync ─────────────────────────────────────
/**
 * Sinkronkan submission FluentForms ke tabel wp_gti_requests.
 *
 * Cara pakai:
 * 1. Buat form di FluentForms untuk request equipment
 * 2. Set GTI_FLUENTFORM_REQUEST_FORM_ID di inc/constants.php
 * 3. Mapping field di bawah sesuai nama field di FluentForms
 *    (lihat tab Settings → Smart Codes di editor form)
 * 4. Data otomatis masuk ke tabel gti_requests dan tampil di
 *    /dashboard/request-equipment
 */
add_action('fluentform/submission_inserted', 'gti_fluentform_to_request', 10, 3);

function gti_fluentform_to_request($insertId, $formData, $form) {
    $target_form_id = GTI_FLUENTFORM_REQUEST_FORM_ID;

    if (empty($target_form_id) || $target_form_id === 0) {
        return;
    }

    if (intval($form->id) !== intval($target_form_id)) {
        return;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'gti_requests';

    // Field names arriving from FluentForms; only emitted when WP_DEBUG is on.
    gti_log("FluentForm {$form->id} submission {$insertId} formData keys", array_keys($formData));

    // ── Generate request_id unik (REQ-XXXX) ────────────────────────────────
    $like   = $wpdb->esc_like('REQ-') . '%';
    $max_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT request_id FROM {$table} WHERE request_id LIKE %s ORDER BY id DESC LIMIT 1",
            $like
        )
    );
    $seq = 1;
    if ($max_id) {
        $last_num = (int) substr($max_id, strrpos($max_id, '-') + 1);
        $seq = $last_num + 1;
    }
    $request_id = 'REQ-' . str_pad($seq, 4, '0', STR_PAD_LEFT);

    // ── Helper: ambil nilai dengan fallback multi-key ───────────────────────
    $get = function ($arr, $keys, $default = '') {
        $keys = is_array($keys) ? $keys : [$keys];
        foreach ($keys as $key) {
            if (isset($arr[$key]) && $arr[$key] !== '') {
                return $arr[$key];
            }
        }
        return $default;
    };

    // ── Mapping: daftar kemungkinan nama field per kolom ───────────────────
    //  Cara pakai:
    //  1. Submit form test di FluentForms
    //  2. Aktifkan WP_DEBUG lalu baca debug.log
    //  3. Cek "formData keys:" → field names yang dikirim
    //  4. Jika belum ada di array ini, tambahkan nama field-nya
    $field_map = array(
        'customer_name'     => ['name', 'full_name', 'customer_name', 'nama', 'your_name'],
        'customer_company'  => ['company', 'customer_company', 'perusahaan', 'company_name', 'organisation'],
        'customer_email'    => ['email', 'customer_email', 'your_email', 'email_address'],
        'customer_phone'    => ['phone', 'customer_phone', 'telepon', 'phone_number', 'your_phone', 'no_hp', 'no_telp'],
        'customer_location' => ['location', 'customer_location', 'alamat', 'address', 'your_location', 'city'],
        'equipment'         => ['equipment', 'equipment_type', 'alat_berat', 'alat', 'machine_type', 'equipment_name'],
        'category'          => ['category', 'equipment_category', 'kategori', 'type'],
        'brand'             => ['brand', 'equipment_brand', 'merek', 'merk', 'manufacturer'],
        'quantity'          => ['quantity', 'qty', 'jumlah', 'jml'],
        'location'          => ['delivery_location', 'lokasi_pengiriman', 'pengiriman_ke', 'delivery_address', 'ship_to'],
        'budget'            => ['budget', 'estimated_budget', 'anggaran', 'harga', 'price', 'budget_estimation'],
        'required_date'     => ['required_date', 'tanggal_dibutuhkan', 'need_date', 'delivery_date', 'due_date'],
        'notes'             => ['notes', 'catatan', 'message', 'additional_notes', 'keterangan', 'remarks'],
    );

    $data = array(
        'request_id'        => $request_id,
        'customer_name'     => sanitize_text_field($get($formData, $field_map['customer_name'])),
        'customer_company'  => sanitize_text_field($get($formData, $field_map['customer_company'])),
        'customer_email'    => sanitize_email($get($formData, $field_map['customer_email'])),
        'customer_phone'    => sanitize_text_field($get($formData, $field_map['customer_phone'])),
        'customer_location' => sanitize_text_field($get($formData, $field_map['customer_location'])),
        'equipment'         => sanitize_text_field($get($formData, $field_map['equipment'])),
        'category'          => sanitize_text_field($get($formData, $field_map['category'])),
        'brand'             => sanitize_text_field($get($formData, $field_map['brand'])),
        'quantity'          => intval($get($formData, $field_map['quantity'], 1)),
        'location'          => sanitize_text_field($get($formData, $field_map['location'])),
        'budget'            => floatval($get($formData, $field_map['budget'], 0)),
        'required_date'     => $get($formData, $field_map['required_date']) ?: null,
        'notes'             => wp_kses_post($get($formData, $field_map['notes'])),
        'status'            => 'new',
        'request_date'      => current_time('mysql'),
        'created_at'        => current_time('mysql'),
    );

    gti_log("Mapped request {$request_id}", $data);

    $result = $wpdb->insert($table, $data);

    if ($result) {
        // Surfaces the submitter on /dashboard/customers.
        do_action('gti_submission_received', 'request-equipment', $data);

        update_post_meta($insertId, '_gti_request_id', $request_id);
        if (class_exists('\FluentForm\App\Services\Submission\SubmissionService')) {
            $service = new \FluentForm\App\Services\Submission\SubmissionService();
            $service->updateStatus($insertId, 'read');
        }
    }
}

// ── FluentForms → Sell Equipment sync ──────────────────────────────────────
/**
 * Sinkronkan submission FluentForms ke tabel wp_gti_sell_requests.
 * Data akan tampil di /dashboard/sell-equipment.
 */
add_action('fluentform/submission_inserted', 'gti_fluentform_to_sell_request', 10, 3);

function gti_fluentform_to_sell_request($insertId, $formData, $form) {
    $target_form_id = GTI_FLUENTFORM_SELL_FORM_ID;

    if (empty($target_form_id) || $target_form_id === 0) {
        return;
    }

    if (intval($form->id) !== intval($target_form_id)) {
        return;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'gti_sell_requests';

    gti_log("FluentForm [SELL] {$form->id} submission {$insertId} formData keys", array_keys($formData));

    // Helper: ambil nilai dengan fallback multi-key
    $get = function ($arr, $keys, $default = '') {
        $keys = is_array($keys) ? $keys : [$keys];
        foreach ($keys as $key) {
            if (isset($arr[$key]) && $arr[$key] !== '') {
                return $arr[$key];
            }
        }
        return $default;
    };

    // Mapping field → kolom database
    $field_map = array(
        'customer_name'       => ['name', 'full_name', 'customer_name', 'nama', 'your_name'],
        'customer_company'    => ['company', 'customer_company', 'perusahaan', 'company_name', 'organisation'],
        'customer_email'      => ['email', 'customer_email', 'your_email', 'email_address'],
        'customer_phone'      => ['phone', 'customer_phone', 'telepon', 'phone_number', 'your_phone', 'no_hp', 'no_telp'],
        'equipment_name'      => ['equipment_name', 'equipment', 'alat', 'nama_alat', 'machine_name'],
        'equipment_brand'     => ['equipment_brand', 'brand', 'merek', 'merk', 'manufacturer'],
        'equipment_model'     => ['equipment_model', 'model', 'tipe', 'tipe_alat'],
        'equipment_year'      => ['equipment_year', 'year', 'tahun', 'tahun_produksi', 'manufacture_year'],
        'equipment_condition' => ['equipment_condition', 'condition', 'kondisi', 'kondisi_alat'],
        'equipment_hours'     => ['equipment_hours', 'hours', 'jam', 'jam_operasi', 'operating_hours'],
        'offered_price'       => ['offered_price', 'price', 'harga', 'harga_tawar', 'selling_price', 'harga_jual'],
    );

    $data = array(
        'customer_name'       => sanitize_text_field($get($formData, $field_map['customer_name'])),
        'customer_company'    => sanitize_text_field($get($formData, $field_map['customer_company'])),
        'customer_email'      => sanitize_email($get($formData, $field_map['customer_email'])),
        'customer_phone'      => sanitize_text_field($get($formData, $field_map['customer_phone'])),
        'equipment_name'      => sanitize_text_field($get($formData, $field_map['equipment_name'])),
        'equipment_brand'     => sanitize_text_field($get($formData, $field_map['equipment_brand'])),
        'equipment_model'     => sanitize_text_field($get($formData, $field_map['equipment_model'])),
        'equipment_year'      => intval($get($formData, $field_map['equipment_year'])) ?: null,
        'equipment_condition' => sanitize_text_field($get($formData, $field_map['equipment_condition'])),
        'equipment_hours'     => intval($get($formData, $field_map['equipment_hours'])) ?: null,
        'offered_price'       => floatval($get($formData, $field_map['offered_price'], 0)),
        'status'              => 'new',
        'created_at'          => current_time('mysql'),
    );

    gti_log('Mapped sell request', $data);

    $result = $wpdb->insert($table, $data);

    if ($result) {
        do_action('gti_submission_received', 'sell-equipment', $data);

        update_post_meta($insertId, '_gti_sell_request_id', $data['equipment_name']);
        if (class_exists('\FluentForm\App\Services\Submission\SubmissionService')) {
            $service = new \FluentForm\App\Services\Submission\SubmissionService();
            $service->updateStatus($insertId, 'read');
        }
    }
}
