<?php
/**
 * Template: News & Articles (/dashboard/news-articles)
 * @package global-tractors
 */
if (!defined('ABSPATH')) exit;
gti_require_login();

$current_user = wp_get_current_user();
$user_name = $current_user->display_name ?: $current_user->user_login;
$user_avatar = get_avatar_url($current_user->ID, ['size' => 80]);

// Articles are native WordPress posts — see inc/modules/news-articles.php
// Filters
$search          = isset($_GET['search'])   ? sanitize_text_field($_GET['search'])   : '';
$status_filter   = isset($_GET['status'])   ? sanitize_text_field($_GET['status'])   : '';
$category_filter = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';

// Pagination — 'page_num' is used instead of 'paged' because WordPress reserves 'paged'
$paged    = isset($_GET['page_num']) ? max(1, intval($_GET['page_num'])) : 1;
$per_page = 10;
$offset   = ($paged - 1) * $per_page;

$results = gti_query_articles(array(
    'search'   => $search,
    'status'   => $status_filter,
    'category' => $category_filter,
    'per_page' => $per_page,
    'page'     => $paged,
));

$articles    = $results['items'];
$total       = $results['total'];
$total_pages = $results['pages'];

// Status counts
$counts          = gti_article_counts();
$total_count     = $counts['total'];
$published_count = $counts['published'];
$draft_count     = $counts['draft'];
$total_views     = $counts['views'];

// Categories for filter
$categories = gti_article_used_categories();

