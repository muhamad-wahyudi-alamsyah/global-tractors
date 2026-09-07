<?php
/**
 * Template: Media Library (/dashboard/media-library)
 * @package global-tractors
 */
if (!defined('ABSPATH')) exit;
gti_require_login();

$current_user = wp_get_current_user();
$user_name = $current_user->display_name ?: $current_user->user_login;
$user_avatar = get_avatar_url($current_user->ID, ['size' => 80]);

// Media comes from the WordPress media library — see inc/modules/media-library.php
// Filters
$search       = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$type_filter  = isset($_GET['type'])   ? sanitize_text_field($_GET['type'])   : '';
$month_filter = isset($_GET['month'])  ? sanitize_text_field($_GET['month'])  : '';

// Pagination — 'page_num' is used instead of 'paged' because WordPress reserves 'paged'
$paged    = isset($_GET['page_num']) ? max(1, intval($_GET['page_num'])) : 1;
$per_page = 24;

$results     = gti_query_media(array(
    'search'   => $search,
    'type'     => $type_filter,
    'month'    => $month_filter,
    'per_page' => $per_page,
    'page'     => $paged,
));
$page_items  = $results['items'];
$total_items = $results['total'];
$total_pages = $results['pages'];

// Statistics
$counts          = gti_media_counts();
$total_media     = $counts['total'];
$total_images    = $counts['images'];
$total_documents = $counts['documents'];
$total_size      = $counts['size'];

// Month grouping / filter options
$unique_months = gti_media_months();
$month_counts  = gti_media_month_counts();
$is_filtered   = ($search || $type_filter || $month_filter);

