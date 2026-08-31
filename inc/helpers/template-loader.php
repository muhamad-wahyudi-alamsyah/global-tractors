<?php
/**
 * Template loader
 * @package global-tractors
 */
if (!defined('ABSPATH')) exit;

function gti_get_template($template, $args = []) {
    $file = GTI_CHILD_DIR . '/templates/' . $template . '.php';
    if (file_exists($file)) {
        extract($args);
        include $file;
    }
}

function gti_get_template_part($part, $args = []) {
    $file = GTI_CHILD_DIR . '/template-parts/' . $part . '.php';
    if (file_exists($file)) {
        extract($args);
        include $file;
    }
}