function gti_na_category_label($cat) {
    return gti_article_category_label($cat);
}
function gti_na_category_color($cat) {
    return gti_article_category_color($cat);
}
function gti_na_status_class($status) {
    return $status === 'published' ? 'available' : ($status === 'draft' ? 'reserved' : 'sold');
}
function gti_na_excerpt($text, $len = 80) {
    if (!$text) return '-';
    return mb_strlen($text) > $len ? mb_substr($text, 0, $len) . '...' : $text;
}
function gti_na_format_views($views) {
    if ($views >= 1000) {
        return number_format($views / 1000, 1) . 'K';
    }
    return number_format($views);
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News &amp; Articles - <?php bloginfo('name'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo GTI_CHILD_URL; ?>/assets/css/dashboard.css">
    <style>
        /* Border overrides — match existing style */
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
        .gti-ue-table .col-thumb { width: 60px; }
        .gti-ue-table .col-title { width: 260px; }
        .gti-ue-table .col-category { width: 120px; text-align: center; }
        .gti-ue-table .col-author { width: 130px; }
        .gti-ue-table .col-status { width: 100px; text-align: center; white-space: nowrap; }
        .gti-ue-table .col-views { width: 80px; text-align: center; }
        .gti-ue-table .col-date { width: 110px; white-space: nowrap; }
        .gti-ue-table .col-actions { width: 70px; text-align: center; }

        /* ====== Content Layout ====== */
        .gti-content { display: flex; gap: 24px; align-items: flex-start; }
        .gti-content-left { flex: 1; min-width: 0; }

        /* ====== Right Drawer — In-Flow (≥1600px) ====== */
        .gti-drawer { width: 340px; max-width: calc(33vw - 100px); flex-shrink: 0; background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; display: flex; flex-direction: column; height: fit-content; }
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
        .gti-drawer-header { display: flex; align-items: flex-start; justify-content: space-between; padding: 20px 24px; border-bottom: 1px solid #e5e7eb; flex-shrink: 0; gap: 12px; }
        .gti-drawer-header-left { flex: 1; min-width: 0; }
        .gti-drawer-header-left h2 { margin: 0 0 6px; font-size: 15px; font-weight: 600; color: #1a1f36; line-height: 1.4; }
        .gti-drawer-status { display: inline-flex; align-items: center; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .gti-drawer-status.status-published { background: #d1fae5; color: #047857; }
        .gti-drawer-status.status-draft { background: #fef3c7; color: #b45309; }
        .gti-drawer-status.status-archived { background: #f3f4f6; color: #6b7280; }
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
        .gti-drawer-label { font-size: 13px; color: #9ca3af; flex-shrink: 0; min-width: 100px; }
        .gti-drawer-value { font-size: 13px; font-weight: 500; color: #1a1f36; text-align: right; word-break: break-word; }
        .gti-drawer-value.is-link { color: #2563eb; }

        /* ====== Drawer Content Preview ====== */
        .gti-drawer-content-preview {
            background: #f9fafb;
            border: 1px solid #f3f4f6;
            border-radius: 8px;
            padding: 14px;
            font-size: 13px;
            color: #374151;
            line-height: 1.6;
            max-height: 160px;
            overflow-y: auto;
            white-space: pre-wrap;
        }

        /* ====== Drawer Tags ====== */
        .gti-drawer-tags { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 4px; }
        .gti-drawer-tag {
            display: inline-flex;
            align-items: center;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
            background: #f3f4f6;
            color: #6b7280;
        }

        /* ====== Drawer Category Badge ====== */
        .gti-drawer-cat-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }

        /* ====== Drawer Featured Image ====== */
        .gti-drawer-featured-img {
            width: 100%;
            height: 140px;
            border-radius: 8px;
            background: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #d1d5db;
            font-size: 32px;
            overflow: hidden;
            margin-bottom: 12px;
        }
        .gti-drawer-featured-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* ====== Drawer Footer ====== */
        .gti-drawer-footer { display: flex; align-items: center; gap: 8px; padding: 16px 24px; border-top: 1px solid #e5e7eb; flex-shrink: 0; background: #fff; }
        .gti-drawer-btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 16px; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer; transition: all 0.15s; border: 1px solid #e5e7eb; background: #fff; color: #374151; font-family: inherit; text-decoration: none; }
        .gti-drawer-btn:hover { background: #f9fafb; }
        .gti-drawer-btn-primary { background: #F5A623; color: #1a1f36; border-color: #F5A623; font-weight: 600; }
        .gti-drawer-btn-primary:hover { background: #e6991a; }
        .gti-drawer-footer-spacer { flex: 1; }

        /* ====== Thumbnail Cell ====== */
        .gti-article-thumb {
            width: 44px;
            height: 44px;
            border-radius: 8px;
            background: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #d1d5db;
            font-size: 16px;
            overflow: hidden;
            flex-shrink: 0;
        }
        .gti-article-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* ====== Category Pill ====== */
        .gti-cat-pill {
            display: inline-flex;
            align-items: center;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
        }

        /* ====== Title Cell ====== */
        .gti-title-cell { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
        .gti-title-cell strong { font-size: 13px; color: #111827; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.4; }
        .gti-title-cell small { font-size: 11px; color: #9ca3af; }

        /* ====== Active Row Highlight ====== */
        .gti-ue-table tbody tr.active-row { background: #FFF8EC; }
        .gti-ue-table tbody tr { cursor: pointer; transition: background 0.15s; }
        .gti-ue-table tbody tr:hover { background: #f9fafb; }

        /* ====== Add New Button ====== */
        .gti-btn-add {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s;
            border: none;
            background: #F5A623;
            color: #1a1f36;
            font-family: inherit;
            text-decoration: none;
        }
        .gti-btn-add:hover { background: #e6991a; }

        /* ====== Views Cell ====== */
        .gti-views-cell {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 13px;
            color: #6b7280;
        }
        .gti-views-cell i { font-size: 11px; color: #d1d5db; }

        /* ====== Responsive ====== */
        @media (max-width: 1200px) {
            .gti-ue-stats-row { flex-wrap: wrap; }
            .gti-ue-toolbar { flex-direction: column; align-items: flex-start; }
            .gti-ue-toolbar-left { width: 100%; }
            .gti-ue-search input { width: 100%; }
        }
        @media (max-width: 768px) {
            .gti-ue-stats-row .gti-ue-stat-card { flex: 1 1 calc(50% - 12px); }
            .gti-ue-toolbar-left { flex-direction: column; }
            .gti-ue-search { width: 100%; }
            .gti-ue-search input { width: 100%; }
            .gti-ue-filter { width: 100%; }
            .gti-ue-filter select { width: 100%; }
            .gti-ue-table-card { overflow-x: auto; }
            .gti-drawer-footer { flex-wrap: wrap; }
            .gti-drawer-footer-spacer { display: none; }
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
                </div>

                <div class="gti-nav-group">
                    <div class="gti-nav-section">MANAGEMENT</div>
                    <a href="<?php echo esc_url(gti_dashboard_url('customers')); ?>" class="gti-nav-item"><i class="fas fa-users"></i><span>Customers</span></a>
                    <a href="<?php echo esc_url(gti_dashboard_url('news-articles')); ?>" class="gti-nav-item active"><i class="fas fa-newspaper"></i><span>News &amp; Articles</span></a>
                    <a href="<?php echo esc_url(gti_dashboard_url('media-library')); ?>" class="gti-nav-item"><i class="fas fa-photo-video"></i><span>Media Library</span></a>
                </div>

                <div class="gti-nav-group">
                    <div class="gti-nav-section">SYSTEM</div>
                    <a href="<?php echo esc_url(gti_dashboard_url('users')); ?>" class="gti-nav-item"><i class="fas fa-user-shield"></i><span>Users</span></a>
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
                        <h1 class="gti-page-title">News &amp; Articles</h1>
                        <p class="gti-welcome">Manage news, articles &amp; announcements <span>&#128240;</span></p>
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
                        <div class="gti-ue-stat-icon"><i class="fas fa-newspaper"></i></div>
                        <div class="gti-ue-stat-info">
                            <p class="gti-ue-stat-label">Total Articles</p>
                            <p class="gti-ue-stat-value"><?php echo esc_html($total_count); ?></p>
                        </div>
                    </div>
                    <div class="gti-ue-stat-card">
                        <div class="gti-ue-stat-icon available"><i class="fas fa-check-circle"></i></div>
                        <div class="gti-ue-stat-info">
                            <p class="gti-ue-stat-label">Published</p>
                            <p class="gti-ue-stat-value"><?php echo esc_html($published_count); ?></p>
                        </div>
                    </div>
                    <div class="gti-ue-stat-card">
                        <div class="gti-ue-stat-icon reserved"><i class="fas fa-file-alt"></i></div>
                        <div class="gti-ue-stat-info">
                            <p class="gti-ue-stat-label">Draft</p>
                            <p class="gti-ue-stat-value"><?php echo esc_html($draft_count); ?></p>
                        </div>
                    </div>
                    <div class="gti-ue-stat-card">
                        <div class="gti-ue-stat-icon sold"><i class="fas fa-eye"></i></div>
                        <div class="gti-ue-stat-info">
                            <p class="gti-ue-stat-label">Total Views</p>
                            <p class="gti-ue-stat-value"><?php echo esc_html(number_format($total_views)); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Toolbar -->
                <form class="gti-ue-toolbar" method="get" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                    <input type="hidden" name="gti_page" value="news-articles">
                    <div class="gti-ue-toolbar-left" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;flex:1;">
                        <div class="gti-ue-search">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" placeholder="Search articles..." value="<?php echo esc_attr($search); ?>">
                        </div>
                        <div class="gti-ue-filter">
                            <select name="category">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo esc_attr($cat); ?>" <?php selected($category_filter, $cat); ?>><?php echo esc_html(gti_na_category_label($cat)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="gti-ue-filter">
                            <select name="status">
                                <option value="">All Status</option>
                                <option value="published" <?php selected($status_filter, 'published'); ?>>Published</option>
                                <option value="draft" <?php selected($status_filter, 'draft'); ?>>Draft</option>
                                <option value="archived" <?php selected($status_filter, 'archived'); ?>>Archived</option>
                            </select>
                        </div>
                        <a href="<?php echo esc_url(gti_dashboard_url('news-articles')); ?>" class="gti-ue-btn-reset">
                            <i class="fas fa-rotate-right"></i> Reset
                        </a>
                    </div>
                    <a href="<?php echo esc_url(gti_dashboard_url('news-articles/add')); ?>" class="gti-btn-add">
                        <i class="fas fa-plus"></i> Add New Article
                    </a>
                </form>

                <!-- Table -->
                <div class="gti-ue-table-card">
                    <table class="gti-ue-table">
                        <thead>
                            <tr>
                                <th class="col-thumb"></th>
                                <th class="col-title">Title</th>
                                <th class="col-category">Category</th>
                                <th class="col-author">Author</th>
                                <th class="col-status">Status</th>
                                <th class="col-views">Views</th>
                                <th class="col-date">Date</th>
                                <th class="col-actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($articles)): ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 60px 20px;">
                                        <div style="color: #9ca3af;">
                                            <i class="fas fa-newspaper" style="font-size: 48px; margin-bottom: 16px; display: block;"></i>
                                            <p style="font-size: 16px; font-weight: 500; margin-bottom: 8px;">No articles found</p>
                                            <p style="font-size: 14px;">
                                                <?php echo ($search || $status_filter || $category_filter) ? 'Try adjusting your filters' : 'No articles yet'; ?>
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($articles as $i => $a): ?>
                                    <?php
                                    $cat_colors = gti_na_category_color($a->category);
                                    ?>
                                    <tr data-article-id="<?php echo esc_attr($a->id); ?>" data-article='<?php echo esc_attr(json_encode($a)); ?>'>
                                        <td class="col-thumb">
                                            <div class="gti-article-thumb">
                                                <?php if ($a->featured_image): ?>
                                                    <img src="<?php echo esc_url($a->featured_image); ?>" alt="">
                                                <?php else: ?>
                                                    <i class="fas fa-file-alt"></i>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="col-title">
                                            <div class="gti-title-cell">
                                                <strong><?php echo esc_html($a->title); ?></strong>
                                                <small><?php echo esc_html($a->article_id); ?></small>
                                            </div>
                                        </td>
                                        <td class="col-category">
                                            <span class="gti-cat-pill" style="background:<?php echo esc_attr($cat_colors[0]); ?>;color:<?php echo esc_attr($cat_colors[1]); ?>">
                                                <?php echo esc_html(gti_na_category_label($a->category)); ?>
                                            </span>
                                        </td>
                                        <td class="col-author">
                                            <strong style="color:#1a1f36"><?php echo esc_html($a->author); ?></strong>
                                        </td>
                                        <td class="col-status">
                                            <span class="gti-badge-status <?php echo esc_attr(gti_na_status_class($a->status)); ?>"><?php echo esc_html(ucfirst($a->status)); ?></span>
                                        </td>
                                        <td class="col-views">
                                            <span class="gti-views-cell">
                                                <i class="fas fa-eye"></i>
                                                <?php echo esc_html(gti_na_format_views((int)$a->views)); ?>
                                            </span>
                                        </td>
                                        <td class="col-date">
                                            <?php echo esc_html(date('M j, Y', strtotime($a->published_at ?: $a->created_at))); ?>
                                        </td>
                                        <td class="col-actions">
                                            <div class="gti-ue-action-menu">
                                                <button class="gti-ue-action-toggle" title="Actions"><i class="fas fa-ellipsis-v"></i></button>
                                                <div class="gti-ue-action-dropdown">
                                                    <button type="button" class="gti-ue-action-item" onclick='showArticleDetail(<?php echo esc_attr(json_encode($a)); ?>)'>
                                                        <i class="fas fa-eye"></i> View Details
                                                    </button>
                                                    <a href="<?php echo esc_url($a->edit_url); ?>" class="gti-ue-action-item">
                                                        <i class="fas fa-edit"></i> Edit Article
                                                    </a>
                                                    <a href="<?php echo esc_url($a->permalink); ?>" target="_blank" rel="noopener" class="gti-ue-action-item">
                                                        <i class="fas fa-external-link-alt"></i> View on Site
                                                    </a>
                                                    <button type="button" class="gti-ue-action-item" onclick="toggleArticleStatus(<?php echo esc_attr($a->id); ?>, '<?php echo esc_attr($a->status === 'published' ? 'draft' : 'published'); ?>')">
                                                        <i class="fas fa-<?php echo $a->status === 'published' ? 'eye-slash' : 'check'; ?>"></i>
                                                        <?php echo $a->status === 'published' ? 'Unpublish' : 'Publish'; ?>
                                                    </button>
                                                    <button type="button" class="gti-ue-action-item" style="color:#dc2626" onclick="deleteArticle(<?php echo esc_attr($a->id); ?>)">
                                                        <i class="fas fa-trash" style="color:#dc2626"></i> Delete
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
                                $query_params = [];
                                if ($search) $query_params['search'] = $search;
                                if ($status_filter) $query_params['status'] = $status_filter;
                                if ($category_filter) $query_params['category'] = $category_filter;
                                $query_params['gti_page'] = 'news-articles';
                                $base_url = gti_dashboard_url('news-articles');
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
                <div class="gti-drawer" id="articleDetailDrawer">
                    <div class="gti-drawer-header">
                        <div class="gti-drawer-header-left">
                            <h2 id="drawer-title">-</h2>
                            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-top:4px;">
                                <span class="gti-drawer-status" id="drawer-status-badge">Published</span>
                                <span class="gti-drawer-cat-badge" id="drawer-cat-badge">-</span>
                            </div>
                        </div>
                        <button type="button" class="gti-drawer-close" onclick="closeDetailDrawer()"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="gti-drawer-body">
                        <!-- Featured Image -->
                        <div class="gti-drawer-featured-img" id="drawer-featured-img">
                            <i class="fas fa-image"></i>
                        </div>

                        <!-- Article Info -->
                        <div class="gti-drawer-section">
                            <div class="gti-drawer-section-title"><i class="fas fa-info-circle"></i> Article Information</div>
                            <div class="gti-drawer-row"><span class="gti-drawer-label">Article ID</span><span class="gti-drawer-value" id="drawer-article-id">-</span></div>
                            <div class="gti-drawer-row"><span class="gti-drawer-label">Author</span><span class="gti-drawer-value" id="drawer-author">-</span></div>
                            <div class="gti-drawer-row"><span class="gti-drawer-label">Published</span><span class="gti-drawer-value" id="drawer-published">-</span></div>
                            <div class="gti-drawer-row"><span class="gti-drawer-label">Created</span><span class="gti-drawer-value" id="drawer-created">-</span></div>
                            <div class="gti-drawer-row"><span class="gti-drawer-label">Views</span><span class="gti-drawer-value" id="drawer-views">-</span></div>
                            <div class="gti-drawer-row"><span class="gti-drawer-label">Featured</span><span class="gti-drawer-value" id="drawer-featured">-</span></div>
                        </div>

                        <!-- Excerpt / Content Preview -->
                        <div class="gti-drawer-section">
                            <div class="gti-drawer-section-title"><i class="fas fa-align-left"></i> Content Preview</div>
                            <div class="gti-drawer-content-preview" id="drawer-excerpt">-</div>
                        </div>

                        <!-- Tags -->
                        <div class="gti-drawer-section">
                            <div class="gti-drawer-section-title"><i class="fas fa-tags"></i> Tags</div>
                            <div class="gti-drawer-tags" id="drawer-tags">
                                <span style="color:#9ca3af;font-size:13px;">No tags</span>
                            </div>
                        </div>

                        <!-- Timeline -->
                        <div class="gti-drawer-section">
                            <div class="gti-drawer-section-title"><i class="fas fa-clock"></i> Activity</div>
                            <div class="gti-drawer-timeline" id="drawer-timeline">
                                <!-- Populated by JS -->
                            </div>
                        </div>
                    </div>
                    <div class="gti-drawer-footer">
                        <a href="#" class="gti-drawer-btn gti-drawer-btn-primary" id="drawer-btn-edit"><i class="fas fa-edit"></i> Edit</a>
                        <a href="#" class="gti-drawer-btn" id="drawer-btn-preview"><i class="fas fa-external-link-alt"></i> View on Site</a>
                        <span class="gti-drawer-footer-spacer"></span>
                        <button type="button" class="gti-drawer-btn" id="drawer-btn-toggle-status" onclick="toggleStatusFromDrawer()">
                            <i class="fas fa-sync-alt"></i> <span id="drawer-btn-toggle-text">Unpublish</span>
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <style>
        /* ====== Drawer Timeline ====== */
        .gti-drawer-timeline { position: relative; padding-left: 28px; }
        .gti-drawer-timeline::before { content: ''; position: absolute; left: 9px; top: 6px; bottom: 6px; width: 2px; background: #e5e7eb; border-radius: 1px; }
        .gti-drawer-timeline-item { position: relative; padding-bottom: 20px; }
        .gti-drawer-timeline-item:last-child { padding-bottom: 0; }
        .gti-drawer-timeline-dot { position: absolute; left: -18px; top: 5px; width: 10px; height: 10px; border-radius: 50%; background: #6B7280; transform: translateX(-50%); }
        .gti-drawer-timeline-dot.is-active { width: 14px; height: 14px; top: 2px; left: -18px; background: #F59E0B; z-index: 1; }
        .gti-drawer-timeline-dot.is-active::before { content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0; margin: auto; width: 6px; height: 6px; border-radius: 50%; background: #000; z-index: -1; }
        .gti-drawer-timeline-event { font-size: 13px; font-weight: 500; color: #1a1f36; }
        .gti-drawer-timeline-item:last-child .gti-drawer-timeline-event { font-weight: 600; color: #111827; }
        .gti-drawer-timeline-meta { font-size: 12px; color: #9ca3af; margin-top: 2px; }
        .gti-drawer-timeline-item:last-child .gti-drawer-timeline-meta { color: #6B7280; }
    </style>

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
        document.querySelectorAll('.gti-ue-table tbody tr[data-article]').forEach(function(row) {
            row.addEventListener('click', function(e) {
                if (e.target.closest('.gti-ue-action-menu')) return;
                var article;
                try { article = JSON.parse(this.getAttribute('data-article')); } catch(err) { return; }
                showArticleDetail(article);
            });
        });

        // Auto-populate drawer with first row on page load
        var firstRow = document.querySelector('.gti-ue-table tbody tr[data-article]');
        if (firstRow) {
            try {
                var firstArticle = JSON.parse(firstRow.getAttribute('data-article'));
                updateDrawerContent(firstArticle);
            } catch(e) {}
        }
    });

    var _currentArticle = null;

    function updateDrawerContent(article) {
        _currentArticle = article;

        document.getElementById('drawer-title').textContent = article.title || '-';

        // Status badge
        var badge = document.getElementById('drawer-status-badge');
        badge.textContent = (article.status || 'draft').charAt(0).toUpperCase() + (article.status || 'draft').slice(1);
        badge.className = 'gti-drawer-status status-' + (article.status || 'draft');

        // Category badge
        var catBadge = document.getElementById('drawer-cat-badge');
        var catLabels = { 'company-news': 'Company News', 'tips': 'Tips & Tricks', 'event': 'Event', 'industry': 'Industry', 'product': 'Product' };
        var catColors = {
            'company-news': ['#dbeafe', '#1d4ed8'],
            'tips': ['#d1fae5', '#047857'],
            'event': ['#fef3c7', '#b45309'],
            'industry': ['#e0e7ff', '#4338ca'],
            'product': ['#fce7f3', '#be185d']
        };
        var catLabel = catLabels[article.category] || article.category;
        var catColor = catColors[article.category] || ['#f3f4f6', '#374151'];
        catBadge.textContent = catLabel;
        catBadge.style.background = catColor[0];
        catBadge.style.color = catColor[1];

        // Featured image
        var imgContainer = document.getElementById('drawer-featured-img');
        if (article.featured_image) {
            imgContainer.innerHTML = '<img src="' + article.featured_image + '" alt="">';
        } else {
            imgContainer.innerHTML = '<i class="fas fa-image"></i>';
        }

        // Info rows
        document.getElementById('drawer-article-id').textContent = article.article_id || '-';
        document.getElementById('drawer-author').textContent = article.author || '-';
        document.getElementById('drawer-published').textContent = article.published_at ? formatDateID(article.published_at) : '-';
        document.getElementById('drawer-created').textContent = article.created_at ? formatDateID(article.created_at) : '-';
        document.getElementById('drawer-views').textContent = formatViews(article.views);
        document.getElementById('drawer-featured').textContent = article.is_featured ? 'Yes' : 'No';

        // Excerpt
        document.getElementById('drawer-excerpt').textContent = article.excerpt || article.content || 'No content available';

        // Tags
        var tagsContainer = document.getElementById('drawer-tags');
        if (article.tags) {
            var tags = article.tags.split(',').map(function(t) { return t.trim(); }).filter(function(t) { return t; });
            if (tags.length > 0) {
                tagsContainer.innerHTML = tags.map(function(tag) {
                    return '<span class="gti-drawer-tag"><i class="fas fa-tag" style="margin-right:4px;font-size:9px;"></i>' + tag + '</span>';
                }).join('');
            } else {
                tagsContainer.innerHTML = '<span style="color:#9ca3af;font-size:13px;">No tags</span>';
            }
        } else {
            tagsContainer.innerHTML = '<span style="color:#9ca3af;font-size:13px;">No tags</span>';
        }

        // Timeline
        var timelineItems = [];
        if (article.created_at) {
            timelineItems.push({ event: 'Article Created', date: article.created_at, actor: article.author || 'System' });
        }
        if (article.published_at) {
            timelineItems.push({ event: 'Published', date: article.published_at, actor: article.author || 'System' });
        }
        if (article.updated_at && article.updated_at !== article.created_at) {
            timelineItems.push({ event: 'Last Updated', date: article.updated_at, actor: 'System' });
        }

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

        // Toggle button text
        var toggleText = document.getElementById('drawer-btn-toggle-text');
        toggleText.textContent = article.status === 'published' ? 'Unpublish' : 'Publish';

        // Footer links point at the real editor and the live post
        var editBtn = document.getElementById('drawer-btn-edit');
        var viewBtn = document.getElementById('drawer-btn-preview');
        editBtn.href = article.edit_url || '#';
        viewBtn.href = article.permalink || '#';
        viewBtn.target = '_blank';
        viewBtn.rel = 'noopener';

        // Highlight active row
        document.querySelectorAll('.gti-ue-table tbody tr').forEach(function(r) { r.classList.remove('active-row'); });
        var activeRow = document.querySelector('.gti-ue-table tbody tr[data-article-id="' + article.id + '"]');
        if (activeRow) activeRow.classList.add('active-row');
    }

    function showArticleDetail(article) {
        updateDrawerContent(article);
        if (window.innerWidth < 1600) {
            document.getElementById('articleDetailDrawer').classList.add('open');
            document.getElementById('drawerBackdrop').classList.add('show');
            document.body.style.overflow = 'hidden';
        }
        document.querySelectorAll('.gti-ue-action-dropdown.show').forEach(function(d) { d.classList.remove('show'); });
    }

    function closeDetailDrawer() {
        document.getElementById('articleDetailDrawer').classList.remove('open');
        document.getElementById('drawerBackdrop').classList.remove('show');
        document.body.style.overflow = '';
    }

    function formatDateID(dateStr) {
        if (!dateStr) return '-';
        var d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        var months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        return d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
    }

    function formatViews(views) {
        views = parseInt(views) || 0;
        if (views >= 1000) return (views / 1000).toFixed(1) + 'K views';
        return views.toLocaleString() + ' views';
    }

    function toggleStatusFromDrawer() {
        if (!_currentArticle) return;
        var newStatus = _currentArticle.status === 'published' ? 'draft' : 'published';
        toggleArticleStatus(_currentArticle.id, newStatus);
    }

    function deleteArticle(articleId) {
        if (!confirm('Move this article to the trash?')) return;

        var formData = new FormData();
        formData.append('action', 'gti_delete_article');
        formData.append('id', articleId);
        formData.append('nonce', typeof gtiAjax !== 'undefined' ? gtiAjax.nonce : '');

        fetch(typeof gtiAjax !== 'undefined' ? gtiAjax.ajaxurl : '/wp-admin/admin-ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.success) {
                location.reload();
            } else {
                alert(res.data && res.data.message ? res.data.message : 'Failed to delete article');
            }
        })
        .catch(function() {
            alert('An error occurred. Please try again.');
        });
    }

    function toggleArticleStatus(articleId, newStatus) {
        var label = newStatus === 'published' ? 'publish' : 'unpublish';
        if (!confirm('Are you sure you want to ' + label + ' this article?')) return;

        var formData = new FormData();
        formData.append('action', 'gti_update_article_status');
        formData.append('id', articleId);
        formData.append('status', newStatus);
        formData.append('nonce', typeof gtiAjax !== 'undefined' ? gtiAjax.nonce : '');

        fetch(typeof gtiAjax !== 'undefined' ? gtiAjax.ajaxurl : '/wp-admin/admin-ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.success) {
                location.reload();
            } else {
                alert(res.data && res.data.message ? res.data.message : 'Failed to update status');
            }
        })
        .catch(function() {
            alert('An error occurred. Please try again.');
        });
    }
    </script>
</body>
</html>
