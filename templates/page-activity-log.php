<?php
/**
 * Template: Activity Log (/dashboard/activity-log)
 * @package global-tractors
 */
if (!defined('ABSPATH')) exit;
gti_require_login();

$current_user = wp_get_current_user();
$user_name = $current_user->display_name ?: $current_user->user_login;
$user_avatar = get_avatar_url($current_user->ID, ['size' => 80]);

// The activity log table ships in two historic shapes; this creates or patches it.
global $wpdb;
gti_ensure_activity_log_table();
$activity_table = gti_activity_log_table();

// Filters
$search        = isset($_GET['search'])      ? sanitize_text_field($_GET['search'])      : '';
$action_filter = isset($_GET['action_type']) ? sanitize_text_field($_GET['action_type']) : '';
$date_from     = isset($_GET['date_from'])   ? sanitize_text_field($_GET['date_from'])   : '';
$date_to       = isset($_GET['date_to'])     ? sanitize_text_field($_GET['date_to'])     : '';

// Pagination — 'page_num' is used instead of 'paged' because WordPress reserves 'paged'
$paged    = isset($_GET['page_num']) ? max(1, intval($_GET['page_num'])) : 1;
$per_page = 15;
$offset   = ($paged - 1) * $per_page;

// Build query
$where  = "WHERE 1=1";
$params = array();

if ($search) {
    $where .= " AND (al.description LIKE %s OR al.action LIKE %s OR al.entity_type LIKE %s OR u.display_name LIKE %s OR u.user_login LIKE %s)";
    $search_like = '%' . $wpdb->esc_like($search) . '%';
    $params = array_merge($params, array($search_like, $search_like, $search_like, $search_like, $search_like));
}

if ($action_filter) {
    $where .= " AND al.action = %s";
    $params[] = $action_filter;
}

if ($date_from) {
    $where .= " AND al.created_at >= %s";
    $params[] = $date_from . ' 00:00:00';
}

if ($date_to) {
    $where .= " AND al.created_at <= %s";
    $params[] = $date_to . ' 23:59:59';
}

$join = "FROM {$activity_table} al LEFT JOIN {$wpdb->users} u ON al.user_id = u.ID";

// Get total count
$count_query = "SELECT COUNT(*) {$join} {$where}";
$total       = !empty($params) ? (int) $wpdb->get_var($wpdb->prepare($count_query, $params)) : (int) $wpdb->get_var($count_query);
$total_pages = (int) ceil($total / $per_page);

// Get activities
$list_query = "SELECT al.*, u.display_name, u.user_login, u.user_email
               {$join}
               {$where}
               ORDER BY al.created_at DESC
               LIMIT %d OFFSET %d";
$activities = $wpdb->get_results($wpdb->prepare(
    $list_query,
    array_merge($params, array($per_page, $offset))
));

// Get unique actions for filter
$action_types = $wpdb->get_col("SELECT DISTINCT action FROM {$activity_table} WHERE action <> '' ORDER BY action");

// Status counts
$total_activities = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$activity_table}");
$today_count = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$activity_table} WHERE created_at >= %s AND created_at <= %s",
    current_time('Y-m-d') . ' 00:00:00',
    current_time('Y-m-d') . ' 23:59:59'
));
$week_count = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$activity_table} WHERE created_at >= %s",
    date('Y-m-d H:i:s', strtotime(current_time('mysql') . ' -7 days'))
));
$my_count = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$activity_table} WHERE user_id = %d",
    $current_user->ID
));

