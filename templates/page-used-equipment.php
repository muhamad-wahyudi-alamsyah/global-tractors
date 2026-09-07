<?php
/**
 * Template: Used Equipment (/dashboard/used-equipment)
 * @package global-tractors
 */
if (!defined('ABSPATH')) exit;
gti_require_login();

$current_user = wp_get_current_user();
$user_name = $current_user->display_name ?: $current_user->user_login;
$user_avatar = get_avatar_url($current_user->ID, ['size' => 80]);

// Database queries
global $wpdb;
$table = $wpdb->prefix . 'gti_equipment';

// Filters
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$category = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';
$brand = isset($_GET['brand']) ? sanitize_text_field($_GET['brand']) : '';
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$condition_filter = isset($_GET['condition']) ? sanitize_text_field($_GET['condition']) : '';

// Pagination
$paged = isset($_GET['page_num']) ? max(1, intval($_GET['page_num'])) : 1;
$per_page = 10;
$offset = ($paged - 1) * $per_page;

// Build WHERE clause
$where = "WHERE type = 'used' AND deleted_at IS NULL";
$params = array();

if ($search) {
    $where .= ' AND (name LIKE %s OR equipment_code LIKE %s OR brand LIKE %s)';
    $search_like = '%' . $wpdb->esc_like($search) . '%';
    $params[] = $search_like;
    $params[] = $search_like;
    $params[] = $search_like;
}
if ($category) {
    $where .= ' AND category = %s';
    $params[] = $category;
}
if ($brand) {
    $where .= ' AND brand = %s';
    $params[] = $brand;
}
if ($status_filter) {
    $where .= ' AND status = %s';
    $params[] = $status_filter;
}
if ($condition_filter) {
    $where .= ' AND condition_status = %s';
    $params[] = $condition_filter;
}

// Get total count
$count_sql = "SELECT COUNT(*) FROM {$table} {$where}";
$total = !empty($params) ? (int) $wpdb->get_var($wpdb->prepare($count_sql, $params)) : (int) $wpdb->get_var($count_sql);
$total_pages = ceil($total / $per_page);

// Get equipment
if (!empty($params)) {
    $equipments = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$table} {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d",
        array_merge($params, array($per_page, $offset))
    ));
} else {
    $equipments = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$table} {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d",
        $per_page, $offset
    ));
}

// Status counts
$status_counts = $wpdb->get_results(
    "SELECT status, COUNT(*) as count FROM {$table} WHERE type = 'used' AND deleted_at IS NULL GROUP BY status"
);
$status_count_map = array();
foreach ($status_counts as $sc) {
    $status_count_map[$sc->status] = $sc->count;
}
$total_equipment = array_sum(array_column($status_counts, 'count'));

// Filter options
$categories = $wpdb->get_col("SELECT DISTINCT category FROM {$table} WHERE type = 'used' AND category != '' AND deleted_at IS NULL ORDER BY category");
$brands = $wpdb->get_col("SELECT DISTINCT brand FROM {$table} WHERE type = 'used' AND brand != '' AND deleted_at IS NULL ORDER BY brand");
$years = $wpdb->get_col("SELECT DISTINCT year FROM {$table} WHERE type = 'used' AND year IS NOT NULL AND deleted_at IS NULL ORDER BY year DESC");

// Format currency inline to avoid redeclaration errors
$_gti_eq_fmt = function($amount) {
    return 'Rp ' . number_format((float)$amount, 0, ',', '.');
};

// Helper function to check if price is valid
if ( ! function_exists( 'gti_is_price_valid' ) ) {
    function gti_is_price_valid( $price_valid_until ) {
        if ( empty( $price_valid_until ) ) {
            return true;
        }
        $valid_until = strtotime( $price_valid_until );
        $today = strtotime( current_time( 'Y-m-d' ) );
        return $valid_until >= $today;
    }
}

