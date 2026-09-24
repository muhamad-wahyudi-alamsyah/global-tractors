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
