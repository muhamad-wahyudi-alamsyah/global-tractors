<?php
/**
 * Users List View
 *
 * @package Global_Tractors
 */

defined('ABSPATH') || exit;

// Check permission
if (!current_user_can('gti_manage_users')) {
    wp_die('You do not have permission to access this page.');
}

// Get all users
$users = get_users(array(
    'role__in' => array('gti_super_admin', 'gti_admin', 'gti_sales', 'gti_inventory', 'administrator'),
    'number' => 50,
    'offset' => 0,
));

// Count by role
$role_counts = array();
foreach ($users as $user) {
    $roles = array_intersect($user->roles, array('gti_super_admin', 'gti_admin', 'gti_sales', 'gti_inventory'));
    if (!empty($roles)) {
        $role = reset($roles);
        $role_counts[$role] = ($role_counts[$role] ?? 0) + 1;
    } elseif (in_array('administrator', $user->roles)) {
        $role_counts['administrator'] = ($role_counts['administrator'] ?? 0) + 1;
    }
}

$total_users = count($users);
$active_count = count(array_filter($users, function($user) { return $user->user_status === 0; }));
$admin_count = $role_counts['gti_super_admin'] ?? 0;
?>

<div class="gti-admin-wrap">
    <!-- Page Header -->
    <div class="gti-page-header">
        <div>
            <h1 class="gti-page-title">Users</h1>
        </div>
        <a href="<?php echo admin_url('admin.php?page=gti-users-add'); ?>" class="gti-btn gti-btn-primary">
            + Add New User
        </a>
    </div>
    
    <!-- Statistics Cards -->
    <div class="gti-stats-row">
        <div class="gti-stat-card stat-primary">
            <div class="stat-value"><?php echo esc_html($total_users); ?></div>
            <div class="stat-label">Total Users</div>
        </div>
        <div class="gti-stat-card stat-success">
            <div class="stat-value"><?php echo esc_html($active_count); ?></div>
            <div class="stat-label">Active Users</div>
        </div>
        <div class="gti-stat-card stat-info">
            <div class="stat-value"><?php echo esc_html($total_users - $active_count); ?></div>
            <div class="stat-label">Inactive Users</div>
        </div>
        <div class="gti-stat-card stat-warning">
            <div class="stat-value"><?php echo esc_html($admin_count); ?></div>
            <div class="stat-label">Administrators</div>
        </div>
    </div>
    
    <!-- Users Table -->
    <div class="gti-table-wrap">
        <table class="gti-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th style="width: 100px; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 40px;">
                            <div class="gti-empty-state">
                                <div class="gti-empty-state-icon">👤</div>
                                <div class="gti-empty-state-title">No users found</div>
                                <div class="gti-empty-state-text">Get started by adding your first user</div>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <?php
                        $user_roles = array_intersect($user->roles, array('gti_super_admin', 'gti_admin', 'gti_sales', 'gti_inventory'));
                        $primary_role = !empty($user_roles) ? reset($user_roles) : ($user->roles[0] ?? 'user');
                        
                        $role_labels = array(
                            'gti_super_admin' => 'Super Admin',
                            'gti_admin'       => 'Admin',
                            'gti_sales'       => 'Sales',
                            'gti_inventory'   => 'Inventory',
                            'administrator'   => 'Administrator',
                        );
                        
                        $is_active = $user->user_status === 0;
                        ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <?php echo get_avatar($user->ID, 40); ?>
                                    <div>
                                        <strong><?php echo esc_html($user->display_name); ?></strong>
                                        <br><small style="color: #6b7280;">@<?php echo esc_html($user->user_login); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo esc_html($user->user_email); ?></td>
                            <td>
                                <span class="gti-badge gti-badge-info">
                                    <?php echo esc_html($role_labels[$primary_role] ?? ucfirst($primary_role)); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($is_active): ?>
                                    <span class="gti-badge gti-badge-success">Active</span>
                                <?php else: ?>
                                    <span class="gti-badge gti-badge-neutral">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <div class="gti-dropdown">
                                    <button type="button" class="gti-btn gti-btn-sm gti-dropdown-toggle">•••</button>
                                    <div class="gti-dropdown-menu">
                                        <a href="<?php echo admin_url('admin.php?page=gti-users-edit&user_id=' . $user->ID); ?>" class="gti-dropdown-item">
                                            Edit
                                        </a>
                                        <?php if ($user->ID != get_current_user_id()): ?>
                                            <a href="#" class="gti-dropdown-item gti-confirm-delete"
                                               data-action="gti_delete_user"
                                               data-id="<?php echo esc_attr($user->ID); ?>"
                                               data-message="Are you sure you want to delete this user?">
                                                Delete
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>