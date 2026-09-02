<?php
/**
 * Global Tractors Indonesia - Child Theme Functions
 *
 * @package Global_Tractors
 * @version 1.0.0
 */

defined('ABSPATH') || exit;

// ── Constants (load first, before anything else) ─────────────────────────────
require_once get_stylesheet_directory() . '/inc/constants.php';

// ── Helpers ──────────────────────────────────────────────────────────────────
require_once GTI_CHILD_DIR . '/inc/helpers/url-helpers.php';
require_once GTI_CHILD_DIR . '/inc/helpers/format-helpers.php';
require_once GTI_CHILD_DIR . '/inc/helpers/template-loader.php';
require_once GTI_CHILD_DIR . '/inc/user/user-helpers.php';
require_once GTI_CHILD_DIR . '/inc/user/user-meta.php';

// ── Security ─────────────────────────────────────────────────────────────────
require_once GTI_CHILD_DIR . '/inc/security/sanitize.php';
require_once GTI_CHILD_DIR . '/inc/security/nonce.php';
require_once GTI_CHILD_DIR . '/inc/security/capabilities.php';

// ── Setup ────────────────────────────────────────────────────────────────────
require_once GTI_CHILD_DIR . '/inc/setup/roles.php';
require_once GTI_CHILD_DIR . '/inc/setup/rewrite.php';
require_once GTI_CHILD_DIR . '/inc/setup/enqueue.php';
require_once GTI_CHILD_DIR . '/inc/db/migrations.php';
require_once GTI_CHILD_DIR . '/inc/setup/install.php';

// ── Dummy Data (uncomment to insert) ─────────────────────────────────────────
// require_once GTI_CHILD_DIR . '/database/dummy-quotations.php';
// add_action('admin_init', 'gti_insert_dummy_quotations');
// require_once GTI_CHILD_DIR . '/database/dummy-sell-requests.php';
// add_action('init', 'gti_insert_dummy_sell_requests', 10);
// require_once GTI_CHILD_DIR . '/database/dummy-customers.php';
// add_action('init', 'gti_insert_dummy_customers', 10);
// require_once GTI_CHILD_DIR . '/database/dummy-news-articles.php';
// add_action('init', 'gti_insert_dummy_news_articles', 10);
// require_once GTI_CHILD_DIR . '/database/dummy-equipment.php';
// add_action('init', 'gti_insert_dummy_equipment', 10);
// ── Shortcodes ─────────────────────────────────────────────────────────
require_once GTI_CHILD_DIR . '/inc/shortcodes/search-card.php';
require_once GTI_CHILD_DIR . '/inc/shortcodes/equipment-filter-used-equipment.php';
require_once GTI_CHILD_DIR . '/inc/shortcodes/equipment-filter-rental-equipment.php';
require_once GTI_CHILD_DIR . '/inc/shortcodes/equipment-filter-spare-parts.php';
// ── AJAX handlers ────────────────────────────────────────────────────────────
require_once GTI_CHILD_DIR . '/inc/ajax/ajax-helpers.php';
require_once GTI_CHILD_DIR . '/inc/ajax/ajax-equipment-filter.php';
require_once GTI_CHILD_DIR . '/inc/ajax/ajax-customer-quotation.php';
// ── GTI Ajax class (equipment, spare parts, customers, etc.) ────────────
require_once GTI_CHILD_DIR . '/includes/class-gti-ajax.php';
add_action('init', function() {
    if (class_exists('GTI_Ajax')) {
        GTI_Ajax::init();
    }
});
// ── Enqueue parent + child styles ────────────────────────────────────────────
add_action('wp_enqueue_scripts', 'gti_enqueue_parent_style');
function gti_enqueue_parent_style() {
    $parent_theme = wp_get_theme('themify-ultra');
    if ($parent_theme->exists()) {
        wp_enqueue_style(
            'themify-ultra-style',
            get_template_directory_uri() . '/style.css',
            [],
            $parent_theme->get('Version')
        );
    }

    wp_enqueue_style(
        'gti-style',
        get_stylesheet_directory_uri() . '/style.css',
        [],
        GTI_VERSION
    );
}

