<?php
/**
 * Template: Users / My Profile (/dashboard/users)
 * @package global-tractors
 */
if (!defined('ABSPATH')) exit;

// Restored during the layout refactor: the shared header renders the user
// chrome, but this page still reads the record itself.
$current_user = wp_get_current_user();
$user_avatar = get_avatar_url($current_user->ID, array('size' => 80));
$profile = gti_get_user_profile($current_user->ID);

// Role labels
$role_labels = array(
    'gti_super_admin' => 'Super Admin',
    'gti_admin'       => 'Admin',
    'gti_sales'       => 'Sales',
    'gti_inventory'   => 'Inventory',
    'administrator'   => 'Administrator',
);
$user_roles = array_intersect($current_user->roles, array('gti_super_admin', 'gti_admin', 'gti_sales', 'gti_inventory', 'administrator'));
$primary_role = !empty($user_roles) ? reset($user_roles) : ($current_user->roles[0] ?? 'User');
$role_label = $role_labels[$primary_role] ?? ucfirst($primary_role);

// Get user activity count
global $wpdb;
gti_ensure_activity_log_table();
$activity_table = gti_activity_log_table();
$activity_count = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$activity_table} WHERE user_id = %d",
    $current_user->ID
));

// Member since / last login — 'gti_last_login' is written by the wp_login hook
// in inc/db/activity-log.php. WP_User has no last_login property of its own.
$member_since   = !empty($profile['registered']) ? date('M j, Y', strtotime($profile['registered'])) : '-';
$last_login_raw = get_user_meta($current_user->ID, 'gti_last_login', true);
$last_login     = $last_login_raw
    ? date('M j, Y H:i', strtotime($last_login_raw))
    : 'This session';

// ── Team directory ──────────────────────────────────────────────────────────
$can_list_users   = current_user_can('list_users');
$can_create_users = current_user_can('create_users');
$can_edit_users   = current_user_can('edit_users');
$can_delete_users = current_user_can('delete_users');

$team          = array();
$editable_roles = array();

if ($can_list_users) {
    // get_editable_roles() is admin-only; this template runs on the front end.
    require_once ABSPATH . 'wp-admin/includes/user.php';
    $editable_roles = get_editable_roles();

    // §6.12 gaps 2-3: the directory used to fetch 200 rows with no search,
    // filter or paging, so a team of any size was simply truncated.
    $user_search = isset($_GET['user_search']) ? sanitize_text_field(wp_unslash($_GET['user_search'])) : '';
    $role_filter = isset($_GET['role']) ? sanitize_text_field(wp_unslash($_GET['role'])) : '';
    $users_per_page = 15;
    $users_page  = gti_current_page_num();

    $user_query_args = array(
        'orderby' => 'display_name',
        'order'   => 'ASC',
        'number'  => $users_per_page,
        'offset'  => ($users_page - 1) * $users_per_page,
        'count_total' => true,
    );
    if ($user_search !== '') {
        $user_query_args['search']         = '*' . $user_search . '*';
        $user_query_args['search_columns'] = array('user_login', 'user_email', 'display_name', 'user_nicename');
    }
    if ($role_filter !== '' && isset($editable_roles[$role_filter])) {
        $user_query_args['role'] = $role_filter;
    }

    $user_query  = new WP_User_Query($user_query_args);
    $team_users  = $user_query->get_results();
    $users_total = (int) $user_query->get_total();
    $users_pages = (int) ceil($users_total / $users_per_page);

    // One grouped query beats a COUNT(*) per user.
    $activity_by_user = array();
    foreach ($wpdb->get_results("SELECT user_id, COUNT(*) AS total FROM {$activity_table} GROUP BY user_id") as $row) {
        $activity_by_user[(int) $row->user_id] = (int) $row->total;
    }

    foreach ($team_users as $team_user) {
        $role_key = $team_user->roles[0] ?? '';
        $team[] = array(
            'id'         => $team_user->ID,
            'name'       => $team_user->display_name ?: $team_user->user_login,
            'login'      => $team_user->user_login,
            'email'      => $team_user->user_email,
            'first_name' => get_user_meta($team_user->ID, 'first_name', true),
            'last_name'  => get_user_meta($team_user->ID, 'last_name', true),
            'phone'      => get_user_meta($team_user->ID, 'gti_phone', true),
            'department' => get_user_meta($team_user->ID, 'department', true),
            'role'       => $role_key,
            'role_label' => $role_labels[$role_key] ?? ($editable_roles[$role_key]['name'] ?? ucfirst((string) $role_key)),
            'avatar'     => get_avatar_url($team_user->ID, array('size' => 64)),
            'activities' => $activity_by_user[$team_user->ID] ?? 0,
            // Supports the PIC workflow: who is carrying what (PRD §6.12 gap 6).
            'assigned'   => gti_assigned_order_count($team_user->ID),
            'owned_posts'=> (int) count_user_posts($team_user->ID, 'post', true),
            'last_login' => get_user_meta($team_user->ID, 'gti_last_login', true),
            'registered' => $team_user->user_registered,
            'is_self'    => ((int) $team_user->ID === (int) $current_user->ID),
        );
    }
}

