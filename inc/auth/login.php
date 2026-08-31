<?php
/**
 * Custom login logic
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Proses login melalui wp_signon() dengan support email/phone.
 * Dipakai oleh AJAX handler (ajax-login.php) & form login langsung.
 *
 * @param string $username  Email atau nomor HP.
 * @param string $password
 * @param bool   $remember
 * @return WP_User|WP_Error
 */
function gti_do_login( $username, $password, $remember = false ) {
    $username = gti_sanitize_text( $username );
    
    // Resolve username: jika phone → dapat user_login-nya
    if ( ! gti_validate_email( $username ) ) {
        $phone   = gti_normalize_phone( $username );
        $wp_user = gti_get_user_by_phone( $phone );
        if ( $wp_user ) {
            $username = $wp_user->user_login;
        }
    }
    
    $credentials = [
        'user_login'    => $username,
        'user_password' => $password,
        'remember'      => (bool) $remember,
    ];
    
    return wp_signon( $credentials, is_ssl() );
}

/**
 * Process login via AJAX.
 */
function gti_ajax_login() {
    // Verify nonce
    if ( ! gti_verify_nonce( 'gti_login_action', 'gti_login_nonce' ) ) {
        wp_send_json_error( [ 'message' => 'Security check failed' ] );
    }
    
    // Rate limit check
    $ip = gti_get_client_ip();
    if ( ! gti_check_rate_limit( "login_{$ip}", GTI_RL_LOGIN_MAX, GTI_RL_LOGIN_WINDOW ) ) {
        wp_send_json_error( [ 'message' => 'Terlalu banyak percobaan login. Silakan coba lagi dalam 15 menit.' ] );
    }
    
    // Sanitize input
    $username = gti_sanitize_text( $_POST['username'] ?? '' );
    $password = $_POST['password'] ?? '';
    $remember = ! empty( $_POST['remember'] );
    
    // Validate input
    if ( empty( $username ) || empty( $password ) ) {
        wp_send_json_error( [ 'message' => 'Email/HP dan password harus diisi' ] );
    }
    
    // Attempt login
    $user = gti_do_login( $username, $password, $remember );
    
    if ( is_wp_error( $user ) ) {
        gti_increment_rate_limit( "login_{$ip}", GTI_RL_LOGIN_WINDOW );
        wp_send_json_error( [ 'message' => 'Email/HP atau password salah' ] );
    }
    
    // Success - reset rate limit
    gti_reset_rate_limit( "login_{$ip}" );
    
    // Log activity
    gti_log_activity( $user->ID, 'login', 'User logged in' );
    
    // Get redirect URL
    $redirect = isset( $_POST['redirect'] ) ? esc_url_raw( $_POST['redirect'] ) : gti_dashboard_url();
    
    wp_send_json_success( [
        'message'  => 'Login berhasil',
        'redirect' => $redirect,
    ] );
}
add_action( 'wp_ajax_gti_login', 'gti_ajax_login' );
add_action( 'wp_ajax_nopriv_gti_login', 'gti_ajax_login' );
