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