// Status badge helper
$_gti_eq_status_badge = function($status, $price_valid_until = null) {
    if ( ! gti_is_price_valid( $price_valid_until ) ) {
        return '<span class="gti-badge-status price-no-longer-valid">Expired</span>';
    }

    $map = array(
        'available' => array('class' => 'available', 'label' => 'Available'),
        'sold' => array('class' => 'sold', 'label' => 'Sold'),
        'reserved' => array('class' => 'reserved', 'label' => 'Reserved'),
        'rented' => array('class' => 'sold', 'label' => 'Rented'),
        'maintenance' => array('class' => 'reserved', 'label' => 'Maintenance'),
        'draft' => array('class' => 'draft', 'label' => 'Draft'),
    );
    $info = isset($map[$status]) ? $map[$status] : array('class' => 'available', 'label' => ucfirst($status));
    return '<span class="gti-badge-status ' . esc_attr($info['class']) . '">' . esc_html($info['label']) . '</span>';
};
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Used Equipment - <?php bloginfo('name'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo GTI_CHILD_URL; ?>/assets/css/dashboard.css">
    <link rel="stylesheet" href="<?php echo GTI_CHILD_URL; ?>/assets/css/add-equipment.css">
    <style>
        /* Border overrides to match request-equipment style */
        .gti-ue-stat-card { border: 1px solid #e5e7eb; }
        .gti-ue-search { border: 1px solid #d1d5db; }
        .gti-ue-filter select { border: 1px solid #d1d5db; }
        .gti-ue-btn-reset { border: 1px solid #d1d5db; }
        .gti-ue-btn-reset:hover { border-color: #d1d5db; }
        .gti-ue-table-card { border: 1px solid #e5e7eb; overflow: visible; }
        .gti-ue-action-toggle { border: 1px solid #e5e7eb; }
        .gti-ue-action-toggle:hover { border-color: #d1d5db; }
        .gti-ue-action-dropdown { border: 1px solid #e5e7eb; }
        .gti-ue-page-btn { border: 1px solid #e5e7eb; }
        .gti-ue-page-btn:hover:not(:disabled) { border-color: #d1d5db; }
        .gti-ue-table { width: 100%; table-layout: fixed; }
        .gti-ue-table thead th { font-size: 13px; font-weight: 600; color: #374151; text-transform: none; letter-spacing: normal; padding: 13px 16px; }
        .gti-ue-table td { font-size: 14px; color: #374151; padding: 14px 16px; }
        .gti-ue-table .col-category { font-size: 13px; font-weight: 600; color: #374151; }
        .gti-ue-table .col-brand { font-size: 13px; font-weight: 600; color: #374151; }
        .gti-ue-table .col-price { font-size: 13px; font-weight: 600; color: #374151; }
        .gti-ue-table .col-condition { font-size: 13px; font-weight: 600; color: #374151; }
        .gti-ue-table .col-year { font-size: 13px; font-weight: 600; color: #374151; }
        .gti-ue-table .col-name { font-size: 13px; font-weight: 600; color: #374151; }

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
        .gti-main { min-width: 0; }
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
        .gti-drawer-status.status-available { background: #d1fae5; color: #047857; }
        .gti-drawer-status.status-sold { background: #fee2e2; color: #b91c1c; }
        .gti-drawer-status.status-reserved { background: #fef3c7; color: #b45309; }
        .gti-drawer-status.status-rented { background: #fee2e2; color: #b91c1c; }
        .gti-drawer-status.status-draft { background: #e0e7ff; color: #4338ca; }
        .gti-drawer-status.status-price-no-longer-valid { background: #fee2e2; color: #dc2626; }
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
        .gti-drawer-label { font-size: 13px; color: #9ca3af; flex-shrink: 0; min-width: 100px; }
        .gti-drawer-value {
            font-size: 13px; font-weight: 500; color: #1a1f36;
            text-align: right; word-break: break-word;
        }
        .gti-drawer-value.is-link { color: #2563eb; }
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
        .gti-drawer-timeline-item:last-child .gti-drawer-timeline-event {
            font-weight: 600; color: #111827;
        }
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
        /* Action menu overflow fix */
        .gti-ue-action-dropdown { z-index: 100; position: fixed; width: max-content; white-space: nowrap; }
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
        .gti-drawer-btn-publish:hover { background: #047857 !important; }
        .gti-drawer-btn-delete:hover { background: #fef2f2 !important; }
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
        /* ====== Drawer Stepper ====== */
        .gti-drawer-stepper {
            display: flex; align-items: flex-start; justify-content: center;
            gap: 0; margin: -24px -24px 20px -24px;
            padding: 20px 16px 16px; border-bottom: 1px solid #f3f4f6;
            flex-shrink: 0;
        }
        .gti-drawer-step {
            display: flex; flex-direction: column; align-items: center;
            gap: 6px; cursor: pointer; flex-shrink: 0;
        }
        .gti-drawer-step-circle {
            width: 30px; height: 30px; border-radius: 50%;
            background: #e5e7eb; color: #9ca3af;
            display: flex; align-items: center; justify-content: center;
            font-size: 12px; font-weight: 600; transition: all 0.25s ease;
        }
        .gti-drawer-step.active .gti-drawer-step-circle {
            background: #F5A623; color: #1a1f36;
            box-shadow: 0 0 0 3px rgba(245,166,35,0.15);
        }
        .gti-drawer-step.completed .gti-drawer-step-circle {
            background: #10b981; color: #fff;
        }
        .gti-drawer-step-label {
            font-size: 10px; font-weight: 500; color: #9ca3af;
            white-space: nowrap; transition: color 0.25s ease;
            text-align: center; max-width: 56px;
            overflow: hidden; text-overflow: ellipsis;
        }
        .gti-drawer-step.active .gti-drawer-step-label {
            color: #1a1f36; font-weight: 600;
        }
        .gti-drawer-step.completed .gti-drawer-step-label {
            color: #10b981;
        }
        .gti-drawer-step-line {
            flex: 1; height: 2px; background: #e5e7eb;
            margin: 0 4px; margin-top: 14px;
            min-width: 12px; max-width: 28px;
            transition: background 0.25s ease;
        }
        .gti-drawer-step-line.active { background: #10b981; }
        .gti-drawer-section[data-section] { display: none; }
        .gti-drawer-section[data-section].active { display: block; }
        /* ====== Drawer Feature Tags ====== */
        .gti-drawer-features { display: flex; flex-wrap: wrap; gap: 6px; }
        .gti-drawer-feature-tag {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 5px 10px; border-radius: 6px;
            background: #f0fdf4; color: #166534; font-size: 12px; font-weight: 500;
            border: 1px solid #bbf7d0;
        }
        .gti-drawer-feature-tag i { font-size: 10px; color: #22c55e; }
        /* ====== Drawer Document Items ====== */
        .gti-drawer-docs { display: flex; flex-direction: column; gap: 6px; }
        .gti-drawer-doc-item {
            display: flex; align-items: center; gap: 8px;
            padding: 8px 12px; border-radius: 8px;
            background: #f9fafb; border: 1px solid #f3f4f6;
            font-size: 13px; color: #374151;
        }
        .gti-drawer-doc-item i { color: #22c55e; font-size: 12px; flex-shrink: 0; }
        .gti-drawer-doc-item.missing { opacity: 0.5; }
        .gti-drawer-doc-item.missing i { color: #9ca3af; }
        .gti-drawer-doc-item.missing span { text-decoration: line-through; }
        /* ====== Drawer Video Thumbnail ====== */
        .gti-drawer-video-thumb {
            display: flex; align-items: center; gap: 10px;
            padding: 12px; border-radius: 8px; border: 1px solid #e5e7eb;
            background: #f9fafb;
        }
        .gti-drawer-video-thumb i { font-size: 28px; color: #F5A623; }
        .gti-drawer-video-thumb span { font-size: 13px; color: #374151; font-weight: 500; }
        .gti-drawer-video-thumb small { display: block; font-size: 11px; color: #9ca3af; margin-top: 2px; }

        /* ====== View Button — Hide on wide screens where drawer is already in-flow ====== */
        @media (min-width: 1600px) {
            .gti-ue-btn-view { display: none !important; }
        }

        /* ====== Fullscreen Edit Modal ====== */
        .gti-ue-edit-overlay {
            display: none; position: fixed; inset: 0; z-index: 2000;
            background: #f3f4f6; overflow-y: auto;
        }
        .gti-ue-edit-overlay.show { display: block; }
        .gti-ue-edit-overlay .gti-edit-backdrop {
            position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 1;
            opacity: 0; transition: opacity 0.25s;
        }
        .gti-ue-edit-overlay.show .gti-edit-backdrop { opacity: 1; }
        .gti-ue-edit-container {
            position: relative; z-index: 2;
            margin: 40px; background: #fff;
            border-radius: 16px; box-shadow: 0 8px 40px rgba(0,0,0,0.12);
            min-height: calc(100vh - 80px); display: flex; flex-direction: column;
        }
        .gti-ue-edit-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 16px 28px; border-bottom: 1px solid #e5e7eb;
            background: #fff; border-radius: 16px 16px 0 0; flex-shrink: 0;
            position: sticky; top: 0; z-index: 10;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .gti-ue-edit-header-left { display: flex; align-items: center; gap: 12px; }
        .gti-ue-edit-header h2 { margin: 0; font-size: 16px; font-weight: 600; color: #1a1f36; display: flex; align-items: center; gap: 8px; white-space: nowrap; }
        .gti-ue-edit-header h2 i { color: #F5A623; }
        /* Compact stepper inside header */
        .gti-ue-edit-header .gti-ae-stepper-card {
            margin: 0; background: none; border: none; box-shadow: none;
            border-radius: 0; overflow: visible;
            flex: 0 1 auto; height: fit-content; width: fit-content;
        }
        .gti-ue-edit-header .gti-ae-stepper {
            gap: 0; padding: 0; margin: 0; justify-content: center;
        }
        .gti-ue-edit-header .gti-ae-step { cursor: pointer; gap: 4px; }
        .gti-ue-edit-header .gti-ae-step-circle {
            width: 24px; height: 24px; font-size: 11px; font-weight: 600;
        }
        .gti-ue-edit-header .gti-ae-step-label {
            font-size: 10px; font-weight: 500;
        }
        .gti-ue-edit-header .gti-ae-step-line {
            margin-top: 11px; min-width: 8px; max-width: 20px; height: 2px;
        }
        .gti-ue-edit-close {
            width: 36px; height: 36px; border-radius: 8px; border: 1px solid #e5e7eb;
            background: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center;
            color: #6b7280; font-size: 16px; transition: all 0.15s;
        }
        .gti-ue-edit-close:hover { background: #f3f4f6; color: #1a1f36; }
        .gti-ue-edit-body {
            flex: 1; padding: 28px; overflow-y: auto;
        }
        .gti-ue-edit-footer {
            display: flex; align-items: center; justify-content: flex-end; gap: 10px;
            padding: 16px 28px; border-top: 1px solid #e5e7eb; flex-shrink: 0;
            background: #fff; border-radius: 0 0 16px 16px;
        }
        .gti-ue-edit-footer .gti-ae-btn-cancel {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 10px 20px; border-radius: 8px; font-size: 13px; font-weight: 500;
            border: 1px solid #e5e7eb; background: #fff; color: #374151;
            cursor: pointer; font-family: inherit; transition: all 0.15s; text-decoration: none;
        }
        .gti-ue-edit-footer .gti-ae-btn-cancel:hover { background: #f9fafb; }
        .gti-ue-edit-footer .gti-ae-btn-submit {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 10px 24px; border-radius: 8px; font-size: 13px; font-weight: 600;
            border: none; background: #F5A623; color: #1a1f36;
            cursor: pointer; font-family: inherit; transition: all 0.15s;
        }
        .gti-ue-edit-footer .gti-ae-btn-submit:hover { background: #e6991a; }
        .gti-ue-edit-footer .gti-ae-btn-submit:disabled { opacity: 0.6; cursor: not-allowed; }

        /* Reuse existing stepper/form styles inside the edit modal */
        .gti-ue-edit-body .gti-ae-step-content { display: none; }
        .gti-ue-edit-body .gti-ae-step-content.active { display: block; }
        .gti-ue-edit-body .gti-ae-step-nav {
            display: flex; justify-content: space-between; margin-top: 24px; padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
        .gti-ue-edit-body .gti-ae-step-nav button {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 10px 20px; border-radius: 8px; font-size: 13px; font-weight: 500;
            cursor: pointer; font-family: inherit; transition: all 0.15s;
        }
        .gti-ue-edit-body .gti-ae-step-nav .gti-ae-prev {
            border: 1px solid #e5e7eb; background: #fff; color: #374151;
        }
        .gti-ue-edit-body .gti-ae-step-nav .gti-ae-prev:hover { background: #f9fafb; }
        .gti-ue-edit-body .gti-ae-step-nav .gti-ae-next {
            border: 1px solid #F5A623; background: #F5A623; color: #1a1f36; font-weight: 600;
        }
        .gti-ue-edit-body .gti-ae-step-nav .gti-ae-next:hover { background: #e6991a; }

        /* ====== Delete Confirmation Modal ====== */
        .gti-ue-delete-overlay {
            display: none; position: fixed; inset: 0; z-index: 3000;
            background: rgba(0,0,0,0.5); align-items: center; justify-content: center;
        }
        .gti-ue-delete-overlay.show { display: flex; }
        .gti-ue-delete-modal {
            background: #fff; border-radius: 16px; width: 440px; max-width: 90vw;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2); overflow: hidden;
            animation: gti-modal-in 0.25s ease;
        }
        @keyframes gti-modal-in { from { opacity:0; transform: scale(0.95); } to { opacity:1; transform: scale(1); } }
        .gti-ue-delete-header {
            display: flex; align-items: center; gap: 12px;
            padding: 20px 24px 0;
        }
        .gti-ue-delete-icon {
            width: 44px; height: 44px; border-radius: 10px; flex-shrink: 0;
            background: #fef2f2; display: flex; align-items: center; justify-content: center;
            color: #dc2626; font-size: 18px;
        }
        .gti-ue-delete-header-text h3 { margin: 0; font-size: 16px; font-weight: 600; color: #1a1f36; }
        .gti-ue-delete-header-text p { margin: 4px 0 0; font-size: 13px; color: #6b7280; }
        .gti-ue-delete-body {
            padding: 16px 24px;
        }
        .gti-ue-delete-eq-info {
            background: #f9fafb; border: 1px solid #f3f4f6; border-radius: 10px;
            padding: 14px 16px; display: flex; align-items: center; gap: 12px;
        }
        .gti-ue-delete-eq-thumb {
            width: 48px; height: 48px; border-radius: 8px; background: #e5e7eb;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
            overflow: hidden;
        }
        .gti-ue-delete-eq-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .gti-ue-delete-eq-thumb i { color: #9ca3af; font-size: 18px; }
        .gti-ue-delete-eq-name { font-size: 14px; font-weight: 600; color: #1a1f36; }
        .gti-ue-delete-eq-code { font-size: 12px; color: #9ca3af; margin-top: 2px; }
        .gti-ue-delete-warning {
            margin-top: 14px; padding: 10px 14px; border-radius: 8px;
            background: #fffbeb; border: 1px solid #fde68a;
            font-size: 12px; color: #92400e; display: flex; align-items: flex-start; gap: 8px;
        }
        .gti-ue-delete-warning i { margin-top: 2px; color: #f59e0b; flex-shrink: 0; }
        .gti-ue-delete-footer {
            display: flex; justify-content: flex-end; gap: 10px;
            padding: 16px 24px; border-top: 1px solid #f3f4f6;
        }
        .gti-ue-delete-footer button {
            padding: 10px 20px; border-radius: 8px; font-size: 13px; font-weight: 500;
            cursor: pointer; font-family: inherit; transition: all 0.15s;
        }
        .gti-ue-delete-cancel {
            border: 1px solid #e5e7eb; background: #fff; color: #374151;
        }
        .gti-ue-delete-cancel:hover { background: #f9fafb; }
        .gti-ue-delete-confirm {
            border: none; background: #dc2626; color: #fff; font-weight: 600;
        }
        .gti-ue-delete-confirm:hover { background: #b91c1c; }
        .gti-ue-delete-confirm:disabled { opacity: 0.6; cursor: not-allowed; }

        /* ====== Toast Notification ====== */
        .gti-ue-toast {
            position: fixed; bottom: 24px; right: 24px; z-index: 5000;
            padding: 14px 20px; border-radius: 10px; font-size: 13px; font-weight: 500;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            display: flex; align-items: center; gap: 10px;
            transform: translateY(120%); opacity: 0; transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
        }
        .gti-ue-toast.show { transform: translateY(0); opacity: 1; }
        .gti-ue-toast.success { background: #059669; color: #fff; }
        .gti-ue-toast.error { background: #991b1b; color: #fff; }
        .gti-ue-toast.warning { background: #92400e; color: #fff; }
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
                    <a href="#" class="gti-nav-parent active" data-toggle="dropdown"><i class="fas fa-truck"></i><span>Equipment</span><i class="fas fa-chevron-down gti-nav-arrow"></i></a>
                    <div class="gti-nav-children">
                        <a href="<?php echo esc_url(gti_dashboard_url('used-equipment')); ?>" class="gti-nav-child active"><i></i><span>Used Equipment</span></a>
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
                        <h1 class="gti-page-title">Used Equipment</h1>
                        <p class="gti-welcome">Welcome back, Admin! <span>&#128075;</span></p>
                    </div>
                </div>
                <div class="gti-header-right">
                    <div class="gti-date-filter">
                        <i class="fas fa-calendar"></i>
                        <span>May 1, 2024 - May 31, 2024</span>
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
                <!-- Used Equipment Stats -->
                <div class="gti-ue-stats-row">
                    <div class="gti-ue-stat-card">
                        <div class="gti-ue-stat-icon"><i class="fas fa-truck"></i></div>
                        <div class="gti-ue-stat-info">
                            <p class="gti-ue-stat-label">Total Equipment</p>
                            <p class="gti-ue-stat-value"><?php echo esc_html($total_equipment); ?></p>
                        </div>
                    </div>
                    <div class="gti-ue-stat-card">
                        <div class="gti-ue-stat-icon available"><i class="fas fa-check-circle"></i></div>
                        <div class="gti-ue-stat-info">
                            <p class="gti-ue-stat-label">Available</p>
                            <p class="gti-ue-stat-value"><?php echo esc_html($status_count_map['available'] ?? 0); ?></p>
                        </div>
                    </div>
                    <div class="gti-ue-stat-card">
                        <div class="gti-ue-stat-icon sold"><i class="fas fa-handshake"></i></div>
                        <div class="gti-ue-stat-info">
                            <p class="gti-ue-stat-label">Sold</p>
                            <p class="gti-ue-stat-value"><?php echo esc_html($status_count_map['sold'] ?? 0); ?></p>
                        </div>
                    </div>
                    <div class="gti-ue-stat-card">
                        <div class="gti-ue-stat-icon reserved"><i class="fas fa-clock"></i></div>
                        <div class="gti-ue-stat-info">
                            <p class="gti-ue-stat-label">Reserved</p>
                            <p class="gti-ue-stat-value"><?php echo esc_html($status_count_map['reserved'] ?? 0); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Toolbar -->
                <form class="gti-ue-toolbar" method="get">
                    <input type="hidden" name="gti_page" value="used-equipment">
                    <div class="gti-ue-toolbar-left">
                        <div class="gti-ue-search">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" placeholder="Search equipment..." value="<?php echo esc_attr($search); ?>">
                        </div>
                        <div class="gti-ue-filter">
                            <select name="category" onchange="this.form.submit()">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo esc_attr($cat); ?>" <?php selected($category, $cat); ?>><?php echo esc_html($cat); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="gti-ue-filter">
                            <select name="brand" onchange="this.form.submit()">
                                <option value="">All Brands</option>
                                <?php foreach ($brands as $b): ?>
                                    <option value="<?php echo esc_attr($b); ?>" <?php selected($brand, $b); ?>><?php echo esc_html($b); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="gti-ue-filter">
                            <select name="status" onchange="this.form.submit()">
                                <option value="">All Status</option>
                                <option value="available" <?php selected($status_filter, 'available'); ?>>Available</option>
                                <option value="sold" <?php selected($status_filter, 'sold'); ?>>Sold</option>
                                <option value="reserved" <?php selected($status_filter, 'reserved'); ?>>Reserved</option>
                                <option value="draft" <?php selected($status_filter, 'draft'); ?>>Draft</option>
                            </select>
                        </div>
                        <div class="gti-ue-filter">
                            <select name="condition" onchange="this.form.submit()">
                                <option value="">All Conditions</option>
                                <option value="Excellent" <?php selected($condition_filter, 'Excellent'); ?>>Excellent</option>
                                <option value="Good" <?php selected($condition_filter, 'Good'); ?>>Good</option>
                                <option value="Fair" <?php selected($condition_filter, 'Fair'); ?>>Fair</option>
                            </select>
                        </div>
                        <a href="<?php echo esc_url(gti_dashboard_url('used-equipment')); ?>" class="gti-ue-btn-reset">
                            <i class="fas fa-rotate-right"></i> Reset
                        </a>
                    </div>
                    <a href="<?php echo esc_url(gti_dashboard_url('used-equipment/add')); ?>" class="gti-ue-btn-add">
                        <i class="fas fa-plus"></i> Add Equipment
                    </a>
                </form>

                <!-- Table -->
                <div class="gti-ue-table-card">
                    <table class="gti-ue-table">
                        <thead>
                            <tr>
                                <th class="col-image">Image</th>
                                <th class="col-name">Equipment Name</th>
                                <th class="col-category">Category</th>
                                <th class="col-brand">Brand</th>
                                <th class="col-condition">Condition</th>
                                <th class="col-year">Year</th>
                                <th class="col-price">Price</th>
                                <th class="col-status">Status</th>
                                <th class="col-actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($equipments)): ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 60px 20px;">
                                        <div style="color: #9ca3af;">
                                            <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 16px; display: block;"></i>
                                            <p style="font-size: 16px; font-weight: 500; margin-bottom: 8px;">No equipment found</p>
                                            <p style="font-size: 14px;">
                                                <?php echo ($search || $category || $brand || $status_filter || $condition_filter) ? 'Try adjusting your filters' : 'Get started by adding your first equipment'; ?>
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($equipments as $eq): ?>
                                    <tr data-eq-id="<?php echo esc_attr($eq->id); ?>" data-eq='<?php echo esc_attr(json_encode($eq)); ?>'>
                                        <td class="col-image">
                                            <div class="gti-ue-thumb">
                                                <?php if (!empty($eq->main_image)): ?>
                                                    <img src="<?php echo esc_url($eq->main_image); ?>" alt="<?php echo esc_attr($eq->name); ?>">
                                                <?php else: ?>
                                                    <i class="fas fa-truck"></i>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="col-name">
                                            <strong><?php echo esc_html($eq->name); ?></strong><br>
                                            <small><?php echo esc_html($eq->year); ?> Model<?php echo $eq->hours ? ', ' . number_format($eq->hours) . ' Hours' : ''; ?></small>
                                        </td>
                                        <td class="col-category"><?php echo esc_html($eq->category); ?></td>
                                        <td class="col-brand"><?php echo esc_html($eq->brand); ?></td>
                                        <td class="col-condition">
                                            <?php
                                            $cond_lower = strtolower($eq->condition_status ?? '');
                                            $cond_class = $cond_lower === 'excellent' ? 'excellent' : ($cond_lower === 'good' ? 'good' : 'fair');
                                            ?>
                                            <span class="gti-condition <?php echo esc_attr($cond_class); ?>"><?php echo esc_html($eq->condition_status); ?></span>
                                        </td>
                                        <td class="col-year"><?php echo esc_html($eq->year); ?></td>
                                        <td class="col-price"><?php echo esc_html($_gti_eq_fmt($eq->price)); ?></td>
                                        <td class="col-status"><?php echo $_gti_eq_status_badge($eq->status, $eq->price_valid_until); ?></td>
                                        <td class="col-actions">
                                            <div class="gti-ue-action-menu">
                                                <button class="gti-ue-action-toggle" title="Actions"><i class="fas fa-ellipsis-v"></i></button>
                                                <div class="gti-ue-action-dropdown">
                                                    <a href="#" class="gti-ue-action-item gti-ue-btn-view"><i class="fas fa-eye"></i> View</a>
                                                    <a href="#" class="gti-ue-action-item gti-ue-btn-edit"><i class="fas fa-edit"></i> Edit</a>
                                                    <a href="#" class="gti-ue-action-item delete gti-ue-btn-delete"><i class="fas fa-trash"></i> Delete</a>
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
                                if ($category) $query_params['category'] = $category;
                                if ($brand) $query_params['brand'] = $brand;
                                if ($status_filter) $query_params['status'] = $status_filter;
                                if ($condition_filter) $query_params['condition'] = $condition_filter;
                                $base_url = gti_dashboard_url('used-equipment');
                                ?>
                                <?php if ($paged > 1): ?>
                                    <a href="<?php echo esc_url($base_url . '?' . http_build_query(array_merge($query_params, ['page_num' => $paged - 1]))); ?>" class="gti-ue-page-btn"><i class="fas fa-chevron-left"></i></a>
                                <?php else: ?>
                                    <button class="gti-ue-page-btn" disabled><i class="fas fa-chevron-left"></i></button>
                                <?php endif; ?>
                                <?php
                                $start = max(1, $paged - 2);
                                $end = min($total_pages, $paged + 2);
                                ?>
                                <?php if ($start > 1): ?>
                                    <a href="<?php echo esc_url($base_url . '?' . http_build_query(array_merge($query_params, ['page_num' => 1]))); ?>" class="gti-ue-page-btn">1</a>
                                    <?php if ($start > 2): ?><span class="gti-ue-page-dots">...</span><?php endif; ?>
                                <?php endif; ?>
                                <?php for ($i = $start; $i <= $end; $i++): ?>
                                    <?php if ($i == $paged): ?>
                                        <button class="gti-ue-page-btn active"><?php echo $i; ?></button>
                                    <?php else: ?>
                                        <a href="<?php echo esc_url($base_url . '?' . http_build_query(array_merge($query_params, ['page_num' => $i]))); ?>" class="gti-ue-page-btn"><?php echo $i; ?></a>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                <?php if ($end < $total_pages): ?>
                                    <?php if ($end < $total_pages - 1): ?><span class="gti-ue-page-dots">...</span><?php endif; ?>
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
                <!-- Detail Drawer -->
                <div class="gti-drawer-backdrop" id="drawerBackdrop"></div>
                <div class="gti-drawer" id="eqDetailDrawer">
        <div class="gti-drawer-header">
            <div class="gti-drawer-header-left">
                <h2 id="drawer-eq-code">-</h2>
                <span class="gti-drawer-status" id="drawer-status-badge">Available</span>
            </div>
            <button type="button" class="gti-drawer-close" onclick="closeDetailDrawer()"><i class="fas fa-times"></i></button>
        </div>
        <div class="gti-drawer-body">
            <!-- Stepper Navigation -->
            <div class="gti-drawer-stepper">
                <div class="gti-drawer-step active" data-section="info" onclick="switchDrawerStep(this)">
                    <div class="gti-drawer-step-circle">1</div>
                    <div class="gti-drawer-step-label">Info</div>
                </div>
                <div class="gti-drawer-step-line"></div>
                <div class="gti-drawer-step" data-section="specs" onclick="switchDrawerStep(this)">
                    <div class="gti-drawer-step-circle">2</div>
                    <div class="gti-drawer-step-label">Specs</div>
                </div>
                <div class="gti-drawer-step-line"></div>
                <div class="gti-drawer-step" data-section="pricing" onclick="switchDrawerStep(this)">
                    <div class="gti-drawer-step-circle">3</div>
                    <div class="gti-drawer-step-label">Pricing</div>
                </div>
                <div class="gti-drawer-step-line"></div>
                <div class="gti-drawer-step" data-section="media" onclick="switchDrawerStep(this)">
                    <div class="gti-drawer-step-circle">4</div>
                    <div class="gti-drawer-step-label">Media</div>
                </div>
                <div class="gti-drawer-step-line"></div>
                <div class="gti-drawer-step" data-section="additional" onclick="switchDrawerStep(this)">
                    <div class="gti-drawer-step-circle">5</div>
                    <div class="gti-drawer-step-label">More</div>
                </div>
            </div>

            <!-- Section: 1 - Info (General + Basic) -->
            <div class="gti-drawer-section active" data-section="info">
                <div class="gti-drawer-section-title"><i class="fas fa-info-circle"></i> General Information</div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Name</span><span class="gti-drawer-value" id="drawer-name">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Equipment Code</span><span class="gti-drawer-value" id="drawer-eq-code-info">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Category</span><span class="gti-drawer-value" id="drawer-category">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Brand</span><span class="gti-drawer-value" id="drawer-brand">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Model</span><span class="gti-drawer-value" id="drawer-model">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Year</span><span class="gti-drawer-value" id="drawer-year">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Hours</span><span class="gti-drawer-value" id="drawer-hours">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Serial Number</span><span class="gti-drawer-value" id="drawer-serial-number">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Condition</span><span class="gti-drawer-value" id="drawer-condition">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Status</span><span class="gti-drawer-value" id="drawer-status-text">-</span></div>
                <div class="gti-drawer-section-title" style="margin-top:18px;"><i class="fas fa-engine"></i> Engine & Origin</div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Engine</span><span class="gti-drawer-value" id="drawer-engine">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Engine Power</span><span class="gti-drawer-value" id="drawer-engine-power">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Origin</span><span class="gti-drawer-value" id="drawer-origin">-</span></div>
                <div class="gti-drawer-section-title" style="margin-top:18px;"><i class="fas fa-clipboard-list"></i> Basic Information</div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Type</span><span class="gti-drawer-value" id="drawer-type">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Operating Weight</span><span class="gti-drawer-value" id="drawer-operating-weight">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Bucket Capacity</span><span class="gti-drawer-value" id="drawer-bucket-capacity">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Location</span><span class="gti-drawer-value" id="drawer-location">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Stock Number</span><span class="gti-drawer-value" id="drawer-stock-number">-</span></div>
                    <div class="gti-drawer-row" style="flex-direction:column;">
                        <span class="gti-drawer-label">Description</span>
                        <span class="gti-drawer-value is-message" id="drawer-description">-</span>
                    </div>
            </div>

            <!-- Section: 2 - Specs (Technical + Features) -->
            <div class="gti-drawer-section" data-section="specs">
                <div class="gti-drawer-section-title"><i class="fas fa-cogs"></i> Technical Specifications</div>
                    <div id="drawer-specs-list"><div class="gti-drawer-row"><span class="gti-drawer-value" style="text-align:center;color:#9ca3af;font-weight:400;">No specifications available</span></div></div>
                <div class="gti-drawer-section-title" style="margin-top:18px;"><i class="fas fa-sliders-h"></i> Features & Configurations</div>
                    <div id="drawer-features-list" class="gti-drawer-features"><span class="gti-drawer-value" style="text-align:center;color:#9ca3af;font-weight:400;width:100%;display:block;">No features</span></div>
            </div>

            <!-- Section: 3 - Pricing & Status -->
            <div class="gti-drawer-section" data-section="pricing">
                <div class="gti-drawer-section-title"><i class="fas fa-tag"></i> Pricing Information</div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Selling Price</span><span class="gti-drawer-value" id="drawer-selling-price">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Rental Price</span><span class="gti-drawer-value" id="drawer-rental-price">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Price Type</span><span class="gti-drawer-value" id="drawer-price-type">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">VAT Included</span><span class="gti-drawer-value" id="drawer-vat">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Currency</span><span class="gti-drawer-value" id="drawer-currency">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Price Valid Until</span><span class="gti-drawer-value" id="drawer-price-valid-until">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Negotiable</span><span class="gti-drawer-value" id="drawer-negotiable">-</span></div>
                <div class="gti-drawer-section-title" style="margin-top:18px;"><i class="fas fa-clipboard-check"></i> Availability</div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Availability</span><span class="gti-drawer-value" id="drawer-availability-status">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Ready to Use</span><span class="gti-drawer-value" id="drawer-ready-to-use">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Service History</span><span class="gti-drawer-value" id="drawer-service-history">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Warranty Available</span><span class="gti-drawer-value" id="drawer-warranty-avail">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Warranty Period</span><span class="gti-drawer-value" id="drawer-warranty-period">-</span></div>
                    <div class="gti-drawer-row" style="flex-direction:column;">
                        <span class="gti-drawer-label">Buyer Notes</span>
                        <span class="gti-drawer-value is-message" id="drawer-buyer-notes">-</span>
                    </div>
            </div>

            <!-- Section: 4 - Media (Images + Video) -->
            <div class="gti-drawer-section" data-section="media">
                <div class="gti-drawer-section-title"><i class="fas fa-images"></i> Images</div>
                    <div class="gti-drawer-row" style="flex-direction:column;">
                        <span class="gti-drawer-label">Main Image</span>
                        <div id="drawer-main-image" style="margin-top:8px;"><span class="gti-drawer-value" style="text-align:center;color:#9ca3af;font-weight:400;">No image</span></div>
                    </div>
                    <div class="gti-drawer-row" style="flex-direction:column;">
                        <span class="gti-drawer-label">Gallery</span>
                        <div id="drawer-gallery" style="margin-top:8px;display:flex;gap:6px;flex-wrap:wrap;"><span class="gti-drawer-value" style="text-align:center;color:#9ca3af;font-weight:400;">No images</span></div>
                    </div>
                <div class="gti-drawer-section-title" style="margin-top:18px;"><i class="fas fa-video"></i> Video</div>
                    <div class="gti-drawer-row" style="flex-direction:column;">
                        <div id="drawer-video"><span class="gti-drawer-value" style="text-align:center;color:#9ca3af;font-weight:400;">No video</span></div>
                    </div>
            </div>

            <!-- Section: 5 - Additional (History, Docs, Location, System) -->
            <div class="gti-drawer-section" data-section="additional">
                <div class="gti-drawer-section-title"><i class="fas fa-history"></i> Equipment History</div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Previous Usage</span><span class="gti-drawer-value" id="drawer-previous-usage">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Working Condition</span><span class="gti-drawer-value" id="drawer-working-condition">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Maintenance Record</span><span class="gti-drawer-value" id="drawer-maintenance-record">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Ownership</span><span class="gti-drawer-value" id="drawer-ownership">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Operator Hours</span><span class="gti-drawer-value" id="drawer-operator-hours">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Last Service Date</span><span class="gti-drawer-value" id="drawer-last-service">-</span></div>
                    <div class="gti-drawer-row" style="flex-direction:column;">
                        <span class="gti-drawer-label">Equipment History</span>
                        <span class="gti-drawer-value is-message" id="drawer-equipment-history">-</span>
                    </div>
                    <div class="gti-drawer-row" style="flex-direction:column;">
                        <span class="gti-drawer-label">Detailed Description</span>
                        <span class="gti-drawer-value is-message" id="drawer-detailed-description">-</span>
                    </div>
                <div class="gti-drawer-section-title" style="margin-top:18px;"><i class="fas fa-file-alt"></i> Documents</div>
                    <div id="drawer-documents-list" class="gti-drawer-docs"><span class="gti-drawer-value" style="text-align:center;color:#9ca3af;font-weight:400;width:100%;display:block;">No documents</span></div>
                <div class="gti-drawer-section-title" style="margin-top:18px;"><i class="fas fa-map-marker-alt"></i> Location Details</div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Country</span><span class="gti-drawer-value" id="drawer-location-country">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Province</span><span class="gti-drawer-value" id="drawer-location-province">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">City</span><span class="gti-drawer-value" id="drawer-location-city">-</span></div>
                    <div class="gti-drawer-row" style="flex-direction:column;">
                        <span class="gti-drawer-label">Address</span>
                        <span class="gti-drawer-value is-message" id="drawer-address">-</span>
                    </div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Map Link</span><span class="gti-drawer-value" id="drawer-map-location">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Location Notes</span><span class="gti-drawer-value" id="drawer-location-notes">-</span></div>
                <div class="gti-drawer-section-title" style="margin-top:18px;"><i class="fas fa-database"></i> System</div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Created</span><span class="gti-drawer-value" id="drawer-created">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Updated</span><span class="gti-drawer-value" id="drawer-updated">-</span></div>
            </div>
        </div>
        <div class="gti-drawer-footer">
            <button type="button" class="gti-drawer-btn gti-drawer-btn-primary" id="drawer-btn-edit"><i class="fas fa-edit"></i> Edit</button>
            <span class="gti-drawer-footer-spacer"></span>
            <button type="button" class="gti-drawer-btn" id="drawer-btn-publish" style="display:none;background:#059669;color:#fff;border-color:#059669;font-weight:600;"><i class="fas fa-check-circle"></i> Publish</button>
            <button type="button" class="gti-drawer-btn" id="drawer-btn-delete" style="color:#b91c1c;border-color:#fca5a5;"><i class="fas fa-trash"></i> Delete</button>
        </div>
    </div>
            </div> <!-- /.gti-content -->
        </main>
    </div>

    <!-- ====== Edit Equipment Modal (Fullscreen Popup) ====== -->
    <div class="gti-ue-edit-overlay" id="gtiEditOverlay">
        <div class="gti-edit-backdrop" onclick="closeEditModal()"></div>
        <div class="gti-ue-edit-container">
            <div class="gti-ue-edit-header">
                <div class="gti-ue-edit-header-left">
                    <h2><i class="fas fa-edit"></i> Edit Equipment</h2>
                </div>
                <div class="gti-ae-stepper-card">
                    <div class="gti-ae-stepper">
                        <div class="gti-ae-step active" data-step="1" onclick="editGoStep(1)">
                            <div class="gti-ae-step-circle">1</div>
                            <div class="gti-ae-step-label">General Info</div>
                        </div>
                        <div class="gti-ae-step-line"></div>
                        <div class="gti-ae-step" data-step="2" onclick="editGoStep(2)">
                            <div class="gti-ae-step-circle">2</div>
                            <div class="gti-ae-step-label">Specifications</div>
                        </div>
                        <div class="gti-ae-step-line"></div>
                        <div class="gti-ae-step" data-step="3" onclick="editGoStep(3)">
                            <div class="gti-ae-step-circle">3</div>
                            <div class="gti-ae-step-label">Pricing</div>
                        </div>
                        <div class="gti-ae-step-line"></div>
                        <div class="gti-ae-step" data-step="4" onclick="editGoStep(4)">
                            <div class="gti-ae-step-circle">4</div>
                            <div class="gti-ae-step-label">Images & Media</div>
                        </div>
                        <div class="gti-ae-step-line"></div>
                        <div class="gti-ae-step" data-step="5" onclick="editGoStep(5)">
                            <div class="gti-ae-step-circle">5</div>
                            <div class="gti-ae-step-label">Additional Info</div>
                        </div>
                    </div>
                </div>
                <button class="gti-ue-edit-close" onclick="closeEditModal()" title="Close"><i class="fas fa-times"></i></button>
            </div>
            <div class="gti-ue-edit-body">
                <form id="gti-edit-form" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="id" id="edit-field-id" value="">
                    <input type="hidden" name="action" value="gti_save_equipment">
                    <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('gti_nonce')); ?>">

                    <!-- Step 1: General Information -->
                    <div class="gti-ae-step-content active" data-step="1">
                        <div class="gti-ae-form-card">
                            <div class="gti-ae-card-header"><h3><i class="fas fa-info-circle"></i> General Information</h3></div>
                            <div class="gti-ae-card-body">
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field"><label>Equipment Name <span class="required">*</span></label><input type="text" name="name" required></div>
                                    <div class="gti-ae-field"><label>Equipment Code <span class="required">*</span></label><input type="text" name="equipment_code" readonly required style="background:#f9fafb;cursor:not-allowed;"></div>
                                    <div class="gti-ae-field"><label>Category <span class="required">*</span></label><select name="category" required><option value="">Select Category</option><option value="Excavator">Excavator</option><option value="Bulldozer">Bulldozer</option><option value="Wheel Loader">Wheel Loader</option><option value="Dump Truck">Dump Truck</option><option value="Motor Grader">Motor Grader</option><option value="Crane">Crane</option><option value="Compactor">Compactor</option></select></div>
                                </div>
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field"><label>Brand <span class="required">*</span></label><select name="brand" required><option value="">Select Brand</option><option value="KOMATSU">KOMATSU</option><option value="CATERPILLAR">CATERPILLAR</option><option value="HITACHI">HITACHI</option><option value="VOLVO">VOLVO</option><option value="KOBELCO">KOBELCO</option><option value="DOOSAN">DOOSAN</option><option value="HYUNDAI">HYUNDAI</option></select></div>
                                    <div class="gti-ae-field"><label>Model <span class="required">*</span></label><input type="text" name="model" required></div>
                                    <div class="gti-ae-field"><label>Year <span class="required">*</span></label><input type="number" name="year" min="1900" max="<?php echo date('Y') + 1; ?>" required></div>
                                </div>
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field"><label>Operating Hours</label><input type="number" name="hours"></div>
                                    <div class="gti-ae-field"><label>Serial Number</label><input type="text" name="serial_number"></div>
                                    <div class="gti-ae-field"><label>Condition <span class="required">*</span></label><select name="condition_status" required><option value="">Select Condition</option><option value="excellent">Excellent</option><option value="good">Good</option><option value="fair">Fair</option><option value="poor">Poor</option></select></div>
                                </div>
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field"><label>Engine</label><input type="text" name="engine"></div>
                                    <div class="gti-ae-field"><label>Engine Power</label><input type="text" name="engine_power"></div>
                                    <div class="gti-ae-field"><label>Country of Origin</label><select name="origin_country"><option value="">Select Origin</option><option value="Japan">Japan</option><option value="USA">USA</option><option value="South Korea">South Korea</option><option value="China">China</option><option value="Germany">Germany</option><option value="Sweden">Sweden</option></select></div>
                                </div>
                            </div>
                        </div>
                        <div class="gti-ae-form-card">
                            <div class="gti-ae-card-header"><h3><i class="fas fa-clipboard-list"></i> Basic Information</h3></div>
                            <div class="gti-ae-card-body">
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field"><label>Equipment Type</label><select name="type"><option value="">Select Type</option><option value="used">Used Equipment</option><option value="rental">Rental Equipment</option></select></div>
                                </div>
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field"><label>Location</label><select name="location"><option value="">Select Location</option><option value="Jakarta">Jakarta</option><option value="Balikpapan">Balikpapan</option><option value="Surabaya">Surabaya</option><option value="Bandung">Bandung</option><option value="Medan">Medan</option><option value="Makassar">Makassar</option></select></div>
                                    <div class="gti-ae-field"><label>Availability Status <span class="required">*</span></label><select name="status" required><option value="">Select Status</option><option value="available">Available</option><option value="sold">Sold</option><option value="reserved">Reserved</option><option value="maintenance">Maintenance</option></select></div>
                                    <div class="gti-ae-field"><label>Stock Number (Internal)</label><input type="text" name="stock_number"></div>
                                </div>
                                <div class="gti-ae-field gti-ae-field-full"><label>Description <span class="required">*</span></label><textarea name="description" rows="4" required></textarea></div>
                            </div>
                        </div>
                        <div class="gti-ae-step-nav"><span></span><button type="button" class="gti-ae-next" onclick="editNextStep()">Next <i class="fas fa-arrow-right"></i></button></div>
                    </div>

                    <!-- Step 2: Specifications -->
                    <div class="gti-ae-step-content" data-step="2">
                        <div class="gti-ae-form-card">
                            <div class="gti-ae-card-header"><h3><i class="fas fa-cogs"></i> Technical Specifications</h3></div>
                            <div class="gti-ae-card-body">
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field"><label>Operating Weight</label><input type="text" name="operating_weight"></div>
                                    <div class="gti-ae-field"><label>Bucket Capacity</label><input type="text" name="bucket_capacity"></div>
                                    <div class="gti-ae-field"><label>Engine Model</label><input type="text" name="engine_model"></div>
                                </div>
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field"><label>Displacement</label><input type="text" name="displacement"></div>
                                    <div class="gti-ae-field"><label>No. of Cylinders</label><input type="number" name="cylinders"></div>
                                </div>
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field"><label>Hydraulic System</label><input type="text" name="hydraulic_system"></div>
                                    <div class="gti-ae-field"><label>Hydraulic Pump Flow</label><input type="text" name="hydraulic_pump_flow"></div>
                                    <div class="gti-ae-field"><label>Max. Digging Depth</label><input type="text" name="max_digging_depth"></div>
                                </div>
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field"><label>Max. Digging Height</label><input type="text" name="max_digging_height"></div>
                                    <div class="gti-ae-field"><label>Max. Reach at Ground Level</label><input type="text" name="max_reach_ground"></div>
                                    <div class="gti-ae-field"><label>Max. Dumping Height</label><input type="text" name="max_dumping_height"></div>
                                </div>
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field"><label>Travel Speed (High/Low)</label><input type="text" name="travel_speed"></div>
                                    <div class="gti-ae-field"><label>Swing Speed</label><input type="text" name="swing_speed"></div>
                                    <div class="gti-ae-field"><label>Fuel Tank Capacity</label><input type="text" name="fuel_tank_capacity"></div>
                                </div>
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field"><label>Hydraulic Tank Capacity</label><input type="text" name="hydraulic_tank_capacity"></div>
                                    <div class="gti-ae-field"><label>Track Width</label><input type="text" name="track_width"></div>
                                    <div class="gti-ae-field"><label>Ground Pressure</label><input type="text" name="ground_pressure"></div>
                                </div>
                            </div>
                        </div>
                        <div class="gti-ae-form-card">
                            <div class="gti-ae-card-header"><h3><i class="fas fa-sliders-h"></i> Features & Configurations</h3></div>
                            <div class="gti-ae-card-body">
                                <div class="gti-ae-features-grid">
                                    <label class="gti-ae-feature-item"><input type="checkbox" name="features[air_conditioner]" value="1"><span class="gti-ae-feature-check"></span><span>Air Conditioner</span></label>
                                    <label class="gti-ae-feature-item"><input type="checkbox" name="features[backup_alarm]" value="1"><span class="gti-ae-feature-check"></span><span>Backup Alarm</span></label>
                                    <label class="gti-ae-feature-item"><input type="checkbox" name="features[led_work_light]" value="1"><span class="gti-ae-feature-check"></span><span>LED Work Light</span></label>
                                    <label class="gti-ae-feature-item"><input type="checkbox" name="features[camera]" value="1"><span class="gti-ae-feature-check"></span><span>Camera</span></label>
                                    <label class="gti-ae-feature-item"><input type="checkbox" name="features[auto_idle]" value="1"><span class="gti-ae-feature-check"></span><span>Auto Idle</span></label>
                                    <label class="gti-ae-feature-item"><input type="checkbox" name="features[hammer_line]" value="1"><span class="gti-ae-feature-check"></span><span>Hammer Line</span></label>
                                    <label class="gti-ae-feature-item"><input type="checkbox" name="features[quick_coupler]" value="1"><span class="gti-ae-feature-check"></span><span>Quick Coupler</span></label>
                                    <label class="gti-ae-feature-item"><input type="checkbox" name="features[gps_system]" value="1"><span class="gti-ae-feature-check"></span><span>GPS System</span></label>
                                    <label class="gti-ae-feature-item"><input type="checkbox" name="features[centralized_greasing]" value="1"><span class="gti-ae-feature-check"></span><span>Centralized Greasing</span></label>
                                </div>
                            </div>
                        </div>
                        <div class="gti-ae-step-nav"><button type="button" class="gti-ae-prev" onclick="editPrevStep()"><i class="fas fa-arrow-left"></i> Previous</button><button type="button" class="gti-ae-next" onclick="editNextStep()">Next <i class="fas fa-arrow-right"></i></button></div>
                    </div>

                    <!-- Step 3: Pricing & Status -->
                    <div class="gti-ae-step-content" data-step="3">
                        <div class="gti-ae-form-card">
                            <div class="gti-ae-card-header"><h3><i class="fas fa-tag"></i> Pricing Information</h3></div>
                            <div class="gti-ae-card-body">
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field"><label>Selling Price (IDR)</label><input type="text" name="selling_price" inputmode="numeric"></div>
                                    <div class="gti-ae-field"><label>Rental Price (IDR/Month)</label><input type="text" name="rental_price" inputmode="numeric"></div>
                                    <div class="gti-ae-field"><label>Price Type</label><select name="price_type"><option value="">-- Select Price Type --</option><option value="sale">For Sale</option><option value="rental">For Rental</option><option value="sale_and_rental">Sale & Rental</option><option value="price_on_ask">Price on Ask</option></select></div>
                                </div>
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field"><label>VAT Included?</label><select name="vat_included"><option value="">-- Select --</option><option value="yes">Yes</option><option value="no">No</option></select></div>
                                    <div class="gti-ae-field"><label>Currency</label><select name="currency"><option value="IDR">IDR (Rp)</option><option value="USD">USD ($)</option><option value="EUR">EUR (€)</option></select></div>
                                    <div class="gti-ae-field"><label>Price Valid Until</label><input type="date" name="price_valid_until"></div>
                                    <div class="gti-ae-field"><label>Negotiable</label><select name="negotiable"><option value="">-- Select --</option><option value="yes">Yes</option><option value="no">No</option></select></div>
                                </div>
                            </div>
                        </div>
                        <div class="gti-ae-form-card">
                            <div class="gti-ae-card-header"><h3><i class="fas fa-clipboard-check"></i> Status & Availability</h3></div>
                            <div class="gti-ae-card-body">
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field"><label>Availability Status</label><select name="availability_status"><option value="">-- Select Status --</option><option value="available">Available</option><option value="reserved">Reserved</option><option value="sold">Sold</option><option value="in_transit">In Transit</option><option value="under_maintenance">Under Maintenance</option></select></div>
                                </div>
                                <!-- stock_number, condition_status, location, operating_weight, bucket_capacity and engine_power
                                     are each defined exactly once in this form. A repeated name= is sent twice by FormData and
                                     PHP keeps only the last value, which silently wiped the earlier step's input. -->
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field"><label>Ready to Use</label><select name="ready_to_use"><option value="">-- Select --</option><option value="yes">Yes</option><option value="no">No</option><option value="after_service">After Service</option></select></div>
                                    <div class="gti-ae-field"><label>Service History</label><select name="service_history"><option value="">-- Select --</option><option value="full">Full Service History</option><option value="partial">Partial Service History</option><option value="none">No Service History</option></select></div>
                                </div>
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field"><label>Warranty Available?</label><select name="warranty_available"><option value="">-- Select --</option><option value="yes">Yes</option><option value="no">No</option></select></div>
                                    <div class="gti-ae-field"><label>Warranty Period</label><input type="text" name="warranty_period"></div>
                                </div>
                                <div class="gti-ae-form-grid"><div class="gti-ae-field gti-ae-field-full"><label>Notes for Buyers</label><textarea name="buyer_notes" rows="3"></textarea></div></div>
                            </div>
                        </div>
                        <div class="gti-ae-step-nav"><button type="button" class="gti-ae-prev" onclick="editPrevStep()"><i class="fas fa-arrow-left"></i> Previous</button><button type="button" class="gti-ae-next" onclick="editNextStep()">Next <i class="fas fa-arrow-right"></i></button></div>
                    </div>

                    <!-- Step 4: Images & Media -->
                    <div class="gti-ae-step-content" data-step="4">
                        <div class="gti-ae-form-card">
                            <div class="gti-ae-card-header"><h3><i class="fas fa-images"></i> Images & Media</h3></div>
                            <div class="gti-ae-card-body">
                                <div class="gti-ae-field gti-ae-field-full">
                                    <label>Main Image</label>
                                    <div class="gti-ae-upload-area" id="gti-edit-main-upload">
                                        <input type="file" name="main_image" id="gti-edit-main-image" accept="image/*" style="display:none;">
                                        <div class="gti-ae-upload-placeholder" id="gti-edit-upload-placeholder">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                            <p>Click or drag image here to upload</p>
                                        </div>
                                        <div class="gti-ae-upload-preview" id="gti-edit-upload-preview" style="display:none;">
                                            <img id="gti-edit-preview-img" src="" alt="Preview">
                                            <button type="button" class="gti-ae-remove-img" id="gti-edit-remove-img"><i class="fas fa-times"></i></button>
                                        </div>
                                    </div>
                                </div>
                                <div class="gti-ae-field gti-ae-field-full">
                                    <label>Additional Images</label>
                                    <div class="gti-ae-upload-area" id="gti-edit-gallery-upload">
                                        <input type="file" name="gallery_images[]" id="gti-edit-gallery-images" accept="image/*" multiple style="display:none;">
                                        <div class="gti-ae-upload-placeholder"><i class="fas fa-images"></i><p>Click or drag multiple images here</p></div>
                                    </div>
                                    <div class="gti-ae-gallery-preview" id="gti-edit-gallery-preview"></div>
                                </div>
                                <div class="gti-ae-field gti-ae-field-full">
                                    <label>Video (Optional)</label>
                                    <div class="gti-ae-upload-area" id="gti-edit-video-upload">
                                        <input type="file" name="video_file" id="gti-edit-video-file" accept="video/*" style="display:none;">
                                        <div class="gti-ae-upload-placeholder" id="gti-edit-video-placeholder"><i class="fas fa-video"></i><p>Click or drag video here to upload</p></div>
                                        <div class="gti-ae-upload-preview" id="gti-edit-video-preview" style="display:none;">
                                            <div class="gti-ae-video-thumb"><i class="fas fa-play-circle"></i><span id="gti-edit-video-name"></span></div>
                                            <button type="button" class="gti-ae-remove-img" id="gti-edit-remove-video"><i class="fas fa-times"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="gti-ae-step-nav"><button type="button" class="gti-ae-prev" onclick="editPrevStep()"><i class="fas fa-arrow-left"></i> Previous</button><button type="button" class="gti-ae-next" onclick="editNextStep()">Next <i class="fas fa-arrow-right"></i></button></div>
                    </div>

                    <!-- Step 5: Additional Information -->
                    <div class="gti-ae-step-content" data-step="5">
                        <div class="gti-ae-form-card">
                            <div class="gti-ae-card-header"><h3><i class="fas fa-clipboard-list"></i> Additional Information</h3></div>
                            <div class="gti-ae-card-body">
                                <div class="gti-ae-field gti-ae-field-full"><label>Description (Detailed)</label><textarea name="detailed_description" rows="4"></textarea></div>
                                <div class="gti-ae-field gti-ae-field-full"><label>Equipment History</label><textarea name="equipment_history" rows="4"></textarea></div>
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field"><label>Previous Usage</label><input type="text" name="previous_usage"></div>
                                    <div class="gti-ae-field"><label>Working Condition</label><select name="working_condition"><option value="">Select</option><option value="excellent">Excellent</option><option value="good">Good</option><option value="fair">Fair</option><option value="poor">Poor</option></select></div>
                                    <div class="gti-ae-field"><label>Maintenance Record</label><select name="maintenance_record"><option value="">Select</option><option value="full">Full Record</option><option value="partial">Partial Record</option><option value="none">No Record</option></select></div>
                                    <div class="gti-ae-field"><label>Ownership</label><select name="ownership"><option value="">Select</option><option value="first_owner">First Owner</option><option value="second_owner">Second Owner</option><option value="third_plus">Third Owner or More</option><option value="company">Company Fleet</option></select></div>
                                    <div class="gti-ae-field"><label>Operator Hours</label><input type="number" name="operator_hours"></div>
                                    <div class="gti-ae-field"><label>Last Service Date</label><input type="date" name="last_service_date"></div>
                                </div>
                            </div>
                        </div>
                        <div class="gti-ae-form-card">
                            <div class="gti-ae-card-header"><h3><i class="fas fa-file-alt"></i> Document Availability</h3></div>
                            <div class="gti-ae-card-body">
                                <div class="gti-ae-doc-grid">
                                    <label class="gti-ae-doc-item"><input type="checkbox" name="documents[unit_certificate]" value="1"><span class="gti-ae-doc-check"></span><span class="gti-ae-doc-label">Unit Certificate (STNK/BPKB)</span></label>
                                    <label class="gti-ae-doc-item"><input type="checkbox" name="documents[import_document]" value="1"><span class="gti-ae-doc-check"></span><span class="gti-ae-doc-label">Import Document</span></label>
                                    <label class="gti-ae-doc-item"><input type="checkbox" name="documents[service_maintenance_record]" value="1"><span class="gti-ae-doc-check"></span><span class="gti-ae-doc-label">Service & Maintenance Record</span></label>
                                    <label class="gti-ae-doc-item"><input type="checkbox" name="documents[customs_document]" value="1"><span class="gti-ae-doc-check"></span><span class="gti-ae-doc-label">Customs Document</span></label>
                                    <label class="gti-ae-doc-item"><input type="checkbox" name="documents[warranty_book]" value="1"><span class="gti-ae-doc-check"></span><span class="gti-ae-doc-label">Warranty Book</span></label>
                                </div>
                            </div>
                        </div>
                        <div class="gti-ae-form-card">
                            <div class="gti-ae-card-header"><h3><i class="fas fa-map-marker-alt"></i> Location Details</h3></div>
                            <div class="gti-ae-card-body">
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field"><label>Country</label><select name="location_country"><option value="">Select Country</option><option value="Indonesia">Indonesia</option><option value="Japan">Japan</option><option value="South Korea">South Korea</option><option value="China">China</option><option value="USA">USA</option><option value="Germany">Germany</option></select></div>
                                    <div class="gti-ae-field"><label>Province</label><select name="location_province"><option value="">Select Province</option><option value="DKI Jakarta">DKI Jakarta</option><option value="Jawa Barat">Jawa Barat</option><option value="Jawa Timur">Jawa Timur</option><option value="Kalimantan Timur">Kalimantan Timur</option><option value="Kalimantan Selatan">Kalimantan Selatan</option><option value="Sulawesi Selatan">Sulawesi Selatan</option><option value="Sumatera Utara">Sumatera Utara</option></select></div>
                                    <div class="gti-ae-field"><label>City / Regency</label><select name="location_city"><option value="">Select City</option><option value="Jakarta">Jakarta</option><option value="Bandung">Bandung</option><option value="Surabaya">Surabaya</option><option value="Balikpapan">Balikpapan</option><option value="Samarinda">Samarinda</option><option value="Makassar">Makassar</option><option value="Medan">Medan</option></select></div>
                                </div>
                                <div class="gti-ae-field gti-ae-field-full"><label>Detailed Address</label><textarea name="detailed_address" rows="2"></textarea></div>
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field"><label>Map Location</label><input type="text" name="map_location" placeholder="Paste Google Maps link"></div>
                                    <div class="gti-ae-field"><label>Additional Notes</label><input type="text" name="location_notes"></div>
                                </div>
                            </div>
                        </div>
                        <div class="gti-ae-step-nav"><button type="button" class="gti-ae-prev" onclick="editPrevStep()"><i class="fas fa-arrow-left"></i> Previous</button><span></span></div>
                    </div>
                </form>
            </div>
            <div class="gti-ue-edit-footer">
                <button type="button" class="gti-ae-btn-cancel" onclick="closeEditModal()">Cancel</button>
                <button type="button" class="gti-ae-btn-submit" id="gti-edit-submit"><i class="fas fa-save"></i> Save Changes</button>
            </div>
        </div>
    </div>

    <!-- ====== Delete Confirmation Modal ====== -->
    <div class="gti-ue-delete-overlay" id="gtiDeleteOverlay">
        <div class="gti-ue-delete-modal">
            <div class="gti-ue-delete-header">
                <div class="gti-ue-delete-icon"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="gti-ue-delete-header-text">
                    <h3>Delete Equipment</h3>
                    <p>This action cannot be undone.</p>
                </div>
            </div>
            <div class="gti-ue-delete-body">
                <div class="gti-ue-delete-eq-info">
                    <div class="gti-ue-delete-eq-thumb" id="delete-eq-thumb"><i class="fas fa-truck"></i></div>
                    <div>
                        <div class="gti-ue-delete-eq-name" id="delete-eq-name">—</div>
                        <div class="gti-ue-delete-eq-code" id="delete-eq-code">—</div>
                    </div>
                </div>
                <div class="gti-ue-delete-warning">
                    <i class="fas fa-info-circle"></i>
                    <span>Equipment will be permanently removed from the system. This data cannot be recovered.</span>
                </div>
            </div>
            <div class="gti-ue-delete-footer">
                <button class="gti-ue-delete-cancel" onclick="closeDeleteModal()">Cancel</button>
                <button class="gti-ue-delete-confirm" id="gti-delete-confirm-btn"><i class="fas fa-trash"></i> Delete</button>
            </div>
        </div>
    </div>

    <!-- ====== Toast ====== -->
    <div class="gti-ue-toast" id="gtiUeToast"></div>

    <script>
    var gtiAjax = gtiAjax || {
        ajaxurl: '<?php echo esc_js(admin_url("admin-ajax.php")); ?>',
        nonce: '<?php echo esc_js(wp_create_nonce("gti_nonce")); ?>'
    };
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

        // Action Dropdown Toggle — fixed positioning outside gti-content
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
                document.querySelectorAll('.gti-ue-action-dropdown.show').forEach(function(d) { d.classList.remove('show'); });
            }
        });

        // ====== VIEW BUTTON ======
        document.querySelectorAll('.gti-ue-btn-view').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var row = btn.closest('tr[data-eq]');
                if (!row) return;
                var eq;
                try { eq = JSON.parse(row.getAttribute('data-eq')); } catch(err) { return; }
                document.querySelectorAll('.gti-ue-action-dropdown.show').forEach(function(d) { d.classList.remove('show'); });
                showEquipmentDetail(eq);
            });
        });

        // ====== EDIT BUTTON ======
        document.querySelectorAll('.gti-ue-btn-edit').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var row = btn.closest('tr[data-eq]');
                if (!row) return;
                var eq;
                try { eq = JSON.parse(row.getAttribute('data-eq')); } catch(err) { return; }
                document.querySelectorAll('.gti-ue-action-dropdown.show').forEach(function(d) { d.classList.remove('show'); });
                openEditModal(eq);
            });
        });

        // ====== DELETE BUTTON ======
        document.querySelectorAll('.gti-ue-btn-delete').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var row = btn.closest('tr[data-eq]');
                if (!row) return;
                var eq;
                try { eq = JSON.parse(row.getAttribute('data-eq')); } catch(err) { return; }
                document.querySelectorAll('.gti-ue-action-dropdown.show').forEach(function(d) { d.classList.remove('show'); });
                openDeleteModal(eq);
            });
        });

        // Drawer backdrop close
        document.getElementById('drawerBackdrop').addEventListener('click', closeDetailDrawer);
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                if (document.getElementById('gtiEditOverlay').classList.contains('show')) { closeEditModal(); return; }
                if (document.getElementById('gtiDeleteOverlay').classList.contains('show')) { closeDeleteModal(); return; }
                closeDetailDrawer();
            }
        });

        // Search input — real-time search with debounce + maintain focus after submit
        var searchInput = document.querySelector('.gti-ue-search input[name="search"]');
        var searchTimer = null;
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                var self = this;
                clearTimeout(searchTimer);
                searchTimer = setTimeout(function() {
                    self.form.submit();
                }, 400);
            });
            // Re-focus search input after page reload if it had a value
            if (searchInput.value) {
                searchInput.focus();
                var len = searchInput.value.length;
                searchInput.setSelectionRange(len, len);
            }
        }

        // Drawer Edit button
        document.getElementById('drawer-btn-edit').addEventListener('click', function() {
            if (_currentDrawerEq) openEditModal(_currentDrawerEq);
        });
        // Drawer Publish button
        document.getElementById('drawer-btn-publish').addEventListener('click', function() {
            if (_currentDrawerEq) handlePublishEquipment(_currentDrawerEq);
        });
        // Drawer Delete button
        document.getElementById('drawer-btn-delete').addEventListener('click', function() {
            if (_currentDrawerEq) openDeleteModal(_currentDrawerEq);
        });

        // Row click → drawer
        document.querySelectorAll('.gti-ue-table tbody tr[data-eq]').forEach(function(row) {
            row.addEventListener('click', function(e) {
                if (e.target.closest('.gti-ue-action-menu') || e.target.type === 'checkbox') return;
                var eq;
                try { eq = JSON.parse(this.getAttribute('data-eq')); } catch(err) { return; }
                showEquipmentDetail(eq);
            });
        });

        // Auto-populate drawer with first row
        var firstRow = document.querySelector('.gti-ue-table tbody tr[data-eq]');
        if (firstRow) {
            try { updateDrawerContent(JSON.parse(firstRow.getAttribute('data-eq'))); } catch(e) {}
        }

        // Edit modal image upload handlers
        initEditImageUploads();

        // Edit modal submit button — bind to click
        var editSubmitBtn = document.getElementById('gti-edit-submit');
        if (editSubmitBtn) {
            editSubmitBtn.addEventListener('click', handleEditSubmit);
        }

        // Delete confirm button
        document.getElementById('gti-delete-confirm-btn').addEventListener('click', handleDeleteConfirm);
    });

    var _currentDrawerEq = null;
    function updateDrawerContent(eq) {
        _currentDrawerEq = eq;
        // Header
        document.getElementById('drawer-eq-code').textContent = eq.equipment_code || '-';
        var badge = document.getElementById('drawer-status-badge');
        var statusMap = { 'available':'Available', 'sold':'Sold', 'reserved':'Reserved', 'rented':'Rented', 'maintenance':'Maintenance', 'draft':'Draft' };
        var sl = '';
        var isPriceValid = true;
        if (eq.price_valid_until) {
            var validDate = new Date(eq.price_valid_until);
            var today = new Date();
            today.setHours(0, 0, 0, 0);
            isPriceValid = validDate >= today;
        }
        if (!isPriceValid) {
            sl = 'Price No Longer Valid';
            badge.textContent = sl;
            badge.className = 'gti-drawer-status status-price-no-longer-valid';
        } else {
            sl = statusMap[eq.status] || eq.status || 'Available';
            badge.textContent = sl;
            badge.className = 'gti-drawer-status status-' + (eq.status || 'available');
        }
        // === Section 1: Info ===
        setText('drawer-name', eq.name);
        setText('drawer-eq-code-info', eq.equipment_code);
        setText('drawer-category', eq.category);
        setText('drawer-brand', eq.brand);
        setText('drawer-model', eq.model);
        setText('drawer-year', eq.year);
        setText('drawer-hours', eq.hours ? number_format(eq.hours) + ' Hours' : null);
        setText('drawer-serial-number', eq.serial_number);
        setText('drawer-condition', eq.condition_status);
        setText('drawer-status-text', sl);
        setText('drawer-engine', eq.engine);
        setText('drawer-engine-power', eq.engine_power);
        setText('drawer-origin', eq.origin_country);
        setText('drawer-type', eq.type);
        setText('drawer-operating-weight', eq.operating_weight);
        setText('drawer-bucket-capacity', eq.bucket_capacity);
        setText('drawer-location', eq.location);
        setText('drawer-stock-number', eq.stock_number);
        setText('drawer-description', eq.description);
        // === Section 2: Specs + Features ===
        var specsHtml = '';
        var specs = null;
        if (eq.specifications) { try { specs = typeof eq.specifications === 'string' ? JSON.parse(eq.specifications) : eq.specifications; } catch(e){} }
        if (specs && typeof specs === 'object') {
            for (var key in specs) { if (specs.hasOwnProperty(key)) specsHtml += '<div class="gti-drawer-row"><span class="gti-drawer-label">' + key.charAt(0).toUpperCase() + key.slice(1).replace(/_/g,' ') + '</span><span class="gti-drawer-value">' + specs[key] + '</span></div>'; }
        }
        document.getElementById('drawer-specs-list').innerHTML = specsHtml || '<div class="gti-drawer-row"><span class="gti-drawer-value" style="text-align:center;color:#9ca3af;font-weight:400;">No specifications available</span></div>';
        // Features
        var featuresHtml = '';
        var features = null;
        if (eq.features) { try { features = typeof eq.features === 'string' ? JSON.parse(eq.features) : eq.features; } catch(e){} }
        var featureLabels = { air_conditioner:'Air Conditioner', backup_alarm:'Backup Alarm', led_work_light:'LED Work Light', camera:'Camera', auto_idle:'Auto Idle', hammer_line:'Hammer Line', quick_coupler:'Quick Coupler', gps_system:'GPS System', centralized_greasing:'Centralized Greasing' };
        if (features && typeof features === 'object') {
            for (var fk in features) { if (features[fk]) featuresHtml += '<span class="gti-drawer-feature-tag"><i class="fas fa-check-circle"></i> ' + (featureLabels[fk] || fk.replace(/_/g,' ')) + '</span>'; }
        }
        document.getElementById('drawer-features-list').innerHTML = featuresHtml || '<span class="gti-drawer-value" style="text-align:center;color:#9ca3af;font-weight:400;width:100%;display:block;">No features</span>';
        // === Section 3: Pricing & Availability ===
        setText('drawer-selling-price', eq.selling_price ? formatRupiah(eq.selling_price) : null);
        setText('drawer-rental-price', eq.rental_price ? formatRupiah(eq.rental_price) + '/Month' : null);
        var priceTypeMap = { 'sale':'For Sale', 'rental':'For Rental', 'sale_and_rental':'Sale & Rental', 'price_on_ask':'Price on Ask' };
        setText('drawer-price-type', priceTypeMap[eq.price_type] || eq.price_type);
        setText('drawer-vat', eq.vat_included ? eq.vat_included.charAt(0).toUpperCase() + eq.vat_included.slice(1) : null);
        setText('drawer-currency', eq.currency);
        setText('drawer-price-valid-until', eq.price_valid_until ? formatDateShort(eq.price_valid_until) : null);
        setText('drawer-negotiable', eq.negotiable ? eq.negotiable.charAt(0).toUpperCase() + eq.negotiable.slice(1) : null);
        setText('drawer-availability-status', eq.availability_status ? eq.availability_status.charAt(0).toUpperCase() + eq.availability_status.slice(1).replace(/_/g,' ') : null);
        setText('drawer-ready-to-use', eq.ready_to_use ? eq.ready_to_use.charAt(0).toUpperCase() + eq.ready_to_use.slice(1).replace(/_/g,' ') : null);
        var shMap = { 'full':'Full Service History', 'partial':'Partial Service History', 'none':'No Service History' };
        setText('drawer-service-history', shMap[eq.service_history] || eq.service_history);
        setText('drawer-warranty-avail', eq.warranty_available ? eq.warranty_available.charAt(0).toUpperCase() + eq.warranty_available.slice(1) : null);
        setText('drawer-warranty-period', eq.warranty_period);
        setText('drawer-buyer-notes', eq.buyer_notes);
        // === Section 4: Media ===
        var mainImgUrl = eq.main_image || '';
        var mainImgEl = document.getElementById('drawer-main-image');
        if (mainImgUrl) {
            mainImgEl.innerHTML = '<img src="' + mainImgUrl + '" alt="Main" style="width:100%;border-radius:8px;border:1px solid #e5e7eb;">';
        } else {
            mainImgEl.innerHTML = '<span class="gti-drawer-value" style="text-align:center;color:#9ca3af;font-weight:400;">No image</span>';
        }
        var galleryUrls = [];
        if (eq.images) { try { var parsed = typeof eq.images === 'string' ? JSON.parse(eq.images) : eq.images; if (Array.isArray(parsed)) galleryUrls = parsed; } catch(e){} }
        var galleryEl = document.getElementById('drawer-gallery');
        if (galleryUrls.length > 0) {
            galleryEl.innerHTML = galleryUrls.map(function(src){ return '<img src="' + src + '" alt="Gallery" style="width:56px;height:56px;object-fit:cover;border-radius:6px;border:1px solid #e5e7eb;">'; }).join('');
        } else {
            galleryEl.innerHTML = '<span class="gti-drawer-value" style="text-align:center;color:#9ca3af;font-weight:400;">No images</span>';
        }
        // Video
        var videoEl = document.getElementById('drawer-video');
        if (eq.video_url) {
            videoEl.innerHTML = '<div class="gti-drawer-video-thumb"><i class="fas fa-play-circle"></i><div><span>Equipment Video</span><small><a href="' + eq.video_url + '" target="_blank" style="color:#2563eb;text-decoration:none;">Open video →</a></small></div></div>';
        } else if (eq.video_file) {
            videoEl.innerHTML = '<div class="gti-drawer-video-thumb"><i class="fas fa-play-circle"></i><div><span>' + (eq.video_file_name || 'Equipment Video') + '</span><small>Uploaded video</small></div></div>';
        } else {
            videoEl.innerHTML = '<span class="gti-drawer-value" style="text-align:center;color:#9ca3af;font-weight:400;">No video</span>';
        }
        // === Section 5: Additional ===
        setText('drawer-previous-usage', eq.previous_usage);
        setText('drawer-working-condition', eq.working_condition ? eq.working_condition.charAt(0).toUpperCase() + eq.working_condition.slice(1) : null);
        var mrMap = { 'full':'Full Record', 'partial':'Partial Record', 'none':'No Record' };
        setText('drawer-maintenance-record', mrMap[eq.maintenance_record] || eq.maintenance_record);
        var ownMap = { 'first_owner':'First Owner', 'second_owner':'Second Owner', 'third_plus':'Third Owner or More', 'company':'Company Fleet' };
        setText('drawer-ownership', ownMap[eq.ownership] || eq.ownership);
        setText('drawer-operator-hours', eq.operator_hours ? number_format(eq.operator_hours) + ' Hours' : null);
        setText('drawer-last-service', eq.last_service_date ? formatDateShort(eq.last_service_date) : null);
        setText('drawer-equipment-history', eq.equipment_history);
        setText('drawer-detailed-description', eq.detailed_description);
        // Documents
        var docsHtml = '';
        var docs = null;
        if (eq.documents) { try { docs = typeof eq.documents === 'string' ? JSON.parse(eq.documents) : eq.documents; } catch(e){} }
        var docLabels = { unit_certificate:'Unit Certificate (STNK/BPKB)', import_document:'Import Document', service_maintenance_record:'Service & Maintenance Record', customs_document:'Customs Document', warranty_book:'Warranty Book' };
        var docOrder = ['unit_certificate','import_document','service_maintenance_record','customs_document','warranty_book'];
        if (docs && typeof docs === 'object') {
            docOrder.forEach(function(dk) {
                var available = docs[dk];
                docsHtml += '<div class="gti-drawer-doc-item' + (available ? '' : ' missing') + '"><i class="fas ' + (available ? 'fa-check-circle' : 'fa-times-circle') + '"></i><span>' + (docLabels[dk] || dk.replace(/_/g,' ')) + '</span></div>';
            });
        } else {
            docOrder.forEach(function(dk) {
                docsHtml += '<div class="gti-drawer-doc-item missing"><i class="fas fa-times-circle"></i><span>' + (docLabels[dk] || dk.replace(/_/g,' ')) + '</span></div>';
            });
        }
        document.getElementById('drawer-documents-list').innerHTML = docsHtml;
        // Location details
        setText('drawer-location-country', eq.location_country);
        setText('drawer-location-province', eq.location_province);
        setText('drawer-location-city', eq.location_city);
        setText('drawer-address', eq.detailed_address);
        if (eq.map_location) {
            document.getElementById('drawer-map-location').innerHTML = '<a href="' + eq.map_location + '" target="_blank" class="gti-drawer-value is-link" style="text-decoration:none;">Open in Maps →</a>';
        } else {
            setText('drawer-map-location', null);
        }
        setText('drawer-location-notes', eq.location_notes);
        // System
        setText('drawer-created', formatDate(eq.created_at));
        setText('drawer-updated', formatDate(eq.updated_at));
        setText('drawer-notes', eq.notes);
        // Highlight active row
        document.querySelectorAll('.gti-ue-table tbody tr').forEach(function(r) { r.classList.remove('active-row'); });
        var activeRow = document.querySelector('.gti-ue-table tbody tr[data-eq-id="' + eq.id + '"]');
        if (activeRow) activeRow.classList.add('active-row');
        // Show/hide Publish button based on status
        var publishBtn = document.getElementById('drawer-btn-publish');
        if (publishBtn) {
            publishBtn.style.display = (eq.status === 'draft') ? '' : 'none';
        }
    }
    function setText(id, val) {
        var el = document.getElementById(id);
        if (el) el.textContent = val || '-';
    }
    function formatDateShort(dateStr) {
        if (!dateStr) return '-';
        var d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        return d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
    }
    function showEquipmentDetail(eq) {
        updateDrawerContent(eq);
        if (window.innerWidth < 1600) {
            document.getElementById('eqDetailDrawer').classList.add('open');
            document.getElementById('drawerBackdrop').classList.add('show');
            document.body.style.overflow = 'hidden';
        }
        document.querySelectorAll('.gti-ue-action-dropdown.show').forEach(function(d) { d.classList.remove('show'); });
    }
    function closeDetailDrawer() {
        document.getElementById('eqDetailDrawer').classList.remove('open');
        document.getElementById('drawerBackdrop').classList.remove('show');
        document.body.style.overflow = '';
        var steps = document.querySelectorAll('#eqDetailDrawer .gti-drawer-step');
        var lines = document.querySelectorAll('#eqDetailDrawer .gti-drawer-step-line');
        steps.forEach(function(s,i){ s.classList.remove('active','completed'); if(i===0) s.classList.add('active'); });
        lines.forEach(function(l){ l.classList.remove('active'); });
        document.querySelectorAll('#eqDetailDrawer .gti-drawer-section[data-section]').forEach(function(s){ s.classList.remove('active'); });
        var first = document.querySelector('#eqDetailDrawer .gti-drawer-section[data-section="info"]');
        if(first) first.classList.add('active');
        _currentDrawerEq = null;
    }
    function switchDrawerStep(step) {
        var section = step.getAttribute('data-section');
        var steps = document.querySelectorAll('#eqDetailDrawer .gti-drawer-step');
        var lines = document.querySelectorAll('#eqDetailDrawer .gti-drawer-step-line');
        var clickedIdx = Array.prototype.indexOf.call(steps, step);
        steps.forEach(function(s, i) {
            s.classList.remove('active','completed');
            if (i < clickedIdx) s.classList.add('completed');
            else if (i === clickedIdx) s.classList.add('active');
        });
        lines.forEach(function(l, i) {
            l.classList.toggle('active', i < clickedIdx);
        });
        document.querySelectorAll('#eqDetailDrawer .gti-drawer-section[data-section]').forEach(function(s) { s.classList.remove('active'); });
        var target = document.querySelector('#eqDetailDrawer .gti-drawer-section[data-section="' + section + '"]');
        if (target) target.classList.add('active');
    }
    function formatRupiah(val) {
        if (!val) return '-';
        return 'Rp ' + Number(val).toLocaleString('id-ID');
    }
    function number_format(val) {
        return Number(val).toLocaleString('id-ID');
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

    // ================================================================
    //  EDIT MODAL — Fullscreen popup with multi-step form
    // ================================================================
    var _editCurrentStep = 1;
    var _editTotalSteps = 5;
    var _editCurrentEq = null;

    function openEditModal(eq) {
        _editCurrentEq = eq;
        _editCurrentStep = 1;
        var overlay = document.getElementById('gtiEditOverlay');
        overlay.classList.add('show');
        document.body.style.overflow = 'hidden';

        // Set hidden id
        document.getElementById('edit-field-id').value = eq.id;

        // Reset stepper
        var steps = overlay.querySelectorAll('.gti-ae-step');
        var lines = overlay.querySelectorAll('.gti-ae-step-line');
        var contents = overlay.querySelectorAll('.gti-ae-step-content');
        steps.forEach(function(s) { s.classList.remove('active', 'completed'); });
        lines.forEach(function(l) { l.classList.remove('active'); });
        contents.forEach(function(c) { c.classList.remove('active'); });
        steps[0].classList.add('active');
        contents[0].classList.add('active');

        // Populate form fields
        var form = document.getElementById('gti-edit-form');
        populateField(form, 'name', eq.name);
        populateField(form, 'equipment_code', eq.equipment_code);
        populateField(form, 'category', eq.category);
        populateField(form, 'brand', eq.brand);
        populateField(form, 'model', eq.model);
        populateField(form, 'year', eq.year);
        populateField(form, 'hours', eq.hours);
        populateField(form, 'serial_number', eq.serial_number);
        populateField(form, 'condition_status', eq.condition_status);
        populateField(form, 'engine', eq.engine);
        populateField(form, 'engine_power', eq.engine_power);
        populateField(form, 'origin_country', eq.origin_country);
        populateField(form, 'type', eq.type);
        populateField(form, 'operating_weight', eq.operating_weight);
        populateField(form, 'bucket_capacity', eq.bucket_capacity);
        populateField(form, 'location', eq.location);
        populateField(form, 'status', eq.status);
        populateField(form, 'stock_number', eq.stock_number);
        populateField(form, 'description', eq.description);
        // Step 2 — Specs
        var specs = parseJson(eq.specifications);
        if (specs) {
            for (var k in specs) { if (specs.hasOwnProperty(k)) populateField(form, k, specs[k]); }
        }
        // Step 2 — Features
        var features = parseJson(eq.features);
        form.querySelectorAll('input[name^="features"]').forEach(function(cb) {
            var key = cb.name.replace('features[', '').replace(']', '');
            cb.checked = !!(features && features[key]);
        });
        // Step 3 — Pricing
        populateField(form, 'selling_price', eq.selling_price ? Number(eq.selling_price).toLocaleString('id-ID') : '');
        populateField(form, 'rental_price', eq.rental_price ? Number(eq.rental_price).toLocaleString('id-ID') : '');
        populateField(form, 'price_type', eq.price_type);
        populateField(form, 'vat_included', eq.vat_included);
        populateField(form, 'currency', eq.currency || 'IDR');
        populateField(form, 'price_valid_until', eq.price_valid_until);
        populateField(form, 'negotiable', eq.negotiable);
        // Step 3 — Availability
        populateField(form, 'availability_status', eq.availability_status);
        populateField(form, 'ready_to_use', eq.ready_to_use);
        populateField(form, 'service_history', eq.service_history);
        populateField(form, 'warranty_available', eq.warranty_available);
        populateField(form, 'warranty_period', eq.warranty_period);
        populateField(form, 'buyer_notes', eq.buyer_notes);
        // Step 4 — Images (show current main image preview)
        var previewEl = document.getElementById('gti-edit-upload-preview');
        var placeholderEl = document.getElementById('gti-edit-upload-placeholder');
        var previewImg = document.getElementById('gti-edit-preview-img');
        if (eq.main_image) {
            previewImg.src = eq.main_image;
            placeholderEl.style.display = 'none';
            previewEl.style.display = '';
        } else {
            previewImg.src = '';
            placeholderEl.style.display = '';
            previewEl.style.display = 'none';
        }
        // Gallery preview
        var galleryPreviewEl = document.getElementById('gti-edit-gallery-preview');
        galleryPreviewEl.innerHTML = '';
        var images = parseJson(eq.images);
        if (images && Array.isArray(images)) {
            images.forEach(function(src) {
                var item = document.createElement('div');
                item.className = 'gti-ae-gallery-item';
                item.innerHTML = '<img src="' + src + '" alt="Gallery"><button type="button" class="gti-ae-remove-img" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>';
                galleryPreviewEl.appendChild(item);
            });
        }
        // Step 5 — Additional info
        populateField(form, 'detailed_description', eq.detailed_description);
        populateField(form, 'equipment_history', eq.equipment_history);
        populateField(form, 'previous_usage', eq.previous_usage);
        populateField(form, 'working_condition', eq.working_condition);
        populateField(form, 'maintenance_record', eq.maintenance_record);
        populateField(form, 'ownership', eq.ownership);
        populateField(form, 'operator_hours', eq.operator_hours);
        populateField(form, 'last_service_date', eq.last_service_date);
        // Documents
        var docs = parseJson(eq.documents);
        form.querySelectorAll('input[name^="documents"]').forEach(function(cb) {
            var key = cb.name.replace('documents[', '').replace(']', '');
            cb.checked = !!(docs && docs[key]);
        });
        // Location details
        populateField(form, 'location_country', eq.location_country);
        populateField(form, 'location_province', eq.location_province);
        populateField(form, 'location_city', eq.location_city);
        populateField(form, 'detailed_address', eq.detailed_address);
        populateField(form, 'map_location', eq.map_location);
        populateField(form, 'location_notes', eq.location_notes);
    }

    function closeEditModal() {
        document.getElementById('gtiEditOverlay').classList.remove('show');
        document.body.style.overflow = '';
        _editCurrentEq = null;
    }

    function editGoStep(step) {
        if (step > _editCurrentStep + 1) return; // can only go 1 ahead
        editShowStep(step);
    }
    function editNextStep() {
        if (_editCurrentStep < _editTotalSteps) editShowStep(_editCurrentStep + 1);
    }
    function editPrevStep() {
        if (_editCurrentStep > 1) editShowStep(_editCurrentStep - 1);
    }
    function editShowStep(step) {
        var overlay = document.getElementById('gtiEditOverlay');
        var steps = overlay.querySelectorAll('.gti-ae-step');
        var lines = overlay.querySelectorAll('.gti-ae-step-line');
        var contents = overlay.querySelectorAll('.gti-ae-step-content');
        steps.forEach(function(s, i) {
            s.classList.remove('active', 'completed');
            if (i + 1 < step) s.classList.add('completed');
            else if (i + 1 === step) s.classList.add('active');
        });
        lines.forEach(function(l, i) { l.classList.toggle('active', i + 1 < step); });
        contents.forEach(function(c) { c.classList.remove('active'); });
        var target = overlay.querySelector('.gti-ae-step-content[data-step="' + step + '"]');
        if (target) target.classList.add('active');
        _editCurrentStep = step;
    }

    function handleEditSubmit(e) {
        if (e) e.preventDefault();
        var form = document.getElementById('gti-edit-form');
        var submitBtn = document.getElementById('gti-edit-submit');
        if (!form || !submitBtn) return;

        var formData = new FormData(form);
        // Ensure action and nonce are present
        if (!formData.has('action')) formData.append('action', 'gti_save_equipment');
        if (!formData.has('nonce')) formData.append('nonce', gtiAjax.nonce);

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

        fetch(gtiAjax.ajaxurl, { method: 'POST', body: formData })
            .then(function(r) {
                return r.text().then(function(text) {
                    try { return JSON.parse(text); }
                    catch(e) {
                        console.error('Save: non-JSON response', text);
                        throw new Error('Server returned non-JSON response');
                    }
                });
            })
            .then(function(data) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save"></i> Save Changes';
                if (data.success) {
                    showUeToast(data.data.message || 'Equipment updated successfully!', 'success');
                    closeEditModal();
                    setTimeout(function() { window.location.reload(); }, 1200);
                } else {
                    showUeToast(data.data.message || 'Failed to save changes', 'error');
                }
            })
            .catch(function(err) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save"></i> Save Changes';
                showUeToast('An error occurred while saving: ' + (err.message || 'Unknown error'), 'error');
            });
    }

    // ================================================================
    //  DELETE MODAL — Confirmation popup
    // ================================================================
    var _deleteCurrentEq = null;

    function openDeleteModal(eq) {
        _deleteCurrentEq = eq;
        document.getElementById('delete-eq-name').textContent = eq.name || '—';
        document.getElementById('delete-eq-code').textContent = eq.equipment_code || '—';
        var thumb = document.getElementById('delete-eq-thumb');
        if (eq.main_image) {
            thumb.innerHTML = '<img src="' + eq.main_image + '" alt="">';
        } else {
            thumb.innerHTML = '<i class="fas fa-truck"></i>';
        }
        var confirmBtn = document.getElementById('gti-delete-confirm-btn');
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = '<i class="fas fa-trash"></i> Delete';
        document.getElementById('gtiDeleteOverlay').classList.add('show');
    }

    function closeDeleteModal() {
        document.getElementById('gtiDeleteOverlay').classList.remove('show');
        _deleteCurrentEq = null;
    }

    function handleDeleteConfirm(e) {
        e.preventDefault();
        if (!_deleteCurrentEq) return;
        var confirmBtn = document.getElementById('gti-delete-confirm-btn');
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';

        var formData = new FormData();
        formData.append('action', 'gti_delete_equipment');
        formData.append('nonce', gtiAjax.nonce);
        formData.append('id', _deleteCurrentEq.id);

        fetch(gtiAjax.ajaxurl, { method: 'POST', body: formData })
            .then(function(r) {
                return r.text().then(function(text) {
                    try { return JSON.parse(text); }
                    catch(e) {
                        // If JSON parsing fails but HTTP was OK, treat as success
                        // (server likely deleted but response had extra output)
                        if (r.ok) return { success: true, data: { message: 'Equipment deleted' } };
                        throw e;
                    }
                });
            })
            .then(function(data) {
                if (data.success) {
                    showUeToast(data.data.message || 'Equipment deleted', 'success');
                    // Capture id BEFORE closing modal (closeDeleteModal sets _deleteCurrentEq = null)
                    var deletedId = _deleteCurrentEq ? _deleteCurrentEq.id : null;
                    closeDeleteModal();
                    // Remove the row from table
                    if (deletedId) {
                        var row = document.querySelector('tr[data-eq-id="' + deletedId + '"]');
                        if (row) {
                            row.style.transition = 'opacity 0.3s, transform 0.3s';
                            row.style.opacity = '0';
                            row.style.transform = 'translateX(20px)';
                            setTimeout(function() { row.remove(); }, 350);
                        }
                    }
                } else {
                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = '<i class="fas fa-trash"></i> Delete';
                    showUeToast(data.data.message || 'Failed to delete', 'error');
                }
            })
            .catch(function(err) {
                console.error('Delete error:', err);
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = '<i class="fas fa-trash"></i> Delete';
                showUeToast('An error occurred while deleting', 'error');
            });
    }

    // ================================================================
    //  PUBLISH EQUIPMENT — Change draft to available
    // ================================================================
    function handlePublishEquipment(eq) {
        var btn = document.getElementById('drawer-btn-publish');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Publishing...';

        var formData = new FormData();
        formData.append('action', 'gti_publish_equipment');
        formData.append('nonce', gtiAjax.nonce);
        formData.append('id', eq.id);

        fetch(gtiAjax.ajaxurl, { method: 'POST', body: formData })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check-circle"></i> Publish';
                if (data.success) {
                    showUeToast(data.data.message || 'Equipment published', 'success');
                    setTimeout(function() { window.location.reload(); }, 1200);
                } else {
                    showUeToast(data.data.message || 'Failed to publish', 'error');
                }
            })
            .catch(function() {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check-circle"></i> Publish';
                showUeToast('An error occurred while publishing', 'error');
            });
    }

    // ================================================================
    //  EDIT MODAL — Image upload handlers
    // ================================================================
    function initEditImageUploads() {
        // Main image
        var mainUpload = document.getElementById('gti-edit-main-upload');
        var mainInput = document.getElementById('gti-edit-main-image');
        var mainPlaceholder = document.getElementById('gti-edit-upload-placeholder');
        var mainPreview = document.getElementById('gti-edit-upload-preview');
        var previewImg = document.getElementById('gti-edit-preview-img');
        var removeImg = document.getElementById('gti-edit-remove-img');
        if (mainUpload && mainInput) {
            mainUpload.addEventListener('click', function(e) {
                if (e.target.closest('.gti-ae-remove-img')) return;
                mainInput.click();
            });
            mainInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    var reader = new FileReader();
                    reader.onload = function(ev) { previewImg.src = ev.target.result; mainPlaceholder.style.display = 'none'; mainPreview.style.display = ''; };
                    reader.readAsDataURL(this.files[0]);
                }
            });
        }
        if (removeImg) {
            removeImg.addEventListener('click', function(e) {
                e.stopPropagation();
                mainInput.value = '';
                previewImg.src = '';
                mainPlaceholder.style.display = '';
                mainPreview.style.display = 'none';
            });
        }
        // Gallery
        var galleryUpload = document.getElementById('gti-edit-gallery-upload');
        var galleryInput = document.getElementById('gti-edit-gallery-images');
        var galleryPreview = document.getElementById('gti-edit-gallery-preview');
        if (galleryUpload && galleryInput) {
            galleryUpload.addEventListener('click', function() { galleryInput.click(); });
            galleryInput.addEventListener('change', function() {
                if (this.files && this.files.length) {
                    Array.from(this.files).slice(0, 10).forEach(function(file) {
                        if (!file.type.startsWith('image/')) return;
                        var reader = new FileReader();
                        reader.onload = function(ev) {
                            var item = document.createElement('div');
                            item.className = 'gti-ae-gallery-item';
                            item.innerHTML = '<img src="' + ev.target.result + '" alt="Gallery"><button type="button" class="gti-ae-remove-img" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>';
                            galleryPreview.appendChild(item);
                        };
                        reader.readAsDataURL(file);
                    });
                }
            });
        }
        // Video
        var videoUpload = document.getElementById('gti-edit-video-upload');
        var videoInput = document.getElementById('gti-edit-video-file');
        var videoPlaceholder = document.getElementById('gti-edit-video-placeholder');
        var videoPreview = document.getElementById('gti-edit-video-preview');
        var videoName = document.getElementById('gti-edit-video-name');
        var removeVideo = document.getElementById('gti-edit-remove-video');
        if (videoUpload && videoInput) {
            videoUpload.addEventListener('click', function(e) {
                if (e.target.closest('.gti-ae-remove-img')) return;
                videoInput.click();
            });
            videoInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    videoName.textContent = this.files[0].name;
                    videoPlaceholder.style.display = 'none';
                    videoPreview.style.display = '';
                }
            });
        }
        if (removeVideo) {
            removeVideo.addEventListener('click', function(e) {
                e.stopPropagation();
                videoInput.value = '';
                videoPlaceholder.style.display = '';
                videoPreview.style.display = 'none';
            });
        }
    }

    // ================================================================
    //  HELPERS
    // ================================================================
    function populateField(form, name, value) {
        var field = form.querySelector('[name="' + name + '"]');
        if (!field) return;
        if (value === null || value === undefined) value = '';
        field.value = value;
    }
    function parseJson(val) {
        if (!val) return null;
        if (typeof val === 'object') return val;
        try { return JSON.parse(val); } catch(e) { return null; }
    }
    function showUeToast(message, type) {
        var toast = document.getElementById('gtiUeToast');
        toast.className = 'gti-ue-toast ' + (type || 'success');
        toast.innerHTML = '<i class="fas ' + (type === 'error' ? 'fa-exclamation-circle' : type === 'warning' ? 'fa-exclamation-triangle' : 'fa-check-circle') + '"></i> ' + message;
        toast.classList.add('show');
        setTimeout(function() { toast.classList.remove('show'); }, 3500);
    }
    </script>
</body>
</html>
