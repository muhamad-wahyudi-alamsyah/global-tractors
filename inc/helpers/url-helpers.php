<?php
/**
 * URL helper functions
 * @package global-tractors
 */
if (!defined('ABSPATH')) exit;

function gti_dashboard_url($path = '') {
    $base = home_url('/' . GTI_DASHBOARD_BASE . '/');
    if ($path) {
        return $base . ltrim($path, '/') . '/';
    }
    return $base;
}

function gti_login_url() {
    return gti_dashboard_url('login');
}

function gti_is_active($path) {
    $current = untrailingslashit($_SERVER['REQUEST_URI'] ?? '');
    $target = untrailingslashit($path);
    return $current === $target || strpos($current, $target . '/') === 0;
}

function gti_require_login() {
    if (!is_user_logged_in()) {
        wp_safe_redirect(gti_login_url());
        exit;
    }
}

function gti_redirect_if_logged_in() {
    if (is_user_logged_in()) {
        wp_safe_redirect(gti_dashboard_url());
        exit;
    }
}
