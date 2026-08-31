<?php
/**
 * GTI Helper Functions
 *
 * @package Global_Tractors
 */

defined('ABSPATH') || exit;

class GTI_Helpers {
    
    /**
     * Format currency to Indonesian Rupiah
     */
    public static function format_currency($amount) {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
    
    /**
     * Format date to Indonesian format
     */
    public static function format_date($date, $format = 'd M Y') {
        if (empty($date)) return '-';
        return date($format, strtotime($date));
    }
    
    /**
     * Format datetime
     */
    public static function format_datetime($datetime) {
        if (empty($datetime)) return '-';
        return date('d M Y H:i', strtotime($datetime));
    }
    
    /**
     * Generate unique ID with prefix
     */
    public static function generate_id($prefix, $table, $column) {
        global $wpdb;
        
        $year = date('y');
        $month = date('m');
        $prefix_full = $prefix . '-' . $year . $month . '-';
        
        // Get last ID
        $last = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT {$column} FROM {$table} WHERE {$column} LIKE %s ORDER BY id DESC LIMIT 1",
                $prefix_full . '%'
            )
        );
        
        if ($last) {
            $number = intval(substr($last, -4)) + 1;
        } else {
            $number = 1;
        }
        
        return $prefix_full . str_pad($number, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Get equipment status badge HTML
     */
    public static function get_status_badge($status) {
        $badges = array(
            'available'      => '<span class="gti-badge gti-badge-success">Available</span>',
            'sold'           => '<span class="gti-badge gti-badge-danger">Sold</span>',
            'rented'         => '<span class="gti-badge gti-badge-info">Rented</span>',
            'reserved'       => '<span class="gti-badge gti-badge-warning">Reserved</span>',
            'maintenance'    => '<span class="gti-badge gti-badge-warning">Maintenance</span>',
            'in_stock'       => '<span class="gti-badge gti-badge-success">In Stock</span>',
            'low_stock'      => '<span class="gti-badge gti-badge-warning">Low Stock</span>',
            'out_of_stock'   => '<span class="gti-badge gti-badge-danger">Out of Stock</span>',
            'new'            => '<span class="gti-badge gti-badge-info">New</span>',
            'processing'     => '<span class="gti-badge gti-badge-warning">Processing</span>',
            'proposal_sent'  => '<span class="gti-badge gti-badge-info">Proposal Sent</span>',
            'waiting_customer' => '<span class="gti-badge gti-badge-warning">Waiting Customer</span>',
            'approved'       => '<span class="gti-badge gti-badge-success">Approved</span>',
            'rejected'       => '<span class="gti-badge gti-badge-danger">Rejected</span>',
            'completed'      => '<span class="gti-badge gti-badge-neutral">Completed</span>',
            'closed'         => '<span class="gti-badge gti-badge-neutral">Closed</span>',
            'active'         => '<span class="gti-badge gti-badge-success">Active</span>',
            'inactive'       => '<span class="gti-badge gti-badge-neutral">Inactive</span>',
            'unread'         => '<span class="gti-badge gti-badge-info">Unread</span>',
            'read'           => '<span class="gti-badge gti-badge-neutral">Read</span>',
        );
        
        return isset($badges[$status]) ? $badges[$status] : '<span class="gti-badge">' . ucfirst($status) . '</span>';
    }
    
    /**
     * Get condition badge HTML
     */
    public static function get_condition_badge($condition) {
        $badges = array(
            'excellent' => '<span class="gti-badge gti-badge-success">Excellent</span>',
            'good'      => '<span class="gti-badge gti-badge-info">Good</span>',
            'fair'      => '<span class="gti-badge gti-badge-warning">Fair</span>',
            'poor'      => '<span class="gti-badge gti-badge-danger">Poor</span>',
        );
        
        return isset($badges[$condition]) ? $badges[$condition] : '<span class="gti-badge">' . ucfirst($condition) . '</span>';
    }
    
    /**
     * Get image URL or placeholder
     */
    public static function get_image_url($image_id, $size = 'thumbnail') {
        if (empty($image_id)) {
            return GTI_URL . '/assets/images/placeholder.png';
        }
        
        // If it's a WordPress attachment ID
        if (is_numeric($image_id)) {
            $image = wp_get_attachment_image_url($image_id, $size);
            return $image ? $image : GTI_URL . '/assets/images/placeholder.png';
        }
        
        // If it's a URL
        return $image_id;
    }
    
    /**
     * Get user avatar or initials
     */
    public static function get_user_avatar($user_id, $name = '') {
        $avatar = get_avatar($user_id, 40);
        if ($avatar) {
            return $avatar;
        }
        
        // Generate initials
        $initials = '';
        if (!empty($name)) {
            $words = explode(' ', $name);
            foreach (array_slice($words, 0, 2) as $word) {
                $initials .= strtoupper($word[0]);
            }
        }
        
        return '<div class="gti-avatar-initials">' . $initials . '</div>';
    }
    
    /**
     * Sanitize input
     */
    public static function sanitize($data) {
        if (is_array($data)) {
            return array_map(array(__CLASS__, 'sanitize'), $data);
        }
        return sanitize_text_field($data);
    }
    
    /**
     * Get current user role
     */
    public static function get_current_user_role() {
        $user = wp_get_current_user();
        if ($user && !empty($user->roles)) {
            return $user->roles[0];
        }
        return '';
    }
    
    /**
     * Check if current user has GTI role
     */
    public static function is_gti_user() {
        $role = self::get_current_user_role();
        return in_array($role, array('gti_super_admin', 'gti_admin', 'gti_sales', 'gti_inventory'));
    }
    
    /**
     * Redirect to page
     */
    public static function redirect($page, $action = '', $id = 0) {
        $url = admin_url('admin.php?page=gti-' . $page);
        
        if ($action) {
            $url .= '&action=' . $action;
        }
        
        if ($id) {
            $url .= '&id=' . $id;
        }
        
        wp_redirect($url);
        exit;
    }
    
    /**
     * Show admin notice
     */
    public static function add_notice($message, $type = 'success') {
        add_action('admin_notices', function() use ($message, $type) {
            echo '<div class="notice notice-' . $type . ' is-dismissible"><p>' . esc_html($message) . '</p></div>';
        });
    }
}
