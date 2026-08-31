<?php
/**
 * Template: Spare Parts (/dashboard/spare-parts)
 * @package global-tractors
 */
if (!defined('ABSPATH')) exit;
gti_require_login();

$current_user = wp_get_current_user();
$user_name = $current_user->display_name ?: $current_user->user_login;
$user_avatar = get_avatar_url($current_user->ID, ['size' => 80]);

// Database queries
global $wpdb;
$table = $wpdb->prefix . 'gti_spare_parts';

// Filters
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$category = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';
$brand = isset($_GET['brand']) ? sanitize_text_field($_GET['brand']) : '';
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';

// Pagination
$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 10;
$offset = ($paged - 1) * $per_page;

// Build WHERE clause
$where = "WHERE 1=1";
$params = array();

if ($search) {
    $where .= " AND (name LIKE %s OR part_number LIKE %s)";
    $search_term = '%' . $wpdb->esc_like($search) . '%';
    $params[] = $search_term;
    $params[] = $search_term;
}
if ($category) {
    $where .= " AND category = %s";
    $params[] = $category;
}
if ($brand) {
    $where .= " AND brand = %s";
    $params[] = $brand;
}
if ($status_filter) {
    $where .= " AND status = %s";
    $params[] = $status_filter;
}

// Get total count
$count_sql = "SELECT COUNT(*) FROM {$table} {$where}";
if (!empty($params)) {
    $total = (int) $wpdb->get_var($wpdb->prepare($count_sql, $params));
} else {
    $total = (int) $wpdb->get_var($count_sql);
}
$total_pages = ceil($total / $per_page);

// Get spare parts
$data_sql = "SELECT * FROM {$table} {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d";
$data_params = $params;
$data_params[] = $per_page;
$data_params[] = $offset;
if (!empty($data_params)) {
    $spare_parts = $wpdb->get_results($wpdb->prepare($data_sql, $data_params));
} else {
    $spare_parts = $wpdb->get_results("SELECT * FROM {$table} ORDER BY created_at DESC LIMIT {$per_page} OFFSET {$offset}");
}

// Status counts
$total_parts = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
$in_stock = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'in_stock'");
$low_stock = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'low_stock'");
$out_of_stock = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'out_of_stock'");
$inventory_value = (float) $wpdb->get_var("SELECT SUM(stock * unit_price) FROM {$table}");

// Categories and brands for filters
$categories = $wpdb->get_col("SELECT DISTINCT category FROM {$table} WHERE category != '' ORDER BY category");
$brands = $wpdb->get_col("SELECT DISTINCT brand FROM {$table} WHERE brand != '' ORDER BY brand");