// Action icon mapping
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
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Log - <?php bloginfo('name'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo GTI_CHILD_URL; ?>/assets/css/dashboard.css">
    <style>
        /* Stats */
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
        .gti-ue-stat-label { font-size: 13px; color: #6b7280; font-weight: 500; }
        .gti-ue-stat-value { font-size: 22px; font-weight: 700; color: #1a1f36; margin-top: 2px; }

        /* Toolbar */
        .gti-ue-toolbar {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .gti-ue-toolbar-left {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
            flex-wrap: wrap;
        }
        .gti-ue-search {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 9px 14px;
            background: #fff;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            min-width: 220px;
            flex: 1;
            max-width: 320px;
        }
        .gti-ue-search i { color: #9ca3af; font-size: 14px; }
        .gti-ue-search input {
            border: none;
            outline: none;
            font-size: 14px;
            font-family: inherit;
            color: #1a1f36;
            width: 100%;
            background: transparent;
        }
        .gti-ue-filter select {
            padding: 9px 12px;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            font-size: 13px;
            font-family: inherit;
            color: #374151;
            background: #fff;
            cursor: pointer;
            appearance: auto;
        }
        .gti-ue-btn-reset {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 9px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            color: #6b7280;
            background: #fff;
            border: 1px solid #d1d5db;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s;
            font-family: inherit;
        }
        .gti-ue-btn-reset:hover { background: #f9fafb; color: #374151; }

        /* Table */
        .gti-ue-table-card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
        }
        .gti-ue-table {
            width: 100%;
            border-collapse: collapse;
        }
        .gti-ue-table thead {
            background: #f9fafb;
        }
        .gti-ue-table thead th {
            padding: 13px 16px;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        .gti-ue-table tbody tr {
            transition: background 0.15s;
            cursor: default;
        }
        .gti-ue-table tbody tr:hover {
            background: #f9fafb;
        }
        .gti-ue-table tbody td {
            padding: 14px 16px;
            font-size: 14px;
            color: #374151;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: middle;
        }
        .gti-ue-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Action icon in table */
        .gti-al-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .gti-al-action-icon {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            flex-shrink: 0;
        }
        .gti-al-action-label {
            font-weight: 500;
            text-transform: capitalize;
        }

        /* User cell */
        .gti-al-user {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .gti-al-user img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }
        .gti-al-user-info strong {
            display: block;
            font-size: 14px;
            color: #1a1f36;
        }
        .gti-al-user-info small {
            color: #9ca3af;
            font-size: 12px;
        }

        /* IP badge */
        .gti-al-ip {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            background: #f3f4f6;
            border-radius: 6px;
            font-size: 12px;
            font-family: 'SF Mono', 'Fira Code', monospace;
            color: #6b7280;
        }

        /* Pagination */
        .gti-ue-pagination {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            border-top: 1px solid #f3f4f6;
        }
        .gti-ue-pagination-info {
            font-size: 13px;
            color: #6b7280;
        }
        .gti-ue-pagination-controls {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .gti-ue-page-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 32px;
            height: 32px;
            padding: 0 8px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            color: #374151;
            background: #fff;
            border: 1px solid #e5e7eb;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s;
        }
        .gti-ue-page-btn:hover:not(:disabled):not(.active) { background: #f9fafb; border-color: #d1d5db; }
        .gti-ue-page-btn.active {
            background: #F5A623;
            color: #1a1f36;
            border-color: #F5A623;
            font-weight: 600;
        }
        .gti-ue-page-btn:disabled { opacity: 0.4; cursor: not-allowed; }
        .gti-ue-page-dots {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 32px;
            height: 32px;
            font-size: 13px;
            color: #9ca3af;
        }

        /* Empty state */
        .gti-al-empty {
            text-align: center;
            padding: 60px 20px;
            color: #9ca3af;
        }
        .gti-al-empty i { font-size: 48px; margin-bottom: 16px; display: block; }
        .gti-al-empty p.title { font-size: 16px; font-weight: 500; margin-bottom: 8px; color: #6b7280; }
        .gti-al-empty p.desc { font-size: 14px; }

        /* Responsive */
        @media (max-width: 1200px) {
            .gti-ue-stats-row { flex-wrap: wrap; }
            .gti-ue-toolbar { flex-direction: column; align-items: flex-start; }
            .gti-ue-toolbar-left { width: 100%; }
            .gti-ue-search { max-width: 100%; }
        }
        @media (max-width: 768px) {
            .gti-ue-stats-row .gti-ue-stat-card { flex: 1 1 calc(50% - 12px); }
            .gti-ue-toolbar-left { flex-direction: column; }
            .gti-ue-search { width: 100%; min-width: auto; }
            .gti-ue-filter select { width: 100%; }
            .gti-ue-table-card { overflow-x: auto; }
            .gti-ue-pagination { flex-direction: column; gap: 12px; }
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
                    <a href="<?php echo esc_url(gti_dashboard_url('users')); ?>" class="gti-nav-item"><i class="fas fa-user-shield"></i><span>Users</span></a>
                    <a href="<?php echo esc_url(gti_dashboard_url('website-settings')); ?>" class="gti-nav-item"><i class="fas fa-sliders-h"></i><span>Website Settings</span></a>
                    <a href="<?php echo esc_url(gti_dashboard_url('activity-log')); ?>" class="gti-nav-item active"><i class="fas fa-history"></i><span>Activity Log</span></a>
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
                        <h1 class="gti-page-title">Activity Log</h1>
                        <p class="gti-welcome">Track all system activities and user actions <span>&#128269;</span></p>
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
                            <small>Super Admin</small>
                        </div>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="gti-content">
                <div class="gti-content-left">

                    <!-- Statistics Cards -->
                    <div class="gti-ue-stats-row">
                        <div class="gti-ue-stat-card">
                            <div class="gti-ue-stat-icon primary"><i class="fas fa-list"></i></div>
                            <div class="gti-ue-stat-info">
                                <p class="gti-ue-stat-label">All Activities</p>
                                <p class="gti-ue-stat-value"><?php echo esc_html($total_activities); ?></p>
                            </div>
                        </div>
                        <div class="gti-ue-stat-card">
                            <div class="gti-ue-stat-icon success"><i class="fas fa-calendar-day"></i></div>
                            <div class="gti-ue-stat-info">
                                <p class="gti-ue-stat-label">Today</p>
                                <p class="gti-ue-stat-value"><?php echo esc_html($today_count); ?></p>
                            </div>
                        </div>
                        <div class="gti-ue-stat-card">
                            <div class="gti-ue-stat-icon info"><i class="fas fa-calendar-week"></i></div>
                            <div class="gti-ue-stat-info">
                                <p class="gti-ue-stat-label">Last 7 Days</p>
                                <p class="gti-ue-stat-value"><?php echo esc_html($week_count); ?></p>
                            </div>
                        </div>
                        <div class="gti-ue-stat-card">
                            <div class="gti-ue-stat-icon warning"><i class="fas fa-user"></i></div>
                            <div class="gti-ue-stat-info">
                                <p class="gti-ue-stat-label">My Activities</p>
                                <p class="gti-ue-stat-value"><?php echo esc_html($my_count); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Toolbar -->
                    <form class="gti-ue-toolbar" method="get">
                        <input type="hidden" name="gti_page" value="activity-log">
                        <div class="gti-ue-toolbar-left">
                            <div class="gti-ue-search">
                                <i class="fas fa-search"></i>
                                <input type="text" name="search" placeholder="Search activities..." value="<?php echo esc_attr($search); ?>">
                            </div>
                            <div class="gti-ue-filter">
                                <select name="action_type">
                                    <option value="">All Actions</option>
                                    <?php foreach ($action_types as $at): ?>
                                        <option value="<?php echo esc_attr($at); ?>" <?php selected($action_filter, $at); ?>><?php echo esc_html(ucwords(str_replace('_', ' ', $at))); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="gti-ue-filter">
                                <input type="date" name="date_from" value="<?php echo esc_attr($date_from); ?>" style="padding:9px 12px;border-radius:8px;border:1px solid #d1d5db;font-size:13px;font-family:inherit;color:#374151;background:#fff;" title="From date">
                            </div>
                            <div class="gti-ue-filter">
                                <input type="date" name="date_to" value="<?php echo esc_attr($date_to); ?>" style="padding:9px 12px;border-radius:8px;border:1px solid #d1d5db;font-size:13px;font-family:inherit;color:#374151;background:#fff;" title="To date">
                            </div>
                            <a href="<?php echo esc_url(gti_dashboard_url('activity-log')); ?>" class="gti-ue-btn-reset">
                                <i class="fas fa-rotate-right"></i> Reset
                            </a>
                        </div>
                    </form>

                    <!-- Table -->
                    <div class="gti-ue-table-card">
                        <?php // The table is created/patched on load, so it always renders. ?>
                            <table class="gti-ue-table">
                                <thead>
                                    <tr>
                                        <th style="width:180px;">User</th>
                                        <th style="width:170px;">Action</th>
                                        <th>Description</th>
                                        <th style="width:150px;">Entity</th>
                                        <th style="width:120px;">IP Address</th>
                                        <th style="width:160px;">Date &amp; Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($activities)): ?>
                                        <tr>
                                            <td colspan="6" style="text-align:center; padding:60px 20px;">
                                                <div class="gti-al-empty">
                                                    <i class="fas fa-inbox"></i>
                                                    <p class="title">No activities found</p>
                                                    <p class="desc">
                                                        <?php echo ($search || $action_filter || $date_from || $date_to) ? 'Try adjusting your filters' : 'No activity recorded yet'; ?>
                                                    </p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($activities as $log): ?>
                                            <?php
                                            $action_style  = gti_get_action_icon($log->action);
                                            $is_guest      = empty($log->user_id) || !$log->user_login;
                                            $display_name  = $log->display_name ?: $log->user_login ?: ($is_guest ? 'Guest / System' : 'Deleted user');
                                            $activity_date = date('M j, Y', strtotime($log->created_at));
                                            $activity_time = date('H:i:s', strtotime($log->created_at));
                                            // Rows written before this page had a description column still render text.
                                            $description   = gti_activity_row_description($log);
                                            $entity_label  = !empty($log->entity_type) ? gti_activity_entity_label($log->entity_type) : '';
                                            ?>
                                            <tr>
                                                <td>
                                                    <div class="gti-al-user">
                                                        <?php if ($is_guest): ?>
                                                            <div class="gti-al-action-icon" style="width:32px;height:32px;border-radius:50%;background:#f3f4f6;color:#9ca3af;"><i class="fas fa-user-secret"></i></div>
                                                        <?php else: ?>
                                                            <?php echo get_avatar($log->user_id, 32); ?>
                                                        <?php endif; ?>
                                                        <div class="gti-al-user-info">
                                                            <strong><?php echo esc_html($display_name); ?></strong>
                                                            <small><?php echo $log->user_login ? '@' . esc_html($log->user_login) : 'not signed in'; ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="gti-al-action">
                                                        <div class="gti-al-action-icon" style="background:<?php echo esc_attr($action_style['bg']); ?>; color:<?php echo esc_attr($action_style['color']); ?>;">
                                                            <i class="fas <?php echo esc_attr($action_style['icon']); ?>"></i>
                                                        </div>
                                                        <span class="gti-al-action-label"><?php echo esc_html(ucwords(str_replace('_', ' ', $log->action))); ?></span>
                                                    </div>
                                                </td>
                                                <td style="max-width:300px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?php echo esc_attr($description); ?>">
                                                    <?php echo esc_html($description ?: '-'); ?>
                                                </td>
                                                <td>
                                                    <?php if ($entity_label): ?>
                                                        <span class="gti-al-ip" style="background:#eef2ff;color:#4338ca;">
                                                            <?php echo esc_html($entity_label); ?><?php echo !empty($log->entity_id) ? ' #' . (int) $log->entity_id : ''; ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span style="color:#9ca3af;">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($log->ip_address)): ?>
                                                        <span class="gti-al-ip"><i class="fas fa-globe" style="font-size:10px;"></i> <?php echo esc_html($log->ip_address); ?></span>
                                                    <?php else: ?>
                                                        <span style="color:#9ca3af;">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div style="font-size:14px; color:#1a1f36; font-weight:500;"><?php echo esc_html($activity_date); ?></div>
                                                    <div style="font-size:12px; color:#9ca3af;"><?php echo esc_html($activity_time); ?></div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>

                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                                <div class="gti-ue-pagination">
                                    <div class="gti-ue-pagination-info">
                                        Showing <?php echo esc_html($offset + 1); ?>-<?php echo esc_html(min($offset + $per_page, $total)); ?> of <?php echo esc_html($total); ?> entries
                                    </div>
                                    <div class="gti-ue-pagination-controls">
                                        <?php
                                        $query_params = array('gti_page' => 'activity-log');
                                        if ($search) $query_params['search'] = $search;
                                        if ($action_filter) $query_params['action_type'] = $action_filter;
                                        if ($date_from) $query_params['date_from'] = $date_from;
                                        if ($date_to) $query_params['date_to'] = $date_to;
                                        $base_url = gti_dashboard_url('activity-log');
                                        ?>

                                        <?php if ($paged > 1): ?>
                                            <a href="<?php echo esc_url($base_url . '?' . http_build_query(array_merge($query_params, ['page_num' => $paged - 1]))); ?>" class="gti-ue-page-btn"><i class="fas fa-chevron-left"></i></a>
                                        <?php else: ?>
                                            <button class="gti-ue-page-btn" disabled><i class="fas fa-chevron-left"></i></button>
                                        <?php endif; ?>

                                        <?php
                                        $start = max(1, $paged - 2);
                                        $end = min($total_pages, $paged + 2);
                                        if ($start > 1): ?>
                                            <a href="<?php echo esc_url($base_url . '?' . http_build_query(array_merge($query_params, ['page_num' => 1]))); ?>" class="gti-ue-page-btn">1</a>
                                            <?php if ($start > 2): ?>
                                                <span class="gti-ue-page-dots">...</span>
                                            <?php endif; ?>
                                        <?php endif; ?>

                                        <?php for ($i = $start; $i <= $end; $i++): ?>
                                            <?php if ($i == $paged): ?>
                                                <button class="gti-ue-page-btn active"><?php echo $i; ?></button>
                                            <?php else: ?>
                                                <a href="<?php echo esc_url($base_url . '?' . http_build_query(array_merge($query_params, ['page_num' => $i]))); ?>" class="gti-ue-page-btn"><?php echo $i; ?></a>
                                            <?php endif; ?>
                                        <?php endfor; ?>

                                        <?php if ($end < $total_pages): ?>
                                            <?php if ($end < $total_pages - 1): ?>
                                                <span class="gti-ue-page-dots">...</span>
                                            <?php endif; ?>
                                            <a href="<?php echo esc_url($base_url . '?' . http_build_query(array_merge($query_params, ['page_num' => $total_pages]))); ?>" class="gti-ue-page-btn"><?php echo $total_pages; ?></a>
                                        <?php endif; ?>

                                        <?php if ($paged < $total_pages): ?>
                                            <a href="<?php echo esc_url($base_url . '?' . http_build_query(array_merge($query_params, ['page_num' => $paged + 1]))); ?>" class="gti-ue-page-btn"><i class="fas fa-chevron-right"></i></a>
                                        <?php else: ?>
                                            <button class="gti-ue-page-btn" disabled><i class="fas fa-chevron-right"></i></button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
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
    });
    </script>
</body>
</html>
