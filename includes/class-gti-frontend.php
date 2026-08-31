<?php
/**
 * GTI Frontend Handler
 * 
 * Handles /dashboard/ page rendering on frontend
 *
 * @package Global_Tractors
 */

defined('ABSPATH') || exit;

class GTI_Frontend {
    
    /**
     * Render the dashboard
     */
    public static function render_dashboard() {
        // Get statistics
        $db = GTI_Database::get_instance();
        $stats = $db->get_dashboard_stats();
        
        // Get recent activity
        global $wpdb;
        $recent_activity = $wpdb->get_results(
            "SELECT al.*, u.display_name as user_name 
             FROM {$wpdb->prefix}gti_activity_log al 
             LEFT JOIN {$wpdb->users} u ON al.user_id = u.ID 
             ORDER BY al.created_at DESC 
             LIMIT 10"
        );
        
        // Get latest equipment
        $latest_equipment = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}gti_equipment 
             WHERE deleted_at IS NULL 
             ORDER BY created_at DESC 
             LIMIT 5"
        );
        
        // Get latest requests
        $latest_requests = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}gti_requests 
             ORDER BY created_at DESC 
             LIMIT 5"
        );
        
        // Load the template
        include GTI_ADMIN_PATH . '/views/dashboard-frontend.php';
    }
}