// Dummy data fallback when DB is empty
if (empty($spare_parts)) {
    $spare_parts = array();

    $d = new stdClass();
    $d->id = 3001; $d->part_number = 'PC200-FIL-001'; $d->name = 'Oil Filter PC200';
    $d->category = 'Filters'; $d->brand = 'KOMATSU';
    $d->description = 'Genuine KOMATSU oil filter for PC200 series excavators. OEM quality.';
    $d->stock = 25; $d->minimum_stock = 10; $d->unit_price = 450000;
    $d->supplier = 'PT KOMATSU Indonesia'; $d->location = 'Warehouse A, Rack 12';
    $d->image = 'https://picsum.photos/seed/sp1/200/200'; $d->status = 'in_stock';
    $d->created_at = '2026-01-10 08:00:00'; $d->updated_at = '2026-08-20 10:00:00';
    $spare_parts[] = $d;

    $d = new stdClass();
    $d->id = 3002; $d->part_number = 'CAT-FLT-002'; $d->name = 'Air Filter 320D';
    $d->category = 'Filters'; $d->brand = 'CATERPILLAR';
    $d->description = 'Heavy-duty air filter for CAT 320D excavator. High filtration efficiency.';
    $d->stock = 4; $d->minimum_stock = 5; $d->unit_price = 380000;
    $d->supplier = 'PT Trakindo Utama'; $d->location = 'Warehouse A, Rack 14';
    $d->image = 'https://picsum.photos/seed/sp2/200/200'; $d->status = 'low_stock';
    $d->created_at = '2026-02-15 09:00:00'; $d->updated_at = '2026-08-18 11:30:00';
    $spare_parts[] = $d;

    $d = new stdClass();
    $d->id = 3003; $d->part_number = 'KMT-BLT-003'; $d->name = 'Track Chain D65';
    $d->category = 'Undercarriage'; $d->brand = 'KOMATSU';
    $d->description = 'KOMATSU D65PX bulldozer track chain assembly. Heavy-duty construction.';
    $d->stock = 0; $d->minimum_stock = 3; $d->unit_price = 12500000;
    $d->supplier = 'PT KOMATSU Indonesia'; $d->location = 'Warehouse B, Rack 05';
    $d->image = 'https://picsum.photos/seed/sp3/200/200'; $d->status = 'out_of_stock';
    $d->created_at = '2026-03-05 10:30:00'; $d->updated_at = '2026-08-15 14:00:00';
    $spare_parts[] = $d;

    $d = new stdClass();
    $d->id = 3004; $d->part_number = 'CAT-HYD-004'; $d->name = 'Hydraulic Pump 950GC';
    $d->category = 'Hydraulics'; $d->brand = 'CATERPILLAR';
    $d->description = 'CAT 950GC wheel loader hydraulic pump assembly. Remanufactured unit with warranty.';
    $d->stock = 2; $d->minimum_stock = 2; $d->unit_price = 18500000;
    $d->supplier = 'PT Trakindo Utama'; $d->location = 'Warehouse B, Rack 08';
    $d->image = 'https://picsum.photos/seed/sp4/200/200'; $d->status = 'in_stock';
    $d->created_at = '2026-04-12 11:15:00'; $d->updated_at = '2026-08-22 09:45:00';
    $spare_parts[] = $d;

    $d = new stdClass();
    $d->id = 3005; $d->part_number = 'HTC-BRK-005'; $d->name = 'Brake Disc ZX210';
    $d->category = 'Brakes'; $d->brand = 'HITACHI';
    $d->description = 'HITACHI ZX210LCH-5A excavator brake disc. OEM specification.';
    $d->stock = 8; $d->minimum_stock = 5; $d->unit_price = 2200000;
    $d->supplier = 'PT Hexindo Adi Perkasa'; $d->location = 'Warehouse A, Rack 20';
    $d->image = 'https://picsum.photos/seed/sp5/200/200'; $d->status = 'in_stock';
    $d->created_at = '2026-05-01 13:00:00'; $d->updated_at = '2026-08-25 08:30:00';
    $spare_parts[] = $d;

    $total_parts = count($spare_parts);
    $in_stock = 3; $low_stock = 1; $out_of_stock = 1;
    $inventory_value = 0;
    foreach ($spare_parts as $sp) $inventory_value += $sp->stock * $sp->unit_price;
}

