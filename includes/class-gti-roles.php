<?php
/**
 * GTI Roles & Capabilities
 *
 * @package Global_Tractors
 */

defined('ABSPATH') || exit;

class GTI_Roles {
    
    /**
     * Capability matrix — PRD §9.3. One source of truth; every role and the
     * WordPress administrator are reconciled against it.
     *
     * @return array role slug => [label, capabilities]
     */
    public static function capability_matrix() {
        return array(
            'gti_super_admin' => array(
                'label' => 'GTI Super Admin',
                'caps'  => array(
                    'gti_access', 'gti_manage_users', 'gti_manage_equipment',
                    'gti_manage_spare_parts', 'gti_manage_requests', 'gti_manage_quotations',
                    'gti_manage_customers', 'gti_manage_settings', 'gti_view_all_requests',
                    'gti_send_email', 'gti_upload_documents',
                ),
            ),
            'gti_admin' => array(
                'label' => 'GTI Admin',
                'caps'  => array(
                    'gti_access', 'gti_manage_equipment', 'gti_manage_spare_parts',
                    'gti_manage_requests', 'gti_manage_quotations', 'gti_manage_customers',
                    'gti_view_all_requests', 'gti_send_email', 'gti_upload_documents',
                ),
            ),
            // Sales deliberately lacks gti_view_all_requests: that absence is what
            // scopes the inbox to rows assigned to them (PRD §5.4).
            'gti_sales' => array(
                'label' => 'GTI Sales',
                'caps'  => array(
                    'gti_access', 'gti_manage_requests', 'gti_manage_quotations',
                    'gti_manage_customers', 'gti_send_email', 'gti_upload_documents',
                ),
            ),
            'gti_inventory' => array(
                'label' => 'GTI Inventory',
                'caps'  => array(
                    'gti_access', 'gti_manage_equipment', 'gti_manage_spare_parts',
                ),
            ),
        );
    }

    /**
     * Every capability this theme defines.
     */
    public static function all_caps() {
        $all = array();
        foreach (self::capability_matrix() as $role) {
            $all = array_merge($all, $role['caps']);
        }
        return array_values(array_unique($all));
    }

    /**
     * Create or reconcile the GTI roles.
     *
     * Safe to re-run: add_role() ignores a role that already exists, so an
     * existing role is brought up to date capability by capability instead.
     */
    public static function create_roles() {
        foreach (self::capability_matrix() as $slug => $spec) {
            $caps = array('read' => true);
            foreach ($spec['caps'] as $cap) {
                $caps[$cap] = true;
            }

            $role = get_role($slug);
            if (!$role) {
                add_role($slug, $spec['label'], $caps);
                continue;
            }

            // Grant what is missing and revoke what the matrix no longer allows.
            foreach ($caps as $cap => $granted) {
                if (!$role->has_cap($cap)) {
                    $role->add_cap($cap);
                }
            }
            foreach (self::all_caps() as $cap) {
                if (!isset($caps[$cap]) && $role->has_cap($cap)) {
                    $role->remove_cap($cap);
                }
            }
        }

        // The WordPress administrator keeps full access.
        $admin = get_role('administrator');
        if ($admin) {
            foreach (self::all_caps() as $cap) {
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
        return wp_list_pluck(self::capability_matrix(), 'label');
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
        
        $gti_roles = array_keys(self::get_roles());
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
