<?php
/**
 * Template: Request Equipment (/dashboard/request-equipment)
 * @package global-tractors
 */
if (!defined('ABSPATH')) exit;
gti_require_login();

$current_user = wp_get_current_user();
$user_name = $current_user->display_name ?: $current_user->user_login;
$user_avatar = get_avatar_url($current_user->ID, ['size' => 80]);

// Get request data from database
global $wpdb;
$table_name = $wpdb->prefix . 'gti_requests';

// Filters
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$brand_filter = isset($_GET['brand']) ? sanitize_text_field($_GET['brand']) : '';
$category_filter = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';
$location_filter = isset($_GET['location']) ? sanitize_text_field($_GET['location']) : '';

// Pagination
$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 10;
$offset = ($paged - 1) * $per_page;

// Build query
$where = "WHERE 1=1";
$params = array();

if ($search) {
    $where .= " AND (request_id LIKE %s OR customer_name LIKE %s OR equipment LIKE %s OR customer_company LIKE %s)";
    $search_like = '%' . $wpdb->esc_like($search) . '%';
    $params = array_merge($params, array($search_like, $search_like, $search_like, $search_like));
}

if ($status_filter) {
    $where .= " AND status = %s";
    $params[] = $status_filter;
}
if ($brand_filter) {
    $where .= " AND brand = %s";
    $params[] = $brand_filter;
}
if ($category_filter) {
    $where .= " AND category = %s";
    $params[] = $category_filter;
}
if ($location_filter) {
    $where .= " AND location = %s";
    $params[] = $location_filter;
}

// Get total count
$count_query = "SELECT COUNT(*) FROM {$table_name} {$where}";
$total = !empty($params) ? $wpdb->get_var($wpdb->prepare($count_query, $params)) : $wpdb->get_var($count_query);
$total_pages = ceil($total / $per_page);

// Get requests
if (!empty($params)) {
    $requests = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$table_name} {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d",
        array_merge($params, array($per_page, $offset))
    ));
} else {
    $requests = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$table_name} ORDER BY created_at DESC LIMIT %d OFFSET %d",
        $per_page, $offset
    ));
}

// Get status counts
$status_counts = $wpdb->get_results(
    "SELECT status, COUNT(*) as count FROM {$table_name} GROUP BY status"
);
$status_count_map = array();
foreach ($status_counts as $sc) {
    $status_count_map[$sc->status] = $sc->count;
}
$total_requests = array_sum(array_column($status_counts, 'count'));

