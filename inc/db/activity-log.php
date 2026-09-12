<?php
/**
 * Activity Log — schema, logger, and event hooks.
 *
 * Two different activity_log schemas historically shipped with this theme:
 *   - inc/db/schema.php                → user_id, action, description, ip_address
 *   - includes/class-gti-activator.php → user_id, action, entity_type, entity_id, details
 * Neither logger was ever loaded, so the table stayed empty and /dashboard/activity-log
 * always rendered its empty state. This file owns a superset of both column sets and is
 * the single place activity is written from.
 *
 * @package global-tractors
 */

if (!defined('ABSPATH')) exit;

define('GTI_ACTIVITY_LOG_SCHEMA', '1.2.0');

/**
 * Fully-qualified activity log table name.
 */
function gti_activity_log_table() {
    global $wpdb;
    return $wpdb->prefix . 'gti_activity_log';
}

if (!function_exists('gti_get_client_ip')) {
    /**
     * Best-effort client IP, proxy aware.
     */
    function gti_get_client_ip() {
        $keys = array('HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR');
        foreach ($keys as $key) {
            if (empty($_SERVER[$key])) continue;
            // X-Forwarded-For may carry a comma separated chain; the client is first.
            foreach (explode(',', $_SERVER[$key]) as $candidate) {
                $candidate = trim($candidate);
                if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                    return substr($candidate, 0, 45);
                }
            }
        }
        return '';
    }
}

/**
 * Create the activity log table, or patch an existing one that was created
 * from either of the two legacy schemas.
 */
function gti_ensure_activity_log_table($force = false) {
    static $checked = false;
    if ($checked && !$force) return;

    if (!$force && get_option('gti_activity_log_schema') === GTI_ACTIVITY_LOG_SCHEMA) {
        $checked = true;
        return;
    }

    global $wpdb;
    $table = gti_activity_log_table();
    $charset_collate = $wpdb->get_charset_collate();

    $wpdb->query(
        "CREATE TABLE IF NOT EXISTS {$table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            action VARCHAR(100) NOT NULL DEFAULT '',
            description TEXT NULL,
            entity_type VARCHAR(50) NULL,
            entity_id BIGINT(20) UNSIGNED NULL DEFAULT 0,
            details LONGTEXT NULL,
            ip_address VARCHAR(45) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY action (action),
            KEY entity (entity_type, entity_id),
            KEY created_at (created_at)
        ) {$charset_collate}"
    );

    // Patch a table created by one of the legacy schemas.
    $existing = $wpdb->get_col("SHOW COLUMNS FROM {$table}");
    if ($existing) {
        $wanted = array(
            'user_id'     => "BIGINT(20) UNSIGNED NOT NULL DEFAULT 0",
            'action'      => "VARCHAR(100) NOT NULL DEFAULT ''",
            'description' => "TEXT NULL",
            'entity_type' => "VARCHAR(50) NULL",
            'entity_id'   => "BIGINT(20) UNSIGNED NULL DEFAULT 0",
            'details'     => "LONGTEXT NULL",
            'ip_address'  => "VARCHAR(45) NULL",
            'created_at'  => "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
        );
        foreach ($wanted as $column => $definition) {
            if (!in_array($column, $existing, true)) {
                $wpdb->query("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
            }
        }
    }

    update_option('gti_activity_log_schema', GTI_ACTIVITY_LOG_SCHEMA, false);
    $checked = true;
}

/**
 * Record one activity row.
 *
 * Signature is backwards compatible with the unused logger in inc/db/queries.php.
 *
 * @param int    $user_id     User the activity belongs to (0 = guest/system).
 * @param string $action      Machine action key, e.g. 'login', 'create', 'status_change'.
 * @param string $description Human readable sentence shown in the dashboard.
 * @param string $entity_type Optional entity bucket, e.g. 'equipment', 'article'.
 * @param int    $entity_id   Optional entity primary key.
 * @param array  $details     Optional extra payload stored as JSON.
 * @return int|false Inserted row id, or false on failure.
 */
