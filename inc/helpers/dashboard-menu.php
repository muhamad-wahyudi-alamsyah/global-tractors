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
 * Menu slug to highlight for a page.
 *
 * A page that is not in the menu lights up the entry it belongs under, as the
 * hand-written sidebars did: an add form under its list, a customer under
 * Customers. The Dashboard entry's slug is ''.
 */
function gti_menu_active_slug( $page ) {
    $map = array(
        'dashboard'            => '',
        'add-used-equipment'   => 'used-equipment',
        'add-rental-equipment' => 'rental-equipment',
        'add-spare-part'       => 'spare-parts',
        'add-news-article'     => 'news-articles',
        'customer-detail'      => 'customers',
    );

    return array_key_exists( $page, $map ) ? $map[ $page ] : $page;
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
 * The three badged inbox lists: type => table, label, icon, dashboard slug.
 */
function gti_inbox_sources() {
    return array(
        'request'   => array( 'gti_requests',      'Request Equipment', 'fa-truck-pickup',     'request-equipment' ),
        'quotation' => array( 'gti_quotations',    'Request Quotation', 'fa-file-invoice',     'request-quotation' ),
        'sell'      => array( 'gti_sell_requests', 'Sell Equipment',    'fa-hand-holding-usd', 'sell-equipment' ),
    );
}

/**
 * WHERE clause for rows still "new" that arrived after the current user last
 * opened that list (gti_mark_list_seen()). Shared by the sidebar badges and the
 * header bell so both always agree.
 */
function gti_unseen_where_sql( $type ) {
    global $wpdb;
    $sources = gti_inbox_sources();
    $seen    = (string) get_user_meta( get_current_user_id(), 'gti_seen_' . $sources[ $type ][3], true );

    return $wpdb->prepare( "WHERE status = 'new' AND created_at > %s", $seen ) . gti_scope_where_sql( $type );
}

/**
 * Live counts behind the sidebar badges (B-06 — this replaced a hardcoded "3").
 *
 * Not cached: a fresh submission has to show up on the very next page load,
 * and three COUNT(*) queries are cheap.
 */
function gti_menu_badge_counts() {
    global $wpdb;

    $keys   = array( 'request' => 'requests_new', 'quotation' => 'quotations_new', 'sell' => 'sell_new' );
    $counts = array();
    foreach ( gti_inbox_sources() as $type => $src ) {
        $counts[ $keys[ $type ] ] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}{$src[0]} " . gti_unseen_where_sql( $type ) );
    }

    return $counts;
}

/**
 * Record that the current user has opened a badged list, so its badge and bell
 * entries only reappear once newer rows arrive. created_at is stored in site
 * time, hence current_time().
 */
function gti_mark_list_seen( $page ) {
    if ( in_array( $page, wp_list_pluck( gti_inbox_sources(), 3 ), true ) ) {
        update_user_meta( get_current_user_id(), 'gti_seen_' . $page, current_time( 'mysql' ) );
    }
}

/**
 * Total for the header notification bell.
 */
function gti_notification_count() {
    return array_sum( gti_menu_badge_counts() );
}

/**
 * Latest unseen "new" rows, newest first, for the header bell popup.
 *
 * @return array[] type, label, icon, ref, customer, time, url
 */
function gti_notification_items( $limit = 10 ) {
    global $wpdb;

    $items = array();
    foreach ( gti_inbox_sources() as $type => $src ) {
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}{$src[0]} " . gti_unseen_where_sql( $type ) . ' ORDER BY created_at DESC LIMIT %d',
            $limit
        ), ARRAY_A );

        foreach ( (array) $rows as $row ) {
            $items[] = array(
                'type'     => $type,
                'label'    => $src[1],
                'icon'     => $src[2],
                'ref'      => gti_entity_ref( $type, $row ),
                'customer' => $row['customer_name'],
                'created'  => $row['created_at'],
                'url'      => add_query_arg( array( 'status' => 'new', 'open' => (int) $row['id'] ), gti_dashboard_url( $src[3] ) ),
            );
        }
    }

    usort( $items, function ( $a, $b ) { return strcmp( $b['created'], $a['created'] ); } );

    return array_slice( $items, 0, $limit );
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

    // Media-library originals; baseurl keeps this domain-agnostic (R-09).
    $uploads = wp_get_upload_dir();
    return $uploads['baseurl'] . '/2026/07/' . ( $variant === 'favicon'
        ? 'logo-pt-global-tractors-indonesia.png'
        : 'logo-header-footer-pt-global-tractors-indonesia.png' );
}
