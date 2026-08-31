<?php
/**
 * Template: Users / My Profile (/dashboard/users)
 * @package global-tractors
 */
if (!defined('ABSPATH')) exit;
gti_require_login();

$current_user = wp_get_current_user();
$user_name = $current_user->display_name ?: $current_user->user_login;
$user_avatar = get_avatar_url($current_user->ID, ['size' => 80]);
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
$activity_table = $wpdb->prefix . 'gti_activity_log';
$activity_count = 0;
if ($wpdb->get_var("SHOW TABLES LIKE '{$activity_table}'") === $activity_table) {
    $activity_count = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$activity_table} WHERE user_id = %d",
        $current_user->ID
    ));
}

// Member since
$member_since = !empty($profile['registered']) ? date('M j, Y', strtotime($profile['registered'])) : '-';
$last_login = !empty($current_user->last_login) ? date('M j, Y H:i', strtotime($current_user->last_login)) : date('M j, Y H:i', strtotime($profile['registered']));
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - <?php bloginfo('name'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo GTI_CHILD_URL; ?>/assets/css/dashboard.css">
    <style>
        /* Profile page styles */
        .gti-profile-header-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 32px;
            margin-bottom: 24px;
        }
        .gti-profile-banner {
            width: 100%;
            height: 120px;
            background: linear-gradient(135deg, #F5A623 0%, #F7C948 100%);
            border-radius: 10px;
            margin-bottom: -50px;
            position: relative;
        }
        .gti-profile-avatar-wrap {
            display: flex;
            align-items: flex-end;
            gap: 20px;
            padding: 0 32px;
            position: relative;
            z-index: 1;
        }
        .gti-profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 4px solid #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            object-fit: cover;
            background: #f3f4f6;
        }
        .gti-profile-name-block {
            padding-bottom: 8px;
        }
        .gti-profile-name-block h2 {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
            color: #1a1f36;
        }
        .gti-profile-name-block .gti-profile-role {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 4px;
            padding: 3px 12px;
            background: #FFF8EC;
            color: #B8860B;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .gti-profile-meta-row {
            display: flex;
            gap: 32px;
            padding: 20px 32px 0;
            flex-wrap: wrap;
        }
        .gti-profile-meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #6b7280;
        }
        .gti-profile-meta-item i {
            color: #9ca3af;
            width: 16px;
        }

        /* Stat cards */
        .gti-ue-stats-row {
            display: flex;
            gap: 16px;
            margin-bottom: 24px;
        }
        .gti-ue-stat-card {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 18px 20px;
            background: #fff;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
        }
        .gti-ue-stat-icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }
        .gti-ue-stat-icon.primary { background: #dbeafe; color: #2563eb; }
        .gti-ue-stat-icon.success { background: #d1fae5; color: #059669; }
        .gti-ue-stat-icon.info { background: #e0e7ff; color: #4f46e5; }
        .gti-ue-stat-icon.warning { background: #fef3c7; color: #d97706; }
        .gti-ue-stat-info p { margin: 0; }
        .gti-ue-stat-label {
            font-size: 13px;
            color: #6b7280;
            font-weight: 500;
        }
        .gti-ue-stat-value {
            font-size: 22px;
            font-weight: 700;
            color: #1a1f36;
            margin-top: 2px;
        }

        /* Profile form card */
        .gti-profile-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
        }
        .gti-profile-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 28px;
            border-bottom: 1px solid #f3f4f6;
        }
        .gti-profile-card-header h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            color: #1a1f36;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .gti-profile-card-header h3 i {
            color: #F5A623;
        }
        .gti-profile-card-body {
            padding: 28px;
        }
        .gti-profile-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .gti-profile-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .gti-profile-field.full {
            grid-column: 1 / -1;
        }
        .gti-profile-field label {
            font-size: 13px;
            font-weight: 500;
            color: #6b7280;
        }
        .gti-profile-field input,
        .gti-profile-field textarea {
            padding: 10px 14px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            color: #1a1f36;
            background: #fff;
            transition: border-color 0.15s;
        }
        .gti-profile-field input:focus,
        .gti-profile-field textarea:focus {
            outline: none;
            border-color: #F5A623;
            box-shadow: 0 0 0 3px rgba(245,166,35,0.1);
        }
        .gti-profile-field input:disabled,
        .gti-profile-field textarea:disabled {
            background: #f9fafb;
            color: #9ca3af;
            cursor: not-allowed;
        }
        .gti-profile-field textarea {
            resize: vertical;
            min-height: 80px;
        }
        .gti-profile-field .field-hint {
            font-size: 12px;
            color: #9ca3af;
        }
        .gti-profile-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid #f3f4f6;
        }
        .gti-btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: #F5A623;
            color: #1a1f36;
            border: 1px solid #F5A623;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.15s;
        }
        .gti-btn-primary:hover { background: #e6991a; }
        .gti-btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }
        .gti-btn-secondary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: #fff;
            color: #374151;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.15s;
        }
        .gti-btn-secondary:hover { background: #f9fafb; }

        /* Alert */
        .gti-alert {
            display: none;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 20px;
        }
        .gti-alert.success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .gti-alert.error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

        /* Password card */
        .gti-password-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            max-width: 480px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .gti-ue-stats-row { flex-wrap: wrap; }
            .gti-ue-stat-card { flex: 1 1 calc(50% - 12px); }
            .gti-profile-grid { grid-template-columns: 1fr; }
            .gti-profile-banner { height: 80px; }
            .gti-profile-avatar-wrap { padding: 0 20px; }
            .gti-profile-avatar { width: 80px; height: 80px; }
            .gti-profile-meta-row { padding: 16px 20px 0; }
            .gti-profile-card-body { padding: 20px; }
        }
    </style>