function gti_log_activity($user_id, $action, $description = '', $entity_type = '', $entity_id = 0, $details = array()) {
    global $wpdb;

    gti_ensure_activity_log_table();

    $row = array(
        'user_id'     => (int) $user_id,
        'action'      => substr((string) $action, 0, 100),
        'description' => (string) $description,
        'entity_type' => substr((string) $entity_type, 0, 50),
        'entity_id'   => (int) $entity_id,
        'details'     => !empty($details) ? wp_json_encode($details) : null,
        'ip_address'  => gti_get_client_ip(),
        'created_at'  => current_time('mysql'),
    );

    $result = $wpdb->insert($table = gti_activity_log_table(), $row);

    // A stale schema (table dropped, or missing columns) is the usual failure —
    // re-run the schema check once and retry before giving up.
    if ($result === false) {
        gti_ensure_activity_log_table(true);
        $result = $wpdb->insert($table, $row);
    }

    return $result ? (int) $wpdb->insert_id : false;
}

/**
 * Log an activity for the user handling the current request.
 */
function gti_log_current_activity($action, $description = '', $entity_type = '', $entity_id = 0, $details = array()) {
    return gti_log_activity(get_current_user_id(), $action, $description, $entity_type, $entity_id, $details);
}

/**
 * Human label for an entity bucket.
 */
function gti_activity_entity_label($entity_type) {
    $labels = array(
        'equipment'    => 'Equipment',
        'spare_part'   => 'Spare Part',
        'request'      => 'Request Equipment',
        'quotation'    => 'Quotation',
        'sell_request' => 'Sell Equipment Request',
        'customer'     => 'Customer',
        'user'         => 'User',
        'article'      => 'Article',
        'media'        => 'Media File',
        'profile'      => 'Profile',
    );
    return $labels[$entity_type] ?? ucwords(str_replace('_', ' ', (string) $entity_type));
}

/**
 * Build a readable sentence for an activity that was logged without one.
 *
 * Rows written by GTI_Ajax before this module existed carry only
 * action/entity_type/entity_id, so the log page would show an empty column.
 */
function gti_activity_build_description($action, $entity_type = '', $entity_id = 0, $details = array()) {
    $entity = gti_activity_entity_label($entity_type);
    $ref    = $entity_id ? ' #' . (int) $entity_id : '';

    if (is_string($details)) {
        $decoded = json_decode($details, true);
        $details = is_array($decoded) ? $decoded : array();
    }
    $details = is_array($details) ? $details : array();

    $name = '';
    foreach (array('name', 'title', 'label', 'filename') as $key) {
        if (!empty($details[$key])) { $name = ' "' . $details[$key] . '"'; break; }
    }

    switch ($action) {
        case 'login':
            return 'Logged in to the dashboard';
        case 'logout':
            return 'Logged out of the dashboard';
        case 'failed_login':
            return 'Failed login attempt' . ($name ?: (!empty($details['username']) ? ' for "' . $details['username'] . '"' : ''));
        case 'register':
            return 'New account registered';
        case 'password_change':
            return 'Password changed';
        case 'profile_update':
            return 'Profile information updated';
        case 'create':
            return 'Created ' . $entity . $name . $ref;
        case 'update':
            return 'Updated ' . $entity . $name . $ref;
        case 'delete':
            return 'Deleted ' . $entity . $name . $ref;
        case 'publish':
            return 'Published ' . $entity . $name . $ref;
        case 'unpublish':
            return 'Unpublished ' . $entity . $name . $ref;
        case 'upload':
            return 'Uploaded ' . $entity . $name;
        case 'status_change':
            $status = $details['new_status'] ?? '';
            $status = $status ? ucwords(str_replace('_', ' ', $status)) : 'a new status';
            return 'Changed ' . $entity . $ref . ' status to ' . $status;
        case 'view':
            return 'Viewed ' . $entity . $name . $ref;
        default:
            return ucwords(str_replace('_', ' ', (string) $action)) . ' — ' . $entity . $name . $ref;
    }
}

/**
 * Description to render for a log row, falling back to a generated sentence.
 */
