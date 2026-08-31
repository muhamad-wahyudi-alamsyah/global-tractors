<?php
/**
 * User meta functions
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Get user meta dengan default.
 *
 * @param int    $user_id
 * @param string $key
 * @param mixed  $default
 * @return mixed
 */
function gti_get_user_meta( $user_id, $key, $default = '' ) {
    $value = get_user_meta( $user_id, $key, true );
    return $value !== '' ? $value : $default;
}

/**
 * Update user meta.
 *
 * @param int    $user_id
 * @param string $key
 * @param mixed  $value
 */
function gti_update_user_meta( $user_id, $key, $value ) {
    update_user_meta( $user_id, $key, $value );
}

/**
 * Get phone number dari user.
 *
 * @param int $user_id
 * @return string
 */
function gti_get_user_phone( $user_id ) {
    return gti_get_user_meta( $user_id, 'gti_phone' );
}

/**
 * Set phone number untuk user.
 *
 * @param int    $user_id
 * @param string $phone
 */
function gti_set_user_phone( $user_id, $phone ) {
    gti_update_user_meta( $user_id, 'gti_phone', gti_normalize_phone( $phone ) );
}

/**
 * Get user profile data.
 *
 * @param int $user_id
 * @return array
 */
function gti_get_user_profile( $user_id ) {
    $user = get_userdata( $user_id );
    if ( ! $user ) return [];
    
    return [
        'id'          => $user->ID,
        'email'       => $user->user_email,
        'name'        => $user->display_name,
        'phone'       => gti_get_user_phone( $user_id ),
        'address'     => gti_get_user_meta( $user_id, 'gti_address' ),
        'city'        => gti_get_user_meta( $user_id, 'gti_city' ),
        'province'    => gti_get_user_meta( $user_id, 'gti_province' ),
        'postal_code' => gti_get_user_meta( $user_id, 'gti_postal_code' ),
        'avatar_url'  => get_avatar_url( $user_id ),
        'registered'  => $user->user_registered,
    ];
}
