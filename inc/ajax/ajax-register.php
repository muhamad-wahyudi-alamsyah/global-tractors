<?php
/**
 * AJAX register handler
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Register handler is in inc/auth/register.php
// This file is for additional register-related AJAX actions

/**
 * Check email availability.
 */
function gti_ajax_check_email() {
    $email = gti_sanitize_email( $_POST['email'] ?? '' );
    
    if ( empty( $email ) ) {
        wp_send_json_error( [ 'message' => 'Email tidak valid' ] );
    }
    
    $exists = gti_email_exists( $email );
    
    wp_send_json_success( [
        'available' => ! $exists,
        'message'   => $exists ? 'Email sudah terdaftar' : 'Email tersedia',
    ] );
}
add_action( 'wp_ajax_gti_check_email', 'gti_ajax_check_email' );
add_action( 'wp_ajax_nopriv_gti_check_email', 'gti_ajax_check_email' );

/**
 * Check phone availability.
 */
function gti_ajax_check_phone() {
    $phone = gti_sanitize_phone( $_POST['phone'] ?? '' );
    
    if ( empty( $phone ) ) {
        wp_send_json_error( [ 'message' => 'Nomor HP tidak valid' ] );
    }
    
    $exists = gti_phone_exists( $phone );
    
    wp_send_json_success( [
        'available' => ! $exists,
        'message'   => $exists ? 'Nomor HP sudah terdaftar' : 'Nomor HP tersedia',
    ] );
}
add_action( 'wp_ajax_gti_check_phone', 'gti_ajax_check_phone' );
add_action( 'wp_ajax_nopriv_gti_check_phone', 'gti_ajax_check_phone' );
