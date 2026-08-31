<?php
/**
 * GTI Admin Pages
 *
 * @package Global_Tractors
 */

defined('ABSPATH') || exit;

class GTI_Admin_Pages {
    
    /**
     * Render Dashboard
     */
    public static function render_dashboard() {
        include GTI_ADMIN_PATH . '/views/dashboard.php';
    }
    
    /**
     * Render Equipment List
     */
    public static function render_equipment_list() {
        include GTI_ADMIN_PATH . '/views/equipment/list.php';
    }
    
    /**
     * Render Equipment Form (Add/Edit)
     */
    public static function render_equipment_form() {
        include GTI_ADMIN_PATH . '/views/equipment/form.php';
    }
    
    /**
     * Render Equipment Detail
     */
    public static function render_equipment_detail() {
        include GTI_ADMIN_PATH . '/views/equipment/detail.php';
    }
    
    /**
     * Render Rental List
     */
    public static function render_rental_list() {
        include GTI_ADMIN_PATH . '/views/equipment/rental-list.php';
    }
    
    /**
     * Render Spare Parts List
     */
    public static function render_spare_parts_list() {
        include GTI_ADMIN_PATH . '/views/spare-parts/list.php';
    }
    
    /**
     * Render Spare Parts Form
     */
    public static function render_spare_parts_form() {
        include GTI_ADMIN_PATH . '/views/spare-parts/form.php';
    }
    
    /**
     * Render Requests List
     */
    public static function render_requests_list() {
        include GTI_ADMIN_PATH . '/views/requests/list.php';
    }
    
    /**
     * Render Request Detail
     */
    public static function render_request_detail() {
        include GTI_ADMIN_PATH . '/views/requests/detail.php';
    }
    
    /**
     * Render Quotations List
     */
    public static function render_quotations_list() {
        include GTI_ADMIN_PATH . '/views/quotations/list.php';
    }
    
    /**
     * Render Quotation Detail
     */
    public static function render_quotation_detail() {
        include GTI_ADMIN_PATH . '/views/quotations/detail.php';
    }
    
    /**
     * Render Customers List
     */
    public static function render_customers_list() {
        include GTI_ADMIN_PATH . '/views/customers/list.php';
    }
    
    /**
     * Render Customer Detail
     */
    public static function render_customer_detail() {
        include GTI_ADMIN_PATH . '/views/customers/detail.php';
    }
    
    /**
     * Render Users List
     */
    public static function render_users_list() {
        include GTI_ADMIN_PATH . '/views/users/list.php';
    }
    
    /**
     * Render User Form
     */
    public static function render_user_form() {
        include GTI_ADMIN_PATH . '/views/users/form.php';
    }
    
    /**
     * Render Sell List
     */
    public static function render_sell_list() {
        include GTI_ADMIN_PATH . '/views/sell-equipment/list.php';
    }
    
    /**
     * Render Sell Detail
     */
    public static function render_sell_detail() {
        include GTI_ADMIN_PATH . '/views/sell-equipment/detail.php';
    }
    
    /**
     * Render Messages List
     */
    public static function render_messages_list() {
        include GTI_ADMIN_PATH . '/views/messages/list.php';
    }
}
