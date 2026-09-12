<?php
/**
 * GTI AJAX Handlers
 *
 * @package Global_Tractors
 */

defined('ABSPATH') || exit;

class GTI_Ajax {

    use GTI_Ajax_Equipment;
    use GTI_Ajax_Spare_Parts;
    use GTI_Ajax_Inbox;
    use GTI_Ajax_Customers;
    use GTI_Ajax_Users;
    use GTI_Ajax_Dashboard;
    
    /**
     * Initialize AJAX hooks
     */
    public static function init() {
        // Equipment AJAX
        add_action('wp_ajax_gti_save_equipment', array(__CLASS__, 'save_equipment'));
        add_action('wp_ajax_gti_delete_equipment', array(__CLASS__, 'delete_equipment'));
        add_action('wp_ajax_gti_publish_equipment', array(__CLASS__, 'publish_equipment'));
        add_action('wp_ajax_gti_get_equipment', array(__CLASS__, 'get_equipment'));
        add_action('wp_ajax_gti_get_next_code', array(__CLASS__, 'get_next_code'));
        
        // Spare Parts AJAX
        add_action('wp_ajax_gti_save_spare_part', array(__CLASS__, 'save_spare_part'));
        add_action('wp_ajax_gti_delete_spare_part', array(__CLASS__, 'delete_spare_part'));
        add_action('wp_ajax_gti_publish_spare_part', array(__CLASS__, 'publish_spare_part'));
        add_action('wp_ajax_gti_get_next_spare_part_code', array(__CLASS__, 'get_next_spare_part_code'));
        
        // Requests AJAX
        add_action('wp_ajax_gti_update_request_status', array(__CLASS__, 'update_request_status'));
        add_action('wp_ajax_gti_delete_request', array(__CLASS__, 'delete_request'));
        
        // Quotations AJAX
        add_action('wp_ajax_gti_save_quotation', array(__CLASS__, 'save_quotation'));
        add_action('wp_ajax_gti_update_quotation_status', array(__CLASS__, 'update_quotation_status'));
        add_action('wp_ajax_gti_delete_quotation', array(__CLASS__, 'delete_quotation'));

        // Sell Equipment AJAX
        add_action('wp_ajax_gti_get_sell_request_detail', array(__CLASS__, 'get_sell_request_detail'));
        add_action('wp_ajax_gti_update_sell_request_status', array(__CLASS__, 'update_sell_request_status'));
        add_action('wp_ajax_gti_delete_sell_request', array(__CLASS__, 'delete_sell_request'));
        
        // Customers AJAX
        add_action('wp_ajax_gti_save_customer', array(__CLASS__, 'save_customer'));
        
        // Users AJAX
        add_action('wp_ajax_gti_save_user', array(__CLASS__, 'save_user'));
        add_action('wp_ajax_gti_delete_user', array(__CLASS__, 'delete_user'));
        
        // Dashboard AJAX
        add_action('wp_ajax_gti_get_dashboard_stats', array(__CLASS__, 'get_dashboard_stats'));
    }
    
    /**
     * Verify nonce for AJAX
     */
private static function verify_nonce() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'gti_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed', 'code' => 'bad_nonce'), 403);
            exit;
        }
    }

    /**
     * Capability gate — PRD §9.4 layer 3.
     *
     * Most handlers here only verified the nonce, which any logged-in user can
     * obtain from any dashboard page. That let an inventory user delete a
     * quotation, for example.
     */
    private static function require_cap($capability) {
        if (!current_user_can($capability)) {
            wp_send_json_error(
                array('message' => 'You do not have permission to do that.', 'code' => 'forbidden'),
                403
            );
            exit;
        }
    }
    
    
        
        
    
    
    
        
    
    
    
        
    
    
    
        
        
        
        
        
        
        
        
    /**
     * Log activity
     */
    private static function log_activity($action, $entity_type, $entity_id, $details = array()) {
        // Routed through the shared logger so these rows also carry a readable
        // description and an IP address, which /dashboard/activity-log renders.
        if (function_exists('gti_log_activity')) {
            gti_log_activity(
                get_current_user_id(),
                $action,
                gti_activity_build_description($action, $entity_type, $entity_id, $details),
                $entity_type,
                $entity_id,
                $details
            );
            return;
        }

        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'gti_activity_log',
            array(
                'user_id'     => get_current_user_id(),
                'action'      => $action,
                'entity_type' => $entity_type,
                'entity_id'   => $entity_id,
                'details'     => wp_json_encode($details),
            )
        );
    }
}

/**
 * Surface wp_mail() failures.
 *
 * This used to append to wp-content/uploads/gti-mail-debug.log on every failure
 * — an unbounded file in a publicly served directory, the same problem as A-05
 * and A-07. Failures are now recorded per message in wp_gti_email_logs by
 * GTI_Mailer, and this only adds a line to the normal debug log.
 */
if (!function_exists('gti_capture_mail_error')) {
    function gti_capture_mail_error($result) {
        if (!$result || ($result instanceof \WP_Error)) {
            gti_log('wp_mail failed', is_wp_error($result) ? $result->get_error_message() : 'wp_mail() returned false');
        }
        return $result;
    }
    add_filter('wp_mail_failed', 'gti_capture_mail_error', 10, 1);
}

// Handlers are registered from inc/bootstrap.php on `init`.
