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

/**
 * Hand out a fresh nonce for a public form.
 *
 * A nonce printed into the page HTML lives at most 24 hours for an anonymous
 * visitor, while a page cache (LiteSpeed, WP Rocket, a CDN, Themify's own
 * cache) happily serves the same HTML for days. The stale nonce then fails
 * verification and the visitor is told their session expired — reloading does
 * not help, because the reload is cached too. The form asks for a live one at
 * submit time instead; this request is a POST to admin-ajax.php, which no page
 * cache stores.
 *
 * Only the actions listed here can be minted, so this is not a generic nonce
 * oracle: each one still guards a handler with its own honeypot and rate limit.
 */
add_action( 'wp_ajax_gti_public_nonce', 'gti_ajax_public_nonce' );
add_action( 'wp_ajax_nopriv_gti_public_nonce', 'gti_ajax_public_nonce' );
function gti_ajax_public_nonce() {
    $allowed = array(
        'quotation' => 'gti_customer_quotation',
    );

    $key = sanitize_key( $_POST['name'] ?? '' );

    if ( ! isset( $allowed[ $key ] ) ) {
        wp_send_json_error( array( 'message' => 'Unknown form.' ) );
    }

    wp_send_json_success( array( 'nonce' => wp_create_nonce( $allowed[ $key ] ) ) );
}
