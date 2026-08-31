<?php
/**
 * Custom logout logic
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Process logout via AJAX.
 */
function gti_ajax_logout() {
    // Verify nonce
    if ( ! gti_verify_nonce( 'gti_logout_action', 'gti_logout_nonce' ) ) {
        wp_send_json_error( [ 'message' => 'Security check failed' ] );
    }
    
    $user_id = get_current_user_id();
    
    // Log activity before logout
    if ( $user_id ) {
        gti_log_activity( $user_id, 'logout', 'User logged out' );
    }
    
    // Logout
    wp_logout();
    
    wp_send_json_success( [
        'message'  => 'Logout berhasil',
        'redirect' => gti_login_url(),
    ] );
}
add_action( 'wp_ajax_gti_logout', 'gti_ajax_logout' );
add_action( 'wp_ajax_nopriv_gti_logout', 'gti_ajax_logout' );
