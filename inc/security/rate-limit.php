<?php
/**
 * Rate limiting functions
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Check rate limit untuk aksi tertentu.
 *
 * @param string $key     Uniq key (mis. IP + action).
 * @param int    $max     Maksimum attempts.
 * @param int    $window  Window dalam detik.
 * @return bool True jika masih diizinkan, false jika melebihi limit.
 */
function gti_check_rate_limit( $key, $max, $window ) {
    $transient_key = 'gti_rl_' . md5( $key );
    $attempts = get_transient( $transient_key );
    
    if ( $attempts === false ) {
        $attempts = 0;
    }
    
    if ( $attempts >= $max ) {
        return false;
    }
    
    return true;
}

/**
 * Increment rate limit counter.
 *
 * @param string $key    Uniq key.
 * @param int    $window Window dalam detik.
 */
function gti_increment_rate_limit( $key, $window ) {
    $transient_key = 'gti_rl_' . md5( $key );
    $attempts = get_transient( $transient_key );
    
    if ( $attempts === false ) {
        $attempts = 0;
    }
    
    set_transient( $transient_key, $attempts + 1, $window );
}

/**
 * Reset rate limit counter.
 *
 * @param string $key Uniq key.
 */
function gti_reset_rate_limit( $key ) {
    $transient_key = 'gti_rl_' . md5( $key );
    delete_transient( $transient_key );
}

/**
 * Get client IP address.
 *
 * @return string
 */
if ( ! function_exists( 'gti_get_client_ip' ) ) {
    function gti_get_client_ip() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    
        // Behind proxy
        if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $ips = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] );
            $ip = trim( $ips[0] );
        }
    
        return $ip;
    }
}
