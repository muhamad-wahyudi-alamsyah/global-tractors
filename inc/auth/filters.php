<?php
/**
 * Auth filters
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Disable WordPress default login page redirect.
 */
add_filter( 'login_display', '__return_false' );

/**
 * Block direct access to wp-login.php for customers.
 */
add_action( 'init', 'gti_block_wplogin_for_customers' );
function gti_block_wplogin_for_customers() {
    if ( ! defined( 'WP_LOGIN_PHP' ) && basename( $_SERVER['SCRIPT_FILENAME'] ?? '' ) === 'wp-login.php' ) {
        if ( is_user_logged_in() && gti_is_customer() ) {
            wp_safe_redirect( gti_dashboard_url() );
            exit;
        }
    }
}
