<?php
/**
 * Nonce helpers
 * @package global-tractors
 */
if (!defined('ABSPATH')) exit;

function gti_verify_nonce($action, $nonce = null) {
    $nonce = $nonce ?? ($_POST['nonce'] ?? $_GET['nonce'] ?? '');
    return wp_verify_nonce($nonce, $action);
}

function gti_create_nonce($action) {
    return wp_create_nonce($action);
}
