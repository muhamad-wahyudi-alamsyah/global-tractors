<?php
/**
 * Dashboard sidebar as data (PRD §13.6).
 *
 * The sidebar used to be 56 lines of markup copied into 18 templates, so adding
 * a menu item meant editing 18 files and the "Notifications" badge was the
 * literal number 3. Declaring it once as an array closes three things at the
 * same time: §9.4 layer 1 (hide what the role cannot use), B-06 (real badge
 * counts), and the 18-file edit.
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Sidebar structure.
 *
 * Item keys: type (item|section|parent), slug, label, icon, cap, badge, children.
 */
function gti_dashboard_menu() {
    $menu = array(
        array( 'type' => 'item', 'slug' => '', 'label' => 'Dashboard', 'icon' => 'fa-th-large' ),

        array( 'type' => 'section', 'label' => 'EQUIPMENT' ),
        array(
            'type' => 'parent', 'label' => 'Equipment', 'icon' => 'fa-truck',
            'cap'  => 'gti_manage_equipment',
            'children' => array(
                array( 'slug' => 'used-equipment',   'label' => 'Used Equipment' ),
                array( 'slug' => 'rental-equipment', 'label' => 'Rental Equipment' ),
            ),
        ),
        array( 'type' => 'item', 'slug' => 'spare-parts', 'label' => 'Spare Parts', 'icon' => 'fa-cog', 'cap' => 'gti_manage_spare_parts' ),

        array( 'type' => 'section', 'label' => 'REQUEST & INQUIRY' ),
        array( 'type' => 'item', 'slug' => 'request-equipment', 'label' => 'Request Equipment', 'icon' => 'fa-file-alt',       'cap' => 'gti_manage_requests',   'badge' => 'requests_new' ),
        array( 'type' => 'item', 'slug' => 'request-quotation', 'label' => 'Request Quotation', 'icon' => 'fa-clipboard-list', 'cap' => 'gti_manage_quotations', 'badge' => 'quotations_new' ),
        array( 'type' => 'item', 'slug' => 'sell-equipment',    'label' => 'Sell Equipment',    'icon' => 'fa-handshake',      'cap' => 'gti_manage_requests',   'badge' => 'sell_new' ),

        array( 'type' => 'section', 'label' => 'MANAGEMENT' ),
        array( 'type' => 'item', 'slug' => 'customers',     'label' => 'Customers',       'icon' => 'fa-users',       'cap' => 'gti_manage_customers' ),
        array( 'type' => 'item', 'slug' => 'news-articles', 'label' => 'News & Articles', 'icon' => 'fa-newspaper',   'cap' => 'edit_posts' ),
        array( 'type' => 'item', 'slug' => 'media-library', 'label' => 'Media Library',   'icon' => 'fa-photo-video', 'cap' => 'upload_files' ),

        array( 'type' => 'section', 'label' => 'SYSTEM' ),
        array( 'type' => 'item', 'slug' => 'users',        'label' => 'Users',        'icon' => 'fa-user-shield', 'cap' => 'gti_manage_users' ),
        array( 'type' => 'item', 'slug' => 'activity-log', 'label' => 'Activity Log', 'icon' => 'fa-history',     'cap' => 'gti_manage_settings' ),
    );

    /**
     * Filter the dashboard sidebar structure.
     */
    return apply_filters( 'gti_dashboard_menu', $menu );
}

/**
 * Menu with entries the current user cannot reach removed.
 *
 * Also drops a section heading that ends up with nothing under it, so a
 * restricted role does not see an empty "SYSTEM" label.
 */
function gti_visible_dashboard_menu() {
    $visible = array();

    foreach ( gti_dashboard_menu() as $item ) {
        if ( ! empty( $item['cap'] ) && ! current_user_can( $item['cap'] ) ) {
            continue;
        }

        if ( ! empty( $item['children'] ) ) {
            $children = array();
            foreach ( $item['children'] as $child ) {
                if ( empty( $child['cap'] ) || current_user_can( $child['cap'] ) ) {
                    $children[] = $child;
                }
            }
            if ( ! $children ) {
                continue;
            }
            $item['children'] = $children;
        }

        $visible[] = $item;
    }

    // Remove headings with no following item.
    $out = array();
    foreach ( $visible as $i => $item ) {
        if ( ( $item['type'] ?? '' ) === 'section' ) {
            $next = $visible[ $i + 1 ] ?? null;
            if ( ! $next || ( $next['type'] ?? '' ) === 'section' ) {
                continue;
            }
        }
        $out[] = $item;
    }

    return $out;
}

/**
 * Live counts behind the sidebar badges (B-06 — this replaced a hardcoded "3").
 *
 * Cached for a minute: the sidebar renders on every dashboard page and these
 * numbers do not need to be to-the-second accurate.
 */
function gti_menu_badge_counts() {
    $cached = get_transient( 'gti_menu_badges_' . get_current_user_id() );
    if ( is_array( $cached ) ) {
        return $cached;
    }

    global $wpdb;

    $counts = array(
        'requests_new'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}gti_requests WHERE status = 'new'" . gti_scope_where_sql( 'request' ) ),
        'quotations_new' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}gti_quotations WHERE status = 'new'" . gti_scope_where_sql( 'quotation' ) ),
        'sell_new'       => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}gti_sell_requests WHERE status = 'new'" . gti_scope_where_sql( 'sell' ) ),
    );

    set_transient( 'gti_menu_badges_' . get_current_user_id(), $counts, MINUTE_IN_SECONDS );

    return $counts;
}

/**
 * Total for the header notification bell.
 */
function gti_notification_count() {
    return array_sum( gti_menu_badge_counts() );
}

/**
 * Dashboard logo URL.
 *
 * R-09: this was hardcoded to a local .test hostname in 18 templates,
 * which would have broken every dashboard header the moment the site moved to
 * its production domain.
 *
 * @param string $variant full|favicon
 */
function gti_logo_url( $variant = 'full' ) {
    $mod = get_theme_mod( $variant === 'favicon' ? 'gti_logo_favicon' : 'gti_logo' );
    if ( $mod ) {
        return $mod;
    }

    if ( $variant === 'full' && has_custom_logo() ) {
        $id  = get_theme_mod( 'custom_logo' );
        $src = $id ? wp_get_attachment_image_src( $id, 'full' ) : null;
        if ( $src ) {
            return $src[0];
        }
    }

    if ( $variant === 'favicon' && get_site_icon_url() ) {
        return get_site_icon_url();
    }

    return GTI_CHILD_URL . '/assets/images/' . ( $variant === 'favicon' ? 'logo-mark.png' : 'logo.png' );
}
