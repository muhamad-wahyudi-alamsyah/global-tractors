<?php
/**
 * GTI Admin Menu Registration
 *
 * @package Global_Tractors
 */

defined('ABSPATH') || exit;

class GTI_Admin_Menu {
    
    /**
     * Register admin menus
     */
    public static function register_menus() {
        // Enqueue admin assets
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_admin_assets'));
        
        // Main GTI Menu
        add_menu_page(
            'GTI Dashboard',           // Page title
            'GTI Admin',               // Menu title
            'gti_access',              // Capability
            'gti-dashboard',           // Menu slug
            array('GTI_Admin_Pages', 'render_dashboard'), // Callback
            'dashicons-building',      // Icon
            3                          // Position
        );
        
        // Dashboard (submenu of main)
        add_submenu_page(
            'gti-dashboard',           // Parent slug
            'Dashboard',               // Page title
            'Dashboard',               // Menu title
            'gti_access',              // Capability
            'gti-dashboard',           // Menu slug
            array('GTI_Admin_Pages', 'render_dashboard') // Callback
        );
        
        // Equipment Menu
        add_menu_page(
            'Equipment',               // Page title
            'Equipment',               // Menu title
            'gti_access',              // Capability
            'gti-equipment',           // Menu slug
            array('GTI_Admin_Pages', 'render_equipment_list'), // Callback
            'dashicons-car',           // Icon
            5                          // Position
        );
        
        // Used Equipment (submenu)
        add_submenu_page(
            'gti-equipment',
            'Used Equipment',
            'Used Equipment',
            'gti_access',
            'gti-equipment',
            array('GTI_Admin_Pages', 'render_equipment_list')
        );
        
        // Rental Equipment (submenu)
        add_submenu_page(
            'gti-equipment',
            'Rental Equipment',
            'Rental Equipment',
            'gti_access',
            'gti-rental',
            array('GTI_Admin_Pages', 'render_rental_list')
        );
        
        // Add Equipment (hidden submenu)
        add_submenu_page(
            null,                      // Hidden
            'Add Equipment',
            'Add Equipment',
            'gti_access',
            'gti-equipment-add',
            array('GTI_Admin_Pages', 'render_equipment_form')
        );
        
        // Edit Equipment (hidden submenu)
        add_submenu_page(
            null,
            'Edit Equipment',
            'Edit Equipment',
            'gti_access',
            'gti-equipment-edit',
            array('GTI_Admin_Pages', 'render_equipment_form')
        );
        
        // Spare Parts Menu
        add_menu_page(
            'Spare Parts',
            'Spare Parts',
            'gti_access',
            'gti-spare-parts',
            array('GTI_Admin_Pages', 'render_spare_parts_list'),
            'dashicons-plugins',
            6
        );
        
        // Request Equipment Menu
        add_menu_page(
            'Request Equipment',
            'Requests',
            'gti_access',
            'gti-requests',
            array('GTI_Admin_Pages', 'render_requests_list'),
            'dashicons-email-alt',
            7
        );
        
        // Request Quotation Menu
        add_menu_page(
            'Request Quotation',
            'Quotations',
            'gti_access',
            'gti-quotations',
            array('GTI_Admin_Pages', 'render_quotations_list'),
            'dashicons-money-alt',
            8
        );
        
        // Sell Equipment Menu
        add_menu_page(
            'Sell Equipment',
            'Sell Equipment',
            'gti_access',
            'gti-sell',
            array('GTI_Admin_Pages', 'render_sell_list'),
            'dashicons-cart',
            9
        );
        
        // Contact Messages Menu
        add_menu_page(
            'Contact Messages',
            'Messages',
            'gti_access',
            'gti-messages',
            array('GTI_Admin_Pages', 'render_messages_list'),
            'dashicons-email',
            10
        );
        
        // Customers Menu
        add_menu_page(
            'Customers',
            'Customers',
            'gti_access',
            'gti-customers',
            array('GTI_Admin_Pages', 'render_customers_list'),
            'dashicons-businessperson',
            11
        );
        
        // Users Menu (only for super admin)
        if (current_user_can('gti_manage_users')) {
            add_menu_page(
                'Users',
                'Users',
                'gti_manage_users',
                'gti-users',
                array('GTI_Admin_Pages', 'render_users_list'),
                'dashicons-admin-users',
                12
            );
        }
    }
    
    /**
     * Add custom capability to admin role
     */
    public static function add_capabilities() {
        // Add capabilities to existing roles
        $admin = get_role('administrator');
        if ($admin) {
            $admin->add_cap('gti_access');
            $admin->add_cap('gti_manage_users');
            $admin->add_cap('gti_manage_equipment');
            $admin->add_cap('gti_manage_spare_parts');
            $admin->add_cap('gti_manage_requests');
            $admin->add_cap('gti_manage_quotations');
            $admin->add_cap('gti_manage_customers');
        }
    }
    
    /**
     * Enqueue admin assets for GTI admin pages
     */
    public static function enqueue_admin_assets($hook) {
        // Only load on GTI admin pages
        if (strpos($hook, 'gti-') === false) {
            return;
        }
        
        // CSS
        wp_enqueue_style(
            'gti-admin',
            GTI_CHILD_URL . '/admin/css/gti-admin.css',
            array(),
            GTI_VERSION
        );
        
        wp_enqueue_style(
            'gti-components',
            GTI_CHILD_URL . '/admin/css/gti-components.css',
            array('gti-admin'),
            GTI_VERSION
        );
        
        // JS
        wp_enqueue_script(
            'gti-admin',
            GTI_CHILD_URL . '/admin/js/gti-admin.js',
            array('jquery'),
            GTI_VERSION,
            true
        );
        
        wp_localize_script('gti-admin', 'gtiAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('gti_nonce'),
        ));
    }
}