// Filter options
$brands = $wpdb->get_col("SELECT DISTINCT brand FROM {$table_name} WHERE brand != '' ORDER BY brand");
$categories = $wpdb->get_col("SELECT DISTINCT category FROM {$table_name} WHERE category != '' ORDER BY category");
$locations = $wpdb->get_col("SELECT DISTINCT location FROM {$table_name} WHERE location != '' ORDER BY location");
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Equipment - <?php bloginfo('name'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo GTI_CHILD_URL; ?>/assets/css/dashboard.css">
    <style>
        /* Border overrides to match spare-parts style */
        .gti-ue-stat-card { border: 1px solid #e5e7eb; }
        .gti-ue-search { border: 1px solid #d1d5db; }
        .gti-ue-filter select { border: 1px solid #d1d5db; }
        .gti-ue-btn-reset { border: 1px solid #d1d5db; text-decoration: none; }
        .gti-ue-btn-reset:hover { border-color: #d1d5db; }
        .gti-ue-table-card { border: 1px solid #e5e7eb; overflow: hidden; }
        .gti-ue-action-toggle { border: 1px solid #e5e7eb; }
        .gti-ue-action-toggle:hover { border-color: #d1d5db; }
        .gti-ue-action-dropdown { border: 1px solid #e5e7eb; }
        .gti-ue-action-item { cursor: pointer; border: none; background: none; width: 100%; text-align: left; font-family: inherit; }
        .gti-ue-page-btn { border: 1px solid #e5e7eb; }
        .gti-ue-page-btn:hover:not(:disabled) { border-color: #d1d5db; }
        .gti-ue-table thead th { font-size: 13px; font-weight: 600; color: #374151; text-transform: none; letter-spacing: normal; padding: 13px 16px; }
        .gti-ue-table td { font-size: 14px; color: #374151; padding: 14px 16px; }

        /* Table column widths */
        .gti-ue-table { width: 100%; table-layout: fixed; }
        .gti-ue-table .col-reqid { width: 110px; }
        .gti-ue-table .col-customer { width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .gti-ue-table .col-equipment { width: 160px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .gti-ue-table .col-qty { width: 70px; text-align: center; }
        .gti-ue-table .col-location { width: 110px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .gti-ue-table .col-budget { width: 100px; text-align: right; white-space: nowrap; }
        .gti-ue-table .col-status { width: 120px; text-align: center; white-space: nowrap; }
        .gti-ue-table .col-date { width: 110px; white-space: nowrap; }
        .gti-ue-table .col-actions { width: 70px; text-align: center; }

        /* ====== Content Layout ====== */
        .gti-content {
            display: flex;
            gap: 24px;
            align-items: flex-start;
        }
        .gti-content-left {
            flex: 1;
            min-width: 0;
        }

        /* ====== Right Drawer — In-Flow (≥1600px) ====== */
        .gti-drawer {
            width: 320px;
            max-width: calc(33vw - 100px);
            flex-shrink: 0;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            height: fit-content;
        }
        .gti-drawer-body {
            overflow-y: visible;
        }
        /* Hide close button when in-flow */
        .gti-drawer .gti-drawer-close {
            display: none;
        }

        /* Prevent main from overflowing */
        .gti-main {
            overflow: hidden;
            min-width: 0;
        }

        /* ====== Drawer Backdrop (only for <1200px) ====== */
        .gti-drawer-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.4);
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .gti-drawer-backdrop.show {
            display: block;
            opacity: 1;
        }

        /* ====== Fixed Drawer Mode (<1600px) ====== */
        @media (max-width: 1599px) {
            .gti-content {
                display: block;
            }
            .gti-drawer {
                position: fixed;
                top: 0;
                right: 0;
                bottom: 0;
                left: auto;
                width: 420px;
                max-width: 100vw;
                max-height: 100vh;
                border-radius: 12px 0 0 12px;
                border: none;
                box-shadow: -4px 0 24px rgba(0,0,0,0.15);
                z-index: 1001;
                transform: translateX(100%);
                transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                height: auto;
            }
            .gti-drawer.open {
                transform: translateX(0);
            }
            .gti-drawer .gti-drawer-close {
                display: flex;
            }
            .gti-drawer-body {
                overflow-y: auto;
                flex: 1;
            }
        }
        @media (max-width: 560px) {
            .gti-drawer {
                width: 100%;
                border-radius: 16px 16px 0 0;
                top: auto;
                max-height: 85vh;
                transform: translateY(100%);
            }
            .gti-drawer.open {
                transform: translateY(0);
            }
        }

        /* ====== Drawer Header ====== */
        .gti-drawer-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 24px;
            border-bottom: 1px solid #e5e7eb;
            flex-shrink: 0;
        }
        .gti-drawer-header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .gti-drawer-header-left h2 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            color: #1a1f36;
        }
        .gti-drawer-status {
            display: inline-flex;
            align-items: center;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .gti-drawer-status.status-new { background: #dbeafe; color: #1d4ed8; }
        .gti-drawer-status.status-processing { background: #fef3c7; color: #b45309; }
        .gti-drawer-status.status-proposal_sent { background: #e0e7ff; color: #4338ca; }
        .gti-drawer-status.status-closed { background: #d1fae5; color: #047857; }
        .gti-drawer-close {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
            background: #fff;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6b7280;
            font-size: 14px;
            transition: all 0.15s;
            flex-shrink: 0;
        }
        .gti-drawer-close:hover { background: #f3f4f6; }

        /* ====== Drawer Body ====== */
        .gti-drawer-body {
            flex: 1;
            overflow-y: auto;
            padding: 24px;
        }

        /* ====== Drawer Sections ====== */
        .gti-drawer-section {
            margin-bottom: 24px;
        }
        .gti-drawer-section:last-child {
            margin-bottom: 0;
        }
        .gti-drawer-section-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 14px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f3f4f6;
        }
        .gti-drawer-section-title i {
            color: #F5A623;
            font-size: 14px;
        }

        /* ====== Drawer Detail Rows ====== */
        .gti-drawer-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 7px 0;
            gap: 12px;
        }
        .gti-drawer-row + .gti-drawer-row {
            border-top: 1px solid #f9fafb;
        }
        .gti-drawer-label {
            font-size: 13px;
            color: #9ca3af;
            flex-shrink: 0;
            min-width: 100px;
        }
        .gti-drawer-value {
            font-size: 13px;
            font-weight: 500;
            color: #1a1f36;
            text-align: right;
            word-break: break-word;
        }
        .gti-drawer-value.is-link {
            color: #2563eb;
        }
        .gti-drawer-value.is-message {
            text-align: left;
            background: #f9fafb;
            padding: 10px 12px;
            border-radius: 8px;
            font-weight: 400;
            color: #374151;
            line-height: 1.5;
            width: 100%;
            margin-top: 4px;
        }

        /* ====== Timeline / Activity Log ====== */
        .gti-drawer-timeline {
            position: relative;
            padding-left: 28px;
        }
        .gti-drawer-timeline::before {
            content: '';
            position: absolute;
            left: 9px;
            top: 6px;
            bottom: 6px;
            width: 2px;
            background: #e5e7eb;
            border-radius: 1px;
        }
        .gti-drawer-timeline-item {
            position: relative;
            padding-bottom: 20px;
        }
        .gti-drawer-timeline-item:last-child {
            padding-bottom: 0;
        }
        /* Default dot: Past / Completed events — small slate gray */
        .gti-drawer-timeline-dot {
            position: absolute;
            left: -18px;
            top: 5px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #6B7280;
            transform: translateX(-50%);
        }
        /* Active dot: Current / Latest event — gold with stacked gray base */
        .gti-drawer-timeline-dot.is-active {
            width: 14px;
            height: 14px;
            top: 2px;
            left: -18px;
            background: #F59E0B;
            z-index: 1;
        }
        .gti-drawer-timeline-dot.is-active::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            margin: auto;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #000;
            z-index: -1;
        }
        .gti-drawer-timeline-event {
            font-size: 13px;
            font-weight: 500;
            color: #1a1f36;
        }
        .gti-drawer-timeline-item:last-child .gti-drawer-timeline-event {
            font-weight: 600;
            color: #111827;
        }
        .gti-drawer-timeline-meta {
            font-size: 12px;
            color: #9ca3af;
            margin-top: 2px;
        }
        .gti-drawer-timeline-item:last-child .gti-drawer-timeline-meta {
            color: #6B7280;
        }

        /* ====== Drawer Footer ====== */
        .gti-drawer-footer {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 16px 24px;
            border-top: 1px solid #e5e7eb;
            flex-shrink: 0;
            background: #fff;
        }

        /* ====== Active Row Highlight ====== */
        .gti-ue-table tbody tr.active-row {
            background: #FFF8EC;
        }
        .gti-ue-table tbody tr {
            cursor: pointer;
            transition: background 0.15s;
        }
        .gti-ue-table tbody tr:hover {
            background: #f9fafb;
        }
        .gti-drawer-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 9px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.15s;
            border: 1px solid #e5e7eb;
            background: #fff;
            color: #374151;
            font-family: inherit;
        }
        .gti-drawer-btn:hover { background: #f9fafb; }
        .gti-drawer-btn-primary {
            background: #F5A623;
            color: #1a1f36;
            border-color: #F5A623;
            font-weight: 600;
        }
        .gti-drawer-btn-primary:hover { background: #e6991a; }
        .gti-drawer-btn-icon {
            width: 36px;
            height: 36px;
            padding: 0;
            justify-content: center;
        }
        .gti-drawer-footer-spacer { flex: 1; }
        .gti-drawer-footer .gti-drawer-btn-group {
            display: flex;
            gap: 6px;
            position: relative;
        }
        .gti-drawer-footer .gti-drawer-btn-group .gti-drawer-dropdown {
            display: none;
            position: absolute;
            bottom: calc(100% + 6px);
            right: 0;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
            min-width: 180px;
            z-index: 10;
            overflow: hidden;
        }
        .gti-drawer-footer .gti-drawer-btn-group .gti-drawer-dropdown.show { display: block; }
        .gti-drawer-dropdown-item {
            display: flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            padding: 10px 14px;
            border: none;
            background: none;
            font-size: 13px;
            color: #374151;
            cursor: pointer;
            font-family: inherit;
            text-align: left;
        }
        .gti-drawer-dropdown-item:hover { background: #f9fafb; }
        .gti-drawer-dropdown-item i { width: 16px; color: #6b7280; }

        /* Responsive */
        @media (max-width: 1200px) {
            .gti-ue-stats-row {
                flex-wrap: wrap;
            }
            .gti-ue-toolbar {
                flex-direction: column;
                align-items: flex-start;
            }
            .gti-ue-toolbar-left {
                width: 100%;
            }
            .gti-ue-search input {
                width: 100%;
            }
        }
        @media (max-width: 768px) {
            .gti-ue-stats-row .gti-ue-stat-card {
                flex: 1 1 calc(50% - 12px);
            }
            .gti-ue-toolbar-left {
                flex-direction: column;
            }
            .gti-ue-search {
                width: 100%;
            }
            .gti-ue-search input {
                width: 100%;
            }
            .gti-ue-filter {
                width: 100%;
            }
            .gti-ue-filter select {
                width: 100%;
            }
            .gti-ue-table-card {
                overflow-x: auto;
            }
            .gti-drawer-footer {
                flex-wrap: wrap;
            }
            .gti-drawer-footer-spacer {
                display: none;
            }
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
                    <a href="<?php echo esc_url(gti_dashboard_url('request-equipment')); ?>" class="gti-nav-item active"><i class="fas fa-file-alt"></i><span>Request Equipment</span></a>
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
                        <h1 class="gti-page-title">Request Equipment</h1>
                        <p class="gti-welcome">Manage equipment requests from customers <span>&#128203;</span></p>
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
                        <div class="gti-ue-stat-icon"><i class="fas fa-file-alt"></i></div>
                        <div class="gti-ue-stat-info">
                            <p class="gti-ue-stat-label">All Requests</p>
                            <p class="gti-ue-stat-value"><?php echo esc_html($total_requests); ?></p>
                        </div>
                    </div>
                    <div class="gti-ue-stat-card">
                        <div class="gti-ue-stat-icon available"><i class="fas fa-plus-circle"></i></div>
                        <div class="gti-ue-stat-info">
                            <p class="gti-ue-stat-label">New</p>
                            <p class="gti-ue-stat-value"><?php echo esc_html($status_count_map['new'] ?? 0); ?></p>
                        </div>
                    </div>
                    <div class="gti-ue-stat-card">
                        <div class="gti-ue-stat-icon reserved"><i class="fas fa-spinner"></i></div>
                        <div class="gti-ue-stat-info">
                            <p class="gti-ue-stat-label">Processing</p>
                            <p class="gti-ue-stat-value"><?php echo esc_html($status_count_map['processing'] ?? 0); ?></p>
                        </div>
                    </div>
                    <div class="gti-ue-stat-card">
                        <div class="gti-ue-stat-icon available"><i class="fas fa-paper-plane"></i></div>
                        <div class="gti-ue-stat-info">
                            <p class="gti-ue-stat-label">Proposal Sent</p>
                            <p class="gti-ue-stat-value"><?php echo esc_html($status_count_map['proposal_sent'] ?? 0); ?></p>
                        </div>
                    </div>
                    <div class="gti-ue-stat-card">
                        <div class="gti-ue-stat-icon sold"><i class="fas fa-check-circle"></i></div>
                        <div class="gti-ue-stat-info">
                            <p class="gti-ue-stat-label">Closed</p>
                            <p class="gti-ue-stat-value"><?php echo esc_html($status_count_map['closed'] ?? 0); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Toolbar -->
                <form class="gti-ue-toolbar" method="get">
                    <input type="hidden" name="gti_page" value="request-equipment">
                    <div class="gti-ue-toolbar-left">
                        <div class="gti-ue-search">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" placeholder="Search requests..." value="<?php echo esc_attr($search); ?>">
                        </div>
                        <div class="gti-ue-filter">
                            <select name="status">
                                <option value="">All Status</option>
                                <option value="new" <?php selected($status_filter, 'new'); ?>>New</option>
                                <option value="processing" <?php selected($status_filter, 'processing'); ?>>Processing</option>
                                <option value="proposal_sent" <?php selected($status_filter, 'proposal_sent'); ?>>Proposal Sent</option>
                                <option value="closed" <?php selected($status_filter, 'closed'); ?>>Closed</option>
                            </select>
                        </div>
                        <div class="gti-ue-filter">
                            <select name="brand">
                                <option value="">All Brands</option>
                                <?php foreach ($brands as $b): ?>
                                    <option value="<?php echo esc_attr($b); ?>" <?php selected($brand_filter, $b); ?>><?php echo esc_html($b); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="gti-ue-filter">
                            <select name="category">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo esc_attr($cat); ?>" <?php selected($category_filter, $cat); ?>><?php echo esc_html($cat); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="gti-ue-filter">
                            <select name="location">
                                <option value="">All Locations</option>
                                <?php foreach ($locations as $loc): ?>
                                    <option value="<?php echo esc_attr($loc); ?>" <?php selected($location_filter, $loc); ?>><?php echo esc_html($loc); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <a href="<?php echo esc_url(gti_dashboard_url('request-equipment')); ?>" class="gti-ue-btn-reset">
                            <i class="fas fa-rotate-right"></i> Reset
                        </a>
                    </div>
                </form>

                <!-- Table -->
                <div class="gti-ue-table-card">
                    <table class="gti-ue-table">
                        <thead>
                            <tr>
                                <th class="col-reqid">Request ID</th>
                                <th class="col-customer">Customer</th>
                                <th class="col-equipment">Requested Equipment</th>
                                <th class="col-qty">Quantity</th>
                                <th class="col-location">Location</th>
                                <th class="col-budget">Budget</th>
                                <th class="col-status">Status</th>
                                <th class="col-date">Request Date</th>
                                <th class="col-actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($requests)): ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 60px 20px;">
                                        <div style="color: #9ca3af;">
                                            <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 16px; display: block;"></i>
                                            <p style="font-size: 16px; font-weight: 500; margin-bottom: 8px;">No requests found</p>
                                            <p style="font-size: 14px;">
                                                <?php echo ($search || $status_filter) ? 'Try adjusting your filters' : 'No equipment requests yet'; ?>
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($requests as $req): ?>
                                    <tr data-req-id="<?php echo esc_attr($req->id); ?>" data-req='<?php echo esc_attr(json_encode($req)); ?>'>
                                        <td class="col-reqid"><strong><?php echo esc_html($req->request_id); ?></strong></td>
                                        <td class="col-customer">
                                            <strong style="color: #1a1f36;"><?php echo esc_html($req->customer_name); ?></strong>
                                            <?php if (!empty($req->customer_company)): ?>
                                                <br><small style="color: #9ca3af; font-size: 12px;"><?php echo esc_html($req->customer_company); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="col-equipment"><?php echo esc_html($req->equipment); ?></td>
                                        <td class="col-qty"><?php echo esc_html($req->quantity); ?> Units</td>
                                        <td class="col-location"><?php echo esc_html($req->location); ?></td>
                                        <td class="col-budget"><?php echo esc_html($req->budget ?: '-'); ?></td>
                                        <td class="col-status">
                                            <?php
                                            $status_class = 'available';
                                            if ($req->status === 'new') {
                                                $status_class = 'available';
                                            } elseif ($req->status === 'processing') {
                                                $status_class = 'reserved';
                                            } elseif ($req->status === 'proposal_sent') {
                                                $status_class = 'available';
                                            } elseif ($req->status === 'closed') {
                                                $status_class = 'sold';
                                            }
                                            $status_label = ucwords(str_replace('_', ' ', $req->status));
                                            ?>
                                            <span class="gti-badge-status <?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_label); ?></span>
                                        </td>
                                        <td class="col-date"><?php echo esc_html(date('M j, Y', strtotime($req->request_date ?: $req->created_at))); ?></td>
                                        <td class="col-actions">
                                            <div class="gti-ue-action-menu">
                                                <button class="gti-ue-action-toggle" title="Actions"><i class="fas fa-ellipsis-v"></i></button>
                                                <div class="gti-ue-action-dropdown">
                                                    <button type="button" class="gti-ue-action-item" onclick='showRequestDetail(<?php echo json_encode($req); ?>)'>
                                                        <i class="fas fa-eye"></i> View Details
                                                    </button>
                                                    <button type="button" class="gti-ue-action-item" onclick="updateStatus(<?php echo esc_attr($req->id); ?>, 'processing')">
                                                        <i class="fas fa-spinner"></i> Mark Processing
                                                    </button>
                                                    <button type="button" class="gti-ue-action-item" onclick="updateStatus(<?php echo esc_attr($req->id); ?>, 'proposal_sent')">
                                                        <i class="fas fa-paper-plane"></i> Send Proposal
                                                    </button>
                                                    <button type="button" class="gti-ue-action-item" onclick="updateStatus(<?php echo esc_attr($req->id); ?>, 'closed')">
                                                        <i class="fas fa-check"></i> Close Request
                                                    </button>
                                                </div>
                                            </div>
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
                                $query_params = array();
                                if ($search) $query_params['search'] = $search;
                                if ($status_filter) $query_params['status'] = $status_filter;
                                $query_params['gti_page'] = 'request-equipment';
                                $base_url = gti_dashboard_url('request-equipment');
                                ?>

                                <?php if ($paged > 1): ?>
                                    <a href="<?php echo esc_url($base_url . '?' . http_build_query(array_merge($query_params, ['paged' => $paged - 1]))); ?>" class="gti-ue-page-btn"><i class="fas fa-chevron-left"></i></a>
                                <?php else: ?>
                                    <button class="gti-ue-page-btn" disabled><i class="fas fa-chevron-left"></i></button>
                                <?php endif; ?>

                                <?php
                                $start = max(1, $paged - 2);
                                $end = min($total_pages, $paged + 2);
                                if ($start > 1): ?>
                                    <a href="<?php echo esc_url($base_url . '?' . http_build_query(array_merge($query_params, ['paged' => 1]))); ?>" class="gti-ue-page-btn">1</a>
                                    <?php if ($start > 2): ?>
                                        <span class="gti-ue-page-dots">...</span>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php for ($i = $start; $i <= $end; $i++): ?>
                                    <?php if ($i == $paged): ?>
                                        <button class="gti-ue-page-btn active"><?php echo $i; ?></button>
                                    <?php else: ?>
                                        <a href="<?php echo esc_url($base_url . '?' . http_build_query(array_merge($query_params, ['paged' => $i]))); ?>" class="gti-ue-page-btn"><?php echo $i; ?></a>
                                    <?php endif; ?>
                                <?php endfor; ?>

                                <?php if ($end < $total_pages): ?>
                                    <?php if ($end < $total_pages - 1): ?>
                                        <span class="gti-ue-page-dots">...</span>
                                    <?php endif; ?>
                                    <a href="<?php echo esc_url($base_url . '?' . http_build_query(array_merge($query_params, ['paged' => $total_pages]))); ?>" class="gti-ue-page-btn"><?php echo $total_pages; ?></a>
                                <?php endif; ?>

                                <?php if ($paged < $total_pages): ?>
                                    <a href="<?php echo esc_url($base_url . '?' . http_build_query(array_merge($query_params, ['paged' => $paged + 1]))); ?>" class="gti-ue-page-btn"><i class="fas fa-chevron-right"></i></a>
                                <?php else: ?>
                                    <button class="gti-ue-page-btn" disabled><i class="fas fa-chevron-right"></i></button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                </div><!-- /.gti-content-left -->

                <!-- Detail Drawer (In-Flow ≥1200px / Fixed <1200px) -->
                <div class="gti-drawer-backdrop" id="drawerBackdrop"></div>
                <div class="gti-drawer" id="reqDetailDrawer">
        <!-- Header -->
        <div class="gti-drawer-header">
            <div class="gti-drawer-header-left">
                <h2 id="drawer-req-id">REQ-0000-0000</h2>
                <span class="gti-drawer-status" id="drawer-status-badge">New</span>
            </div>
            <button type="button" class="gti-drawer-close" onclick="closeDetailDrawer()">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Body -->
        <div class="gti-drawer-body">
            <!-- Customer Information -->
            <div class="gti-drawer-section">
                <div class="gti-drawer-section-title">
                    <i class="fas fa-user"></i> Customer Information
                </div>
                <div class="gti-drawer-row">
                    <span class="gti-drawer-label">Name</span>
                    <span class="gti-drawer-value" id="drawer-customer-name">-</span>
                </div>
                <div class="gti-drawer-row">
                    <span class="gti-drawer-label">Company</span>
                    <span class="gti-drawer-value" id="drawer-company">-</span>
                </div>
                <div class="gti-drawer-row">
                    <span class="gti-drawer-label">Email</span>
                    <span class="gti-drawer-value is-link" id="drawer-email">-</span>
                </div>
                <div class="gti-drawer-row">
                    <span class="gti-drawer-label">Phone</span>
                    <span class="gti-drawer-value" id="drawer-phone">-</span>
                </div>
                <div class="gti-drawer-row">
                    <span class="gti-drawer-label">Address</span>
                    <span class="gti-drawer-value" id="drawer-address">-</span>
                </div>
            </div>

            <!-- Request Information -->
            <div class="gti-drawer-section">
                <div class="gti-drawer-section-title">
                    <i class="fas fa-file-alt"></i> Request Information
                </div>
                <div class="gti-drawer-row">
                    <span class="gti-drawer-label">Equipment</span>
                    <span class="gti-drawer-value" id="drawer-equipment">-</span>
                </div>
                <div class="gti-drawer-row">
                    <span class="gti-drawer-label">Category</span>
                    <span class="gti-drawer-value" id="drawer-category">-</span>
                </div>
                <div class="gti-drawer-row">
                    <span class="gti-drawer-label">Quantity</span>
                    <span class="gti-drawer-value" id="drawer-quantity">-</span>
                </div>
                <div class="gti-drawer-row">
                    <span class="gti-drawer-label">Location</span>
                    <span class="gti-drawer-value" id="drawer-location">-</span>
                </div>
                <div class="gti-drawer-row">
                    <span class="gti-drawer-label">Budget Range</span>
                    <span class="gti-drawer-value" id="drawer-budget">-</span>
                </div>
                <div class="gti-drawer-row">
                    <span class="gti-drawer-label">Usage Purpose</span>
                    <span class="gti-drawer-value" id="drawer-usage-purpose">-</span>
                </div>
                <div class="gti-drawer-row">
                    <span class="gti-drawer-label">Request Date</span>
                    <span class="gti-drawer-value" id="drawer-request-date">-</span>
                </div>
                <div class="gti-drawer-row" style="flex-direction: column;">
                    <span class="gti-drawer-label">Message</span>
                    <span class="gti-drawer-value is-message" id="drawer-message">-</span>
                </div>
            </div>

            <!-- Timeline -->
            <div class="gti-drawer-section">
                <div class="gti-drawer-section-title">
                    <i class="fas fa-clock"></i> Timeline
                </div>
                <div class="gti-drawer-timeline" id="drawer-timeline">
                    <!-- Populated by JS -->
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="gti-drawer-footer">
            <button type="button" class="gti-drawer-btn gti-drawer-btn-primary" id="drawer-btn-status">
                <i class="fas fa-sync-alt"></i> Update
            </button>
            <button type="button" class="gti-drawer-btn" id="drawer-btn-reply">
                <i class="fas fa-envelope"></i> Reply
            </button>
            <span class="gti-drawer-footer-spacer"></span>
            <div class="gti-drawer-btn-group">
                <button type="button" class="gti-drawer-btn gti-drawer-btn-icon" id="drawer-btn-more" title="More Actions">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
                <div class="gti-drawer-dropdown" id="drawer-more-dropdown">
                    <button type="button" class="gti-drawer-dropdown-item" onclick="updateStatusFromDrawer('processing')">
                        <i class="fas fa-spinner"></i> Mark Processing
                    </button>
                    <button type="button" class="gti-drawer-dropdown-item" onclick="updateStatusFromDrawer('proposal_sent')">
                        <i class="fas fa-paper-plane"></i> Send Proposal
                    </button>
                    <button type="button" class="gti-drawer-dropdown-item" onclick="updateStatusFromDrawer('closed')">
                        <i class="fas fa-check"></i> Close Request
                    </button>
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

        // Action Dropdown Toggle
        document.addEventListener('click', function(e) {
            var toggle = e.target.closest('.gti-ue-action-toggle');
            if (toggle) {
                e.preventDefault();
                var menu = toggle.closest('.gti-ue-action-menu');
                var dropdown = menu.querySelector('.gti-ue-action-dropdown');
                var isOpen = dropdown.classList.contains('show');

                document.querySelectorAll('.gti-ue-action-dropdown.show').forEach(function(d) {
                    d.classList.remove('show');
                });

                if (!isOpen) {
                    dropdown.classList.add('show');
                }
                return;
            }

            if (!e.target.closest('.gti-ue-action-menu')) {
                document.querySelectorAll('.gti-ue-action-dropdown.show').forEach(function(d) {
                    d.classList.remove('show');
                });
            }
        });

        // Backdrop click → close drawer (only effective on small screens)
        document.getElementById('drawerBackdrop').addEventListener('click', function() {
            closeDetailDrawer();
        });

        // Close drawer on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeDetailDrawer();
        });

        // More actions dropdown toggle
        document.getElementById('drawer-btn-more').addEventListener('click', function(e) {
            e.stopPropagation();
            document.getElementById('drawer-more-dropdown').classList.toggle('show');
        });
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.gti-drawer-btn-group')) {
                document.getElementById('drawer-more-dropdown').classList.remove('show');
            }
        });

        // Row click → update drawer (+ open if small screen)
        document.querySelectorAll('.gti-ue-table tbody tr[data-req]').forEach(function(row) {
            row.addEventListener('click', function(e) {
                if (e.target.closest('.gti-ue-action-menu')) return;
                var req;
                try { req = JSON.parse(this.getAttribute('data-req')); } catch(err) { return; }
                showRequestDetail(req);
            });
        });

        // Auto-populate drawer with first row on page load (content only, no open)
        var firstRow = document.querySelector('.gti-ue-table tbody tr[data-req]');
        if (firstRow) {
            try {
                var firstReq = JSON.parse(firstRow.getAttribute('data-req'));
                updateDrawerContent(firstReq);
            } catch(e) {}
        }
    });

    // Current request data for drawer actions
    var _currentDrawerReq = null;

    // Update drawer content without opening (for auto-populate)
    function updateDrawerContent(req) {
        _currentDrawerReq = req;

        document.getElementById('drawer-req-id').textContent = req.request_id || 'REQ-0000-0000';
        var badge = document.getElementById('drawer-status-badge');
        var statusLabel = (req.status || 'new').replace('_', ' ');
        badge.textContent = statusLabel.charAt(0).toUpperCase() + statusLabel.slice(1);
        badge.className = 'gti-drawer-status status-' + (req.status || 'new');

        document.getElementById('drawer-customer-name').textContent = req.customer_name || '-';
        document.getElementById('drawer-company').textContent = req.customer_company || '-';
        document.getElementById('drawer-email').textContent = req.customer_email || '-';
        document.getElementById('drawer-phone').textContent = req.customer_phone || '-';
        document.getElementById('drawer-address').textContent = req.customer_address || req.customer_location || '-';

        var equipLabel = req.equipment || '-';
        if (req.brand) equipLabel = req.brand + ' ' + equipLabel;
        document.getElementById('drawer-equipment').textContent = equipLabel;
        document.getElementById('drawer-category').textContent = req.category || '-';
        document.getElementById('drawer-quantity').textContent = req.quantity ? req.quantity + ' Unit' : '-';
        document.getElementById('drawer-location').textContent = req.location || '-';
        document.getElementById('drawer-budget').textContent = req.budget || '-';
        document.getElementById('drawer-usage-purpose').textContent = req.usage_purpose || req.notes || '-';
        var reqDate = req.request_date || req.created_at;
        document.getElementById('drawer-request-date').textContent = reqDate ? formatDateID(reqDate) : '-';
        document.getElementById('drawer-message').textContent = req.message || req.notes || '-';

        /* Build timeline items array */
        var timelineItems = [];
        var createdDate = req.created_at || req.request_date;
        if (createdDate) {
            timelineItems.push({
                event: 'Request Created',
                date: createdDate,
                actor: req.created_by || 'System'
            });
        }
        var statusMap = { 'new': 'New', 'processing': 'Processing', 'proposal_sent': 'Proposal Sent', 'closed': 'Closed' };
        var currentStatus = req.status || 'new';
        if (currentStatus !== 'new') {
            var statusDate = req.updated_at || createdDate;
            timelineItems.push({
                event: 'Status Updated to ' + (statusMap[currentStatus] || currentStatus),
                date: statusDate,
                actor: req.updated_by || 'System'
            });
        }

        /* Render: last item = active (gold), rest = completed (gray) */
        var timelineHtml = '';
        timelineItems.forEach(function(item, idx) {
            var isLast = idx === timelineItems.length - 1;
            var dotClass = isLast ? 'gti-drawer-timeline-dot is-active' : 'gti-drawer-timeline-dot';
            timelineHtml += '<div class="gti-drawer-timeline-item">' +
                '<div class="' + dotClass + '"></div>' +
                '<div class="gti-drawer-timeline-event">' + item.event + '</div>' +
                '<div class="gti-drawer-timeline-meta">' + formatDateID(item.date) + ' &middot; by ' + item.actor + '</div>' +
                '</div>';
        });
        document.getElementById('drawer-timeline').innerHTML = timelineHtml || '<div style="color:#9ca3af;font-size:13px;">No activity yet</div>';

        document.querySelectorAll('.gti-ue-table tbody tr').forEach(function(r) {
            r.classList.remove('active-row');
        });
        var activeRow = document.querySelector('.gti-ue-table tbody tr[data-req-id="' + req.id + '"]');
        if (activeRow) activeRow.classList.add('active-row');
    }

    // Show request detail drawer (opens on small screens)
    function showRequestDetail(req) {
        updateDrawerContent(req);

        // On smaller screens: open fixed drawer + show backdrop
        if (window.innerWidth < 1600) {
            document.getElementById('reqDetailDrawer').classList.add('open');
            document.getElementById('drawerBackdrop').classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        document.querySelectorAll('.gti-ue-action-dropdown.show').forEach(function(d) {
            d.classList.remove('show');
        });
    }

    // Format date to Indonesian format
    function formatDateID(dateStr) {
        if (!dateStr) return '-';
        var d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        var months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        var day = d.getDate();
        var month = months[d.getMonth()];
        var year = d.getFullYear();
        var hours = d.getHours();
        var minutes = String(d.getMinutes()).padStart(2, '0');
        var ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12 || 12;
        return day + ' ' + month + ' ' + year + ', ' + hours + ':' + minutes + ' ' + ampm;
    }

    // Close detail drawer (effective on small screens only)
    function closeDetailDrawer() {
        document.getElementById('reqDetailDrawer').classList.remove('open');
        document.getElementById('drawerBackdrop').classList.remove('show');
        document.body.style.overflow = '';
        document.getElementById('drawer-more-dropdown').classList.remove('show');
        _currentDrawerReq = null;
    }

    // Update status from drawer
    function updateStatusFromDrawer(newStatus) {
        if (!_currentDrawerReq) return;
        document.getElementById('drawer-more-dropdown').classList.remove('show');
        updateStatus(_currentDrawerReq.id, newStatus);
    }

    // Update request status via AJAX
    function updateStatus(requestId, newStatus) {
        if (!confirm('Are you sure you want to update this request status?')) return;

        var formData = new FormData();
        formData.append('action', 'gti_update_request_status');
        formData.append('id', requestId);
        formData.append('status', newStatus);
        formData.append('nonce', gtiAjax.nonce);

        fetch(gtiAjax.ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.success) {
                location.reload();
            } else {
                alert(res.data?.message || 'Failed to update status');
            }
        })
        .catch(function() {
            alert('An error occurred. Please try again.');
        });

        // Close dropdown
        document.querySelectorAll('.gti-ue-action-dropdown.show').forEach(function(d) {
            d.classList.remove('show');
        });
    }
    </script>
</body>
</html>
