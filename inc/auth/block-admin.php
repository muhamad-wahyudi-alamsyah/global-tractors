<?php
/**
 * Keep GTI users out of wp-admin (m-13).
 *
 * B-3 gave the GTI roles core caps (edit_posts, upload_files, list_users…) so
 * the dashboard's News, Media and Users pages work. Those same caps would let a
 * Sales user edit posts or users straight from /wp-admin, outside every guard
 * the dashboard applies. WordPress administrators keep wp-admin.
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function gti_is_dashboard_only_user() {
    if ( ! is_user_logged_in() || current_user_can( 'manage_options' ) ) return false;
    return current_user_can( 'gti_access' ) || in_array( GTI_ROLE_CUSTOMER, (array) wp_get_current_user()->roles, true );
}

// Priority 1: WooCommerce's prevent_admin_access() also runs on admin_init (10)
// and, since plugins hook before the theme, would send Sales — who lacks
// edit_posts — to /my-account/ first.
add_action( 'admin_init', 'gti_block_admin_for_dashboard_users', 1 );
function gti_block_admin_for_dashboard_users() {
    // admin-ajax.php also fires admin_init; the dashboard and public forms live on it.
    if ( wp_doing_ajax() ) return;
    if ( gti_is_dashboard_only_user() ) {
        wp_safe_redirect( gti_dashboard_url() );
        exit;
    }
}

// Every link in the admin bar points into wp-admin, which now just bounces them.
add_filter( 'show_admin_bar', function ( $show ) {
    return gti_is_dashboard_only_user() ? false : $show;
} );