</head>
<body class="gti-body">
    <div class="gti-wrapper">
        <!-- Sidebar -->
        <aside class="gti-sidebar" id="gti-sidebar">
            <div class="gti-sidebar-header">
                <div class="gti-logo">
                    <img src="http://global-tractors.test/wp-content/uploads/2026/07/logo-header-footer-pt-global-tractors-indonesia.png" alt="PT Global Tractors Indonesia" class="gti-logo-img">
                    <img src="http://global-tractors.test/wp-content/uploads/2026/07/cropped-favicon-pt-global-tractors-indonesia.png" alt="GTI" class="gti-logo-favicon">
                </div>
            </div>

            <div class="gti-sidebar-divider"></div>

            <nav class="gti-nav">
                <div class="gti-nav-group">
                    <a href="<?php echo esc_url(gti_dashboard_url()); ?>" class="gti-nav-item">
                        <i class="fas fa-th-large"></i>
                        <span>Dashboard</span>
                    </a>
                </div>

                <div class="gti-nav-group gti-has-children">
                    <div class="gti-nav-section">EQUIPMENT</div>
                    <a href="#" class="gti-nav-parent" data-toggle="dropdown"><i class="fas fa-truck"></i><span>Equipment</span><i class="fas fa-chevron-down gti-nav-arrow"></i></a>
                    <div class="gti-nav-children">
                        <a href="<?php echo esc_url(gti_dashboard_url('used-equipment')); ?>" class="gti-nav-child"><i></i><span>Used Equipment</span></a>
                        <a href="<?php echo esc_url(gti_dashboard_url('rental-equipment')); ?>" class="gti-nav-child"><i></i><span>Rental Equipment</span></a>
                    </div>
                    <a href="<?php echo esc_url(gti_dashboard_url('spare-parts')); ?>" class="gti-nav-item"><i class="fas fa-cog"></i><span>Spare Parts</span></a>
                </div>

                <div class="gti-nav-group">
                    <div class="gti-nav-section">REQUEST &amp; INQUIRY</div>
                    <a href="<?php echo esc_url(gti_dashboard_url('request-equipment')); ?>" class="gti-nav-item"><i class="fas fa-file-alt"></i><span>Request Equipment</span></a>
                    <a href="<?php echo esc_url(gti_dashboard_url('request-quotation')); ?>" class="gti-nav-item"><i class="fas fa-clipboard-list"></i><span>Request Quotation</span></a>
                    <a href="<?php echo esc_url(gti_dashboard_url('sell-equipment')); ?>" class="gti-nav-item"><i class="fas fa-handshake"></i><span>Sell Equipment</span></a>
                    <a href="<?php echo esc_url(gti_dashboard_url('contact-messages')); ?>" class="gti-nav-item"><i class="fas fa-envelope"></i><span>Contact Messages</span></a>
                </div>

                <div class="gti-nav-group">
                    <div class="gti-nav-section">MANAGEMENT</div>
                    <a href="<?php echo esc_url(gti_dashboard_url('customers')); ?>" class="gti-nav-item"><i class="fas fa-users"></i><span>Customers</span></a>
                    <a href="<?php echo esc_url(gti_dashboard_url('news-articles')); ?>" class="gti-nav-item"><i class="fas fa-newspaper"></i><span>News &amp; Articles</span></a>
                    <a href="<?php echo esc_url(gti_dashboard_url('media-library')); ?>" class="gti-nav-item"><i class="fas fa-photo-video"></i><span>Media Library</span></a>
                </div>

                <div class="gti-nav-group">
                    <div class="gti-nav-section">SYSTEM</div>
                    <a href="<?php echo esc_url(gti_dashboard_url('users')); ?>" class="gti-nav-item active"><i class="fas fa-user-shield"></i><span>Users</span></a>
                    <a href="<?php echo esc_url(gti_dashboard_url('website-settings')); ?>" class="gti-nav-item"><i class="fas fa-sliders-h"></i><span>Website Settings</span></a>
                    <a href="<?php echo esc_url(gti_dashboard_url('activity-log')); ?>" class="gti-nav-item"><i class="fas fa-history"></i><span>Activity Log</span></a>
                </div>
            </nav>

            <div class="gti-sidebar-footer">
                <a href="#" class="gti-nav-item" id="gti-collapse-btn">
                    <i class="fas fa-chevron-left"></i>
                    <span>Collapse Menu</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="gti-main" id="gti-main">
            <!-- Header -->
            <header class="gti-header">
                <div class="gti-header-left">
                    <button class="gti-menu-toggle" id="gti-menu-toggle"><i class="fas fa-bars"></i></button>
                    <div>
                        <h1 class="gti-page-title">My Profile</h1>
                        <p class="gti-welcome">Manage your account information <span>&#128100;</span></p>
                    </div>
                </div>
                <div class="gti-header-right">
                    <div class="gti-date-filter">
                        <i class="fas fa-calendar"></i>
                        <span><?php echo date('M j, Y'); ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="gti-notifications">
                        <i class="fas fa-bell"></i>
                        <span class="gti-badge">3</span>
                    </div>
                    <div class="gti-user-menu">
                        <img src="<?php echo esc_url($user_avatar); ?>" alt="Avatar" class="gti-avatar">
                        <div class="gti-user-info">
                            <strong><?php echo esc_html($user_name); ?></strong>
                            <small><?php echo esc_html($role_label); ?></small>
                        </div>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="gti-content">
                <div class="gti-content-left">

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
                                <span><?php echo esc_html(($profile['city'] ?: '') . ($profile['province'] ? ', ' . $profile['province'] : '') ?: '-'); ?></span>
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

                </div>
            </div>
        </main>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Sidebar collapse
        var collapseBtn = document.getElementById('gti-collapse-btn');
        var sidebar = document.getElementById('gti-sidebar');
        var mainEl = document.getElementById('gti-main');

        if (collapseBtn && sidebar) {
            if (localStorage.getItem('gti-sidebar-collapsed') === 'true') {
                sidebar.classList.add('collapsed');
                if (mainEl) mainEl.classList.add('collapsed');
            }
            collapseBtn.addEventListener('click', function(e) {
                e.preventDefault();
                sidebar.classList.toggle('collapsed');
                if (mainEl) mainEl.classList.toggle('collapsed');
                localStorage.setItem('gti-sidebar-collapsed', sidebar.classList.contains('collapsed'));
            });
        }

        // Sidebar dropdown toggle
        document.querySelectorAll('[data-toggle="dropdown"]').forEach(function(toggle) {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                var group = this.closest('.gti-has-children');
                if (group) group.classList.toggle('open');
            });
        });

        // Mobile menu toggle
        var menuToggle = document.getElementById('gti-menu-toggle');
        if (menuToggle) {
            menuToggle.addEventListener('click', function() {
                sidebar.classList.toggle('mobile-open');
            });
        }

        // Profile form
        var profileForm = document.getElementById('gti-profile-form');
        var profileBtn = document.getElementById('gti-profile-btn');
        var profileAlert = document.getElementById('gti-profile-alert');

        if (profileForm) {
            profileForm.addEventListener('submit', function(e) {
                e.preventDefault();
                profileBtn.disabled = true;
                profileBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Saving...</span>';
                profileAlert.style.display = 'none';

                var data = new FormData(profileForm);
                data.append('action', 'gti_update_profile');

                fetch(gtiAjax.ajaxurl, { method: 'POST', body: data })
                    .then(function(r) { return r.json(); })
                    .then(function(res) {
                        profileBtn.disabled = false;
                        profileBtn.innerHTML = '<i class="fas fa-save"></i> <span>Save Changes</span>';
                        profileAlert.style.display = 'block';
                        if (res.success) {
                            profileAlert.className = 'gti-alert success';
                            profileAlert.textContent = res.data.message || 'Profile updated successfully!';
                        } else {
                            profileAlert.className = 'gti-alert error';
                            profileAlert.textContent = res.data.message || 'Failed to update profile.';
                        }
                        setTimeout(function() { profileAlert.style.display = 'none'; }, 5000);
                    })
                    .catch(function() {
                        profileBtn.disabled = false;
                        profileBtn.innerHTML = '<i class="fas fa-save"></i> <span>Save Changes</span>';
                        profileAlert.style.display = 'block';
                        profileAlert.className = 'gti-alert error';
                        profileAlert.textContent = 'Network error. Please try again.';
                    });
            });
        }

        // Password form
        var passwordForm = document.getElementById('gti-password-form');
        var passwordBtn = document.getElementById('gti-password-btn');
        var passwordAlert = document.getElementById('gti-password-alert');

        if (passwordForm) {
            passwordForm.addEventListener('submit', function(e) {
                e.preventDefault();
                var newPass = document.getElementById('gti-new-password').value;
                var confirmPass = document.getElementById('gti-confirm-password').value;

                if (newPass !== confirmPass) {
                    passwordAlert.style.display = 'block';
                    passwordAlert.className = 'gti-alert error';
                    passwordAlert.textContent = 'New passwords do not match.';
                    return;
                }
                if (newPass.length < 8) {
                    passwordAlert.style.display = 'block';
                    passwordAlert.className = 'gti-alert error';
                    passwordAlert.textContent = 'Password must be at least 8 characters.';
                    return;
                }

                passwordBtn.disabled = true;
                passwordBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Updating...</span>';
                passwordAlert.style.display = 'none';

                var data = new FormData(passwordForm);
                data.append('action', 'gti_change_password');

                fetch(gtiAjax.ajaxurl, { method: 'POST', body: data })
                    .then(function(r) { return r.json(); })
                    .then(function(res) {
                        passwordBtn.disabled = false;
                        passwordBtn.innerHTML = '<i class="fas fa-key"></i> <span>Update Password</span>';
                        passwordAlert.style.display = 'block';
                        if (res.success) {
                            passwordAlert.className = 'gti-alert success';
                            passwordAlert.textContent = res.data.message || 'Password updated successfully!';
                            passwordForm.reset();
                        } else {
                            passwordAlert.className = 'gti-alert error';
                            passwordAlert.textContent = res.data.message || 'Failed to update password.';
                        }
                        setTimeout(function() { passwordAlert.style.display = 'none'; }, 5000);
                    })
                    .catch(function() {
                        passwordBtn.disabled = false;
                        passwordBtn.innerHTML = '<i class="fas fa-key"></i> <span>Update Password</span>';
                        passwordAlert.style.display = 'block';
                        passwordAlert.className = 'gti-alert error';
                        passwordAlert.textContent = 'Network error. Please try again.';
                    });
            });
        }
    });
    </script>
</body>
</html>
