<?php
/**
 * Custom registration logic
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Process registration via AJAX.
 */
function gti_ajax_register() {
    // Verify nonce
    if ( ! gti_verify_nonce( 'gti_register_action', 'gti_register_nonce' ) ) {
        wp_send_json_error( [ 'message' => 'Security check failed' ] );
    }
    
    // Rate limit check
    $ip = gti_get_client_ip();
    if ( ! gti_check_rate_limit( "register_{$ip}", GTI_RL_REGISTER_MAX, GTI_RL_REGISTER_WINDOW ) ) {
        wp_send_json_error( [ 'message' => 'Terlalu banyak percobaan registrasi. Silakan coba lagi dalam 1 jam.' ] );
    }
    
    // Sanitize input
    $name     = gti_sanitize_text( $_POST['name'] ?? '' );
    $email    = gti_sanitize_email( $_POST['email'] ?? '' );
    $phone    = gti_sanitize_phone( $_POST['phone'] ?? '' );
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['password_confirm'] ?? '';
    
    // Validate input
    if ( empty( $name ) || empty( $email ) || empty( $phone ) || empty( $password ) ) {
        wp_send_json_error( [ 'message' => 'Semua field harus diisi' ] );
    }
    
    if ( ! gti_validate_name( $name ) ) {
        wp_send_json_error( [ 'message' => 'Nama harus minimal 2 karakter' ] );
    }
    
    if ( ! gti_validate_email( $email ) ) {
        wp_send_json_error( [ 'message' => 'Format email tidak valid' ] );
    }
    
    if ( ! gti_validate_phone( $phone ) ) {
        wp_send_json_error( [ 'message' => 'Format nomor HP tidak valid' ] );
    }
    
    if ( $password !== $confirm ) {
        wp_send_json_error( [ 'message' => 'Password tidak cocok' ] );
    }
    
    $password_validation = gti_validate_password( $password );
    if ( ! $password_validation['valid'] ) {
        wp_send_json_error( [ 'message' => $password_validation['message'] ] );
    }
    
    // Check if email exists
    if ( gti_email_exists( $email ) ) {
        wp_send_json_error( [ 'message' => 'Email sudah terdaftar' ] );
    }
    
    // Check if phone exists
    if ( gti_phone_exists( $phone ) ) {
        wp_send_json_error( [ 'message' => 'Nomor HP sudah terdaftar' ] );
    }
    
    // Create user
    $user_id = wp_create_user( $email, $password, $email );
    
    if ( is_wp_error( $user_id ) ) {
        wp_send_json_error( [ 'message' => 'Gagal membuat akun: ' . $user_id->get_error_message() ] );
    }
    
    // Set user data
    wp_update_user( [
        'ID'           => $user_id,
        'display_name' => $name,
        'role'         => GTI_ROLE_CUSTOMER,
    ] );
    
    // Set phone
    gti_set_user_phone( $user_id, $phone );
    
    // Increment rate limit
    gti_increment_rate_limit( "register_{$ip}", GTI_RL_REGISTER_WINDOW );
    
    // Log activity
    gti_log_activity( $user_id, 'register', 'New user registered' );
    
    // Auto login
    $user = get_userdata( $user_id );
    wp_set_current_user( $user_id );
    wp_set_auth_cookie( $user_id, true );
    
    wp_send_json_success( [
        'message'  => 'Registrasi berhasil',
        'redirect' => gti_dashboard_url(),
    ] );
}
add_action( 'wp_ajax_gti_register', 'gti_ajax_register' );
add_action( 'wp_ajax_nopriv_gti_register', 'gti_ajax_register' );
