<?php
/**
 * Capabilities helpers
 * @package global-tractors
 */
if (!defined('ABSPATH')) exit;

function gti_is_admin() {
    return current_user_can('manage_options');
}

function gti_is_editor() {
    return current_user_can('edit_posts') || current_user_can('gti_editor');
}

function gti_require_capability($capability = 'manage_options') {
    if (!current_user_can($capability)) {
        wp_die('Anda tidak memiliki akses ke halaman ini.', 403);
    }
}