// Format currency inline to avoid redeclaration errors
$_gti_sp_fmt = function($amount) {
    return 'Rp ' . number_format((float)$amount, 0, ',', '.');
};
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spare Parts - <?php bloginfo('name'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo GTI_CHILD_URL; ?>/assets/css/dashboard.css">
    <style>
        /* Stats: Inventory Value card wider */
        .gti-ue-stats-row { flex-wrap: wrap; }
        .gti-ue-stats-row .gti-ue-stat-card:last-child { flex: 1.4; }
        .gti-ue-stat-value { font-size: 22px; word-break: break-all; }

        /* Border overrides to match request-equipment style */
        .gti-ue-stat-card { border: 1px solid #e5e7eb; }
        .gti-ue-search { border: 1px solid #d1d5db; }
        .gti-ue-filter select { border: 1px solid #d1d5db; }
        .gti-ue-btn-reset { border: 1px solid #d1d5db; text-decoration: none; }
        .gti-ue-btn-reset:hover { border-color: #d1d5db; }
        .gti-ue-table-card { border: 1px solid #e5e7eb; }
        .gti-ue-action-toggle { border: 1px solid #e5e7eb; }
        .gti-ue-action-toggle:hover { border-color: #d1d5db; }
        .gti-ue-action-dropdown { border: 1px solid #e5e7eb; }
        .gti-ue-page-btn { border: 1px solid #e5e7eb; }
        .gti-ue-page-btn:hover:not(:disabled) { border-color: #d1d5db; }
        .gti-ue-table thead th { font-size: 13px; font-weight: 600; color: #374151; text-transform: none; letter-spacing: normal; padding: 13px 16px; }
        .gti-ue-table td { font-size: 14px; color: #374151; padding: 14px 16px; }
        .gti-ue-table .col-category { font-size: 13px; font-weight: 600; color: #374151; }
        .gti-ue-table .col-brand { font-size: 13px; font-weight: 600; color: #374151; }
        .gti-ue-table .col-price { font-size: 13px; font-weight: 600; color: #374151; }

        /* Table column widths */
        .gti-ue-table .col-checkbox { width: 36px; }
        .gti-ue-table .col-image { width: 44px; }
        .gti-ue-table .col-partnum { width: 140px; text-align: center; padding-left: 20px; padding-right: 12px; }
        .gti-ue-table .col-partname { width: 160px; }
        .gti-ue-table .col-category { width: 80px; }
        .gti-ue-table .col-brand { width: 90px; }
        .gti-ue-table .col-stock { width: 55px; text-align: center; }
        .gti-ue-table .col-price { width: 95px; text-align: right; white-space: nowrap; }
        .gti-ue-table .col-totalval { width: 105px; text-align: right; white-space: nowrap; }
        .gti-ue-table .col-status { width: 120px; text-align: center; white-space: nowrap; }
        .gti-ue-table .col-actions { width: 80px; text-align: center; }

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
        .gti-drawer-body { overflow-y: visible; }
        .gti-drawer .gti-drawer-close { display: none; }
        .gti-main { overflow: hidden; min-width: 0; }
        .gti-drawer-backdrop {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,0.4); z-index: 1000;
            opacity: 0; transition: opacity 0.3s ease;
        }
        .gti-drawer-backdrop.show { display: block; opacity: 1; }
        @media (max-width: 1599px) {
            .gti-content { display: block; }
            .gti-drawer {
                position: fixed; top: 0; right: 0; bottom: 0; left: auto;
                width: 420px; max-width: 100vw; max-height: 100vh;
                border-radius: 12px 0 0 12px; border: none;
                box-shadow: -4px 0 24px rgba(0,0,0,0.15); z-index: 1001;
                transform: translateX(100%);
                transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                height: auto;
            }
            .gti-drawer.open { transform: translateX(0); }
            .gti-drawer .gti-drawer-close { display: flex; }
            .gti-drawer-body { overflow-y: auto; flex: 1; }
        }
        @media (max-width: 560px) {
            .gti-drawer {
                width: 100%; border-radius: 16px 16px 0 0;
                top: auto; max-height: 85vh;
                transform: translateY(100%);
            }
            .gti-drawer.open { transform: translateY(0); }
        }
        .gti-drawer-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 20px 24px; border-bottom: 1px solid #e5e7eb; flex-shrink: 0;
        }
        .gti-drawer-header-left { display: flex; align-items: center; gap: 12px; }
        .gti-drawer-header-left h2 { margin: 0; font-size: 16px; font-weight: 600; color: #1a1f36; }
        .gti-drawer-status {
            display: inline-flex; align-items: center;
            padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;
        }
        .gti-drawer-status.status-in_stock { background: #d1fae5; color: #047857; }
        .gti-drawer-status.status-low_stock { background: #fef3c7; color: #b45309; }
        .gti-drawer-status.status-out_of_stock { background: #fee2e2; color: #b91c1c; }
        .gti-drawer-close {
            width: 32px; height: 32px; border-radius: 6px;
            border: 1px solid #e5e7eb; background: #fff; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            color: #6b7280; font-size: 14px; transition: all 0.15s; flex-shrink: 0;
        }
        .gti-drawer-close:hover { background: #f3f4f6; }
        .gti-drawer-body { flex: 1; overflow-y: auto; padding: 24px; }
        .gti-drawer-section { margin-bottom: 24px; }
        .gti-drawer-section:last-child { margin-bottom: 0; }
        .gti-drawer-section-title {
            display: flex; align-items: center; gap: 8px;
            font-size: 13px; font-weight: 600; color: #6b7280;
            text-transform: uppercase; letter-spacing: 0.5px;
            margin-bottom: 14px; padding-bottom: 10px;
            border-bottom: 1px solid #f3f4f6;
        }
        .gti-drawer-section-title i { color: #F5A623; font-size: 14px; }
        .gti-drawer-row {
            display: flex; justify-content: space-between;
            align-items: flex-start; padding: 7px 0; gap: 12px;
        }
        .gti-drawer-row + .gti-drawer-row { border-top: 1px solid #f9fafb; }
        .gti-drawer-label { font-size: 13px; color: #9ca3af; flex-shrink: 0; min-width: 120px; }
        .gti-drawer-value {
            font-size: 13px; font-weight: 500; color: #1a1f36;
            text-align: right; word-break: break-word;
        }
        .gti-drawer-value.is-message {
            text-align: left; background: #f9fafb; padding: 10px 12px;
            border-radius: 8px; font-weight: 400; color: #374151;
            line-height: 1.5; width: 100%; margin-top: 4px;
        }
        .gti-drawer-timeline { position: relative; padding-left: 28px; }
        .gti-drawer-timeline::before {
            content: ''; position: absolute; left: 9px; top: 6px; bottom: 6px;
            width: 2px; background: #e5e7eb; border-radius: 1px;
        }
        .gti-drawer-timeline-item { position: relative; padding-bottom: 20px; }
        .gti-drawer-timeline-item:last-child { padding-bottom: 0; }
        .gti-drawer-timeline-dot {
            position: absolute; left: -18px; top: 5px;
            width: 10px; height: 10px; border-radius: 50%;
            background: #6B7280; transform: translateX(-50%);
        }
        .gti-drawer-timeline-dot.is-active {
            width: 14px; height: 14px; top: 2px; left: -18px;
            background: #F59E0B; z-index: 1;
        }
        .gti-drawer-timeline-dot.is-active::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            margin: auto; width: 6px; height: 6px; border-radius: 50%;
            background: #000; z-index: -1;
        }
        .gti-drawer-timeline-event { font-size: 13px; font-weight: 500; color: #1a1f36; }
        .gti-drawer-timeline-item:last-child .gti-drawer-timeline-event { font-weight: 600; color: #111827; }
        .gti-drawer-timeline-meta { font-size: 12px; color: #9ca3af; margin-top: 2px; }
        .gti-drawer-timeline-item:last-child .gti-drawer-timeline-meta { color: #6B7280; }
        .gti-drawer-footer {
            display: flex; align-items: center; gap: 8px;
            padding: 16px 24px; border-top: 1px solid #e5e7eb;
            flex-shrink: 0; background: #fff;
        }
        .gti-ue-table tbody tr.active-row { background: #FFF8EC; }
        .gti-ue-table tbody tr { cursor: pointer; transition: background 0.15s; }
        .gti-ue-table tbody tr:hover { background: #f9fafb; }
        .gti-drawer-btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 9px 16px; border-radius: 8px; font-size: 13px;
            font-weight: 500; cursor: pointer; transition: all 0.15s;
            border: 1px solid #e5e7eb; background: #fff;
            color: #374151; font-family: inherit;
        }
        .gti-drawer-btn:hover { background: #f9fafb; }
        .gti-drawer-btn-primary {
            background: #F5A623; color: #1a1f36;
            border-color: #F5A623; font-weight: 600;
        }
        .gti-drawer-btn-primary:hover { background: #e6991a; }
        .gti-drawer-btn-icon {
            width: 36px; height: 36px; padding: 0; justify-content: center;
        }
        .gti-drawer-footer-spacer { flex: 1; }
        .gti-drawer-footer .gti-drawer-btn-group {
            display: flex; gap: 6px; position: relative;
        }
        .gti-drawer-footer .gti-drawer-btn-group .gti-drawer-dropdown {
            display: none; position: absolute; bottom: calc(100% + 6px); right: 0;
            background: #fff; border: 1px solid #e5e7eb; border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.1); min-width: 180px;
            z-index: 10; overflow: hidden;
        }
        .gti-drawer-footer .gti-drawer-btn-group .gti-drawer-dropdown.show { display: block; }
        .gti-drawer-dropdown-item {
            display: flex; align-items: center; gap: 8px; width: 100%;
            padding: 10px 14px; border: none; background: none;
            font-size: 13px; color: #374151; cursor: pointer;
            font-family: inherit; text-align: left;
        }
        .gti-drawer-dropdown-item:hover { background: #f9fafb; }
        .gti-drawer-dropdown-item i { width: 16px; color: #6b7280; }
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

                <div class="gti-nav-group gti-has-children open">
                    <div class="gti-nav-section">EQUIPMENT</div>
                    <a href="#" class="gti-nav-parent" data-toggle="dropdown"><i class="fas fa-truck"></i><span>Equipment</span><i class="fas fa-chevron-down gti-nav-arrow"></i></a>
                    <div class="gti-nav-children">
                        <a href="<?php echo esc_url(gti_dashboard_url('used-equipment')); ?>" class="gti-nav-child"><i></i><span>Used Equipment</span></a>
                        <a href="<?php echo esc_url(gti_dashboard_url('rental-equipment')); ?>" class="gti-nav-child"><i></i><span>Rental Equipment</span></a>
                    </div>
                    <a href="<?php echo esc_url(gti_dashboard_url('spare-parts')); ?>" class="gti-nav-item active"><i class="fas fa-cog"></i><span>Spare Parts</span></a>
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
                        <h1 class="gti-page-title">Spare Parts</h1>
                        <p class="gti-welcome">Manage your spare parts inventory <span>&#128295;</span></p>
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
                <!-- Spare Parts Stats -->
                <div class="gti-ue-stats-row">
                    <div class="gti-ue-stat-card">
                        <div class="gti-ue-stat-icon"><i class="fas fa-cogs"></i></div>
                        <div class="gti-ue-stat-info">
                            <p class="gti-ue-stat-label">Total Parts</p>
                            <p class="gti-ue-stat-value"><?php echo esc_html($total_parts); ?></p>
                        </div>
                    </div>
                    <div class="gti-ue-stat-card">
                        <div class="gti-ue-stat-icon available"><i class="fas fa-check-circle"></i></div>
                        <div class="gti-ue-stat-info">
                            <p class="gti-ue-stat-label">In Stock</p>
                            <p class="gti-ue-stat-value"><?php echo esc_html($in_stock); ?></p>
                        </div>
                    </div>
                    <div class="gti-ue-stat-card">
                        <div class="gti-ue-stat-icon reserved"><i class="fas fa-exclamation-triangle"></i></div>
                        <div class="gti-ue-stat-info">
                            <p class="gti-ue-stat-label">Low Stock</p>
                            <p class="gti-ue-stat-value"><?php echo esc_html($low_stock); ?></p>
                        </div>
                    </div>
                    <div class="gti-ue-stat-card">
                        <div class="gti-ue-stat-icon sold"><i class="fas fa-times-circle"></i></div>
                        <div class="gti-ue-stat-info">
                            <p class="gti-ue-stat-label">Out of Stock</p>
                            <p class="gti-ue-stat-value"><?php echo esc_html($out_of_stock); ?></p>
                        </div>
                    </div>
                    <div class="gti-ue-stat-card">
                        <div class="gti-ue-stat-icon"><i class="fas fa-coins"></i></div>
                        <div class="gti-ue-stat-info">
                            <p class="gti-ue-stat-label">Inventory Value</p>
                            <p class="gti-ue-stat-value"><?php echo esc_html($_gti_sp_fmt($inventory_value)); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Toolbar -->
                <form class="gti-ue-toolbar" method="get">
                    <div class="gti-ue-toolbar-left">
                        <div class="gti-ue-search">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" placeholder="Search spare parts..." value="<?php echo esc_attr($search); ?>">
                        </div>
                        <div class="gti-ue-filter">
                            <select name="category">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo esc_attr($cat); ?>" <?php selected($category, $cat); ?>><?php echo esc_html($cat); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="gti-ue-filter">
                            <select name="brand">
                                <option value="">All Brands</option>
                                <?php foreach ($brands as $b): ?>
                                    <option value="<?php echo esc_attr($b); ?>" <?php selected($brand, $b); ?>><?php echo esc_html($b); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="gti-ue-filter">
                            <select name="status">
                                <option value="">All Status</option>
                                <option value="in_stock" <?php selected($status_filter, 'in_stock'); ?>>In Stock</option>
                                <option value="low_stock" <?php selected($status_filter, 'low_stock'); ?>>Low Stock</option>
                                <option value="out_of_stock" <?php selected($status_filter, 'out_of_stock'); ?>>Out of Stock</option>
                            </select>
                        </div>
                        <a href="<?php echo esc_url(gti_dashboard_url('spare-parts')); ?>" class="gti-ue-btn-reset">
                            <i class="fas fa-rotate-right"></i> Reset
                        </a>
                    </div>
                    <a href="<?php echo esc_url(gti_dashboard_url('spare-parts/add')); ?>" class="gti-ue-btn-add">
                        <i class="fas fa-plus"></i> Add Spare Part
                    </a>
                </form>

                <!-- Table -->
                <div class="gti-ue-table-card">
                    <table class="gti-ue-table">
                        <thead>
                            <tr>
                                <th class="col-checkbox"><input type="checkbox"></th>
                                <th class="col-image">Image</th>
                                <th class="col-partnum">Part Number</th>
                                <th class="col-partname">Part Name</th>
                                <th class="col-category">Category</th>
                                <th class="col-brand">Brand</th>
                                <th class="col-stock">Stock</th>
                                <th class="col-price">Unit Price</th>
                                <th class="col-totalval">Total Value</th>
                                <th class="col-status">Status</th>
                                <th class="col-actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($spare_parts)): ?>
                                <tr>
                                    <td colspan="11" style="text-align: center; padding: 60px 20px;">
                                        <div style="color: #9ca3af;">
                                            <i class="fas fa-cogs" style="font-size: 48px; margin-bottom: 16px; display: block;"></i>
                                            <p style="font-size: 16px; font-weight: 500; margin-bottom: 8px;">No spare parts found</p>
                                            <p style="font-size: 14px;">
                                                <?php if ($search || $category || $brand || $status_filter): ?>
                                                    Try adjusting your filters
                                                <?php else: ?>
                                                    Get started by adding your first spare part
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($spare_parts as $part): ?>
                                    <tr data-sp-id="<?php echo esc_attr($part->id); ?>" data-sp='<?php echo esc_attr(json_encode($part)); ?>'>
                                        <td class="col-checkbox"><input type="checkbox"></td>
                                        <td class="col-image">
                                            <div class="gti-ue-thumb">
                                                <?php if (!empty($part->image)): ?>
                                                    <img src="<?php echo esc_url($part->image); ?>" alt="<?php echo esc_attr($part->name); ?>">
                                                <?php else: ?>
                                                    <i class="fas fa-cog"></i>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="col-partnum"><strong><?php echo esc_html($part->part_number); ?></strong></td>
                                        <td class="col-partname"><?php echo esc_html($part->name); ?></td>
                                        <td class="col-category"><?php echo esc_html($part->category); ?></td>
                                        <td class="col-brand"><?php echo esc_html($part->brand); ?></td>
                                        <td class="col-stock">
                                            <strong><?php echo esc_html($part->stock); ?></strong>
                                        </td>
                                        <td class="col-price"><?php echo esc_html($_gti_sp_fmt($part->unit_price)); ?></td>
                                        <td class="col-totalval"><strong><?php echo esc_html($_gti_sp_fmt($part->stock * $part->unit_price)); ?></strong></td>
                                        <td class="col-status">
                                            <?php
                                            $status_class = 'available';
                                            $status_label = 'In Stock';
                                            if ($part->status === 'low_stock') {
                                                $status_class = 'reserved';
                                                $status_label = 'Low Stock';
                                            } elseif ($part->status === 'out_of_stock') {
                                                $status_class = 'sold';
                                                $status_label = 'Out of Stock';
                                            }
                                            ?>
                                            <span class="gti-badge-status <?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_label); ?></span>
                                        </td>
                                        <td class="col-actions">
                                            <div class="gti-ue-action-menu">
                                                <button class="gti-ue-action-toggle" title="Actions"><i class="fas fa-ellipsis-v"></i></button>
                                                <div class="gti-ue-action-dropdown">
                                                    <a href="#" class="gti-ue-action-item"><i class="fas fa-eye"></i> View</a>
                                                    <a href="<?php echo admin_url('admin.php?page=gti-spare-parts-edit&id=' . $part->id); ?>" class="gti-ue-action-item"><i class="fas fa-edit"></i> Edit</a>
                                                    <a href="#" class="gti-ue-action-item delete gti-confirm-delete"
                                                       data-action="gti_delete_spare_part"
                                                       data-id="<?php echo esc_attr($part->id); ?>"
                                                       data-message="Are you sure you want to delete this spare part?">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </a>
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
                                $base_url = '?';
                                if ($search) $base_url .= 'search=' . urlencode($search) . '&';
                                if ($category) $base_url .= 'category=' . urlencode($category) . '&';
                                if ($brand) $base_url .= 'brand=' . urlencode($brand) . '&';
                                if ($status_filter) $base_url .= 'status=' . urlencode($status_filter) . '&';
                                ?>

                                <?php if ($paged > 1): ?>
                                    <a href="<?php echo $base_url; ?>paged=<?php echo $paged - 1; ?>" class="gti-ue-page-btn"><i class="fas fa-chevron-left"></i></a>
                                <?php else: ?>
                                    <button class="gti-ue-page-btn" disabled><i class="fas fa-chevron-left"></i></button>
                                <?php endif; ?>

                                <?php
                                $start = max(1, $paged - 2);
                                $end = min($total_pages, $paged + 2);
                                if ($start > 1): ?>
                                    <a href="<?php echo $base_url; ?>paged=1" class="gti-ue-page-btn">1</a>
                                    <?php if ($start > 2): ?>
                                        <span class="gti-ue-page-dots">...</span>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php for ($i = $start; $i <= $end; $i++): ?>
                                    <?php if ($i == $paged): ?>
                                        <button class="gti-ue-page-btn active"><?php echo $i; ?></button>
                                    <?php else: ?>
                                        <a href="<?php echo $base_url; ?>paged=<?php echo $i; ?>" class="gti-ue-page-btn"><?php echo $i; ?></a>
                                    <?php endif; ?>
                                <?php endfor; ?>

                                <?php if ($end < $total_pages): ?>
                                    <?php if ($end < $total_pages - 1): ?>
                                        <span class="gti-ue-page-dots">...</span>
                                    <?php endif; ?>
                                    <a href="<?php echo $base_url; ?>paged=<?php echo $total_pages; ?>" class="gti-ue-page-btn"><?php echo $total_pages; ?></a>
                                <?php endif; ?>

                                <?php if ($paged < $total_pages): ?>
                                    <a href="<?php echo $base_url; ?>paged=<?php echo $paged + 1; ?>" class="gti-ue-page-btn"><i class="fas fa-chevron-right"></i></a>
                                <?php else: ?>
                                    <button class="gti-ue-page-btn" disabled><i class="fas fa-chevron-right"></i></button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                </div><!-- /.gti-content-left -->

                <!-- Detail Drawer -->
                <div class="gti-drawer-backdrop" id="drawerBackdrop"></div>
                <div class="gti-drawer" id="spDetailDrawer">
        <div class="gti-drawer-header">
            <div class="gti-drawer-header-left">
                <h2 id="drawer-part-number">-</h2>
                <span class="gti-drawer-status" id="drawer-status-badge">In Stock</span>
            </div>
            <button type="button" class="gti-drawer-close" onclick="closeDetailDrawer()"><i class="fas fa-times"></i></button>
        </div>
        <div class="gti-drawer-body">
            <div class="gti-drawer-section">
                <div class="gti-drawer-section-title"><i class="fas fa-cog"></i> Part Information</div>
                <div class="gti-drawer-row"><span class="gti-drawer-label">Part Number</span><span class="gti-drawer-value" id="drawer-partnum">-</span></div>
                <div class="gti-drawer-row"><span class="gti-drawer-label">Part Name</span><span class="gti-drawer-value" id="drawer-name">-</span></div>
                <div class="gti-drawer-row"><span class="gti-drawer-label">Category</span><span class="gti-drawer-value" id="drawer-category">-</span></div>
                <div class="gti-drawer-row"><span class="gti-drawer-label">Brand</span><span class="gti-drawer-value" id="drawer-brand">-</span></div>
            </div>
            <div class="gti-drawer-section">
                <div class="gti-drawer-section-title"><i class="fas fa-boxes"></i> Inventory</div>
                <div class="gti-drawer-row"><span class="gti-drawer-label">Current Stock</span><span class="gti-drawer-value" id="drawer-stock">-</span></div>
                <div class="gti-drawer-row"><span class="gti-drawer-label">Minimum Stock</span><span class="gti-drawer-value" id="drawer-min-stock">-</span></div>
                <div class="gti-drawer-row"><span class="gti-drawer-label">Status</span><span class="gti-drawer-value" id="drawer-status-text">-</span></div>
            </div>
            <div class="gti-drawer-section">
                <div class="gti-drawer-section-title"><i class="fas fa-coins"></i> Pricing</div>
                <div class="gti-drawer-row"><span class="gti-drawer-label">Unit Price</span><span class="gti-drawer-value" id="drawer-unit-price">-</span></div>
                <div class="gti-drawer-row"><span class="gti-drawer-label">Total Value</span><span class="gti-drawer-value" id="drawer-total-value">-</span></div>
            </div>
            <div class="gti-drawer-section">
                <div class="gti-drawer-section-title"><i class="fas fa-info-circle"></i> Additional</div>
                <div class="gti-drawer-row"><span class="gti-drawer-label">Supplier</span><span class="gti-drawer-value" id="drawer-supplier">-</span></div>
                <div class="gti-drawer-row"><span class="gti-drawer-label">Location</span><span class="gti-drawer-value" id="drawer-location">-</span></div>
                <div class="gti-drawer-row" style="flex-direction: column;">
                    <span class="gti-drawer-label">Description</span>
                    <span class="gti-drawer-value is-message" id="drawer-description">-</span>
                </div>
                <div class="gti-drawer-row"><span class="gti-drawer-label">Created</span><span class="gti-drawer-value" id="drawer-created">-</span></div>
                <div class="gti-drawer-row"><span class="gti-drawer-label">Updated</span><span class="gti-drawer-value" id="drawer-updated">-</span></div>
            </div>
        </div>
        <div class="gti-drawer-footer">
            <a href="#" class="gti-drawer-btn gti-drawer-btn-primary"><i class="fas fa-edit"></i> Edit</a>
            <span class="gti-drawer-footer-spacer"></span>
            <div class="gti-drawer-btn-group">
                <button type="button" class="gti-drawer-btn gti-drawer-btn-icon" id="drawer-btn-more" title="More Actions"><i class="fas fa-ellipsis-v"></i></button>
                <div class="gti-drawer-dropdown" id="drawer-more-dropdown">
                    <a href="#" class="gti-drawer-dropdown-item"><i class="fas fa-eye"></i> View Full Details</a>
                    <a href="#" class="gti-drawer-dropdown-item" style="color:#b91c1c;"><i class="fas fa-trash"></i> Delete</a>
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
                var menu = toggle.closest('.gti-ue-action-menu');
                var dropdown = menu.querySelector('.gti-ue-action-dropdown');
                var isOpen = dropdown.classList.contains('show');
                document.querySelectorAll('.gti-ue-action-dropdown.show').forEach(function(d) { d.classList.remove('show'); });
                if (!isOpen) dropdown.classList.add('show');
                return;
            }
            if (!e.target.closest('.gti-ue-action-menu')) {
                document.querySelectorAll('.gti-ue-action-dropdown.show').forEach(function(d) { d.classList.remove('show'); });
            }
        });
        // Backdrop close
        document.getElementById('drawerBackdrop').addEventListener('click', closeDetailDrawer);
        document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeDetailDrawer(); });
        // More actions dropdown
        document.getElementById('drawer-btn-more').addEventListener('click', function(e) {
            e.stopPropagation();
            document.getElementById('drawer-more-dropdown').classList.toggle('show');
        });
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.gti-drawer-btn-group')) {
                document.getElementById('drawer-more-dropdown').classList.remove('show');
            }
        });
        // Row click → drawer
        document.querySelectorAll('.gti-ue-table tbody tr[data-sp]').forEach(function(row) {
            row.addEventListener('click', function(e) {
                if (e.target.closest('.gti-ue-action-menu') || e.target.type === 'checkbox') return;
                var sp;
                try { sp = JSON.parse(this.getAttribute('data-sp')); } catch(err) { return; }
                showSparePartDetail(sp);
            });
        });
        // Auto-populate drawer with first row
        var firstRow = document.querySelector('.gti-ue-table tbody tr[data-sp]');
        if (firstRow) {
            try { updateDrawerContent(JSON.parse(firstRow.getAttribute('data-sp'))); } catch(e) {}
        }
        // Delete confirmation
        document.querySelectorAll('.gti-confirm-delete').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                var message = this.getAttribute('data-message') || 'Are you sure you want to delete this item?';
                if (confirm(message)) {
                    var action = this.getAttribute('data-action');
                    var id = this.getAttribute('data-id');
                    var formData = new FormData();
                    formData.append('action', action);
                    formData.append('id', id);
                    formData.append('nonce', gtiAjax.nonce);
                    fetch(gtiAjax.ajaxurl, { method: 'POST', body: formData })
                        .then(function(r) { return r.json(); })
                        .then(function(data) {
                            if (data.success) location.reload();
                            else alert(data.data || 'Delete failed.');
                        })
                        .catch(function() { alert('An error occurred.'); });
                }
            });
        });
    });

    var _currentDrawerSp = null;
    function updateDrawerContent(sp) {
        _currentDrawerSp = sp;
        document.getElementById('drawer-part-number').textContent = sp.part_number || '-';
        var badge = document.getElementById('drawer-status-badge');
        var statusMap = { 'in_stock':'In Stock', 'low_stock':'Low Stock', 'out_of_stock':'Out of Stock' };
        var sl = statusMap[sp.status] || sp.status || 'In Stock';
        badge.textContent = sl;
        badge.className = 'gti-drawer-status status-' + (sp.status || 'in_stock');
        document.getElementById('drawer-partnum').textContent = sp.part_number || '-';
        document.getElementById('drawer-name').textContent = sp.name || '-';
        document.getElementById('drawer-category').textContent = sp.category || '-';
        document.getElementById('drawer-brand').textContent = sp.brand || '-';
        document.getElementById('drawer-stock').textContent = sp.stock || '0';
        document.getElementById('drawer-min-stock').textContent = sp.minimum_stock || '10';
        document.getElementById('drawer-status-text').textContent = sl;
        document.getElementById('drawer-unit-price').textContent = formatRupiah(sp.unit_price);
        document.getElementById('drawer-total-value').textContent = formatRupiah((sp.stock || 0) * (sp.unit_price || 0));
        document.getElementById('drawer-supplier').textContent = sp.supplier || '-';
        document.getElementById('drawer-location').textContent = sp.location || '-';
        document.getElementById('drawer-description').textContent = sp.description || '-';
        document.getElementById('drawer-created').textContent = formatDate(sp.created_at);
        document.getElementById('drawer-updated').textContent = formatDate(sp.updated_at);
        document.querySelectorAll('.gti-ue-table tbody tr').forEach(function(r) { r.classList.remove('active-row'); });
        var activeRow = document.querySelector('.gti-ue-table tbody tr[data-sp-id="' + sp.id + '"]');
        if (activeRow) activeRow.classList.add('active-row');
    }
    function showSparePartDetail(sp) {
        updateDrawerContent(sp);
        if (window.innerWidth < 1600) {
            document.getElementById('spDetailDrawer').classList.add('open');
            document.getElementById('drawerBackdrop').classList.add('show');
            document.body.style.overflow = 'hidden';
        }
        document.querySelectorAll('.gti-ue-action-dropdown.show').forEach(function(d) { d.classList.remove('show'); });
    }
    function closeDetailDrawer() {
        document.getElementById('spDetailDrawer').classList.remove('open');
        document.getElementById('drawerBackdrop').classList.remove('show');
        document.body.style.overflow = '';
        document.getElementById('drawer-more-dropdown').classList.remove('show');
        _currentDrawerSp = null;
    }
    function formatRupiah(val) {
        if (!val) return '-';
        return 'Rp ' + Number(val).toLocaleString('id-ID');
    }
    function formatDate(dateStr) {
        if (!dateStr) return '-';
        var d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        var months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        var day = d.getDate();
        var hours = d.getHours(); var minutes = String(d.getMinutes()).padStart(2, '0');
        var ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12 || 12;
        return day + ' ' + months[d.getMonth()] + ' ' + d.getFullYear() + ', ' + hours + ':' + minutes + ' ' + ampm;
    }
    </script>
</body>
</html>
