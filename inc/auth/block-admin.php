<?php
/**
 * Block wp-admin access untuk customers
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Redirect customer ke dashboard jika coba akses /wp-admin/.
 */
add_action( 'admin_init', 'gti_block_admin_for_customer' );
function gti_block_admin_for_customer() {
    if ( wp_doing_ajax() ) return;
    if ( ! is_user_logged_in() ) return;
    
    if ( gti_is_customer() ) {
        wp_safe_redirect( gti_dashboard_url() );
        exit;
    }
}

/**
 * Sembunyikan admin bar untuk customer.
 */
add_filter( 'show_admin_bar', 'gti_hide_admin_bar_customer' );
function gti_hide_admin_bar_customer( $show ) {
    if ( ! is_user_logged_in() ) return $show;
    if ( gti_is_customer() ) return false;
    
    // Juga sembunyikan di halaman dashboard untuk semua user
    $page = get_query_var( 'gti_page' );
    if ( $page ) return false;
    
    return $show;
}

/**
 * Redirect customer ke dashboard setelah login (bukan ke wp-admin).
 *
 * @param string  $redirect_to  URL redirect default.
 * @param string  $request      URL yang diminta.
 * @param WP_User $user         User yang login.
 * @return string
 */
add_filter( 'login_redirect', 'gti_customer_login_redirect', 10, 3 );
function gti_customer_login_redirect( $redirect_to, $request, $user ) {
    if ( is_wp_error( $user ) ) return $redirect_to;
    if ( gti_is_customer( $user ) ) {
        return gti_dashboard_url();
    }
    return $redirect_to;
}

/**
 * Override wp-login.php: arahkan link login ke custom dashboard login.
 */
add_filter( 'login_url', function ( $url, $redirect ) {
    if ( ! empty( $redirect ) ) {
        return gti_login_url() . '?redirect=' . urlencode( $redirect );
    }
    return gti_login_url();
}, 10, 2 );
