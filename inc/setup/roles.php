<?php
/**
 * Custom roles
 * @package global-tractors
 */
if (!defined('ABSPATH')) exit;

add_action('after_switch_theme', 'gti_setup_roles');
function gti_setup_roles() {
    if (!get_role('gti_editor')) {
        add_role('gti_editor', 'GTI Editor', [
            'read' => true,
            'edit_posts' => true,
            'edit_published_posts' => true,
            'publish_posts' => true,
            'read_private_posts' => true,
        ]);
    }
}
