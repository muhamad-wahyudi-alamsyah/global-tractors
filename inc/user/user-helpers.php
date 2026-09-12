<?php
/**
 * User helper functions
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'gti_normalize_phone' ) ) {
    /**
     * Normalise an Indonesian phone number to bare 62XXXXXXXXX digits.
     *
     * Callers below and in user-meta.php have always relied on this, but it was
     * never defined anywhere — reaching gti_get_user_by_phone() was a fatal.
     *
     * @param string $phone
     * @return string
     */
    function gti_normalize_phone( $phone ) {
        $digits = preg_replace( '/[^0-9]/', '', (string) $phone );
        if ( $digits === '' ) return '';

        if ( strpos( $digits, '0' ) === 0 ) {
            return '62' . substr( $digits, 1 );      // 0812... → 62812...
        }
        if ( strpos( $digits, '62' ) === 0 ) {
            return $digits;                          // already normalised (+62 loses its plus above)
        }
        return '62' . $digits;                       // bare 812... → 62812...
    }
}

/**
 * Get user by phone number.
 *
 * @param string $phone
 * @return WP_User|null
 */
function gti_get_user_by_phone( $phone ) {
    global $wpdb;
    
    $phone = gti_normalize_phone( $phone );
    
    $user_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT user_id FROM {$wpdb->usermeta}
             WHERE meta_key = 'gti_phone'
             AND meta_value = %s",
            $phone
        )
    );
    
    if ( $user_id ) {
        return get_userdata( $user_id );
    }
    
    return null;
}

/**
 * Check if email exists.
 *
 * @param string $email
 * @return bool
 */
function gti_email_exists( $email ) {
    return email_exists( $email ) !== false;
}

/**
 * Check if phone exists.
 *
 * @param string $phone
 * @return bool
 */
function gti_phone_exists( $phone ) {
    return gti_get_user_by_phone( $phone ) !== null;
}

/**
 * Get user display name.
 *
 * @param int $user_id
 * @return string
 */
function gti_get_user_name( $user_id ) {
    $user = get_userdata( $user_id );
    return $user ? $user->display_name : '';
}

/**
 * Get user orders.
 *
 * @param int $user_id
 * @param int $limit
 * @return array
 */
function gti_get_user_orders( $user_id, $limit = 10 ) {
    if ( ! class_exists( 'WooCommerce' ) ) return [];
    
    $args = [
        'customer_id' => $user_id,
        'limit'       => $limit,
        'orderby'     => 'date',
        'order'       => 'DESC',
        'return'      => 'objects',
    ];
    
    return wc_get_orders( $args );
}

/**
 * Display label for a user's role.
 *
 * Replaces the hardcoded `<small>Super Admin</small>` that was copied into
 * every dashboard header, where it showed the same title to every user
 * regardless of their actual role (PRD §3.2 B-07).
 *
 * @param int $user_id Defaults to the current user.
 */
function gti_role_label_for_user( $user_id = 0 ) {
    $user = $user_id ? get_userdata( $user_id ) : wp_get_current_user();

    if ( ! $user || ! $user->exists() ) {
        return '';
    }

    // A GTI role wins over the underlying WordPress role.
    if ( class_exists( 'GTI_Roles' ) ) {
        $gti_roles = GTI_Roles::get_roles();
        foreach ( (array) $user->roles as $role ) {
            if ( isset( $gti_roles[ $role ] ) ) {
                return $gti_roles[ $role ];
            }
        }
    }

    $wp_roles = wp_roles();
    foreach ( (array) $user->roles as $role ) {
        if ( isset( $wp_roles->role_names[ $role ] ) ) {
            return translate_user_role( $wp_roles->role_names[ $role ] );
        }
    }

    return '';
}