$can_upload = current_user_can('upload_files');
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Media Library - <?php bloginfo('name'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo GTI_CHILD_URL; ?>/assets/css/dashboard.css">
    <style>
        /* ====== Stats Row ====== */
        .gti-ml-stats { display: flex; gap: 18px; margin-bottom: 20px; }
        .gti-ml-stat { flex: 1; min-width: 0; display: flex; align-items: center; gap: 16px; padding: 20px; background: var(--gti-card); border: 1px solid #e5e7eb; border-radius: 15px; }
        .gti-ml-stat-icon { width: 56px; height: 56px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; border-radius: 14px; font-size: 22px; }
        .gti-ml-stat-icon.blue { background: #e8f0fe; color: #3b82f6; }
        .gti-ml-stat-icon.green { background: #e6ffe3; color: #3daf00; }
        .gti-ml-stat-icon.orange { background: #fff0e0; color: #F5A623; }
        .gti-ml-stat-icon.purple { background: #f3e8ff; color: #8b5cf6; }
        .gti-ml-stat-info { display: flex; flex-direction: column; gap: 4px; }
        .gti-ml-stat-label { font-size: 12px; font-weight: 500; color: #8d8c8c; margin: 0; }
        .gti-ml-stat-value { font-size: 28px; font-weight: 700; color: var(--gti-dark); margin: 0; line-height: 1; }

        /* ====== Toolbar ====== */
        .gti-ml-toolbar { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; gap: 12px; flex-wrap: wrap; }
        .gti-ml-toolbar-left { display: flex; align-items: center; gap: 8px; flex: 1; flex-wrap: wrap; }
        .gti-ml-search { display: flex; align-items: center; gap: 8px; padding: 8px 12px; background: var(--gti-card); border: 1px solid #d1d5db; border-radius: 10px; min-width: 180px; flex: 1; max-width: 320px; }
        .gti-ml-search i { color: var(--gti-muted); font-size: 13px; }
        .gti-ml-search input { border: none; outline: none; font-size: 13px; font-family: inherit; color: var(--gti-dark); width: 100%; background: transparent; }
        .gti-ml-search input::placeholder { color: var(--gti-muted); }
        .gti-ml-filter select { padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px; font-family: inherit; color: var(--gti-dark); background: var(--gti-card); cursor: pointer; outline: none; min-width: 120px; }
        .gti-ml-filter select:focus { border-color: #F5A623; }
        .gti-ml-btn-reset { display: inline-flex; align-items: center; gap: 6px; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px; font-weight: 500; color: var(--gti-dark); text-decoration: none; background: var(--gti-card); cursor: pointer; font-family: inherit; }
        .gti-ml-btn-reset:hover { background: #f9fafb; }
        .gti-ml-toolbar-right { display: flex; gap: 8px; }
        .gti-ml-view-toggle { display: flex; border: 1px solid #d1d5db; border-radius: 8px; overflow: hidden; }
        .gti-ml-view-btn { padding: 8px 12px; border: none; background: var(--gti-card); color: var(--gti-muted); cursor: pointer; font-size: 13px; transition: all 0.15s; }
        .gti-ml-view-btn.active { background: #F5A623; color: #1a1f36; }
        .gti-ml-view-btn:hover:not(.active) { background: #f9fafb; }
        .gti-ml-view-btn + .gti-ml-view-btn { border-left: 1px solid #d1d5db; }
        .gti-ml-upload-btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; color: #fff; background: #F5A623; cursor: pointer; font-family: inherit; transition: all 0.15s; }
        .gti-ml-upload-btn:hover { background: #e6991a; }
        .gti-ml-upload-btn i { font-size: 12px; }

        /* ====== Grid View ====== */
        .gti-ml-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 16px; margin-bottom: 20px; }
        .gti-ml-card { background: var(--gti-card); border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; cursor: pointer; transition: all 0.2s; position: relative; }
        .gti-ml-card:hover { border-color: #F5A623; box-shadow: 0 4px 12px rgba(245,166,35,0.15); }
        .gti-ml-card.active { border-color: #F5A623; box-shadow: 0 0 0 2px rgba(245,166,35,0.3); }
        .gti-ml-card-thumb { width: 100%; aspect-ratio: 1; background: #f3f4f6; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        .gti-ml-card-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .gti-ml-card-thumb .gti-ml-doc-icon { font-size: 36px; color: #9ca3af; }
        .gti-ml-card-info { padding: 10px 12px; }
        .gti-ml-card-name { font-size: 12px; font-weight: 600; color: var(--gti-dark); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .gti-ml-card-meta { display: flex; justify-content: space-between; align-items: center; margin-top: 4px; }
        .gti-ml-card-date { font-size: 11px; color: var(--gti-muted); }
        .gti-ml-card-ext { font-size: 10px; font-weight: 600; color: #fff; background: #6b7280; padding: 1px 6px; border-radius: 4px; text-transform: uppercase; }
        .gti-ml-card-ext.img { background: #3daf00; }
        .gti-ml-card-ext.pdf { background: #ef4444; }
        .gti-ml-card-ext.doc { background: #3b82f6; }
        .gti-ml-card-check { position: absolute; top: 8px; left: 8px; width: 22px; height: 22px; border-radius: 50%; border: 2px solid rgba(255,255,255,0.8); background: rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 10px; opacity: 0; transition: opacity 0.15s; }
        .gti-ml-card:hover .gti-ml-card-check { opacity: 1; }
        .gti-ml-card.selected .gti-ml-card-check { opacity: 1; background: #F5A623; border-color: #F5A623; }

        /* ====== List View ====== */
        .gti-ml-grid.is-list { display: block; }
        .gti-ml-grid.is-list .gti-ml-card { display: flex; align-items: center; gap: 14px; margin-bottom: 8px; padding: 8px 12px; }
        .gti-ml-grid.is-list .gti-ml-card-thumb { width: 44px; height: 44px; aspect-ratio: auto; flex-shrink: 0; border-radius: 8px; }
        .gti-ml-grid.is-list .gti-ml-card-thumb .gti-ml-doc-icon { font-size: 18px; }
        .gti-ml-grid.is-list .gti-ml-card-info { flex: 1; min-width: 0; padding: 0; display: flex; align-items: center; justify-content: space-between; gap: 16px; }
        .gti-ml-grid.is-list .gti-ml-card-name { flex: 1; min-width: 0; }
        .gti-ml-grid.is-list .gti-ml-card-meta { margin-top: 0; gap: 14px; flex-shrink: 0; }
        .gti-ml-grid.is-list .gti-ml-month-header { display: flex; }

        /* ====== Month Group Header ====== */
        .gti-ml-month-header { display: flex; align-items: center; gap: 10px; padding: 8px 0; margin-top: 8px; }
        .gti-ml-month-header h3 { font-size: 14px; font-weight: 600; color: var(--gti-dark); margin: 0; }
        .gti-ml-month-count { font-size: 12px; color: var(--gti-muted); }
        .gti-ml-month-line { flex: 1; height: 1px; background: #e5e7eb; }

        /* ====== Empty State ====== */
        .gti-ml-empty { text-align: center; padding: 80px 20px; color: var(--gti-muted); }
        .gti-ml-empty i { font-size: 48px; margin-bottom: 16px; display: block; }
        .gti-ml-empty h3 { font-size: 16px; font-weight: 500; color: var(--gti-gray); margin-bottom: 8px; }
        .gti-ml-empty p { font-size: 14px; }

        /* ====== Pagination ====== */
        .gti-ml-pagination { display: flex; justify-content: space-between; align-items: center; padding: 16px 0; border-top: 1px solid #f3f4f6; }
        .gti-ml-page-info { font-size: 13px; color: var(--gti-muted); }
        .gti-ml-page-controls { display: flex; gap: 4px; }
        .gti-ml-page-btn { display: inline-flex; align-items: center; justify-content: center; min-width: 32px; height: 32px; padding: 0 8px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 13px; font-weight: 500; color: var(--gti-dark); text-decoration: none; background: var(--gti-card); cursor: pointer; font-family: inherit; }
        .gti-ml-page-btn:hover:not(:disabled) { background: #f9fafb; border-color: #d1d5db; }
        .gti-ml-page-btn.active { background: #F5A623; color: #1a1f36; border-color: #F5A623; }
        .gti-ml-page-btn:disabled { opacity: 0.4; cursor: not-allowed; }

        /* ====== Right Drawer ====== */
        .gti-content { display: flex; gap: 24px; align-items: flex-start; }
        .gti-content-left { flex: 1; min-width: 0; min-width: 0; }
        .gti-main { min-width: 0; }
        .gti-drawer-backdrop { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 1000; opacity: 0; transition: opacity 0.3s ease; }
        .gti-drawer-backdrop.show { display: block; opacity: 1; }

        .gti-drawer { width: 400px; max-width: calc(33vw - 80px); flex-shrink: 0; background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; display: flex; flex-direction: column; height: fit-content; }
        .gti-drawer-body { overflow-y: visible; }
        .gti-drawer .gti-drawer-close { display: none; }

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

        .gti-drawer-header { display: flex; align-items: center; justify-content: space-between; padding: 20px 24px; border-bottom: 1px solid #e5e7eb; flex-shrink: 0; }
        .gti-drawer-header-left { display: flex; align-items: center; gap: 12px; min-width: 0; }
        .gti-drawer-header-left h2 { margin: 0; font-size: 16px; font-weight: 600; color: #1a1f36; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .gti-drawer-close { width: 32px; height: 32px; border-radius: 6px; border: 1px solid #e5e7eb; background: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center; color: #6b7280; font-size: 14px; transition: all 0.15s; flex-shrink: 0; }
        .gti-drawer-close:hover { background: #f3f4f6; }

        .gti-drawer-body { flex: 1; overflow-y: auto; padding: 24px; }

        .gti-drawer-section { margin-bottom: 24px; }
        .gti-drawer-section:last-child { margin-bottom: 0; }
        .gti-drawer-section-title { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px solid #f3f4f6; }
        .gti-drawer-section-title i { color: #F5A623; font-size: 14px; }

        /* Image Preview */
        .gti-drawer-preview { width: 100%; border-radius: 10px; overflow: hidden; background: #f3f4f6; margin-bottom: 16px; display: flex; align-items: center; justify-content: center; min-height: 200px; max-height: 300px; }
        .gti-drawer-preview img { width: 100%; max-height: 300px; object-fit: contain; }
        .gti-drawer-preview .gti-ml-doc-icon-lg { font-size: 64px; color: #9ca3af; }

        .gti-drawer-row { display: flex; justify-content: space-between; align-items: flex-start; padding: 7px 0; gap: 12px; }
        .gti-drawer-row + .gti-drawer-row { border-top: 1px solid #f9fafb; }
        .gti-drawer-label { font-size: 13px; color: #9ca3af; flex-shrink: 0; min-width: 100px; }
        .gti-drawer-value { font-size: 13px; font-weight: 500; color: #1a1f36; text-align: right; word-break: break-all; }
        .gti-drawer-value.is-link { color: #2563eb; cursor: pointer; text-decoration: underline; }

        .gti-drawer-footer { display: flex; align-items: center; gap: 8px; padding: 16px 24px; border-top: 1px solid #e5e7eb; flex-shrink: 0; background: #fff; }
        .gti-drawer-btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 16px; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer; transition: all 0.15s; border: 1px solid #e5e7eb; background: #fff; color: #374151; font-family: inherit; }
        .gti-drawer-btn:hover { background: #f9fafb; }
        .gti-drawer-btn-primary { background: #F5A623; color: #1a1f36; border-color: #F5A623; font-weight: 600; }
        .gti-drawer-btn-primary:hover { background: #e6991a; }
        .gti-drawer-btn-danger { color: #ef4444; border-color: #fecaca; }
        .gti-drawer-btn-danger:hover { background: #fef2f2; }
        .gti-drawer-footer-spacer { flex: 1; }

        /* ====== Responsive ====== */
        @media (max-width: 1200px) {
            .gti-ml-toolbar { flex-direction: column; align-items: flex-start; }
            .gti-ml-toolbar-left { width: 100%; }
            .gti-ml-search input { width: 100%; }
        }
        @media (max-width: 768px) {
            .gti-ml-stats { flex-wrap: wrap; }
            .gti-ml-stat { flex: 1 1 calc(50% - 9px); }
            .gti-ml-grid { grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 10px; }
            .gti-ml-toolbar-left { flex-direction: column; }
            .gti-ml-search { max-width: 100%; }
            .gti-ml-filter select { width: 100%; }
            .gti-ml-page-info { display: none; }
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
                    <a href="<?php echo esc_url(gti_dashboard_url('media-library')); ?>" class="gti-nav-item active"><i class="fas fa-photo-video"></i><span>Media Library</span></a>
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
                        <h1 class="gti-page-title">Media Library</h1>
                        <p class="gti-welcome">Manage all uploaded files and images <span>&#128247;</span></p>
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
                    <div class="gti-ml-stats">
                        <div class="gti-ml-stat">
                            <div class="gti-ml-stat-icon blue"><i class="fas fa-photo-video"></i></div>
                            <div class="gti-ml-stat-info">
                                <p class="gti-ml-stat-label">Total Media</p>
                                <p class="gti-ml-stat-value"><?php echo esc_html($total_media); ?></p>
                            </div>
                        </div>
                        <div class="gti-ml-stat">
                            <div class="gti-ml-stat-icon green"><i class="fas fa-image"></i></div>
                            <div class="gti-ml-stat-info">
                                <p class="gti-ml-stat-label">Images</p>
                                <p class="gti-ml-stat-value"><?php echo esc_html($total_images); ?></p>
                            </div>
                        </div>
                        <div class="gti-ml-stat">
                            <div class="gti-ml-stat-icon orange"><i class="fas fa-file-alt"></i></div>
                            <div class="gti-ml-stat-info">
                                <p class="gti-ml-stat-label">Documents</p>
                                <p class="gti-ml-stat-value"><?php echo esc_html($total_documents); ?></p>
                            </div>
                        </div>
                        <div class="gti-ml-stat">
                            <div class="gti-ml-stat-icon purple"><i class="fas fa-database"></i></div>
                            <div class="gti-ml-stat-info">
                                <p class="gti-ml-stat-label">Total Size</p>
                                <p class="gti-ml-stat-value"><?php echo esc_html(size_format($total_size)); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Toolbar -->
                    <form class="gti-ml-toolbar" method="get">
                        <input type="hidden" name="gti_page" value="media-library">
                        <div class="gti-ml-toolbar-left">
                            <div class="gti-ml-search">
                                <i class="fas fa-search"></i>
                                <input type="text" name="search" placeholder="Search files..." value="<?php echo esc_attr($search); ?>">
                            </div>
                            <div class="gti-ml-filter">
                                <select name="type">
                                    <option value="">All Types</option>
                                    <option value="image" <?php selected($type_filter, 'image'); ?>>Images</option>
                                    <option value="document" <?php selected($type_filter, 'document'); ?>>Documents</option>
                                </select>
                            </div>
                            <div class="gti-ml-filter">
                                <select name="month">
                                    <option value="">All Months</option>
                                    <?php foreach ($unique_months as $mk => $ml): ?>
                                        <option value="<?php echo esc_attr($mk); ?>" <?php selected($month_filter, $mk); ?>><?php echo esc_html($ml); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <a href="<?php echo esc_url(gti_dashboard_url('media-library')); ?>" class="gti-ml-btn-reset">
                                <i class="fas fa-rotate-right"></i> Reset
                            </a>
                        </div>
                        <div class="gti-ml-toolbar-right">
                            <div class="gti-ml-view-toggle">
                                <button type="button" class="gti-ml-view-btn active" data-view="grid" title="Grid View"><i class="fas fa-th"></i></button>
                                <button type="button" class="gti-ml-view-btn" data-view="list" title="List View"><i class="fas fa-list"></i></button>
                            </div>
                            <?php if ($can_upload): ?>
                            <button type="button" class="gti-ml-upload-btn" id="uploadMediaBtn">
                                <i class="fas fa-cloud-upload-alt"></i> Upload File
                            </button>
                            <?php endif; ?>
                        </div>
                    </form>

                    <!-- Grid View -->
                    <div class="gti-ml-grid" id="mediaGrid">
                        <?php if (empty($page_items)): ?>
                            <div class="gti-ml-empty" style="grid-column: 1/-1;">
                                <i class="fas fa-photo-video"></i>
                                <h3>No media found</h3>
                                <p><?php echo $is_filtered ? 'Try adjusting your filters' : 'No files uploaded yet'; ?></p>
                            </div>
                        <?php else: ?>
                            <?php
                            $current_month = '';
                            foreach ($page_items as $item):
                                if ($item['month'] !== $current_month && !$is_filtered):
                                    $current_month = $item['month'];
                                    ?>
                                    <div class="gti-ml-month-header" style="grid-column: 1/-1;">
                                        <h3><?php echo esc_html($item['month_label']); ?></h3>
                                        <span class="gti-ml-month-count"><?php echo (int) ($month_counts[$current_month] ?? 0); ?> files</span>
                                        <div class="gti-ml-month-line"></div>
                                    </div>
                                <?php endif; ?>
                                <div class="gti-ml-card" data-media-id="<?php echo esc_attr($item['id']); ?>"
                                     data-media='<?php echo esc_attr(json_encode($item)); ?>'
                                     onclick="openMediaDetail(this)">
                                    <div class="gti-ml-card-thumb">
                                        <?php if ($item['type'] === 'image'): ?>
                                            <img src="<?php echo esc_url($item['thumb'] ?: $item['url']); ?>" alt="<?php echo esc_attr($item['alt'] ?: $item['filename']); ?>" loading="lazy">
                                        <?php else: ?>
                                            <i class="fas fa-file-alt gti-ml-doc-icon"></i>
                                        <?php endif; ?>
                                        <div class="gti-ml-card-check"><i class="fas fa-check"></i></div>
                                    </div>
                                    <div class="gti-ml-card-info">
                                        <div class="gti-ml-card-name" title="<?php echo esc_attr($item['filename']); ?>"><?php echo esc_html($item['filename']); ?></div>
                                        <div class="gti-ml-card-meta">
                                            <span class="gti-ml-card-date"><?php echo esc_html($item['date_human']); ?></span>
                                            <span class="gti-ml-card-ext <?php echo $item['type'] === 'image' ? 'img' : ''; ?>"><?php echo esc_html($item['ext']); ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="gti-ml-pagination">
                            <div class="gti-ml-page-info">
                                Showing <?php echo esc_html(($paged - 1) * $per_page + 1); ?>-<?php echo esc_html(min($paged * $per_page, $total_items)); ?> of <?php echo esc_html($total_items); ?> files
                            </div>
                            <div class="gti-ml-page-controls">
                                <?php
                                $qp = array();
                                $qp['gti_page'] = 'media-library';
                                if ($search) $qp['search'] = $search;
                                if ($type_filter) $qp['type'] = $type_filter;
                                if ($month_filter) $qp['month'] = $month_filter;
                                $base = gti_dashboard_url('media-library');
                                ?>
                                <?php if ($paged > 1): ?>
                                    <a href="<?php echo esc_url($base . '?' . http_build_query(array_merge($qp, ['page_num' => $paged - 1]))); ?>" class="gti-ml-page-btn"><i class="fas fa-chevron-left"></i></a>
                                <?php else: ?>
                                    <button class="gti-ml-page-btn" disabled><i class="fas fa-chevron-left"></i></button>
                                <?php endif; ?>

                                <?php
                                $start_p = max(1, $paged - 2);
                                $end_p = min($total_pages, $paged + 2);
                                if ($start_p > 1): ?>
                                    <a href="<?php echo esc_url($base . '?' . http_build_query(array_merge($qp, ['page_num' => 1]))); ?>" class="gti-ml-page-btn">1</a>
                                    <?php if ($start_p > 2): ?><span style="padding:0 4px;color:#9ca3af;">...</span><?php endif; ?>
                                <?php endif; ?>
                                <?php for ($i = $start_p; $i <= $end_p; $i++): ?>
                                    <?php if ($i === $paged): ?>
                                        <button class="gti-ml-page-btn active"><?php echo $i; ?></button>
                                    <?php else: ?>
                                        <a href="<?php echo esc_url($base . '?' . http_build_query(array_merge($qp, ['page_num' => $i]))); ?>" class="gti-ml-page-btn"><?php echo $i; ?></a>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                <?php if ($end_p < $total_pages): ?>
                                    <?php if ($end_p < $total_pages - 1): ?><span style="padding:0 4px;color:#9ca3af;">...</span><?php endif; ?>
                                    <a href="<?php echo esc_url($base . '?' . http_build_query(array_merge($qp, ['page_num' => $total_pages]))); ?>" class="gti-ml-page-btn"><?php echo $total_pages; ?></a>
                                <?php endif; ?>

                                <?php if ($paged < $total_pages): ?>
                                    <a href="<?php echo esc_url($base . '?' . http_build_query(array_merge($qp, ['page_num' => $paged + 1]))); ?>" class="gti-ml-page-btn"><i class="fas fa-chevron-right"></i></a>
                                <?php else: ?>
                                    <button class="gti-ml-page-btn" disabled><i class="fas fa-chevron-right"></i></button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div><!-- /.gti-content-left -->

                <!-- Detail Drawer -->
                <div class="gti-drawer-backdrop" id="drawerBackdrop"></div>
                <div class="gti-drawer" id="mediaDetailDrawer">
                    <!-- Header -->
                    <div class="gti-drawer-header">
                        <div class="gti-drawer-header-left">
                            <h2 id="drawer-filename">File Details</h2>
                            <span class="gti-drawer-status status-new" id="drawer-type-badge">IMAGE</span>
                        </div>
                        <button type="button" class="gti-drawer-close" onclick="closeMediaDrawer()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="gti-drawer-body">
                        <!-- Preview -->
                        <div class="gti-drawer-preview" id="drawer-preview"></div>

                        <!-- File Information -->
                        <div class="gti-drawer-section">
                            <div class="gti-drawer-section-title">
                                <i class="fas fa-info-circle"></i> File Information
                            </div>
                            <div class="gti-drawer-row">
                                <span class="gti-drawer-label">File Name</span>
                                <span class="gti-drawer-value" id="drawer-filename-val">-</span>
                            </div>
                            <div class="gti-drawer-row">
                                <span class="gti-drawer-label">File Type</span>
                                <span class="gti-drawer-value" id="drawer-filetype">-</span>
                            </div>
                            <div class="gti-drawer-row">
                                <span class="gti-drawer-label">File Size</span>
                                <span class="gti-drawer-value" id="drawer-filesize">-</span>
                            </div>
                            <div class="gti-drawer-row">
                                <span class="gti-drawer-label">Upload Date</span>
                                <span class="gti-drawer-value" id="drawer-uploaded">-</span>
                            </div>
                            <div class="gti-drawer-row">
                                <span class="gti-drawer-label">Dimensions</span>
                                <span class="gti-drawer-value" id="drawer-dims">-</span>
                            </div>
                            <div class="gti-drawer-row">
                                <span class="gti-drawer-label">Title</span>
                                <span class="gti-drawer-value" id="drawer-title-val">-</span>
                            </div>
                            <div class="gti-drawer-row">
                                <span class="gti-drawer-label">Alt Text</span>
                                <span class="gti-drawer-value" id="drawer-alt">-</span>
                            </div>
                            <div class="gti-drawer-row">
                                <span class="gti-drawer-label">Uploaded By</span>
                                <span class="gti-drawer-value" id="drawer-uploader">-</span>
                            </div>
                            <div class="gti-drawer-row">
                                <span class="gti-drawer-label">Attached To</span>
                                <span class="gti-drawer-value" id="drawer-attached">-</span>
                            </div>
                        </div>

                        <!-- URL Section -->
                        <div class="gti-drawer-section">
                            <div class="gti-drawer-section-title">
                                <i class="fas fa-link"></i> File URL
                            </div>
                            <div class="gti-drawer-row" style="flex-direction: column;">
                                <span class="gti-drawer-value is-link" id="drawer-url" onclick="copyUrl(this)" style="background: #f9fafb; padding: 8px 12px; border-radius: 8px; font-size: 12px; text-align: left; word-break: break-all; cursor: pointer;">-</span>
                                <small style="color: #9ca3af; font-size: 11px; margin-top: 4px;">Click to copy URL</small>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="gti-drawer-footer">
                        <a href="#" class="gti-drawer-btn gti-drawer-btn-primary" id="drawer-download" target="_blank">
                            <i class="fas fa-download"></i> Download
                        </a>
                        <button type="button" class="gti-drawer-btn" id="drawer-copy-btn" onclick="copyUrl(document.getElementById('drawer-url'))">
                            <i class="fas fa-copy"></i> Copy URL
                        </button>
                        <span class="gti-drawer-footer-spacer"></span>
                        <button type="button" class="gti-drawer-btn gti-drawer-btn-danger" id="drawer-delete">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Upload Modal -->
    <div id="uploadModal" style="display:none; position:fixed; inset:0; z-index:2000; background:rgba(0,0,0,0.5); display:none; align-items:center; justify-content:center;">
        <div style="background:#fff; border-radius:12px; padding:32px; max-width:480px; width:90%; box-shadow:0 8px 32px rgba(0,0,0,0.2);">
            <h3 style="margin:0 0 8px; font-size:18px; font-weight:600; color:#1a1f36;">Upload Media</h3>
            <p style="margin:0 0 20px; font-size:13px; color:#6b7280;">Select files to upload to the media library</p>
            <div id="uploadDropZone" style="border:2px dashed #d1d5db; border-radius:10px; padding:40px 20px; text-align:center; cursor:pointer; transition: all 0.2s;">
                <i class="fas fa-cloud-upload-alt" style="font-size:36px; color:#9ca3af; margin-bottom:12px; display:block;"></i>
                <p style="margin:0; font-size:14px; color:#6b7280; font-weight:500;">Drag & drop files here</p>
                <p style="margin:4px 0 0; font-size:12px; color:#9ca3af;">or click to browse</p>
                <input type="file" id="fileInput" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx" style="display:none;">
            </div>
            <div id="uploadProgress" style="margin-top:16px; display:none;">
                <div style="background:#f3f4f6; border-radius:6px; height:6px; overflow:hidden;">
                    <div id="uploadProgressBar" style="background:#F5A623; height:100%; width:0; transition:width 0.3s; border-radius:6px;"></div>
                </div>
                <p id="uploadStatus" style="margin:8px 0 0; font-size:12px; color:#6b7280; text-align:center;"></p>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:20px;">
                <button type="button" class="gti-drawer-btn" onclick="closeUploadModal()">Cancel</button>
                <button type="button" class="gti-drawer-btn gti-drawer-btn-primary" id="startUploadBtn" disabled>Upload</button>
            </div>
        </div>
    </div>

    <script>
    (function() {
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

        // Backdrop close
        document.getElementById('drawerBackdrop').addEventListener('click', closeMediaDrawer);
        document.addEventListener('keydown', function(e) { if (e.key === 'Escape') { closeMediaDrawer(); closeUploadModal(); } });

        // View toggle — actually switches layout, and the choice sticks.
        var mediaGrid = document.getElementById('mediaGrid');
        function applyMediaView(view) {
            if (!mediaGrid) return;
            mediaGrid.classList.toggle('is-list', view === 'list');
            document.querySelectorAll('.gti-ml-view-btn').forEach(function(b) {
                b.classList.toggle('active', b.getAttribute('data-view') === view);
            });
        }
        applyMediaView(localStorage.getItem('gti-media-view') || 'grid');

        document.querySelectorAll('.gti-ml-view-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var view = this.getAttribute('data-view');
                localStorage.setItem('gti-media-view', view);
                applyMediaView(view);
            });
        });

        // Upload button (hidden for users without the upload_files capability)
        var uploadBtn = document.getElementById('uploadMediaBtn');
        if (uploadBtn) {
            uploadBtn.addEventListener('click', function() {
                document.getElementById('uploadModal').style.display = 'flex';
            });
        }

        // Drop zone
        var dz = document.getElementById('uploadDropZone');
        var fi = document.getElementById('fileInput');
        dz.addEventListener('click', function() { fi.click(); });
        dz.addEventListener('dragover', function(e) { e.preventDefault(); dz.style.borderColor = '#F5A623'; dz.style.background = '#FFF8EC'; });
        dz.addEventListener('dragleave', function() { dz.style.borderColor = '#d1d5db'; dz.style.background = ''; });
        dz.addEventListener('drop', function(e) { e.preventDefault(); dz.style.borderColor = '#d1d5db'; dz.style.background = ''; handleFiles(e.dataTransfer.files); });
        fi.addEventListener('change', function() { handleFiles(this.files); });

        var _pendingFiles = [];
        function handleFiles(files) {
            _pendingFiles = Array.from(files);
            if (_pendingFiles.length > 0) {
                document.getElementById('startUploadBtn').disabled = false;
                document.getElementById('uploadStatus').textContent = _pendingFiles.length + ' file(s) selected';
            }
        }

        document.getElementById('startUploadBtn').addEventListener('click', function() {
            if (_pendingFiles.length === 0) return;
            var formData = new FormData();
            _pendingFiles.forEach(function(f) { formData.append('files[]', f); });
            formData.append('action', 'gti_upload_media');
            formData.append('nonce', (typeof gtiAjax !== 'undefined' ? gtiAjax.nonce : ''));

            document.getElementById('uploadProgress').style.display = 'block';
            document.getElementById('uploadStatus').textContent = 'Uploading...';

            fetch((typeof gtiAjax !== 'undefined' ? gtiAjax.ajaxurl : '/wp-admin/admin-ajax.php'), {
                method: 'POST',
                body: formData
            }).then(function(r) { return r.json(); }).then(function(res) {
                document.getElementById('uploadProgressBar').style.width = '100%';
                var msg = res.data && res.data.message ? res.data.message : (res.success ? 'Upload complete!' : 'Upload failed. Please try again.');
                document.getElementById('uploadStatus').textContent = res.success ? msg + ' Reloading...' : msg;
                if (res.success) { setTimeout(function() { location.reload(); }, 1200); }
            }).catch(function() {
                document.getElementById('uploadStatus').textContent = 'Upload failed. Please try again.';
            });
        });
    })();

    var _currentMedia = null;

    // Open media detail drawer
    function openMediaDetail(el) {
        var data;
        try { data = JSON.parse(el.getAttribute('data-media')); } catch(e) { return; }
        _currentMedia = data;

        // Mark active card
        document.querySelectorAll('.gti-ml-card').forEach(function(c) { c.classList.remove('active'); });
        el.classList.add('active');

        // Populate drawer
        document.getElementById('drawer-filename').textContent = data.filename;
        var badge = document.getElementById('drawer-type-badge');
        badge.textContent = data.type.toUpperCase();
        badge.className = 'gti-drawer-status ' + (data.type === 'image' ? 'status-new' : 'status-processing');

        // Preview
        var preview = document.getElementById('drawer-preview');
        if (data.type === 'image') {
            preview.innerHTML = '<img src="' + data.url + '" alt="' + data.filename + '">';
        } else {
            preview.innerHTML = '<i class="fas fa-file-alt gti-ml-doc-icon-lg"></i>';
        }

        document.getElementById('drawer-filename-val').textContent = data.filename;
        document.getElementById('drawer-filetype').textContent = data.ext + ' (' + data.type + ')';
        document.getElementById('drawer-filesize').textContent = data.size_human;
        document.getElementById('drawer-uploaded').textContent = data.date_human;
        document.getElementById('drawer-dims').textContent = data.dims ? data.dims[0] + ' × ' + data.dims[1] + ' px' : '-';
        document.getElementById('drawer-title-val').textContent = data.title || '-';
        document.getElementById('drawer-alt').textContent = data.alt || '-';
        document.getElementById('drawer-uploader').textContent = data.uploader || '-';
        document.getElementById('drawer-attached').textContent = data.attached_to || 'Unattached';
        document.getElementById('drawer-url').textContent = data.url;
        document.getElementById('drawer-url').setAttribute('data-url', data.url);
        document.getElementById('drawer-download').href = data.url;

        // Open drawer on small screens
        if (window.innerWidth < 1600) {
            document.getElementById('mediaDetailDrawer').classList.add('open');
            document.getElementById('drawerBackdrop').classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeMediaDrawer() {
        document.getElementById('mediaDetailDrawer').classList.remove('open');
        document.getElementById('drawerBackdrop').classList.remove('show');
        document.body.style.overflow = '';
        document.querySelectorAll('.gti-ml-card').forEach(function(c) { c.classList.remove('active'); });
    }

    function closeUploadModal() {
        document.getElementById('uploadModal').style.display = 'none';
    }

    // Permanently remove the attachment, its resized files included.
    document.getElementById('drawer-delete').addEventListener('click', function() {
        if (!_currentMedia) return;
        if (!confirm('Permanently delete "' + _currentMedia.filename + '"? This cannot be undone.')) return;

        var btn = this;
        btn.disabled = true;

        var formData = new FormData();
        formData.append('action', 'gti_delete_media');
        formData.append('id', _currentMedia.id);
        formData.append('nonce', (typeof gtiAjax !== 'undefined' ? gtiAjax.nonce : ''));

        fetch((typeof gtiAjax !== 'undefined' ? gtiAjax.ajaxurl : '/wp-admin/admin-ajax.php'), {
            method: 'POST',
            body: formData
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            btn.disabled = false;
            if (res.success) {
                location.reload();
            } else {
                alert(res.data && res.data.message ? res.data.message : 'Failed to delete the file.');
            }
        })
        .catch(function() {
            btn.disabled = false;
            alert('An error occurred. Please try again.');
        });
    });

    function copyUrl(el) {
        var url = el.getAttribute('data-url') || el.textContent;
        if (navigator.clipboard) {
            navigator.clipboard.writeText(url).then(function() {
                var orig = el.textContent;
                el.textContent = 'URL copied!';
                setTimeout(function() { el.textContent = orig; }, 1500);
            });
        }
    }
    </script>
</body>
</html>
