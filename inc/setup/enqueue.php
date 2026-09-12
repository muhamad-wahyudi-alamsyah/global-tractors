<?php
/**
 * Enqueue scripts and styles
 * @package global-tractors
 */
if (!defined('ABSPATH')) exit;

add_action('wp_enqueue_scripts', 'gti_enqueue_dashboard_assets');
/**
 * Dashboard assets.
 *
 * Font Awesome and Google Fonts used to be raw <link> tags repeated in 18
 * templates, and the 4,100 lines of CSS those templates carried inline are now
 * the dashboard-*.css files loaded here (PRD §13.7). Shared sheets load before
 * the per-page sheet so page-level overrides still win.
 */
function gti_enqueue_dashboard_assets() {
    $page = get_query_var('gti_page');
    if (!$page) {
        return;
    }

    wp_enqueue_style(
        'gti-google-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap',
        [],
        null
    );

    wp_enqueue_style(
        'gti-font-awesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
        [],
        '6.5.1'
    );

    gti_enqueue_style('gti-dashboard', 'assets/css/dashboard.css', ['gti-google-fonts']);

    // Shared component stylesheets, in cascade order.
    foreach (['layout', 'table', 'drawer', 'modal', 'toast', 'forms'] as $component) {
        gti_enqueue_style(
            'gti-dashboard-' . $component,
            'assets/css/dashboard-' . $component . '.css',
            ['gti-dashboard']
        );
    }

    $assets = gti_page_assets();

    foreach ((array) $assets['css'] as $handle) {
        gti_enqueue_style('gti-css-' . $handle, 'assets/css/' . $handle . '.css', ['gti-dashboard']);
    }

    // The page's own sheet loads last so it can override anything above.
    gti_enqueue_style('gti-page-' . $page, 'assets/css/pages/' . $page . '.css', ['gti-dashboard']);

    // Shared UI behaviour: toast, drawer, modal, dropdown, sidebar, fetch wrapper.
    gti_enqueue_script('gti-dashboard-ui', 'assets/js/dashboard-ui.js', []);

    wp_localize_script('gti-dashboard-ui', 'gtiAjax', [
        'ajaxurl'   => admin_url('admin-ajax.php'),
        'nonce'     => wp_create_nonce('gti_nonce'),
        'version'   => GTI_VERSION,
        'maxUpload' => (int) wp_max_upload_size(),
        'docMax'    => GTI_ATTACHMENT_MAX_BYTES,
        'dashboard' => untrailingslashit(gti_dashboard_url()),
    ]);

    // Inbox pages share one behaviour layer on top of dashboard-ui.
    $deps = ['gti-dashboard-ui'];
    if (in_array($page, ['request-equipment', 'request-quotation', 'sell-equipment'], true)) {
        gti_enqueue_script('gti-inbox-ui', 'assets/js/inbox-ui.js', ['gti-dashboard-ui']);
        $deps[] = 'gti-inbox-ui';
    }

    foreach ((array) $assets['js'] as $handle) {
        $script = 'gti-page-' . $handle;
        if (gti_enqueue_script($script, 'assets/js/pages/' . $handle . '.js', $deps)) {
            $data = gti_page_data();
            if ($data) {
                wp_localize_script($script, 'gtiPageData', $data);
            }
        }
    }

    if ($page === 'dashboard' || $page === '') {
        wp_enqueue_script('gti-chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js', [], '4.4.0', true);
    }

    // The article form renders wp_editor(); on a front-end route its scripts and
    // styles are not registered unless we ask for them here (PRD §6.10 gap 2).
    if ($page === 'add-news-article') {
        wp_enqueue_editor();
        wp_enqueue_media();
    }

    if (in_array($page, ['add-equipment', 'add-used-equipment', 'add-rental-equipment', 'add-spare-part'], true)) {
        gti_enqueue_style('gti-add-equipment', 'assets/css/add-equipment.css', ['gti-dashboard']);
        gti_enqueue_script('gti-add-equipment', 'assets/js/add-equipment.js', ['gti-dashboard-ui']);
    }
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

    // Only load when any of the filter shortcodes are present on the page
    $has_filter_shortcode = is_a($post, 'WP_Post') && (
        has_shortcode($post->post_content, 'gti_used_equipment_filter') ||
        has_shortcode($post->post_content, 'gti_rental_equipment_filter') ||
        has_shortcode($post->post_content, 'gti_spare_parts_filter')
    );
    if ( $has_filter_shortcode ) {
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

        // The inquiry form posts to admin-ajax; give it the real URL instead of
        // assuming /wp-admin/ is reachable at that path.
        wp_localize_script('gti-equipment-detail', 'gtiAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
        ]);
    }
}
