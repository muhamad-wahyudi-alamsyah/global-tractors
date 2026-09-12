<?php
/**
 * Users AJAX handlers
 *
 * Team accounts.
 *
 * Split out of includes/class-gti-ajax.php, which had grown to 1,279 lines
 * covering eight unrelated domains (PRD §13.3 R-04). These are traits rather
 * than new classes so the handlers keep their GTI_Ajax::method() identity and
 * every existing add_action() registration keeps working unchanged.
 *
 * @package global-tractors
 */

defined('ABSPATH') || exit;

trait GTI_Ajax_Users {

/**
     * Save User
     */
    public static function save_user() {
        self::verify_nonce();

        $user_id = intval($_POST['user_id'] ?? 0);

        $required_cap = $user_id > 0 ? 'edit_users' : 'create_users';
        if (!current_user_can($required_cap)) {
            wp_send_json_error(array('message' => 'You do not have permission to manage users.'));
            exit;
        }

        // Only roles this user is allowed to hand out.
        $role = sanitize_text_field($_POST['role'] ?? '');
        if ($role && !array_key_exists($role, get_editable_roles())) {
            wp_send_json_error(array('message' => 'That role is not available.'));
            exit;
        }

        $userdata = array(
            'user_login'   => sanitize_user($_POST['user_login'] ?? ''),
            'user_email'   => sanitize_email($_POST['user_email'] ?? ''),
            'first_name'   => sanitize_text_field($_POST['first_name'] ?? ''),
            'last_name'    => sanitize_text_field($_POST['last_name'] ?? ''),
            'display_name' => sanitize_text_field($_POST['display_name'] ?? ''),
            'role'         => $role,
        );
        if ($userdata['display_name'] === '') {
            unset($userdata['display_name']);
        }
        
        // Password only on create or if provided
        if (!empty($_POST['user_pass'])) {
            $userdata['user_pass'] = $_POST['user_pass'];
        }
        
        if ($user_id > 0) {
            $userdata['ID'] = $user_id;
            unset($userdata['user_login']); // user_login is immutable in WordPress
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
            update_user_meta($result, 'gti_phone', sanitize_text_field($_POST['phone'] ?? ''));
            update_user_meta($result, 'department', sanitize_text_field($_POST['department'] ?? ''));
            if (isset($_POST['profile_photo'])) {
                update_user_meta($result, 'profile_photo', intval($_POST['profile_photo']));
            }

            $saved_user = get_userdata($result);
            self::log_activity($user_id > 0 ? 'update' : 'create', 'user', $result, array(
                'name' => $saved_user ? $saved_user->display_name : '',
            ));
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

        if (!current_user_can('delete_users')) {
            wp_send_json_error(array('message' => 'You do not have permission to delete users.'));
            exit;
        }

        $user_id = intval($_POST['id']);

        // Don't allow deleting yourself
        if ($user_id == get_current_user_id()) {
            wp_send_json_error(array('message' => 'Cannot delete your own account', 'code' => 'self_delete'));
            exit;
        }

        $doomed = get_userdata($user_id);
        if (!$doomed) {
            wp_send_json_error(array('message' => 'That user no longer exists.', 'code' => 'not_found'), 404);
            exit;
        }

        // PRD §6.12 gap 5 — never leave the panel without a super admin.
        if (in_array('gti_super_admin', (array) $doomed->roles, true)) {
            $remaining = get_users(array(
                'role'    => 'gti_super_admin',
                'exclude' => array($user_id),
                'fields'  => 'ID',
                'number'  => 1,
            ));
            if (empty($remaining) && count(get_users(array('role' => 'administrator', 'fields' => 'ID', 'number' => 1))) === 0) {
                wp_send_json_error(array(
                    'message' => 'This is the last super admin. Promote someone else first.',
                    'code'    => 'last_super_admin',
                ));
                exit;
            }
        }

        // PRD §6.12 gap 4 — wp_delete_user() with no reassign target DELETES
        // everything the user authored. Content is handed over instead, and the
        // caller must say to whom when there is any.
        $reassign = isset($_POST['reassign_to']) ? (int) $_POST['reassign_to'] : 0;
        $owned    = (int) count_user_posts($user_id, 'post', true);

        if ($owned > 0 && $reassign <= 0) {
            wp_send_json_error(array(
                'message' => sprintf(
                    'This user has %d article(s). Choose who should inherit them before deleting.',
                    $owned
                ),
                'code'  => 'reassign_required',
                'posts' => $owned,
            ));
            exit;
        }

        if ($reassign > 0 && (!get_userdata($reassign) || $reassign === $user_id)) {
            wp_send_json_error(array('message' => 'That reassignment target is not valid.', 'code' => 'bad_reassign'));
            exit;
        }

        require_once ABSPATH . 'wp-admin/includes/user.php';

        $result = $reassign > 0
            ? wp_delete_user($user_id, $reassign)
            : wp_delete_user($user_id);

        if ($result) {
            self::log_activity('delete', 'user', $user_id, array(
                'name'        => $doomed->display_name,
                'reassign_to' => $reassign ?: null,
                'posts_moved' => $reassign ? $owned : 0,
            ));
            wp_send_json_success(array(
                'message' => $reassign
                    ? sprintf('User deleted; %d article(s) reassigned.', $owned)
                    : 'User deleted.',
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete user', 'code' => 'db_error'));
        }

        exit;
    }

}