// ── Flush rewrite rules ─────────────────────────────────────────────────────
add_action('init', function () {
    $option = get_option('gti_flush_rewrite_v3', 'no');
    if ($option !== 'yes') {
        update_option('gti_flush_rewrite_v3', 'yes');
        gti_flush_rewrite_rules();
    }
}, 9999);

// ── Noindex dashboard pages ──────────────────────────────────────────────────
add_filter('wp_robots', 'gti_noindex_dashboard_pages');
function gti_noindex_dashboard_pages(array $robots): array {
    if (get_query_var('gti_page')) {
        $robots['noindex'] = true;
        $robots['nofollow'] = true;
    }
    return $robots;
}

add_action('send_headers', 'gti_send_noindex_header_for_dashboard');
function gti_send_noindex_header_for_dashboard(): void {
    if (get_query_var('gti_page')) {
        header('X-Robots-Tag: noindex, nofollow', true);
    }
}

// ── Block canonical redirect for dashboard pages ─────────────────────────────
add_filter('redirect_canonical', function ($redirect_url) {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    if (str_starts_with($path, '/dashboard/') || $path === '/dashboard') {
        return false;
    }
    return $redirect_url;
});

// ── Add body class for dashboard pages ───────────────────────────────────────
add_filter('body_class', 'gti_body_class');
function gti_body_class($classes) {
    if (get_query_var('gti_page')) {
        $classes[] = 'gti-dashboard-page';
    }
    return $classes;
}

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

    // ── Debug: log semua field names yang diterima dari FluentForms ─────────
    //  Lihat hasilnya di: wp-content/debug-fluentform-fields.log
    //  Setelah mapping benar, hapus blok debug ini atau uncomment update_option di bawah.
    $log_file = WP_CONTENT_DIR . '/debug-fluentform-fields.log';
    $log  = "=== Form ID: {$form->id} | Submission ID: {$insertId} ===\n";
    $log .= "Timestamp: " . current_time('mysql') . "\n";
    $log .= "formData keys: " . print_r(array_keys($formData), true) . "\n";
    $log .= "formData: " . print_r($formData, true) . "\n\n";
    file_put_contents($log_file, $log, FILE_APPEND | LOCK_EX);

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
    //  2. Buka wp-content/debug-fluentform-fields.log
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

    // Log mapped result
    $mapped_log = "Mapped → {$request_id}:\n" . print_r($data, true) . "\n\n";
    file_put_contents($log_file, $mapped_log, FILE_APPEND | LOCK_EX);

    $result = $wpdb->insert($table, $data);

    if ($result) {
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

    // Debug log
    $log_file = WP_CONTENT_DIR . '/debug-fluentform-fields.log';
    $log  = "=== [SELL] Form ID: {$form->id} | Submission ID: {$insertId} ===\n";
    $log .= "Timestamp: " . current_time('mysql') . "\n";
    $log .= "formData keys: " . print_r(array_keys($formData), true) . "\n";
    $log .= "formData: " . print_r($formData, true) . "\n\n";
    file_put_contents($log_file, $log, FILE_APPEND | LOCK_EX);

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

    $mapped_log = "Mapped [SELL] →\n" . print_r($data, true) . "\n\n";
    file_put_contents($log_file, $mapped_log, FILE_APPEND | LOCK_EX);

    $result = $wpdb->insert($table, $data);

    if ($result) {
        update_post_meta($insertId, '_gti_sell_request_id', $data['equipment_name']);
        if (class_exists('\FluentForm\App\Services\Submission\SubmissionService')) {
            $service = new \FluentForm\App\Services\Submission\SubmissionService();
            $service->updateStatus($insertId, 'read');
        }
    }
}
