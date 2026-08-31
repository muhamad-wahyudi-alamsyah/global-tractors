<?php
/**
 * Install/setup
 * @package global-tractors
 */
if (!defined('ABSPATH')) exit;

add_action('after_switch_theme', 'gti_install');
function gti_install() {
    gti_setup_roles();
    gti_flush_rewrite_rules();
    gti_run_migrations();
}