function gti_activity_row_description($row) {
    if (!empty($row->description)) {
        return $row->description;
    }
    return gti_activity_build_description(
        $row->action ?? '',
        $row->entity_type ?? '',
        $row->entity_id ?? 0,
        $row->details ?? array()
    );
}

// ─────────────────────────────────────────────────────────────────────────────
// Event hooks — everything below is what actually fills the log.
// ─────────────────────────────────────────────────────────────────────────────

add_action('wp_login', 'gti_activity_on_login', 10, 2);
function gti_activity_on_login($user_login, $user = null) {
    if (!$user instanceof WP_User) {
        $user = get_user_by('login', $user_login);
    }
    if (!$user) return;

    // Powers the "Last Login" tile on /dashboard/users.
    update_user_meta($user->ID, 'gti_last_login', current_time('mysql'));
    gti_log_activity($user->ID, 'login', 'Logged in to the dashboard');
}

add_action('wp_logout', 'gti_activity_on_logout');
function gti_activity_on_logout($user_id = 0) {
    $user_id = $user_id ?: get_current_user_id();
    if (!$user_id) return;
    gti_log_activity($user_id, 'logout', 'Logged out of the dashboard');
}

add_action('wp_login_failed', 'gti_activity_on_login_failed');
function gti_activity_on_login_failed($username) {
    $user = get_user_by('login', $username) ?: get_user_by('email', $username);
    gti_log_activity(
        $user ? $user->ID : 0,
        'failed_login',
        'Failed login attempt for "' . $username . '"',
        'user',
        $user ? $user->ID : 0,
        array('username' => $username)
    );
}

add_action('user_register', 'gti_activity_on_user_register');
function gti_activity_on_user_register($user_id) {
    $user = get_userdata($user_id);
    gti_log_activity(
        $user_id,
        'register',
        'New account registered' . ($user ? ' — ' . $user->user_email : ''),
        'user',
        $user_id
    );
}

add_action('profile_update', 'gti_activity_on_profile_update', 10, 2);
function gti_activity_on_profile_update($user_id, $old_data = null) {
    $user = get_userdata($user_id);
    $actor = get_current_user_id() ?: $user_id;
    $self  = ($actor === (int) $user_id);
    gti_log_activity(
        $actor,
        'profile_update',
        $self ? 'Profile information updated' : 'Updated profile of ' . ($user ? $user->display_name : 'user #' . $user_id),
        'user',
        $user_id
    );
}

add_action('after_password_reset', 'gti_activity_on_password_reset');
function gti_activity_on_password_reset($user) {
    if (!$user instanceof WP_User) return;
    gti_log_activity($user->ID, 'password_change', 'Password was reset', 'user', $user->ID);
}

add_action('add_attachment', 'gti_activity_on_attachment_added');
function gti_activity_on_attachment_added($post_id) {
    $file = get_post_meta($post_id, '_wp_attached_file', true);
    gti_log_current_activity(
        'upload',
        'Uploaded media file "' . ($file ? basename($file) : get_the_title($post_id)) . '"',
        'media',
        $post_id,
        array('filename' => $file ? basename($file) : '')
    );
}

add_action('delete_attachment', 'gti_activity_on_attachment_deleted');
function gti_activity_on_attachment_deleted($post_id) {
    $file = get_post_meta($post_id, '_wp_attached_file', true);
    gti_log_current_activity(
        'delete',
        'Deleted media file "' . ($file ? basename($file) : get_the_title($post_id)) . '"',
        'media',
        $post_id,
        array('filename' => $file ? basename($file) : '')
    );
}

