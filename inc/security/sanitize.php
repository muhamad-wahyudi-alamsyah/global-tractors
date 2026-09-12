<?php
/**
 * Sanitize helpers
 * @package global-tractors
 */
if (!defined('ABSPATH')) exit;

function gti_sanitize_text($input) {
    return sanitize_text_field($input);
}

function gti_sanitize_email($input) {
    return sanitize_email($input);
}

function gti_sanitize_textarea($input) {
    return sanitize_textarea_field($input);
}

/**
 * Normalise a phone number to digits, keeping a leading +.
 *
 * Referenced by the registration and profile paths, which called it without it
 * ever being defined — those paths fatal on the first submission.
 */
function gti_sanitize_phone($input) {
    $input = trim((string) $input);
    $plus  = strpos($input, '+') === 0 ? '+' : '';

    return $plus . preg_replace('/\D+/', '', $input);
}
