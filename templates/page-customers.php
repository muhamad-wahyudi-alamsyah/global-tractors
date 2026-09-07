<?php
/**
 * Template: Customers List (/dashboard/customers)
 * @package global-tractors
 */
if (!defined('ABSPATH')) exit;
gti_require_login();

$current_user = wp_get_current_user();
$user_name = $current_user->display_name ?: $current_user->user_login;
$user_avatar = get_avatar_url($current_user->ID, ['size' => 80]);

// Database
global $wpdb;
gti_ensure_customers_table();
$table_name = gti_customers_table();

// Everyone who submitted a Request Equipment or a Request Quotation becomes a
// customer here. New submissions sync on arrival; this backfills anything that
// predates the sync hooks (throttled to once every 5 minutes).
gti_sync_customers_from_sources();

// Filters
$search        = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$source_filter = isset($_GET['source']) ? sanitize_text_field($_GET['source']) : '';

// Pagination — 'page_num' is used instead of 'paged' because WordPress reserves 'paged'
$paged    = isset($_GET['page_num']) ? max(1, intval($_GET['page_num'])) : 1;
$per_page = 10;
$offset   = ($paged - 1) * $per_page;

// Build query
$where  = "WHERE 1=1";
$params = [];

if ($search) {
    $where      .= " AND (name LIKE %s OR company LIKE %s OR email LIKE %s OR phone LIKE %s OR customer_id LIKE %s)";
    $search_like = '%' . $wpdb->esc_like($search) . '%';
    $params      = array_merge($params, [$search_like, $search_like, $search_like, $search_like, $search_like]);
}
if ($status_filter) {
    $where   .= " AND status = %s";
    $params[] = $status_filter;
}
if ($source_filter) {
    $where   .= " AND source = %s";
    $params[] = $source_filter;
}

// Count
$count_query = "SELECT COUNT(*) FROM {$table_name} {$where}";
$total       = !empty($params) ? $wpdb->get_var($wpdb->prepare($count_query, $params)) : $wpdb->get_var($count_query);
$total_pages = ceil($total / $per_page);

// Fetch
if (!empty($params)) {
    $customers = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$table_name} {$where} ORDER BY registered_date DESC LIMIT %d OFFSET %d",
        array_merge($params, [$per_page, $offset])
    ));
} else {
    $customers = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$table_name} {$where} ORDER BY registered_date DESC LIMIT %d OFFSET %d",
        $per_page, $offset
    ));
}

// Status counts — unfiltered, so the stat cards do not move with the search box
$total_customers = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
$active_count    = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE status = 'active'");
$inactive_count  = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE status <> 'active'");
$total_requests  = (int) $wpdb->get_var("SELECT COALESCE(SUM(total_transactions), 0) FROM {$table_name}");

// Where customers came from, for the filter dropdown
$sources = $wpdb->get_col("SELECT DISTINCT source FROM {$table_name} WHERE source <> '' ORDER BY source");
$source_labels = array(
    'request-equipment' => 'Request Equipment',
    'request-quotation' => 'Request Quotation',
    'sell-equipment'    => 'Sell Equipment',
);