add_action('transition_post_status', 'gti_activity_on_post_transition', 10, 3);
function gti_activity_on_post_transition($new_status, $old_status, $post) {
    if (!$post instanceof WP_Post || $post->post_type !== 'post') return;
    if ($new_status === 'auto-draft' || $old_status === $new_status) return;
    if (wp_is_post_revision($post->ID) || wp_is_post_autosave($post->ID)) return;
    if (in_array($new_status, array('inherit', 'trash'), true) && $old_status === 'auto-draft') return;

    if ($old_status === 'new' || $old_status === 'auto-draft') {
        $action = 'create';
        $text   = 'Created article "' . $post->post_title . '"';
    } elseif ($new_status === 'publish') {
        $action = 'publish';
        $text   = 'Published article "' . $post->post_title . '"';
    } elseif ($new_status === 'trash') {
        $action = 'delete';
        $text   = 'Moved article "' . $post->post_title . '" to trash';
    } elseif ($old_status === 'publish') {
        $action = 'unpublish';
        $text   = 'Unpublished article "' . $post->post_title . '"';
    } else {
        $action = 'status_change';
        $text   = 'Changed article "' . $post->post_title . '" status to ' . ucfirst($new_status);
    }

    gti_log_current_activity($action, $text, 'article', $post->ID, array(
        'title'      => $post->post_title,
        'old_status' => $old_status,
        'new_status' => $new_status,
    ));
}

// ── Icon/colour per action type (moved out of the template, R-06) ───────────
if ( ! function_exists( 'gti_get_action_icon' ) ) {
    function gti_get_action_icon($action) {
        $icons = array(
            'login'              => array('icon' => 'fa-sign-in-alt', 'color' => '#059669', 'bg' => '#d1fae5'),
            'logout'             => array('icon' => 'fa-sign-out-alt', 'color' => '#6b7280', 'bg' => '#f3f4f6'),
            'create'             => array('icon' => 'fa-plus-circle', 'color' => '#2563eb', 'bg' => '#dbeafe'),
            'update'             => array('icon' => 'fa-edit', 'color' => '#d97706', 'bg' => '#fef3c7'),
            'delete'             => array('icon' => 'fa-trash-alt', 'color' => '#dc2626', 'bg' => '#fee2e2'),
            'view'               => array('icon' => 'fa-eye', 'color' => '#6366f1', 'bg' => '#e0e7ff'),
            'publish'            => array('icon' => 'fa-bullhorn', 'color' => '#047857', 'bg' => '#d1fae5'),
            'unpublish'          => array('icon' => 'fa-eye-slash', 'color' => '#b45309', 'bg' => '#fef3c7'),
            'upload'             => array('icon' => 'fa-cloud-arrow-up', 'color' => '#0891b2', 'bg' => '#cffafe'),
            'status_change'      => array('icon' => 'fa-exchange-alt', 'color' => '#8b5cf6', 'bg' => '#ede9fe'),
            'password_change'    => array('icon' => 'fa-key', 'color' => '#ec4899', 'bg' => '#fce7f3'),
            'profile_update'     => array('icon' => 'fa-user-edit', 'color' => '#0891b2', 'bg' => '#cffafe'),
            'export'             => array('icon' => 'fa-download', 'color' => '#059669', 'bg' => '#d1fae5'),
            'import'             => array('icon' => 'fa-upload', 'color' => '#2563eb', 'bg' => '#dbeafe'),
            'register'           => array('icon' => 'fa-user-plus', 'color' => '#059669', 'bg' => '#d1fae5'),
            'failed_login'       => array('icon' => 'fa-exclamation-triangle', 'color' => '#dc2626', 'bg' => '#fee2e2'),
        );
        return $icons[$action] ?? array('icon' => 'fa-circle', 'color' => '#6b7280', 'bg' => '#f3f4f6');
    }
}

/**
 * Users that appear in the activity log, for the filter dropdown (PRD §6.13).
 *
 * Driven by the log itself rather than the full user list, so the dropdown only
 * offers people who actually have entries.
 */
function gti_activity_log_users() {
    global $wpdb;

    $table = gti_activity_log_table();
    $ids   = $wpdb->get_col( "SELECT DISTINCT user_id FROM {$table} WHERE user_id > 0" );

    if ( ! $ids ) {
        return array();
    }

    return get_users( array(
        'include' => array_map( 'intval', $ids ),
        'orderby' => 'display_name',
        'order'   => 'ASC',
    ) );
}
