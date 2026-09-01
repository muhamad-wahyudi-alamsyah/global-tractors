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
require_once GTI_CHILD_DIR . '/database/dummy-sell-requests.php';
add_action('init', 'gti_insert_dummy_sell_requests', 10);
require_once GTI_CHILD_DIR . '/database/dummy-customers.php';
add_action('init', 'gti_insert_dummy_customers', 10);
require_once GTI_CHILD_DIR . '/database/dummy-news-articles.php';
add_action('init', 'gti_insert_dummy_news_articles', 10);
// require_once GTI_CHILD_DIR . '/database/dummy-equipment.php';
// add_action('init', 'gti_insert_dummy_equipment', 10);
// ── Shortcodes ─────────────────────────────────────────────────────────
require_once GTI_CHILD_DIR . '/inc/shortcodes/search-card.php';
require_once GTI_CHILD_DIR . '/inc/shortcodes/equipment-filter.php';
// ── AJAX handlers ────────────────────────────────────────────────────────────
require_once GTI_CHILD_DIR . '/inc/ajax/ajax-helpers.php';
require_once GTI_CHILD_DIR . '/inc/ajax/ajax-equipment-filter.php';
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
