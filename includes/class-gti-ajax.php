<?php
/**
 * GTI AJAX Handlers
 *
 * @package Global_Tractors
 */

defined('ABSPATH') || exit;

class GTI_Ajax {
    
    /**
     * Initialize AJAX hooks
     */
    public static function init() {
        // Equipment AJAX
        add_action('wp_ajax_gti_save_equipment', array(__CLASS__, 'save_equipment'));
        add_action('wp_ajax_gti_delete_equipment', array(__CLASS__, 'delete_equipment'));
        add_action('wp_ajax_gti_get_equipment', array(__CLASS__, 'get_equipment'));
        
        // Spare Parts AJAX
        add_action('wp_ajax_gti_save_spare_part', array(__CLASS__, 'save_spare_part'));
        add_action('wp_ajax_gti_delete_spare_part', array(__CLASS__, 'delete_spare_part'));
        
        // Requests AJAX
        add_action('wp_ajax_gti_update_request_status', array(__CLASS__, 'update_request_status'));
        
        // Quotations AJAX
        add_action('wp_ajax_gti_save_quotation', array(__CLASS__, 'save_quotation'));
        add_action('wp_ajax_gti_update_quotation_status', array(__CLASS__, 'update_quotation_status'));
        
        // Sell Equipment AJAX
        add_action('wp_ajax_gti_get_sell_request_detail', array(__CLASS__, 'get_sell_request_detail'));
        add_action('wp_ajax_gti_update_sell_request_status', array(__CLASS__, 'update_sell_request_status'));
        
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
        if (!wp_verify_nonce($_POST['nonce'], 'gti_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            exit;
        }
    }
    
    /**
     * Save Equipment
     */
    public static function save_equipment() {
        self::verify_nonce();
        
        global $wpdb;
        $table = $wpdb->prefix . 'gti_equipment';
        
        $id = intval($_POST['id'] ?? 0);
        $data = array(
            'equipment_code'    => sanitize_text_field($_POST['equipment_code']),
            'name'              => sanitize_text_field($_POST['name']),
            'type'              => sanitize_text_field($_POST['type']),
            'category'          => sanitize_text_field($_POST['category']),
            'brand'             => sanitize_text_field($_POST['brand']),
            'model'             => sanitize_text_field($_POST['model']),
            'year'              => intval($_POST['year']),
            'condition_status'  => sanitize_text_field($_POST['condition_status']),
            'status'            => sanitize_text_field($_POST['status']),
            'price'             => floatval($_POST['price']),
            'price_type'        => sanitize_text_field($_POST['price_type']),
            'location'          => sanitize_text_field($_POST['location']),
            'description'       => wp_kses_post($_POST['description']),
            'hours'             => intval($_POST['hours'] ?? 0),
            'negotiable'        => intval($_POST['negotiable'] ?? 0),
            'notes'             => wp_kses_post($_POST['notes'] ?? ''),
            'created_by'        => get_current_user_id(),
        );
        
        if ($id > 0) {
            // Update
            $result = $wpdb->update($table, $data, array('id' => $id));
        } else {
            // Insert
            $result = $wpdb->insert($table, $data);
            $id = $wpdb->insert_id;
        }
        
        if ($result !== false) {
            // Log activity
            self::log_activity($id > 0 ? 'update' : 'create', 'equipment', $id);
            
            wp_send_json_success(array(
                'message' => 'Equipment saved successfully',
                'id' => $id,
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to save equipment'));
        }
        
        exit;
    }
    
    /**
     * Delete Equipment
     */
    public static function delete_equipment() {
        self::verify_nonce();
        
        global $wpdb;
        $table = $wpdb->prefix . 'gti_equipment';
        $id = intval($_POST['id']);
        
        // Soft delete
        $result = $wpdb->update(
            $table,
            array('deleted_at' => current_time('mysql')),
            array('id' => $id)
        );
        
        if ($result !== false) {
            self::log_activity('delete', 'equipment', $id);
            wp_send_json_success(array('message' => 'Equipment deleted'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete equipment'));
        }
        
        exit;
    }
    
    /**
     * Get Equipment by ID
     */
    public static function get_equipment() {
        self::verify_nonce();
        
        global $wpdb;
        $table = $wpdb->prefix . 'gti_equipment';
        $id = intval($_POST['id']);
        
        $equipment = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d AND deleted_at IS NULL", $id)
        );
        
        if ($equipment) {
            wp_send_json_success($equipment);
        } else {
            wp_send_json_error(array('message' => 'Equipment not found'));
        }
        
        exit;
    }
    
    /**
     * Save Spare Part
     */
    public static function save_spare_part() {
        self::verify_nonce();
        
        global $wpdb;
        $table = $wpdb->prefix . 'gti_spare_parts';
        
        $id = intval($_POST['id'] ?? 0);
        $stock = intval($_POST['stock']);
        $minimum_stock = intval($_POST['minimum_stock'] ?? 10);
        
        // Determine status based on stock
        if ($stock == 0) {
            $status = 'out_of_stock';
        } elseif ($stock <= $minimum_stock) {
            $status = 'low_stock';
        } else {
            $status = 'in_stock';
        }
        
        $data = array(
            'part_number'   => sanitize_text_field($_POST['part_number']),
            'name'          => sanitize_text_field($_POST['name']),
            'category'      => sanitize_text_field($_POST['category']),
            'brand'         => sanitize_text_field($_POST['brand']),
            'description'   => wp_kses_post($_POST['description']),
            'stock'         => $stock,
            'minimum_stock' => $minimum_stock,
            'unit_price'    => floatval($_POST['unit_price']),
            'supplier'      => sanitize_text_field($_POST['supplier']),
            'location'      => sanitize_text_field($_POST['location']),
            'status'        => $status,
        );
        
        if ($id > 0) {
            $result = $wpdb->update($table, $data, array('id' => $id));
        } else {
            $result = $wpdb->insert($table, $data);
            $id = $wpdb->insert_id;
        }
        
        if ($result !== false) {
            self::log_activity($id > 0 ? 'update' : 'create', 'spare_part', $id);
            wp_send_json_success(array('message' => 'Spare part saved', 'id' => $id));
        } else {
            wp_send_json_error(array('message' => 'Failed to save spare part'));
        }
        
        exit;
    }
    
    /**
     * Delete Spare Part
     */
    public static function delete_spare_part() {
        self::verify_nonce();
        
        global $wpdb;
        $table = $wpdb->prefix . 'gti_spare_parts';
        $id = intval($_POST['id']);
        
        $result = $wpdb->delete($table, array('id' => $id));
        
        if ($result !== false) {
            self::log_activity('delete', 'spare_part', $id);
            wp_send_json_success(array('message' => 'Spare part deleted'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete'));
        }
        
        exit;
    }
    
    /**
     * Update Request Status
     */
    public static function update_request_status() {
        self::verify_nonce();
        
        global $wpdb;
        $table = $wpdb->prefix . 'gti_requests';
        
        $id = intval($_POST['id']);
        $status = sanitize_text_field($_POST['status']);
        
        $result = $wpdb->update(
            $table,
            array('status' => $status),
            array('id' => $id)
        );
        
        if ($result !== false) {
            self::log_activity('status_change', 'request', $id, array('new_status' => $status));
            wp_send_json_success(array('message' => 'Status updated'));
        } else {
            wp_send_json_error(array('message' => 'Failed to update status'));
        }
        
        exit;
    }
    
    /**
     * Save Quotation
     */
    public static function save_quotation() {
        self::verify_nonce();
        
        global $wpdb;
        $table = $wpdb->prefix . 'gti_quotations';
        
        $id = intval($_POST['id'] ?? 0);
        
        // Calculate totals
        $subtotal = floatval($_POST['subtotal']);
        $discount = floatval($_POST['discount']);
        $discount_type = sanitize_text_field($_POST['discount_type'] ?? 'amount');
        $tax_rate = floatval($_POST['tax_rate'] ?? 11);
        
        if ($discount_type === 'percentage') {
            $discount_amount = $subtotal * ($discount / 100);
        } else {
            $discount_amount = $discount;
        }
        
        $tax_amount = ($subtotal - $discount_amount) * ($tax_rate / 100);
        $total = $subtotal - $discount_amount + $tax_amount;
        
        $data = array(
            'quotation_id'      => sanitize_text_field($_POST['quotation_id']),
            'customer_name'     => sanitize_text_field($_POST['customer_name']),
            'customer_company'  => sanitize_text_field($_POST['customer_company']),
            'customer_email'    => sanitize_email($_POST['customer_email']),
            'customer_phone'    => sanitize_text_field($_POST['customer_phone']),
            'customer_address'  => wp_kses_post($_POST['customer_address']),
            'items'             => wp_json_encode($_POST['items']),
            'subtotal'          => $subtotal,
            'discount'          => $discount,
            'discount_type'     => $discount_type,
            'tax_rate'          => $tax_rate,
            'tax_amount'        => $tax_amount,
            'total'             => $total,
            'sales_pic'         => sanitize_text_field($_POST['sales_pic']),
            'valid_until'       => sanitize_text_field($_POST['valid_until']),
            'delivery_location' => sanitize_text_field($_POST['delivery_location']),
            'additional_notes'  => wp_kses_post($_POST['additional_notes']),
            'status'            => sanitize_text_field($_POST['status']),
            'request_date'      => sanitize_text_field($_POST['request_date']),
        );
        
        if ($id > 0) {
            $result = $wpdb->update($table, $data, array('id' => $id));
        } else {
            $result = $wpdb->insert($table, $data);
            $id = $wpdb->insert_id;
        }
        
        if ($result !== false) {
            self::log_activity($id > 0 ? 'update' : 'create', 'quotation', $id);
            wp_send_json_success(array('message' => 'Quotation saved', 'id' => $id));
        } else {
            wp_send_json_error(array('message' => 'Failed to save quotation'));
        }
        
        exit;
    }
    
    /**
     * Update Quotation Status
     */
    public static function update_quotation_status() {
        self::verify_nonce();
        
        global $wpdb;
        $table = $wpdb->prefix . 'gti_quotations';
        
        $id = intval($_POST['id']);
        $status = sanitize_text_field($_POST['status']);
        
        $result = $wpdb->update(
            $table,
            array('status' => $status),
            array('id' => $id)
        );
        
        if ($result !== false) {
            self::log_activity('status_change', 'quotation', $id, array('new_status' => $status));
            wp_send_json_success(array('message' => 'Status updated'));
        } else {
            wp_send_json_error(array('message' => 'Failed to update status'));
        }
        
        exit;
    }
    
    /**
     * Get Sell Request Detail (for right drawer)
     */
    public static function get_sell_request_detail() {
        self::verify_nonce();
        
        global $wpdb;
        $table = $wpdb->prefix . 'gti_sell_requests';
        $id = intval($_POST['id']);
        
        $request = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id)
        );
        
        if ($request) {
            // Parse images JSON
            $request->images_array = json_decode($request->images, true) ?: array();
            
            wp_send_json_success($request);
        } else {
            wp_send_json_error(array('message' => 'Sell request not found'));
        }
        
        exit;
    }
    
    /**
     * Update Sell Request Status
     */
    public static function update_sell_request_status() {
        self::verify_nonce();
        
        global $wpdb;
        $table = $wpdb->prefix . 'gti_sell_requests';
        
        $id = intval($_POST['id']);
        $status = sanitize_text_field($_POST['status']);
        
        $result = $wpdb->update(
            $table,
            array('status' => $status),
            array('id' => $id)
        );
        
        if ($result !== false) {
            self::log_activity('status_change', 'sell_request', $id, array('new_status' => $status));
            wp_send_json_success(array('message' => 'Status updated'));
        } else {
            wp_send_json_error(array('message' => 'Failed to update status'));
        }
        
        exit;
    }
    
    /**
     * Save Customer
     */
    public static function save_customer() {
        self::verify_nonce();
        
        global $wpdb;
        $table = $wpdb->prefix . 'gti_customers';
        
        $id = intval($_POST['id'] ?? 0);
        $data = array(
            'customer_id'   => sanitize_text_field($_POST['customer_id']),
            'name'          => sanitize_text_field($_POST['name']),
            'company'       => sanitize_text_field($_POST['company']),
            'email'         => sanitize_email($_POST['email']),
            'phone'         => sanitize_text_field($_POST['phone']),
            'address'       => wp_kses_post($_POST['address']),
            'industry'      => sanitize_text_field($_POST['industry']),
            'location'      => sanitize_text_field($_POST['location']),
            'status'        => sanitize_text_field($_POST['status']),
            'registered_date' => sanitize_text_field($_POST['registered_date']),
        );
        
        if ($id > 0) {
            $result = $wpdb->update($table, $data, array('id' => $id));
        } else {
            $result = $wpdb->insert($table, $data);
            $id = $wpdb->insert_id;
        }
        
        if ($result !== false) {
            self::log_activity($id > 0 ? 'update' : 'create', 'customer', $id);
            wp_send_json_success(array('message' => 'Customer saved', 'id' => $id));
        } else {
            wp_send_json_error(array('message' => 'Failed to save customer'));
        }
        
        exit;
    }
    
    /**
     * Save User
     */
    public static function save_user() {
        self::verify_nonce();
        
        $user_id = intval($_POST['user_id'] ?? 0);
        $userdata = array(
            'user_login'   => sanitize_user($_POST['user_login']),
            'user_email'   => sanitize_email($_POST['user_email']),
            'first_name'   => sanitize_text_field($_POST['first_name']),
            'last_name'    => sanitize_text_field($_POST['last_name']),
            'role'         => sanitize_text_field($_POST['role']),
        );
        
        // Password only on create or if provided
        if (!empty($_POST['user_pass'])) {
            $userdata['user_pass'] = $_POST['user_pass'];
        }
        
        if ($user_id > 0) {
            $result = wp_update_user($userdata);
        } else {
            if (empty($userdata['user_pass'])) {
                wp_send_json_error(array('message' => 'Password is required for new users'));
                exit;
            }
            $result = wp_insert_user($userdata);
        }
        
        if (!is_wp_error($result)) {
            // Update meta
            update_user_meta($result, 'phone', sanitize_text_field($_POST['phone']));
            update_user_meta($result, 'department', sanitize_text_field($_POST['department']));
            update_user_meta($result, 'profile_photo', intval($_POST['profile_photo'] ?? 0));
            
            self::log_activity($user_id > 0 ? 'update' : 'create', 'user', $result);
            wp_send_json_success(array('message' => 'User saved', 'id' => $result));
        } else {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        exit;
    }
    
    /**
     * Delete User
     */
    public static function delete_user() {
        self::verify_nonce();
        
        $user_id = intval($_POST['id']);
        
        // Don't allow deleting yourself
        if ($user_id == get_current_user_id()) {
            wp_send_json_error(array('message' => 'Cannot delete your own account'));
            exit;
        }
        
        $result = wp_delete_user($user_id);
        
        if ($result) {
            self::log_activity('delete', 'user', $user_id);
            wp_send_json_success(array('message' => 'User deleted'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete user'));
        }
        
        exit;
    }
    
    /**
     * Get Dashboard Statistics
     */
    public static function get_dashboard_stats() {
        self::verify_nonce();
        
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
    
    /**
     * Log activity
     */
    private static function log_activity($action, $entity_type, $entity_id, $details = array()) {
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

// Initialize AJAX handlers
GTI_Ajax::init();
