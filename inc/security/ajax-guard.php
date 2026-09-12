<?php
/**
 * Uniform AJAX + page guards.
 *
 * PRD §9.4 requires capability to be enforced in three layers: sidebar menu,
 * page template, and AJAX handler. This file holds the last two so every
 * handler runs the same nonce → capability → (optional) row-scope sequence
 * instead of each one re-implementing it.
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'gti_ajax_guard' ) ) {
    /**
     * Gate an admin AJAX handler. Sends a JSON error and exits on failure.
     *
     * @param string|string[] $capability One cap, or a list where any one suffices.
     */
    function gti_ajax_guard( $capability = 'read' ) {
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => 'You must be logged in.', 'code' => 'not_logged_in' ), 401 );
        }
        if ( ! check_ajax_referer( 'gti_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed. Please reload the page.', 'code' => 'bad_nonce' ), 403 );
        }
        if ( $capability && ! gti_user_can_any( $capability ) ) {
            wp_send_json_error( array( 'message' => 'You do not have permission to do that.', 'code' => 'forbidden' ), 403 );
        }
    }
}

if ( ! function_exists( 'gti_user_can_any' ) ) {
    /**
     * True when the current user holds at least one of the given capabilities.
     *
     * @param string|string[] $capabilities
     */
    function gti_user_can_any( $capabilities ) {
        foreach ( (array) $capabilities as $cap ) {
            if ( current_user_can( $cap ) ) {
                return true;
            }
        }
        return false;
    }
}

if ( ! function_exists( 'gti_require_cap' ) ) {
    /**
     * Page-level guard (PRD §9.4 layer 2). Redirects to the dashboard root
     * rather than wp_die() so a user who lands on a page their role cannot see
     * gets somewhere useful instead of a dead end.
     *
     * @param string|string[] $capabilities
     */
    function gti_require_cap( $capabilities ) {
        gti_require_login();
        if ( ! gti_user_can_any( $capabilities ) ) {
            wp_safe_redirect( gti_dashboard_url() );
            exit;
        }
    }
}

if ( ! function_exists( 'gti_send_json_error' ) ) {
    /**
     * Standard error shape from PRD §8.3: always a message, plus a stable code
     * the client can branch on.
     */
    function gti_send_json_error( $message, $code = 'error', $status = null ) {
        wp_send_json_error( array( 'message' => $message, 'code' => $code ), $status );
    }
}
