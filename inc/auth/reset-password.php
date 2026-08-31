<?php
/**
 * Reset password logic
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Process forgot password via AJAX.
 */
function gti_ajax_forgot_password() {
    if ( ! gti_verify_nonce( 'gti_forgot_password_action', 'gti_forgot_password_nonce' ) ) {
        wp_send_json_error( [ 'message' => 'Security check failed' ] );
    }
    
    $ip = gti_get_client_ip();
    if ( ! gti_check_rate_limit( "forgot_{$ip}", GTI_RL_LOGIN_MAX, GTI_RL_LOGIN_WINDOW ) ) {
        wp_send_json_error( [ 'message' => 'Terlalu banyak percobaan. Silakan coba lagi nanti.' ] );
    }
    
    $email = gti_sanitize_email( $_POST['email'] ?? '' );
    
    if ( empty( $email ) || ! gti_validate_email( $email ) ) {
        wp_send_json_error( [ 'message' => 'Email tidak valid' ] );
    }
    
    $user = get_user_by( 'email', $email );
    
    // Always show success for security
    if ( $user ) {
        $reset_key = get_password_reset_key( $user );
        
        if ( ! is_wp_error( $reset_key ) ) {
            $reset_url = network_site_url(
                "wp-login.php?action=rp&key={$reset_key}&login=" . rawurlencode( $user->user_login ),
                'login'
            );
            
            $subject = 'Reset Password - ' . get_bloginfo( 'name' );
            $message = "Halo {$user->display_name},\n\n";
            $message .= "Kami menerima permintaan untuk reset password akun Anda.\n\n";
            $message .= "Klik link berikut untuk reset password:\n";
            $message .= "{$reset_url}\n\n";
            $message .= "Link ini akan kedaluwarsa dalam 24 jam.\n\n";
            $message .= "Jika Anda tidak meminta reset password, abaikan email ini.\n\n";
            $message .= "Terima kasih,\n";
            $message .= get_bloginfo( 'name' );
            
            wp_mail( $user->user_email, $subject, $message );
        }
    }
    
    gti_increment_rate_limit( "forgot_{$ip}", GTI_RL_LOGIN_WINDOW );
    
    wp_send_json_success( [
        'message' => 'Jika email terdaftar, link reset password telah dikirim',
    ] );
}
add_action( 'wp_ajax_gti_forgot_password', 'gti_ajax_forgot_password' );
add_action( 'wp_ajax_nopriv_gti_forgot_password', 'gti_ajax_forgot_password' );
