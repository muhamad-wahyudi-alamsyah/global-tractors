<?php
/**
 * GTI Roles & Capabilities
 *
 * @package Global_Tractors
 */

defined('ABSPATH') || exit;

class GTI_Roles {
    
    /**
     * Create custom roles
     */
    public static function create_roles() {
        // Super Admin - Full access
        add_role(
            'gti_super_admin',
            'GTI Super Admin',
            array(
                'gti_access'            => true,
                'gti_manage_users'       => true,
                'gti_manage_equipment'   => true,
                'gti_manage_spare_parts' => true,
                'gti_manage_requests'    => true,
                'gti_manage_quotations'  => true,
                'gti_manage_customers'   => true,
                'gti_manage_settings'    => true,
                'read'                   => true,
            )
        );
        
        // Admin - General admin access
        add_role(
            'gti_admin',
            'GTI Admin',
            array(
                'gti_access'            => true,
                'gti_manage_equipment'   => true,
                'gti_manage_spare_parts' => true,
                'gti_manage_requests'    => true,
                'gti_manage_quotations'  => true,
                'gti_manage_customers'   => true,
                'read'                   => true,
            )
        );
        
        // Sales - Quotation & customer focus
        add_role(
            'gti_sales',
            'GTI Sales',
            array(
                'gti_access'            => true,
                'gti_manage_requests'    => true,
                'gti_manage_quotations'  => true,
                'gti_manage_customers'   => true,
                'read'                   => true,
            )
        );
        
        // Inventory - Equipment & spare parts focus
        add_role(
            'gti_inventory',
            'GTI Inventory',
            array(
                'gti_access'            => true,
                'gti_manage_equipment'   => true,
                'gti_manage_spare_parts' => true,
                'read'                   => true,
            )
        );
        
        // Add capabilities to administrator
        $admin = get_role('administrator');
        if ($admin) {
            $caps = array(
                'gti_access',
                'gti_manage_users',
                'gti_manage_equipment',
                'gti_manage_spare_parts',
                'gti_manage_requests',
                'gti_manage_quotations',
                'gti_manage_customers',
                'gti_manage_settings',
            );
            
            foreach ($caps as $cap) {
                $admin->add_cap($cap);
            }
        }
    }
    
    /**
     * Remove custom roles
     */
    public static function remove_roles() {
        remove_role('gti_super_admin');
        remove_role('gti_admin');
        remove_role('gti_sales');
        remove_role('gti_inventory');
    }
    
    /**
     * Get all GTI roles
     */
    public static function get_roles() {
        return array(
            'gti_super_admin' => 'GTI Super Admin',
            'gti_admin'       => 'GTI Admin',
            'gti_sales'       => 'GTI Sales',
            'gti_inventory'   => 'GTI Inventory',
        );
    }
    
    /**
     * Check if user has GTI role
     */
    public static function has_gti_role($user_id = 0) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }
        
        $gti_roles = self::get_roles();
        return !empty(array_intersect($gti_roles, $user->roles));
    }
    
    /**
     * Get user's primary GTI role
     */
    public static function get_user_role($user_id = 0) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $user = get_userdata($user_id);
        if (!$user) {
            return '';
        }
        
        $gti_roles = self::get_roles();
        foreach ($user->roles as $role) {
            if (isset($gti_roles[$role])) {
                return $role;
            }
        }
        
        return '';
    }
}
