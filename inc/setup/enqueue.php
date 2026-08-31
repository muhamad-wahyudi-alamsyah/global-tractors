<?php
/**
 * Enqueue scripts and styles
 * @package global-tractors
 */
if (!defined('ABSPATH')) exit;

add_action('wp_enqueue_scripts', 'gti_enqueue_dashboard_styles');
function gti_enqueue_dashboard_styles() {
    $page = get_query_var('gti_page');
    if (!$page) return;

    wp_enqueue_style(
        'gti-dashboard',
        GTI_CHILD_URL . '/assets/css/dashboard.css',
        [],
        GTI_VERSION
    );

    wp_enqueue_style(
        'gti-google-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap',
        [],
        null
    );

    wp_enqueue_script(
        'gti-chartjs',
        'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
        [],
        '4.4.0',
        true
    );

    wp_enqueue_script(
        'gti-dashboard',
        GTI_CHILD_URL . '/assets/js/dashboard.js',
        ['jquery', 'gti-chartjs'],
        GTI_VERSION,
        true
    );

    wp_localize_script('gti-dashboard', 'gtiAjax', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('gti_nonce'),
        'version' => GTI_VERSION,
    ]);
}

// ── Frontend: Search Card Assets ─────────────────────────────────────────
add_action('wp_enqueue_scripts', 'gti_enqueue_search_card_assets');
function gti_enqueue_search_card_assets() {
    // Only load on non-dashboard pages (frontend website)
    if (get_query_var('gti_page')) return;

    // Google Fonts (Inter) — only if not already loaded
    if (!wp_style_is('gti-google-fonts', 'enqueued')) {
        wp_enqueue_style(
            'gti-google-fonts',
            'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap',
            [],
            null
        );
    }

    // Search card CSS
    wp_enqueue_style(
        'gti-search-card',
        GTI_CHILD_URL . '/assets/css/search-card.css',
        ['gti-google-fonts'],
        GTI_VERSION
    );

    // Font Awesome (icons) — only if not already loaded
    if (!wp_style_is('font-awesome', 'enqueued') && !wp_style_is('font-awesome-5', 'enqueued')) {
        wp_enqueue_style(
            'gti-font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
            [],
            '6.5.1'
        );
    }

    // Search card JS
    wp_enqueue_script(
        'gti-search-card',
        GTI_CHILD_URL . '/assets/js/search-card.js',
        [],
        GTI_VERSION,
        true
    );

    // Localize equipment page URL for search redirect
    wp_localize_script('gti-search-card', 'gtiSearchData', [
        'equipmentUrl' => home_url('/equipment/'),
    ]);
}

// ── Frontend: Equipment Filter Assets ────────────────────────────────────
add_action('wp_enqueue_scripts', 'gti_enqueue_equipment_filter_assets');
function gti_enqueue_equipment_filter_assets() {
    global $post;

    // Only load when the shortcode is present on the page
    if ( is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'gti_equipment_filter') ) {
        // Google Fonts (Inter)
        if (!wp_style_is('gti-google-fonts', 'enqueued')) {
            wp_enqueue_style(
                'gti-google-fonts',
                'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap',
                [],
                null
            );
        }

        // Font Awesome
        if (!wp_style_is('font-awesome', 'enqueued') && !wp_style_is('font-awesome-5', 'enqueued')) {
            wp_enqueue_style(
                'gti-font-awesome',
                'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
                [],
                '6.5.1'
            );
        }

        // Equipment Filter CSS
        wp_enqueue_style(
            'gti-equipment-filter',
            GTI_CHILD_URL . '/assets/css/equipment-filter.css',
            ['gti-google-fonts'],
            GTI_VERSION
        );

        // Equipment Filter JS
        wp_enqueue_script(
            'gti-equipment-filter',
            GTI_CHILD_URL . '/assets/js/equipment-filter.js',
            [],
            GTI_VERSION,
            true
        );

        // Equipment Detail CSS
        wp_enqueue_style(
            'gti-equipment-detail',
            GTI_CHILD_URL . '/assets/css/equipment-detail.css',
            ['gti-google-fonts'],
            GTI_VERSION
        );

        // Equipment Detail JS
        wp_enqueue_script(
            'gti-equipment-detail',
            GTI_CHILD_URL . '/assets/js/equipment-detail.js',
            [],
            GTI_VERSION,
            true
        );
    }
}
