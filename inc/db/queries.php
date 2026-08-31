<?php
/**
 * Database queries
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Get dashboard statistics.
 *
 * @return array
 */
function gti_get_dashboard_stats() {
    global $wpdb;
    
    $stats = [
        'total_users'     => 0,
        'total_orders'    => 0,
        'total_revenue'   => 0,
        'recent_activity' => [],
    ];
    
    // Total users (customers only)
    $stats['total_users'] = (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->users} u
         INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
         WHERE um.meta_key = '{$wpdb->prefix}capabilities'
         AND um.meta_value LIKE '%gti_customer%'"
    );
    
    // Total orders (from WooCommerce if exists)
    if ( class_exists( 'WooCommerce' ) ) {
        $stats['total_orders'] = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}woocommerce_order_posts
             WHERE post_status IN ('wc-completed', 'wc-processing')"
        );
        
        $stats['total_revenue'] = (float) $wpdb->get_var(
            "SELECT SUM(meta_value) FROM {$wpdb->prefix}woocommerce_order_itemmeta oi
             INNER JOIN {$wpdb->prefix}woocommerce_order_items oit ON oi.order_item_id = oit.order_item_id
             INNER JOIN {$wpdb->prefix}woocommerce_order_posts op ON oit.order_id = op.ID
             WHERE oi.meta_key = '_line_total'
             AND op.post_status IN ('wc-completed', 'wc-processing')"
        );
    }
    
    // Recent activity
    $table_name = gti_table( 'activity_log' );
    $stats['recent_activity'] = $wpdb->get_results(
        "SELECT al.*, u.display_name as user_name
         FROM {$table_name} al
         LEFT JOIN {$wpdb->users} u ON al.user_id = u.ID
         ORDER BY al.created_at DESC
         LIMIT 10"
    );
    
    return $stats;
}

/**
 * Log activity.
 *
 * @param int    $user_id    User ID.
 * @param string $action     Action name.
 * @param string $description Description.
 */
function gti_log_activity( $user_id, $action, $description = '' ) {
    global $wpdb;
    
    $table_name = gti_table( 'activity_log' );
    
    $wpdb->insert(
        $table_name,
        [
            'user_id'     => $user_id,
            'action'      => $action,
            'description' => $description,
            'ip_address'  => gti_get_client_ip(),
        ],
        [ '%d', '%s', '%s', '%s' ]
    );
}
