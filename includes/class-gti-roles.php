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
                    // Core WordPress caps the dashboard actually gates on: without
                    // these the Users, News and Media Library pages are invisible
                    // or answer 403, however many gti_* caps the role carries.
                    'list_users', 'create_users', 'edit_users', 'delete_users', 'promote_users',
                    'upload_files', 'edit_posts', 'publish_posts', 'edit_others_posts',
                    'delete_posts', 'edit_published_posts', 'delete_published_posts',
                ),
            ),
            'gti_admin' => array(
                'label' => 'GTI Admin',
                'caps'  => array(
                    'gti_access', 'gti_manage_equipment', 'gti_manage_spare_parts',
                    'gti_manage_requests', 'gti_manage_quotations', 'gti_manage_customers',
                    'gti_view_all_requests', 'gti_send_email', 'gti_upload_documents',
                    'upload_files', 'edit_posts', 'publish_posts', 'edit_others_posts',
                    'edit_published_posts', 'delete_posts', 'delete_published_posts',
                ),
            ),
            // Sales deliberately lacks gti_view_all_requests: that absence is what
            // scopes the inbox to rows assigned to them (PRD §5.4).
            'gti_sales' => array(
                'label' => 'GTI Sales',
                'caps'  => array(
                    'gti_access', 'gti_manage_requests', 'gti_manage_quotations',
                    'gti_manage_customers', 'gti_send_email', 'gti_upload_documents',
                    'upload_files',
                ),
            ),
            'gti_inventory' => array(
                'label' => 'GTI Inventory',
                'caps'  => array(
                    'gti_access', 'gti_manage_equipment', 'gti_manage_spare_parts',
                    'upload_files',
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
     * Keep the user-management caps granted above pointed at gti_* accounts only.
     *
     * create_users/delete_users/edit_users are global in WordPress: granting them
     * to gti_super_admin would otherwise let a GTI account edit or delete the
     * site administrator. Both filters are skipped for real administrators.
     */
    public static function register_guards() {
        add_filter('editable_roles', array(__CLASS__, 'filter_editable_roles'));
        add_filter('map_meta_cap', array(__CLASS__, 'filter_map_meta_cap'), 10, 4);
    }

    public static function filter_editable_roles($roles) {
        if (current_user_can('manage_options')) {
            return $roles;
        }
        if (!self::has_gti_role()) {
            return $roles;
        }
        return array_intersect_key($roles, self::capability_matrix());
    }

    public static function filter_map_meta_cap($caps, $cap, $user_id, $args) {
        if (!in_array($cap, array('edit_user', 'delete_user', 'promote_user'), true)) {
            return $caps;
        }
        if (user_can($user_id, 'manage_options') || !self::has_gti_role($user_id)) {
            return $caps;
        }

        $target_id = isset($args[0]) ? (int) $args[0] : 0;
        if (!$target_id || $target_id === (int) $user_id) {
            return $caps;
        }

        $target = get_userdata($target_id);
        if (!$target || !array_intersect(array_keys(self::capability_matrix()), $target->roles)) {
            return array('do_not_allow');
        }

        return $caps;
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
