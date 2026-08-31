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
