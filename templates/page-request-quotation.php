<?php
/**
 * Template: Request Quotation (/dashboard/request-quotation)
 * @package global-tractors
 */
if (!defined('ABSPATH')) exit;
gti_require_login();

$current_user = wp_get_current_user();
$user_name = $current_user->display_name ?: $current_user->user_login;
$user_avatar = get_avatar_url($current_user->ID, ['size' => 80]);

// Get quotation data from database
global $wpdb;
$table_name = $wpdb->prefix . 'gti_quotations';

// Filters
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$brand_filter = isset($_GET['brand']) ? sanitize_text_field($_GET['brand']) : '';
$pic_filter = isset($_GET['pic']) ? sanitize_text_field($_GET['pic']) : '';

// Pagination
$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 10;
$offset = ($paged - 1) * $per_page;

// Build query
$where = "WHERE 1=1";
$params = array();

if ($search) {
    $where .= " AND (quotation_id LIKE %s OR customer_name LIKE %s OR customer_company LIKE %s)";
    $search_like = '%' . $wpdb->esc_like($search) . '%';
    $params = array_merge($params, array($search_like, $search_like, $search_like));
}

if ($status_filter) {
    $where .= " AND status = %s";
    $params[] = $status_filter;
}

// Get total count
$count_query = "SELECT COUNT(*) FROM {$table_name} {$where}";
$total = !empty($params) ? $wpdb->get_var($wpdb->prepare($count_query, $params)) : $wpdb->get_var($count_query);
$total_pages = ceil($total / $per_page);

