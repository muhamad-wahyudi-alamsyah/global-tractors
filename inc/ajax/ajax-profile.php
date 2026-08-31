<?php
/**
 * AJAX profile handler
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Get user profile.
 */
function gti_ajax_get_profile() {
    $user_id = gti_ajax_get_user_id();
    if ( ! $user_id ) {
        gti_ajax_error( 'Unauthorized', 401 );
    }
    
    $profile = gti_get_user_profile( $user_id );
    gti_ajax_success( [ 'profile' => $profile ] );
}
add_action( 'wp_ajax_gti_get_profile', 'gti_ajax_get_profile' );

/**
 * Update user profile.
 */
function gti_ajax_update_profile() {
    $user_id = gti_ajax_get_user_id();
    if ( ! $user_id ) {
        gti_ajax_error( 'Unauthorized', 401 );
    }
    
    if ( ! gti_verify_nonce( 'gti_profile_action', 'gti_profile_nonce' ) ) {
        gti_ajax_error( 'Security check failed', 403 );
    }
    
    $name    = gti_sanitize_text( $_POST['name'] ?? '' );
    $phone   = gti_sanitize_phone( $_POST['phone'] ?? '' );
    $address = gti_sanitize_textarea( $_POST['address'] ?? '' );
    $city    = gti_sanitize_text( $_POST['city'] ?? '' );
    
    if ( empty( $name ) ) {
        gti_ajax_error( 'Nama harus diisi' );
    }
    
    if ( ! gti_validate_phone( $phone ) ) {
        gti_ajax_error( 'Format nomor HP tidak valid' );
    }
    
    // Check if phone is taken by another user
    $existing_user = gti_get_user_by_phone( $phone );
    if ( $existing_user && $existing_user->ID != $user_id ) {
        gti_ajax_error( 'Nomor HP sudah digunakan user lain' );
    }
    
    // Update user
    wp_update_user( [
        'ID'           => $user_id,
        'display_name' => $name,
    ] );
    
    // Update meta
    gti_set_user_phone( $user_id, $phone );
    gti_update_user_meta( $user_id, 'gti_address', $address );
    gti_update_user_meta( $user_id, 'gti_city', $city );
    
    gti_log_activity( $user_id, 'profile_update', 'Profile updated' );
    
    gti_ajax_success( [ 'message' => 'Profil berhasil diupdate' ] );
}
add_action( 'wp_ajax_gti_update_profile', 'gti_ajax_update_profile' );