gti_dashboard_open( array(
    'page'     => 'users',
    'title'    => 'Users',
    'subtitle' => 'Profile and team directory 👤',
    'cap'      => 'gti_access',
    'js'       => array( 'users' ),
) );
?>


            <!-- Content -->
            <div class="gti-content">
                <div class="gti-content-left">

                    <?php if ($can_list_users) : ?>
                        <?php /* Two tabs instead of one long stack (PRD §6.12 gap 1).
                                 The second only exists for users who may list others. */ ?>
                        <div class="gti-cd-tabs" id="gti-users-tabs">
                            <a href="#tab-profile" class="gti-cd-tab active" data-tab="profile">My Profile</a>
                            <a href="#tab-team" class="gti-cd-tab" data-tab="team">System Users</a>
                        </div>
                    <?php endif; ?>

                    <div class="gti-cd-tab-content" id="tab-profile">

                    <!-- Profile Header Card -->
                    <div class="gti-profile-header-card">
                        <div class="gti-profile-banner"></div>
                        <div class="gti-profile-avatar-wrap">
                            <img src="<?php echo esc_url($user_avatar); ?>" alt="Avatar" class="gti-profile-avatar">
                            <div class="gti-profile-name-block">
                                <h2><?php echo esc_html($current_user->display_name); ?></h2>
                                <span class="gti-profile-role"><i class="fas fa-shield-alt"></i> <?php echo esc_html($role_label); ?></span>
                            </div>
                        </div>
                        <div class="gti-profile-meta-row">
                            <div class="gti-profile-meta-item">
                                <i class="fas fa-envelope"></i>
                                <span><?php echo esc_html($current_user->user_email); ?></span>
                            </div>
                            <div class="gti-profile-meta-item">
                                <i class="fas fa-phone"></i>
                                <span><?php echo esc_html($profile['phone'] ?: '-'); ?></span>
                            </div>
                            <div class="gti-profile-meta-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?php echo esc_html(trim(implode(', ', array_filter(array($profile['city'], $profile['province'])))) ?: '-'); ?></span>
                            </div>
                            <div class="gti-profile-meta-item">
                                <i class="fas fa-calendar-alt"></i>
                                <span>Joined <?php echo esc_html($member_since); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="gti-ue-stats-row">
                        <div class="gti-ue-stat-card">
                            <div class="gti-ue-stat-icon primary"><i class="fas fa-user"></i></div>
                            <div class="gti-ue-stat-info">
                                <p class="gti-ue-stat-label">Account Status</p>
                                <p class="gti-ue-stat-value" style="font-size:16px;">Active</p>
                            </div>
                        </div>
                        <div class="gti-ue-stat-card">
                            <div class="gti-ue-stat-icon success"><i class="fas fa-history"></i></div>
                            <div class="gti-ue-stat-info">
                                <p class="gti-ue-stat-label">Activities</p>
                                <p class="gti-ue-stat-value"><?php echo esc_html($activity_count); ?></p>
                            </div>
                        </div>
                        <div class="gti-ue-stat-card">
                            <div class="gti-ue-stat-icon info"><i class="fas fa-clock"></i></div>
                            <div class="gti-ue-stat-info">
                                <p class="gti-ue-stat-label">Last Login</p>
                                <p class="gti-ue-stat-value" style="font-size:14px; font-weight:500;"><?php echo esc_html($last_login); ?></p>
                            </div>
                        </div>
                        <div class="gti-ue-stat-card">
                            <div class="gti-ue-stat-icon warning"><i class="fas fa-calendar-check"></i></div>
                            <div class="gti-ue-stat-info">
                                <p class="gti-ue-stat-label">Member Since</p>
                                <p class="gti-ue-stat-value" style="font-size:14px; font-weight:500;"><?php echo esc_html($member_since); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Profile Information Form -->
                    <div class="gti-profile-card" style="margin-bottom: 24px;">
                        <div class="gti-profile-card-header">
                            <h3><i class="fas fa-user-edit"></i> Profile Information</h3>
                        </div>
                        <div class="gti-profile-card-body">
                            <div id="gti-profile-alert" class="gti-alert" role="alert"></div>
                            <form id="gti-profile-form" novalidate>
                                <?php wp_nonce_field('gti_profile_action', 'gti_profile_nonce'); ?>
                                <div class="gti-profile-grid">
                                    <div class="gti-profile-field">
                                        <label for="gti-name">Full Name</label>
                                        <input type="text" id="gti-name" name="name" value="<?php echo esc_attr($profile['name']); ?>" required>
                                    </div>
                                    <div class="gti-profile-field">
                                        <label>Email</label>
                                        <input type="email" value="<?php echo esc_attr($profile['email']); ?>" disabled>
                                        <span class="field-hint">Email cannot be changed</span>
                                    </div>
                                    <div class="gti-profile-field">
                                        <label for="gti-phone">Phone Number</label>
                                        <input type="tel" id="gti-phone" name="phone" value="<?php echo esc_attr($profile['phone']); ?>">
                                    </div>
                                    <div class="gti-profile-field">
                                        <label for="gti-city">City</label>
                                        <input type="text" id="gti-city" name="city" value="<?php echo esc_attr($profile['city']); ?>">
                                    </div>
                                    <div class="gti-profile-field">
                                        <label for="gti-province">Province</label>
                                        <input type="text" id="gti-province" name="province" value="<?php echo esc_attr($profile['province']); ?>">
                                    </div>
                                    <div class="gti-profile-field full">
                                        <label for="gti-address">Address</label>
                                        <textarea id="gti-address" name="address" rows="3"><?php echo esc_textarea($profile['address']); ?></textarea>
                                    </div>
                                </div>
                                <div class="gti-profile-actions">
                                    <button type="submit" class="gti-btn-primary" id="gti-profile-btn">
                                        <i class="fas fa-save"></i>
                                        <span>Save Changes</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Change Password -->
                    <div class="gti-profile-card">
                        <div class="gti-profile-card-header">
                            <h3><i class="fas fa-lock"></i> Change Password</h3>
                        </div>
                        <div class="gti-profile-card-body">
                            <div id="gti-password-alert" class="gti-alert" role="alert"></div>
                            <form id="gti-password-form" novalidate>
                                <?php wp_nonce_field('gti_password_action', 'gti_password_nonce'); ?>
                                <div class="gti-password-grid">
                                    <div class="gti-profile-field">
                                        <label for="gti-current-password">Current Password</label>
                                        <input type="password" id="gti-current-password" name="current_password" required>
                                    </div>
                                    <div class="gti-profile-field">
                                        <label for="gti-new-password">New Password</label>
                                        <input type="password" id="gti-new-password" name="new_password" required minlength="8">
                                        <span class="field-hint">Minimum 8 characters</span>
                                    </div>
                                    <div class="gti-profile-field">
                                        <label for="gti-confirm-password">Confirm New Password</label>
                                        <input type="password" id="gti-confirm-password" name="confirm_password" required>
                                    </div>
                                </div>
                                <div class="gti-profile-actions">
                                    <button type="submit" class="gti-btn-primary" id="gti-password-btn">
                                        <i class="fas fa-key"></i>
                                        <span>Update Password</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    </div><!-- /#tab-profile -->

                    <!-- Team Directory -->
                    <?php if ($can_list_users): ?>
                    <div class="gti-cd-tab-content" id="tab-team" style="display:none">
                    <div class="gti-profile-card">
                        <div class="gti-profile-card-header">
                            <h3><i class="fas fa-user-shield"></i> System Users <span style="font-weight:500;color:#9ca3af;font-size:13px;">(<?php echo (int) $users_total; ?>)</span></h3>
                            <?php if ($can_create_users): ?>
                                <button type="button" class="gti-btn-primary" id="gti-add-user-btn">
                                    <i class="fas fa-plus"></i> <span>Add User</span>
                                </button>
                            <?php endif; ?>
                        </div>
                        <div class="gti-profile-card-body" style="padding: 0;">
                            <div id="gti-users-alert" class="gti-alert" role="alert" style="margin: 20px 28px 0;"></div>

                            <form class="gti-ue-toolbar" method="get" style="padding: 16px 28px 0;">
                                <input type="hidden" name="gti_page" value="users">
                                <div class="gti-ue-toolbar-left">
                                    <div class="gti-ue-search">
                                        <i class="fas fa-search"></i>
                                        <input type="text" name="user_search" placeholder="Search users…"
                                               value="<?php echo esc_attr($user_search); ?>">
                                    </div>
                                    <div class="gti-ue-filter">
                                        <select name="role">
                                            <option value="">All Roles</option>
                                            <?php foreach ($editable_roles as $gti_role_key => $gti_role) : ?>
                                                <option value="<?php echo esc_attr($gti_role_key); ?>" <?php selected($role_filter, $gti_role_key); ?>>
                                                    <?php echo esc_html(translate_user_role($gti_role['name'])); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <button type="submit" class="gti-ue-btn-reset"><i class="fas fa-filter"></i> Apply</button>
                                    <a href="<?php echo esc_url(gti_dashboard_url('users')); ?>" class="gti-ue-btn-reset">
                                        <i class="fas fa-rotate-right"></i> Reset
                                    </a>
                                </div>
                            </form>

                            <div style="overflow-x:auto;">
                                <table class="gti-users-table">
                                    <thead>
                                        <tr>
                                            <th>User</th>
                                            <th style="width:170px;">Role</th>
                                            <th style="width:150px;">Phone</th>
                                            <th style="width:120px;text-align:center;">Assigned Orders</th>
                                            <th style="width:110px;text-align:center;">Activities</th>
                                            <th style="width:160px;">Last Login</th>
                                            <?php if ($can_edit_users || $can_delete_users): ?>
                                                <th style="width:110px;text-align:right;">Actions</th>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($team as $member): ?>
                                            <tr>
                                                <td>
                                                    <div class="gti-users-cell">
                                                        <img src="<?php echo esc_url($member['avatar']); ?>" alt="" class="gti-users-avatar">
                                                        <div>
                                                            <strong><?php echo esc_html($member['name']); ?><?php echo $member['is_self'] ? ' <span style="color:#9ca3af;font-weight:500;">(you)</span>' : ''; ?></strong>
                                                            <small><?php echo esc_html($member['email']); ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><span class="gti-users-role"><?php echo esc_html($member['role_label']); ?></span></td>
                                                <td><?php echo esc_html($member['phone'] ?: '—'); ?></td>
                                                <td style="text-align:center;">
                                                    <?php if ($member['assigned'] > 0) : ?>
                                                        <strong><?php echo (int) $member['assigned']; ?></strong>
                                                    <?php else : ?>
                                                        <span style="color:#9ca3af;">&mdash;</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="text-align:center;"><?php echo esc_html($member['activities']); ?></td>
                                                <td style="color:#6b7280;font-size:13px;">
                                                    <?php echo esc_html($member['last_login'] ? date('M j, Y H:i', strtotime($member['last_login'])) : 'Never'); ?>
                                                </td>
                                                <?php if ($can_edit_users || $can_delete_users): ?>
                                                <td style="text-align:right;white-space:nowrap;">
                                                    <?php if ($can_edit_users): ?>
                                                        <button type="button" class="gti-users-icon-btn" title="Edit user"
                                                                data-user='<?php echo esc_attr(wp_json_encode($member)); ?>'
                                                                onclick="gtiEditUser(this)">
                                                            <i class="fas fa-pen"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <?php if ($can_delete_users && !$member['is_self']): ?>
                                                        <button type="button" class="gti-users-icon-btn danger js-delete-user" title="Delete user"
                                                                data-user='<?php echo esc_attr(wp_json_encode(array(
                                                                    'id'    => $member['id'],
                                                                    'name'  => $member['name'],
                                                                    'email' => $member['email'],
                                                                    'posts' => $member['owned_posts'],
                                                                ))); ?>'>
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($team)): ?>
                                            <tr><td colspan="7" style="text-align:center;padding:40px;color:#9ca3af;">No users found.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                                <?php if ($users_pages > 1) : ?>
                                    <div style="padding: 0 28px 20px;">
                                        <?php
                                        gti_render_pagination(array(
                                            'total'    => $users_total,
                                            'per_page' => $users_per_page,
                                            'current'  => $users_page,
                                            'base_url' => gti_dashboard_url('users'),
                                            'params'   => array_filter(array(
                                                'gti_page'    => 'users',
                                                'user_search' => $user_search,
                                                'role'        => $role_filter,
                                            )),
                                        ));
                                        ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    </div><!-- /#tab-team -->
                    <?php endif; ?>

                </div>
            </div>

<?php
gti_dashboard_close( array(
    'modals' => array( 'delete', 'user-delete', 'user' ),
) );
