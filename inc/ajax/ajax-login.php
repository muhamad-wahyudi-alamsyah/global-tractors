<?php
/**
 * AJAX login handler
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Login handler is in inc/auth/login.php
// This file is for additional login-related AJAX actions

/**
 * Check login status.
 */
function gti_ajax_check_login() {
    wp_send_json_success( [
        'logged_in' => is_user_logged_in(),
        'user'      => is_user_logged_in() ? [
            'id'    => get_current_user_id(),
            'name'  => wp_get_current_user()->display_name,
            'email' => wp_get_current_user()->user_email,
            'role'  => wp_get_current_user()->roles[0] ?? '',
        ] : null,
    ] );
}
add_action( 'wp_ajax_gti_check_login', 'gti_ajax_check_login' );
add_action( 'wp_ajax_nopriv_gti_check_login', 'gti_ajax_check_login' );
