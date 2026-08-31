<?php
/**
 * AJAX helpers
 * @package global-tractors
 */
if (!defined('ABSPATH')) exit;

add_action('wp_ajax_gti_logout', 'gti_ajax_logout');
function gti_ajax_logout() {
    check_ajax_referer('gti_nonce', 'nonce');
    wp_logout();
    wp_send_json_success(['redirect' => gti_login_url()]);
}

add_action('wp_ajax_gti_get_dashboard_stats', 'gti_ajax_get_dashboard_stats');
function gti_ajax_get_dashboard_stats() {
    check_ajax_referer('gti_nonce', 'nonce');

    $stats = [
        'used_equipment' => 148,
        'rental_active' => 25,
        'spare_parts' => 372,
        'request_equipment' => 18,
        'sell_submissions' => 14,
        'request_quotation' => 31,
        'contact_messages' => 23,
        'news_articles' => 12,
        'website_visitors' => 12540,
    ];

    wp_send_json_success($stats);
}

// Login AJAX
add_action('wp_ajax_nopriv_gti_ajax_login', 'gti_ajax_login');
add_action('wp_ajax_gti_ajax_login', 'gti_ajax_login');
function gti_ajax_login() {
    check_ajax_referer('gti_nonce', 'nonce');

    $creds = [
        'user_login'    => sanitize_user($_POST['log'] ?? ''),
        'user_password' => $_POST['pwd'] ?? '',
        'remember'      => true
    ];

    $user = wp_signon($creds, is_ssl());

    if (is_wp_error($user)) {
        wp_send_json_error('Username atau password salah.');
    }

    wp_send_json_success(['redirect' => gti_dashboard_url()]);
}
