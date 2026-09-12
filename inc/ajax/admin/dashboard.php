<?php
/**
 * Dashboard AJAX handlers
 *
 * Dashboard figures.
 *
 * Split out of includes/class-gti-ajax.php, which had grown to 1,279 lines
 * covering eight unrelated domains (PRD §13.3 R-04). These are traits rather
 * than new classes so the handlers keep their GTI_Ajax::method() identity and
 * every existing add_action() registration keeps working unchanged.
 *
 * @package global-tractors
 */

defined('ABSPATH') || exit;

trait GTI_Ajax_Dashboard {

/**
     * Get Dashboard Statistics
     */
    public static function get_dashboard_stats() {
        self::verify_nonce();
        self::require_cap('gti_access');
        
        global $wpdb;
        
        $stats = array(
            'equipment_used'    => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}gti_equipment WHERE type = 'used' AND deleted_at IS NULL"),
            'equipment_rental'  => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}gti_equipment WHERE type = 'rental' AND deleted_at IS NULL"),
            'spare_parts'       => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}gti_spare_parts"),
            'requests'          => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}gti_requests"),
            'quotations'        => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}gti_quotations"),
            'customers'         => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}gti_customers"),
            'sell_requests'     => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}gti_sell_requests"),
            'messages'          => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}gti_messages WHERE status = 'unread'"),
        );
        
        // Recent activity
        $stats['recent_activity'] = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}gti_activity_log ORDER BY created_at DESC LIMIT 10"
        );
        
        wp_send_json_success($stats);
        exit;
    }

}