// Format currency
function gti_fmt_currency($amount) {
    return 'IDR ' . number_format((float)$amount, 0, ',', '.');
}
function gti_fmt_date($date) {
    if (!$date) return '-';
    return date('d M Y', strtotime($date));
}
function gti_customer_initials($name) {
    $parts = explode(' ', trim($name));
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $p) {
        $initials .= mb_strtoupper(mb_substr($p, 0, 1));
    }
    return $initials;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers - <?php bloginfo('name'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo GTI_CHILD_URL; ?>/assets/css/dashboard.css">
    <style>
        /* Border overrides — match request-equipment style */
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
        .gti-ue-table .col-customer { width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .gti-ue-table .col-company { width: 160px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .gti-ue-table .col-email { width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .gti-ue-table .col-phone { width: 130px; }
        .gti-ue-table .col-status { width: 110px; text-align: center; white-space: nowrap; }
        .gti-ue-table .col-transactions { width: 100px; text-align: center; }
        .gti-ue-table .col-actions { width: 70px; text-align: center; }

        /* ====== Content Layout ====== */
        .gti-content { display: flex; gap: 24px; align-items: flex-start; }
        .gti-content-left { flex: 1; min-width: 0; }

        /* ====== Right Drawer — In-Flow (≥1600px) ====== */
        .gti-drawer { width: 320px; max-width: calc(33vw - 100px); flex-shrink: 0; background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; display: flex; flex-direction: column; height: fit-content; }
        .gti-drawer-body { overflow-y: visible; }
        .gti-drawer .gti-drawer-close { display: none; }
        .gti-main { min-width: 0; }

        /* ====== Drawer Backdrop (only for <1200px) ====== */
        .gti-drawer-backdrop { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 1000; opacity: 0; transition: opacity 0.3s ease; }
        .gti-drawer-backdrop.show { display: block; opacity: 1; }

        /* ====== Fixed Drawer Mode (<1600px) ====== */
        @media (max-width: 1599px) {
            .gti-content { display: block; }
            .gti-drawer { position: fixed; top: 0; right: 0; bottom: 0; left: auto; width: 420px; max-width: 100vw; max-height: 100vh; border-radius: 12px 0 0 12px; border: none; box-shadow: -4px 0 24px rgba(0,0,0,0.15); z-index: 1001; transform: translateX(100%); transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); height: auto; }
            .gti-drawer.open { transform: translateX(0); }
            .gti-drawer .gti-drawer-close { display: flex; }
            .gti-drawer-body { overflow-y: auto; flex: 1; }
        }
        @media (max-width: 560px) {
            .gti-drawer { width: 100%; border-radius: 16px 16px 0 0; top: auto; max-height: 85vh; transform: translateY(100%); }
            .gti-drawer.open { transform: translateY(0); }
        }

        /* ====== Drawer Header ====== */
        .gti-drawer-header { display: flex; align-items: center; justify-content: space-between; padding: 20px 24px; border-bottom: 1px solid #e5e7eb; flex-shrink: 0; }
        .gti-drawer-header-left { display: flex; align-items: center; gap: 12px; }
        .gti-drawer-header-left h2 { margin: 0; font-size: 16px; font-weight: 600; color: #1a1f36; }
        .gti-drawer-status { display: inline-flex; align-items: center; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .gti-drawer-status.status-active { background: #d1fae5; color: #047857; }
        .gti-drawer-status.status-inactive { background: #fee2e2; color: #dc2626; }
        .gti-drawer-close { width: 32px; height: 32px; border-radius: 6px; border: 1px solid #e5e7eb; background: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center; color: #6b7280; font-size: 14px; transition: all 0.15s; flex-shrink: 0; }
        .gti-drawer-close:hover { background: #f3f4f6; }

        /* ====== Drawer Body ====== */
        .gti-drawer-body { flex: 1; overflow-y: auto; padding: 24px; }

        /* ====== Drawer Sections ====== */
        .gti-drawer-section { margin-bottom: 24px; }
        .gti-drawer-section:last-child { margin-bottom: 0; }
        .gti-drawer-section-title { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px solid #f3f4f6; }
        .gti-drawer-section-title i { color: #F5A623; font-size: 14px; }

        /* ====== Drawer Detail Rows ====== */
        .gti-drawer-row { display: flex; justify-content: space-between; align-items: flex-start; padding: 7px 0; gap: 12px; }
        .gti-drawer-row + .gti-drawer-row { border-top: 1px solid #f9fafb; }
        .gti-drawer-label { font-size: 13px; color: #9ca3af; flex-shrink: 0; min-width: 110px; }
        .gti-drawer-value { font-size: 13px; font-weight: 500; color: #1a1f36; text-align: right; word-break: break-word; }
        .gti-drawer-value.is-link { color: #2563eb; }

        /* ====== Drawer Footer ====== */
        .gti-drawer-footer { display: flex; align-items: center; gap: 8px; padding: 16px 24px; border-top: 1px solid #e5e7eb; flex-shrink: 0; background: #fff; }
        .gti-drawer-btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 16px; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer; transition: all 0.15s; border: 1px solid #e5e7eb; background: #fff; color: #374151; font-family: inherit; text-decoration: none; }
        .gti-drawer-btn:hover { background: #f9fafb; }
        .gti-drawer-btn-primary { background: #F5A623; color: #1a1f36; border-color: #F5A623; font-weight: 600; }
        .gti-drawer-btn-primary:hover { background: #e6991a; }

        /* ====== Customer Cell ====== */
        .gti-cust-cell { display: flex; align-items: center; gap: 12px; }
        .gti-cust-avatar { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; flex-shrink: 0; color: #fff; }
        .gti-cust-avatar.bg-1 { background: #f59e0b; }
        .gti-cust-avatar.bg-2 { background: #3b82f6; }
        .gti-cust-avatar.bg-3 { background: #10b981; }
        .gti-cust-avatar.bg-4 { background: #8b5cf6; }
        .gti-cust-avatar.bg-5 { background: #ef4444; }
        .gti-cust-avatar.bg-6 { background: #06b6d4; }
        .gti-cust-avatar.bg-7 { background: #ec4899; }
        .gti-cust-name-group { display: flex; flex-direction: column; min-width: 0; }
        .gti-cust-name-group strong { font-size: 13px; color: #111827; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .gti-cust-name-group small { font-size: 11px; color: #9ca3af; }

        /* ====== Active Row Highlight ====== */
        .gti-ue-table tbody tr.active-row { background: #FFF8EC; }
        .gti-ue-table tbody tr { cursor: pointer; transition: background 0.15s; }
        .gti-ue-table tbody tr:hover { background: #f9fafb; }
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
                    <a href="<?php echo esc_url(gti_dashboard_url('customers')); ?>" class="gti-nav-item active"><i class="fas fa-users"></i><span>Customers</span></a>
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
                        <h1 class="gti-page-title">Customers</h1>
                        <p class="gti-welcome">Manage customer data and relationships <span>&#128101;</span></p>
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
                        <div class="gti-ue-stat-icon"><i class="fas fa-users"></i></div>
                        <div class="gti-ue-stat-info">
                            <p class="gti-ue-stat-label">Total Customers</p>
                            <p class="gti-ue-stat-value"><?php echo esc_html($total_customers); ?></p>
                        </div>
                    </div>
                    <div class="gti-ue-stat-card">
                        <div class="gti-ue-stat-icon available"><i class="fas fa-user-check"></i></div>
                        <div class="gti-ue-stat-info">
                            <p class="gti-ue-stat-label">Active</p>
                            <p class="gti-ue-stat-value"><?php echo esc_html($active_count); ?></p>
                        </div>
                    </div>
                    <div class="gti-ue-stat-card">
                        <div class="gti-ue-stat-icon sold"><i class="fas fa-user-clock"></i></div>
                        <div class="gti-ue-stat-info">
                            <p class="gti-ue-stat-label">Inactive</p>
                            <p class="gti-ue-stat-value"><?php echo esc_html($inactive_count); ?></p>
                        </div>
                    </div>
                    <div class="gti-ue-stat-card">
                        <div class="gti-ue-stat-icon reserved"><i class="fas fa-file-signature"></i></div>
                        <div class="gti-ue-stat-info">
                            <p class="gti-ue-stat-label">Requests &amp; Quotations</p>
                            <p class="gti-ue-stat-value"><?php echo esc_html($total_requests); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Toolbar -->
                <form class="gti-ue-toolbar" method="get">
                    <input type="hidden" name="gti_page" value="customers">
                    <div class="gti-ue-toolbar-left">
                        <div class="gti-ue-search">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" placeholder="Search customers..." value="<?php echo esc_attr($search); ?>">
                        </div>
                        <div class="gti-ue-filter">
                            <select name="status">
                                <option value="">All Status</option>
                                <option value="active" <?php selected($status_filter, 'active'); ?>>Active</option>
                                <option value="inactive" <?php selected($status_filter, 'inactive'); ?>>Inactive</option>
                            </select>
                        </div>
                        <?php if (!empty($sources)): ?>
                        <div class="gti-ue-filter">
                            <select name="source">
                                <option value="">All Sources</option>
                                <?php foreach ($sources as $src): ?>
                                    <option value="<?php echo esc_attr($src); ?>" <?php selected($source_filter, $src); ?>>
                                        <?php echo esc_html($source_labels[$src] ?? ucwords(str_replace('-', ' ', $src))); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        <a href="<?php echo esc_url(gti_dashboard_url('customers')); ?>" class="gti-ue-btn-reset">
                            <i class="fas fa-rotate-right"></i> Reset
                        </a>
                    </div>
                </form>

                <!-- Table -->
                <div class="gti-ue-table-card">
                    <table class="gti-ue-table">
                        <thead>
                            <tr>
                                <th class="col-customer">Customer</th>
                                <th class="col-company">Company</th>
                                <th class="col-email">Email</th>
                                <th class="col-phone">Phone</th>
                                <th class="col-status">Status</th>
                                <th class="col-transactions">Transactions</th>
                                <th class="col-actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($customers)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 60px 20px;">
                                        <div style="color: #9ca3af;">
                                            <i class="fas fa-users" style="font-size: 48px; margin-bottom: 16px; display: block;"></i>
                                            <p style="font-size: 16px; font-weight: 500; margin-bottom: 8px;">No customers found</p>
                                            <p style="font-size: 14px;">
                                                <?php echo ($search || $status_filter || $source_filter)
                                                    ? 'Try adjusting your filters'
                                                    : 'Customers appear here automatically once someone submits a Request Equipment or Request Quotation'; ?>
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($customers as $i => $c): ?>
                                    <?php
                                    $initials = gti_customer_initials($c->name);
                                    $bg_class = 'bg-' . (($i % 7) + 1);
                                    ?>
                                    <tr data-cust-id="<?php echo esc_attr($c->customer_id); ?>" data-cust='<?php echo esc_attr(json_encode($c)); ?>'>
                                        <td class="col-customer">
                                            <div class="gti-cust-cell">
                                                <div class="gti-cust-avatar <?php echo esc_attr($bg_class); ?>"><?php echo esc_html($initials); ?></div>
                                                <div class="gti-cust-name-group">
                                                    <strong><?php echo esc_html($c->name); ?></strong>
                                                    <small><?php echo esc_html($c->customer_id); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="col-company">
                                            <strong style="color:#1a1f36"><?php echo esc_html($c->company ?: '—'); ?></strong>
                                        </td>
                                        <td class="col-email">
                                            <a href="mailto:<?php echo esc_attr($c->email); ?>" style="color:#2563eb;text-decoration:none;font-size:13px"><?php echo esc_html($c->email); ?></a>
                                        </td>
                                        <td class="col-phone"><?php echo esc_html($c->phone ?: '—'); ?></td>
                                        <td class="col-status">
                                            <span class="gti-badge-status <?php echo $c->status === 'active' ? 'available' : 'sold'; ?>"><?php echo esc_html(ucfirst($c->status)); ?></span>
                                        </td>
                                        <td class="col-transactions"><?php echo (int) $c->total_transactions; ?></td>
                                        <td class="col-actions">
                                            <div class="gti-ue-action-menu">
                                                <button class="gti-ue-action-toggle" title="Actions"><i class="fas fa-ellipsis-v"></i></button>
                                                <div class="gti-ue-action-dropdown">
                                                    <button type="button" class="gti-ue-action-item" onclick='showCustomerDetail(<?php echo esc_attr(json_encode($c)); ?>)'>
                                                        <i class="fas fa-eye"></i> View Details
                                                    </button>
                                                    <a href="mailto:<?php echo esc_attr($c->email); ?>" class="gti-ue-action-item">
                                                        <i class="fas fa-envelope"></i> Send Email
                                                    </a>
                                                    <a href="<?php echo esc_url(gti_dashboard_url('customer-detail') . '?' . http_build_query(['id' => $c->customer_id])); ?>" class="gti-ue-action-item">
                                                        <i class="fas fa-external-link-alt"></i> Full Profile
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
                                $query_params = [];
                                if ($search) $query_params['search'] = $search;
                                if ($status_filter) $query_params['status'] = $status_filter;
                                if ($source_filter) $query_params['source'] = $source_filter;
                                $query_params['gti_page'] = 'customers';
                                $base_url = gti_dashboard_url('customers');
                                ?>
                                <?php if ($paged > 1): ?>
                                    <a href="<?php echo esc_url($base_url . '?' . http_build_query(array_merge($query_params, ['page_num' => $paged - 1]))); ?>" class="gti-ue-page-btn"><i class="fas fa-chevron-left"></i></a>
                                <?php else: ?>
                                    <button class="gti-ue-page-btn" disabled><i class="fas fa-chevron-left"></i></button>
                                <?php endif; ?>

                                <?php
                                $start = max(1, $paged - 2);
                                $end   = min($total_pages, $paged + 2);
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
                </div><!-- /.gti-content-left -->

                <!-- Detail Drawer (In-Flow ≥1600px / Fixed <1600px) -->
                <div class="gti-drawer-backdrop" id="drawerBackdrop"></div>
                <div class="gti-drawer" id="custDetailDrawer">
                    <div class="gti-drawer-header">
                        <div class="gti-drawer-header-left">
                            <h2 id="drawer-cust-name">-</h2>
                            <span class="gti-drawer-status" id="drawer-status-badge">Active</span>
                        </div>
                        <button type="button" class="gti-drawer-close" onclick="closeDetailDrawer()"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="gti-drawer-body">
                        <div class="gti-drawer-section">
                            <div class="gti-drawer-section-title"><i class="fas fa-user"></i> Profile Summary</div>
                            <div class="gti-drawer-row"><span class="gti-drawer-label">Customer ID</span><span class="gti-drawer-value" id="drawer-cust-id">-</span></div>
                            <div class="gti-drawer-row"><span class="gti-drawer-label">Email</span><span class="gti-drawer-value is-link" id="drawer-email">-</span></div>
                            <div class="gti-drawer-row"><span class="gti-drawer-label">Phone</span><span class="gti-drawer-value" id="drawer-phone">-</span></div>
                            <div class="gti-drawer-row"><span class="gti-drawer-label">Since</span><span class="gti-drawer-value" id="drawer-since">-</span></div>
                            <div class="gti-drawer-row"><span class="gti-drawer-label">Rating</span><span class="gti-drawer-value" id="drawer-rating">-</span></div>
                        </div>
                        <div class="gti-drawer-section">
                            <div class="gti-drawer-section-title"><i class="fas fa-building"></i> Company Information</div>
                            <div class="gti-drawer-row"><span class="gti-drawer-label">Company</span><span class="gti-drawer-value" id="drawer-company">-</span></div>
                            <div class="gti-drawer-row"><span class="gti-drawer-label">Industry</span><span class="gti-drawer-value" id="drawer-industry">-</span></div>
                            <div class="gti-drawer-row"><span class="gti-drawer-label">Location</span><span class="gti-drawer-value" id="drawer-location">-</span></div>
                            <div class="gti-drawer-row"><span class="gti-drawer-label">NPWP</span><span class="gti-drawer-value" id="drawer-npwp">-</span></div>
                        </div>
                        <div class="gti-drawer-section">
                            <div class="gti-drawer-section-title"><i class="fas fa-chart-bar"></i> Statistics</div>
                            <div class="gti-drawer-row"><span class="gti-drawer-label">Transactions</span><span class="gti-drawer-value" id="drawer-transactions">-</span></div>
                            <div class="gti-drawer-row"><span class="gti-drawer-label">Total Spent</span><span class="gti-drawer-value" id="drawer-spent">-</span></div>
                            <div class="gti-drawer-row"><span class="gti-drawer-label">Last Contact</span><span class="gti-drawer-value" id="drawer-last-contact">-</span></div>
                        </div>
                    </div>
                    <div class="gti-drawer-footer">
                        <a href="#" class="gti-drawer-btn gti-drawer-btn-primary" id="drawer-btn-profile"><i class="fas fa-external-link-alt"></i> Full Profile</a>
                        <a href="#" class="gti-drawer-btn" id="drawer-btn-email"><i class="fas fa-envelope"></i> Send Email</a>
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

        // Backdrop click → close drawer
        document.getElementById('drawerBackdrop').addEventListener('click', function() {
            closeDetailDrawer();
        });

        // Close drawer on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeDetailDrawer();
        });

        // Row click → open drawer
        document.querySelectorAll('.gti-ue-table tbody tr[data-cust]').forEach(function(row) {
            row.addEventListener('click', function(e) {
                if (e.target.closest('.gti-ue-action-menu')) return;
                var cust;
                try { cust = JSON.parse(this.getAttribute('data-cust')); } catch(err) { return; }
                showCustomerDetail(cust);
            });
        });

        // Auto-populate drawer with first row on page load
        var firstRow = document.querySelector('.gti-ue-table tbody tr[data-cust]');
        if (firstRow) {
            try {
                var firstCust = JSON.parse(firstRow.getAttribute('data-cust'));
                updateDrawerContent(firstCust);
            } catch(e) {}
        }
    });

    var _currentDrawerCust = null;

    function updateDrawerContent(cust) {
        _currentDrawerCust = cust;
        document.getElementById('drawer-cust-name').textContent = cust.name || '-';
        var badge = document.getElementById('drawer-status-badge');
        badge.textContent = (cust.status || 'active').charAt(0).toUpperCase() + (cust.status || 'active').slice(1);
        badge.className = 'gti-drawer-status status-' + (cust.status || 'active');

        document.getElementById('drawer-cust-id').textContent = cust.customer_id || '-';
        document.getElementById('drawer-email').textContent = cust.email || '-';
        document.getElementById('drawer-phone').textContent = cust.phone || '-';
        document.getElementById('drawer-since').textContent = cust.registered_date ? formatDateID(cust.registered_date) : '-';
        document.getElementById('drawer-rating').textContent = cust.rating ? cust.rating + ' / 5.0' : '-';

        document.getElementById('drawer-company').textContent = cust.company || '-';
        document.getElementById('drawer-industry').textContent = cust.industry || '-';
        document.getElementById('drawer-location').textContent = [cust.city, cust.province, cust.country].filter(Boolean).join(', ') || '-';
        document.getElementById('drawer-npwp').textContent = cust.npwp || '-';

        document.getElementById('drawer-transactions').textContent = cust.total_transactions || '0';
        document.getElementById('drawer-spent').textContent = cust.total_spent ? formatCurrencyID(cust.total_spent) : '-';
        document.getElementById('drawer-last-contact').textContent = cust.last_contact ? formatDateID(cust.last_contact) : '-';

        var profileUrl = '<?php echo esc_url(gti_dashboard_url('customer-detail')); ?>' + '?id=' + (cust.customer_id || '');
        var emailUrl = 'mailto:' + (cust.email || '');
        document.getElementById('drawer-btn-profile').href = profileUrl;
        document.getElementById('drawer-btn-email').href = emailUrl;

        document.querySelectorAll('.gti-ue-table tbody tr').forEach(function(r) { r.classList.remove('active-row'); });
        var activeRow = document.querySelector('.gti-ue-table tbody tr[data-cust-id="' + cust.customer_id + '"]');
        if (activeRow) activeRow.classList.add('active-row');
    }

    function showCustomerDetail(cust) {
        updateDrawerContent(cust);
        if (window.innerWidth < 1600) {
            document.getElementById('custDetailDrawer').classList.add('open');
            document.getElementById('drawerBackdrop').classList.add('show');
            document.body.style.overflow = 'hidden';
        }
        document.querySelectorAll('.gti-ue-action-dropdown.show').forEach(function(d) { d.classList.remove('show'); });
    }

    function closeDetailDrawer() {
        document.getElementById('custDetailDrawer').classList.remove('open');
        document.getElementById('drawerBackdrop').classList.remove('show');
        document.body.style.overflow = '';
        _currentDrawerCust = null;
    }

    function formatDateID(dateStr) {
        if (!dateStr) return '-';
        var d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        var months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        return d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
    }

    function formatCurrencyID(amount) {
        return 'IDR ' + Number(amount).toLocaleString('id-ID');
    }
    </script>
</body>
</html>
