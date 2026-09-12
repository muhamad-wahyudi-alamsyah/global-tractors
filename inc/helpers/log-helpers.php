<?php
/**
 * Debug logging helper.
 *
 * Replaces the ad-hoc file_put_contents() debug writes that shipped to
 * production paths (/tmp/gti-email-debug.log, wp-content/debug-fluentform-fields.log).
 * Those grew without bound and, in the wp-content case, were publicly reachable.
 *
 * gti_log() is a no-op unless WP_DEBUG is on, and routes through error_log()
 * so output follows whatever the site already configured.
 *
 * @package global-tractors
 */
if (!defined('ABSPATH')) exit;

if (!function_exists('gti_log')) {
    /**
     * Write a debug line. Silent unless WP_DEBUG is enabled.
     *
     * @param string $message Human-readable message.
     * @param mixed  $context Optional payload; arrays/objects are dumped.
     */
    function gti_log($message, $context = null) {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        $line = '[GTI] ' . $message;
        if ($context !== null) {
            $line .= ' ' . (is_scalar($context) ? (string) $context : wp_json_encode($context));
        }
        error_log($line);
    }
}