// Get quotations
if (!empty($params)) {
    $quotations = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$table_name} {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d",
        array_merge($params, array($per_page, $offset))
    ));
} else {
    $quotations = $wpdb->get_results($wpdb->prepare(
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
$total_quotations = array_sum(array_column($status_counts, 'count'));

// Filter options
$brands = $wpdb->get_col("SELECT DISTINCT brand FROM {$table_name} WHERE brand != '' ORDER BY brand");
$pics = $wpdb->get_col("SELECT DISTINCT sales_pic FROM {$table_name} WHERE sales_pic != '' ORDER BY sales_pic");
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Quotation - <?php bloginfo('name'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo GTI_CHILD_URL; ?>/assets/css/dashboard.css">
    <script>
        var gtiAjax = {
            ajaxurl: '<?php echo admin_url('admin-ajax.php'); ?>',
            nonce: '<?php echo wp_create_nonce('gti_nonce'); ?>'
        };
    </script>
    <style>
        /* Completed status badge — teal */
        .gti-badge-status.completed { background: #ccfbf1; color: #0d9488; }
        /* Waiting Customer — orange */
        .gti-badge-status.waiting_customer { background: #ffedd5; color: #c2410c; }

        /* Shrink stat cards — cleaner look */
        .gti-ue-stats-row .gti-ue-stat-card { padding: 14px 16px; }
        .gti-ue-stats-row .gti-ue-stat-icon { width: 36px; height: 36px; font-size: 15px; }
        .gti-ue-stats-row .gti-ue-stat-value { font-size: 20px; }
        .gti-ue-stats-row .gti-ue-stat-label { font-size: 12px; }

        /* Border overrides to match spare-parts style */
        .gti-ue-stat-card { border: 1px solid #e5e7eb; }
        .gti-ue-search { border: 1px solid #d1d5db; }
        .gti-ue-filter select { border: 1px solid #d1d5db; }
        .gti-ue-btn-reset { border: 1px solid #d1d5db; text-decoration: none; }
        .gti-ue-btn-reset:hover { border-color: #d1d5db; }
        .gti-ue-table-card { border: 1px solid #e5e7eb; overflow: visible; }
        .gti-ue-action-toggle { border: 1px solid #e5e7eb; }
        .gti-ue-action-toggle:hover { border-color: #d1d5db; }
        .gti-ue-action-dropdown { border: 1px solid #e5e7eb; }
        /* Action menu overflow fix */
        .gti-ue-action-dropdown { z-index: 100; position: fixed; width: max-content; white-space: nowrap; }
        .gti-ue-action-item { cursor: pointer; border: none; background: none; width: 100%; text-align: left; font-family: inherit; }
        .gti-ue-page-btn { border: 1px solid #e5e7eb; }
        .gti-ue-page-btn:hover:not(:disabled) { border-color: #d1d5db; }
        .gti-ue-table thead th { font-size: 13px; font-weight: 600; color: #374151; text-transform: none; letter-spacing: normal; padding: 13px 16px; }
        .gti-ue-table td { font-size: 14px; color: #374151; padding: 14px 16px; }

        /* Table column widths */
        .gti-ue-table { width: 100%; table-layout: fixed; }
        .gti-ue-table .col-quotid { width: 120px; }
        .gti-ue-table .col-customer { width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .gti-ue-table .col-company { width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .gti-ue-table .col-items { width: 160px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .gti-ue-table .col-pic { width: 130px; }
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

        .gti-main {
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
        .gti-drawer-status.status-waiting_customer { background: #ffedd5; color: #c2410c; }
        .gti-drawer-status.status-approved { background: #d1fae5; color: #047857; }
        .gti-drawer-status.status-rejected { background: #fee2e2; color: #dc2626; }
        .gti-drawer-status.status-completed { background: #f3f4f6; color: #374151; }
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
        .gti-drawer-value.is-bold {
            font-weight: 700;
            color: #1a1f36;
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

        /* ====== Sales PIC Card ====== */
        .gti-drawer-pic-card {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: #f9fafb;
            border-radius: 8px;
            margin-top: 8px;
        }
        .gti-drawer-pic-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .gti-drawer-pic-avatar-placeholder {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #F5A623;
            color: #1a1f36;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 600;
            border: 2px solid #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .gti-drawer-pic-info {
            display: flex;
            flex-direction: column;
        }
        .gti-drawer-pic-name {
            font-size: 14px;
            font-weight: 600;
            color: #1a1f36;
        }
        .gti-drawer-pic-role {
            font-size: 12px;
            color: #6b7280;
        }

        /* ====== Attachment Card ====== */
        .gti-drawer-attachment {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            margin-top: 8px;
            cursor: pointer;
            transition: all 0.15s;
            text-decoration: none;
        }
        .gti-drawer-attachment:hover {
            background: #fee2e2;
            border-color: #fca5a5;
        }
        .gti-drawer-attachment-icon {
            width: 40px;
            height: 40px;
            background: #dc2626;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 18px;
            flex-shrink: 0;
        }
        .gti-drawer-attachment-info {
            display: flex;
            flex-direction: column;
            flex: 1;
            min-width: 0;
        }
        .gti-drawer-attachment-name {
            font-size: 13px;
            font-weight: 500;
            color: #1a1f36;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .gti-drawer-attachment-meta {
            font-size: 12px;
            color: #6b7280;
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
            background: #10B981;
            color: #fff;
            border-color: #10B981;
            font-weight: 600;
        }
        .gti-drawer-btn-primary:hover { background: #059669; }
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
                    <a href="<?php echo esc_url(gti_dashboard_url('request-equipment')); ?>" class="gti-nav-item"><i class="fas fa-file-alt"></i><span>Request Equipment</span></a>
                    <a href="<?php echo esc_url(gti_dashboard_url('request-quotation')); ?>" class="gti-nav-item active"><i class="fas fa-clipboard-list"></i><span>Request Quotation</span></a>
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
                        <h1 class="gti-page-title">Request Quotation</h1>
                        <p class="gti-welcome">Manage quotation requests and create proposals <span>&#128203;</span></p>
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
                        <div class="gti-ue-stat-icon"><i class="fas fa-plus-circle"></i></div>
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
                        <div class="gti-ue-stat-icon maintenance"><i class="fas fa-clock"></i></div>
                        <div class="gti-ue-stat-info">
                            <p class="gti-ue-stat-label">Waiting Customer</p>
                            <p class="gti-ue-stat-value"><?php echo esc_html($status_count_map['waiting_customer'] ?? 0); ?></p>
                        </div>
                    </div>
                    <div class="gti-ue-stat-card">
                        <div class="gti-ue-stat-icon available"><i class="fas fa-check-circle"></i></div>
                        <div class="gti-ue-stat-info">
                            <p class="gti-ue-stat-label">Approved</p>
                            <p class="gti-ue-stat-value"><?php echo esc_html($status_count_map['approved'] ?? 0); ?></p>
                        </div>
                    </div>
                    <div class="gti-ue-stat-card">
                        <div class="gti-ue-stat-icon sold"><i class="fas fa-times-circle"></i></div>
                        <div class="gti-ue-stat-info">
                            <p class="gti-ue-stat-label">Rejected</p>
                            <p class="gti-ue-stat-value"><?php echo esc_html($status_count_map['rejected'] ?? 0); ?></p>
                        </div>
                    </div>
                    <div class="gti-ue-stat-card">
                        <div class="gti-ue-stat-icon"><i class="fas fa-flag-checkered"></i></div>
                        <div class="gti-ue-stat-info">
                            <p class="gti-ue-stat-label">Completed</p>
                            <p class="gti-ue-stat-value"><?php echo esc_html($status_count_map['completed'] ?? 0); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Toolbar -->
                <form class="gti-ue-toolbar" method="get">
                    <input type="hidden" name="gti_page" value="request-quotation">
                    <div class="gti-ue-toolbar-left">
                        <div class="gti-ue-search">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" placeholder="Search quotations..." value="<?php echo esc_attr($search); ?>">
                        </div>
                        <div class="gti-ue-filter">
                            <select name="status">
                                <option value="">All Status</option>
                                <option value="new" <?php selected($status_filter, 'new'); ?>>New</option>
                                <option value="processing" <?php selected($status_filter, 'processing'); ?>>Processing</option>
                                <option value="waiting_customer" <?php selected($status_filter, 'waiting_customer'); ?>>Waiting Customer</option>
                                <option value="approved" <?php selected($status_filter, 'approved'); ?>>Approved</option>
                                <option value="rejected" <?php selected($status_filter, 'rejected'); ?>>Rejected</option>
                                <option value="completed" <?php selected($status_filter, 'completed'); ?>>Completed</option>
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
                            <select name="pic">
                                <option value="">All PIC</option>
                                <?php foreach ($pics as $pic): ?>
                                    <option value="<?php echo esc_attr($pic); ?>" <?php selected($pic_filter, $pic); ?>><?php echo esc_html($pic); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <a href="<?php echo esc_url(gti_dashboard_url('request-quotation')); ?>" class="gti-ue-btn-reset">
                            <i class="fas fa-rotate-right"></i> Reset
                        </a>
                    </div>
                </form>

                <!-- Table -->
                <div class="gti-ue-table-card">
                    <table class="gti-ue-table">
                        <thead>
                            <tr>
                                <th class="col-quotid">Quotation ID</th>
                                <th class="col-customer">Customer</th>
                                <th class="col-company">Company</th>
                                <th class="col-items">Requested Items</th>
                                <th class="col-pic">Sales PIC</th>
                                <th class="col-status">Status</th>
                                <th class="col-date">Request Date</th>
                                <th class="col-actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($quotations)): ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 60px 20px;">
                                        <div style="color: #9ca3af;">
                                            <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 16px; display: block;"></i>
                                            <p style="font-size: 16px; font-weight: 500; margin-bottom: 8px;">No quotations found</p>
                                            <p style="font-size: 14px;">
                                                <?php echo ($search || $status_filter) ? 'Try adjusting your filters' : 'No quotation requests yet'; ?>
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($quotations as $quot): ?>
                                    <?php
                                    // Parse items from JSON
                                    $items = json_decode($quot->items ?? '[]', true);
                                    $item_count = count($items);
                                    $item_summary = $item_count > 0 ? $item_count . ' Item' . ($item_count > 1 ? 's' : '') : '-';
                                    $first_item = $items[0]['name'] ?? '';
                                    if ($item_count > 1) {
                                        $item_summary .= ' (' . $first_item . ' + more)';
                                    } elseif ($first_item) {
                                        $item_summary = $first_item;
                                    }
                                    ?>
                                    <tr data-quot-id="<?php echo esc_attr($quot->id); ?>" data-quot='<?php echo esc_attr(json_encode($quot)); ?>'>
                                        <td class="col-quotid"><strong><?php echo esc_html($quot->quotation_id); ?></strong></td>
                                        <td class="col-customer">
                                            <strong style="color: #1a1f36;"><?php echo esc_html($quot->customer_name); ?></strong>
                                        </td>
                                        <td class="col-company">
                                            <small style="color: #6b7280;"><?php echo esc_html($quot->customer_company); ?></small>
                                        </td>
                                        <td class="col-items" title="<?php echo esc_attr($item_summary); ?>"><?php echo esc_html($item_summary); ?></td>
                                        <td class="col-pic">
                                            <span><?php echo esc_html($quot->sales_pic); ?></span>
                                        </td>
                                        <td class="col-status">
                                            <?php
                                            $status_class = '';
                                            if ($quot->status === 'new') {
                                                $status_class = 'available';
                                            } elseif ($quot->status === 'processing') {
                                                $status_class = 'reserved';
                                            } elseif ($quot->status === 'waiting_customer') {
                                                $status_class = 'waiting_customer';
                                            } elseif ($quot->status === 'approved') {
                                                $status_class = 'available';
                                            } elseif ($quot->status === 'rejected') {
                                                $status_class = 'sold';
                                            } elseif ($quot->status === 'completed') {
                                                $status_class = 'completed';
                                            }
                                            $status_label = ucwords(str_replace('_', ' ', $quot->status));
                                            ?>
                                            <span class="gti-badge-status <?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_label); ?></span>
                                        </td>
                                        <td class="col-date"><?php echo esc_html(date('M j, Y', strtotime($quot->request_date ?: $quot->created_at))); ?></td>
                                        <td class="col-actions">
                                            <div class="gti-ue-action-menu">
                                                <button class="gti-ue-action-toggle" title="Actions"><i class="fas fa-ellipsis-v"></i></button>
                                                <div class="gti-ue-action-dropdown">
                                                    <button type="button" class="gti-ue-action-item" onclick='showQuotationDetail(<?php echo json_encode($quot); ?>)'>
                                                        <i class="fas fa-eye"></i> View Details
                                                    </button>
                                                    <button type="button" class="gti-ue-action-item" onclick="updateStatus(<?php echo esc_attr($quot->id); ?>, 'processing')">
                                                        <i class="fas fa-spinner"></i> Mark Processing
                                                    </button>
                                                    <button type="button" class="gti-ue-action-item" onclick="updateStatus(<?php echo esc_attr($quot->id); ?>, 'waiting_customer')">
                                                        <i class="fas fa-paper-plane"></i> Send Proposal
                                                    </button>
                                                    <button type="button" class="gti-ue-action-item" onclick="updateStatus(<?php echo esc_attr($quot->id); ?>, 'approved')">
                                                        <i class="fas fa-check"></i> Mark Approved
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
                                $query_params['gti_page'] = 'request-quotation';
                                $base_url = gti_dashboard_url('request-quotation');
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

                <!-- Detail Drawer (In-Flow ≥1600px / Fixed <1600px) -->
                <div class="gti-drawer-backdrop" id="drawerBackdrop"></div>
                <div class="gti-drawer" id="quotDetailDrawer">
                    <!-- Header -->
                    <div class="gti-drawer-header">
                        <div class="gti-drawer-header-left">
                            <h2 id="drawer-quot-id">-</h2>
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
                                <span class="gti-drawer-value is-bold" id="drawer-phone">-</span>
                            </div>
                            <div class="gti-drawer-row" style="flex-direction: column; gap: 4px;">
                                <span class="gti-drawer-label" style="min-width: auto;">Address</span>
                                <span class="gti-drawer-value is-message" id="drawer-address">-</span>
                            </div>
                        </div>

                        <!-- Request Information -->
                        <div class="gti-drawer-section">
                            <div class="gti-drawer-section-title">
                                <i class="fas fa-file-alt"></i> Request Information
                            </div>
                            <div class="gti-drawer-row">
                                <span class="gti-drawer-label">Requested Date</span>
                                <span class="gti-drawer-value" id="drawer-requested-date">-</span>
                            </div>
                            <div class="gti-drawer-row">
                                <span class="gti-drawer-label">Valid Until</span>
                                <span class="gti-drawer-value" id="drawer-valid-until">-</span>
                            </div>
                            <div class="gti-drawer-row">
                                <span class="gti-drawer-label">Payment Terms</span>
                                <span class="gti-drawer-value" id="drawer-payment-terms">-</span>
                            </div>
                            <div class="gti-drawer-row">
                                <span class="gti-drawer-label">Delivery Location</span>
                                <span class="gti-drawer-value" id="drawer-delivery-location">-</span>
                            </div>
                            <div class="gti-drawer-row" style="flex-direction: column; gap: 4px;">
                                <span class="gti-drawer-label" style="min-width: auto;">Notes</span>
                                <span class="gti-drawer-value is-message" id="drawer-notes">-</span>
                            </div>
                        </div>

                        <!-- Summary -->
                        <div class="gti-drawer-section">
                            <div class="gti-drawer-section-title">
                                <i class="fas fa-list-alt"></i> Summary
                            </div>
                            <div class="gti-drawer-row">
                                <span class="gti-drawer-label">Total Items</span>
                                <span class="gti-drawer-value" id="drawer-total-items">-</span>
                            </div>
                            <div class="gti-drawer-row">
                                <span class="gti-drawer-label">Est. Total Value</span>
                                <span class="gti-drawer-value is-bold" id="drawer-total-value">-</span>
                            </div>
                            
                            <!-- Sales PIC -->
                            <div style="margin-top: 12px;">
                                <span class="gti-drawer-label" style="display: block; margin-bottom: 4px;">Sales PIC</span>
                                <div class="gti-drawer-pic-card" id="drawer-pic-card">
                                    <div class="gti-drawer-pic-avatar-placeholder" id="drawer-pic-avatar">A</div>
                                    <div class="gti-drawer-pic-info">
                                        <span class="gti-drawer-pic-name" id="drawer-pic-name">-</span>
                                        <span class="gti-drawer-pic-role">Sales Representative</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Attachments -->
                            <div style="margin-top: 16px;">
                                <span class="gti-drawer-label" style="display: block; margin-bottom: 4px;">Attachments</span>
                                <a href="#" class="gti-drawer-attachment" id="drawer-attachment" target="_blank">
                                    <div class="gti-drawer-attachment-icon">
                                        <i class="fas fa-file-pdf"></i>
                                    </div>
                                    <div class="gti-drawer-attachment-info">
                                        <span class="gti-drawer-attachment-name" id="drawer-attachment-name">-</span>
                                        <span class="gti-drawer-attachment-meta">Click to download</span>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="gti-drawer-footer">
                        <button type="button" class="gti-drawer-btn gti-drawer-btn-primary" id="drawer-btn-create" onclick="createQuotation()">
                            <i class="fas fa-file-plus"></i> Create Quotation
                        </button>
                        <button type="button" class="gti-drawer-btn" id="drawer-btn-reply" onclick="replyToCustomer()">
                            <i class="fas fa-envelope"></i> Reply to Customer
                        </button>
                        <span class="gti-drawer-footer-spacer"></span>
                        <div class="gti-drawer-btn-group">
                            <button type="button" class="gti-drawer-btn gti-drawer-btn-icon" id="drawer-btn-more" title="More Actions">
                                <i class="fas fa-ellipsis-h"></i>
                            </button>
                            <div class="gti-drawer-dropdown" id="drawer-more-dropdown">
                                <button type="button" class="gti-drawer-dropdown-item" onclick="updateStatusFromDrawer('processing')">
                                    <i class="fas fa-spinner"></i> Mark Processing
                                </button>
                                <button type="button" class="gti-drawer-dropdown-item" onclick="updateStatusFromDrawer('waiting_customer')">
                                    <i class="fas fa-paper-plane"></i> Send Proposal
                                </button>
                                <button type="button" class="gti-drawer-dropdown-item" onclick="updateStatusFromDrawer('approved')">
                                    <i class="fas fa-check"></i> Mark Approved
                                </button>
                                <button type="button" class="gti-drawer-dropdown-item" onclick="updateStatusFromDrawer('rejected')">
                                    <i class="fas fa-times"></i> Mark Rejected
                                </button>
                                <button type="button" class="gti-drawer-dropdown-item" onclick="updateStatusFromDrawer('completed')">
                                    <i class="fas fa-flag-checkered"></i> Mark Completed
                                </button>
                            </div>
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
                e.stopPropagation();
                var menu = toggle.closest('.gti-ue-action-menu');
                var dropdown = menu.querySelector('.gti-ue-action-dropdown');
                var isOpen = dropdown.classList.contains('show');
                document.querySelectorAll('.gti-ue-action-dropdown.show').forEach(function(d) { d.classList.remove('show'); });
                if (!isOpen) {
                    // Position dropdown relative to viewport, outside parent containers
                    var rect = toggle.getBoundingClientRect();
                    var ddWidth = 160;
                    var ddHeight = 110;
                    var top = rect.bottom + 4;
                    var left = rect.right - ddWidth;
                    // Prevent overflow below viewport
                    if (top + ddHeight > window.innerHeight) {
                        top = rect.top - ddHeight - 4;
                    }
                    // Prevent overflow left of viewport
                    if (left < 8) left = 8;
                    dropdown.style.top = top + 'px';
                    dropdown.style.left = left + 'px';
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
        document.querySelectorAll('.gti-ue-table tbody tr[data-quot]').forEach(function(row) {
            row.addEventListener('click', function(e) {
                if (e.target.closest('.gti-ue-action-menu')) return;
                var quot;
                try { quot = JSON.parse(this.getAttribute('data-quot')); } catch(err) { return; }
                showQuotationDetail(quot);
            });
        });

        // Auto-populate drawer with first row on page load (content only, no open)
        var firstRow = document.querySelector('.gti-ue-table tbody tr[data-quot]');
        if (firstRow) {
            try {
                var firstQuot = JSON.parse(firstRow.getAttribute('data-quot'));
                updateDrawerContent(firstQuot);
            } catch(e) {}
        }
    });

    // Current quotation data for drawer actions
    var _currentDrawerQuot = null;

    // Format currency to IDR
    function formatCurrency(amount) {
        if (!amount || isNaN(amount)) return '-';
        return 'IDR ' + parseInt(amount).toLocaleString('id-ID');
    }

    // Update drawer content without opening (for auto-populate)
    function updateDrawerContent(quot) {
        _currentDrawerQuot = quot;

        // Parse items
        var items = [];
        try {
            items = JSON.parse(quot.items || '[]');
        } catch(e) {}

        document.getElementById('drawer-quot-id').textContent = quot.quotation_id || '-';
        var badge = document.getElementById('drawer-status-badge');
        var statusLabel = (quot.status || 'new').replace('_', ' ');
        badge.textContent = statusLabel.charAt(0).toUpperCase() + statusLabel.slice(1);
        badge.className = 'gti-drawer-status status-' + (quot.status || 'new');

        // Customer Information
        document.getElementById('drawer-customer-name').textContent = quot.customer_name || '-';
        document.getElementById('drawer-company').textContent = quot.customer_company || '-';
        document.getElementById('drawer-email').textContent = quot.customer_email || '-';
        document.getElementById('drawer-phone').textContent = quot.customer_phone || '-';
        document.getElementById('drawer-address').textContent = quot.customer_address || quot.delivery_location || '-';

        // Request Information
        var reqDate = quot.request_date || quot.created_at;
        document.getElementById('drawer-requested-date').textContent = reqDate ? formatDateID(reqDate) : '-';
        document.getElementById('drawer-valid-until').textContent = quot.valid_until ? formatDateID(quot.valid_until) : '-';
        document.getElementById('drawer-payment-terms').textContent = quot.payment_terms ? quot.payment_terms + ' Days' : '30 Days';
        document.getElementById('drawer-delivery-location').textContent = quot.delivery_location || '-';
        document.getElementById('drawer-notes').textContent = quot.additional_notes || quot.notes || '-';

        // Summary
        document.getElementById('drawer-total-items').textContent = items.length + ' Item' + (items.length !== 1 ? 's' : '');
        document.getElementById('drawer-total-value').textContent = formatCurrency(quot.total);

        // Sales PIC
        var picName = quot.sales_pic || 'Unassigned';
        document.getElementById('drawer-pic-avatar').textContent = picName.charAt(0).toUpperCase();
        document.getElementById('drawer-pic-name').textContent = picName;

        // Attachment (placeholder)
        var attachmentName = quot.attachment_name || '-';
        document.getElementById('drawer-attachment-name').textContent = attachmentName;
        document.getElementById('drawer-attachment').href = quot.attachment_url || '#';

        // Highlight active row
        document.querySelectorAll('.gti-ue-table tbody tr').forEach(function(r) {
            r.classList.remove('active-row');
        });
        var activeRow = document.querySelector('.gti-ue-table tbody tr[data-quot-id="' + quot.id + '"]');
        if (activeRow) activeRow.classList.add('active-row');
    }

    // Show quotation detail drawer (opens on small screens)
    function showQuotationDetail(quot) {
        updateDrawerContent(quot);

        // On smaller screens: open fixed drawer + show backdrop
        if (window.innerWidth < 1600) {
            document.getElementById('quotDetailDrawer').classList.add('open');
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
        document.getElementById('quotDetailDrawer').classList.remove('open');
        document.getElementById('drawerBackdrop').classList.remove('show');
        document.body.style.overflow = '';
        document.getElementById('drawer-more-dropdown').classList.remove('show');
        _currentDrawerQuot = null;
    }

    // Update status from drawer
    function updateStatusFromDrawer(newStatus) {
        if (!_currentDrawerQuot) return;
        document.getElementById('drawer-more-dropdown').classList.remove('show');
        updateStatus(_currentDrawerQuot.id, newStatus);
    }

    // Update quotation status via AJAX
    function updateStatus(quotationId, newStatus) {
        if (!confirm('Are you sure you want to update this quotation status?')) return;

        var formData = new FormData();
        formData.append('action', 'gti_update_quotation_status');
        formData.append('id', quotationId);
        formData.append('status', newStatus);
        formData.append('nonce', gtiAjax.nonce);

        fetch(gtiAjax.ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.success) {
                // Email is auto-sent via server hook, just reload
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

    // Create quotation action
    function createQuotation() {
        if (!_currentDrawerQuot) return;
        alert('Create Quotation form will open for: ' + _currentDrawerQuot.quotation_id);
        // TODO: Implement create quotation modal/form
    }

    // Reply to customer action
    function replyToCustomer() {
        if (!_currentDrawerQuot) return;
        var email = _currentDrawerQuot.customer_email;
        if (email) {
            window.location.href = 'mailto:' + email + '?subject=Re: Quotation ' + _currentDrawerQuot.quotation_id;
        } else {
            alert('No email address available for this customer.');
        }
    }
    </script>
</body>
</html>
