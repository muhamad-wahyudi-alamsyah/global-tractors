<?php
/**
 * User validation functions
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Validate email format.
 *
 * @param string $email
 * @return bool
 */
function gti_validate_email( $email ) {
    return filter_var( $email, FILTER_VALIDATE_EMAIL ) !== false;
}

/**
 * Validate phone format (Indonesian).
 *
 * @param string $phone
 * @return bool
 */
function gti_validate_phone( $phone ) {
    $phone = gti_normalize_phone( $phone );
    
    // Must start with 62 and be 10-13 digits
    if ( preg_match( '/^62[0-9]{8,11}$/', $phone ) ) {
        return true;
    }
    
    return false;
}

/**
 * Validate password strength.
 *
 * @param string $password
 * @return array ['valid' => bool, 'message' => string]
 */
function gti_validate_password( $password ) {
    if ( strlen( $password ) < 8 ) {
        return [
            'valid'   => false,
            'message' => 'Password harus minimal 8 karakter',
        ];
    }
    
    if ( ! preg_match( '/[A-Z]/', $password ) ) {
        return [
            'valid'   => false,
            'message' => 'Password harus mengandung huruf besar',
        ];
    }
    
    if ( ! preg_match( '/[a-z]/', $password ) ) {
        return [
            'valid'   => false,
            'message' => 'Password harus mengandung huruf kecil',
        ];
    }
    
    if ( ! preg_match( '/[0-9]/', $password ) ) {
        return [
            'valid'   => false,
            'message' => 'Password harus mengandung angka',
        ];
    }
    
    return [
        'valid'   => true,
        'message' => '',
    ];
}

/**
 * Validate name.
 *
 * @param string $name
 * @return bool
 */
function gti_validate_name( $name ) {
    return strlen( trim( $name ) ) >= 2;
}
