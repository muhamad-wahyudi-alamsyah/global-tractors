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
    <link rel="stylesheet" href="<?php echo GTI_CHILD_URL; ?>/assets/css/add-equipment.css">
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

        /* Action menu overflow fix */
        .gti-ue-action-dropdown { z-index: 100; position: fixed; width: max-content; white-space: nowrap; }
        .gti-drawer-btn-delete:hover { background: #fef2f2 !important; }

        /* ====== View Button — Hide on wide screens ====== */
        @media (min-width: 1600px) {
            .gti-ue-btn-view { display: none !important; }
        }

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
        .gti-drawer-step.completed .gti-drawer-step-label { color: #10b981; }
        .gti-drawer-step-line {
            flex: 1; height: 2px; background: #e5e7eb;
            margin: 0 4px; margin-top: 14px;
            min-width: 12px; max-width: 28px;
            transition: background 0.25s ease;
        }
        .gti-drawer-step-line.active { background: #10b981; }
        .gti-drawer-section[data-section] { display: none; }
        .gti-drawer-section[data-section].active { display: block; }

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
        .gti-ue-edit-header h2 { margin: 0; font-size: 16px; font-weight: 600; color: #1a1f36; display: flex; align-items: center; gap: 8px; }
        .gti-ue-edit-header h2 i { color: #F5A623; }
        .gti-ue-edit-close {
            width: 36px; height: 36px; border-radius: 8px; border: 1px solid #e5e7eb;
            background: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center;
            color: #6b7280; font-size: 16px; transition: all 0.15s;
        }
        .gti-ue-edit-close:hover { background: #f3f4f6; color: #1a1f36; }
        .gti-ue-edit-body { flex: 1; padding: 28px; overflow-y: auto; }
        .gti-ue-edit-footer {
            display: flex; align-items: center; justify-content: flex-end; gap: 10px;
            padding: 16px 28px; border-top: 1px solid #e5e7eb; flex-shrink: 0;
            background: #fff; border-radius: 0 0 16px 16px;
        }
        .gti-ue-edit-footer .gti-ae-btn-cancel {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 10px 20px; border-radius: 8px; font-size: 13px; font-weight: 500;
            border: 1px solid #e5e7eb; background: #fff; color: #374151;
            cursor: pointer; font-family: inherit; transition: all 0.15s;
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
        .gti-ue-delete-header { display: flex; align-items: center; gap: 12px; padding: 20px 24px 0; }
        .gti-ue-delete-icon {
            width: 44px; height: 44px; border-radius: 10px; flex-shrink: 0;
            background: #fef2f2; display: flex; align-items: center; justify-content: center;
            color: #dc2626; font-size: 18px;
        }
        .gti-ue-delete-header-text h3 { margin: 0; font-size: 16px; font-weight: 600; color: #1a1f36; }
        .gti-ue-delete-header-text p { margin: 4px 0 0; font-size: 13px; color: #6b7280; }
        .gti-ue-delete-body { padding: 16px 24px; }
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
        .gti-ue-delete-cancel { border: 1px solid #e5e7eb; background: #fff; color: #374151; }
        .gti-ue-delete-cancel:hover { background: #f9fafb; }
        .gti-ue-delete-confirm { border: none; background: #dc2626; color: #fff; font-weight: 600; }
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
                    <input type="hidden" name="gti_page" value="spare-parts">
                    <div class="gti-ue-toolbar-left">
                        <div class="gti-ue-search">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" placeholder="Search spare parts..." value="<?php echo esc_attr($search); ?>">
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
            <!-- Stepper Navigation -->
            <div class="gti-drawer-stepper">
                <div class="gti-drawer-step active" data-section="info" onclick="switchDrawerStep(this)">
                    <div class="gti-drawer-step-circle">1</div>
                    <div class="gti-drawer-step-label">Info</div>
                </div>
                <div class="gti-drawer-step-line"></div>
                <div class="gti-drawer-step" data-section="inventory" onclick="switchDrawerStep(this)">
                    <div class="gti-drawer-step-circle">2</div>
                    <div class="gti-drawer-step-label">Inventory</div>
                </div>
                <div class="gti-drawer-step-line"></div>
                <div class="gti-drawer-step" data-section="additional" onclick="switchDrawerStep(this)">
                    <div class="gti-drawer-step-circle">3</div>
                    <div class="gti-drawer-step-label">More</div>
                </div>
            </div>

            <div class="gti-drawer-section active" data-section="info">
                <div class="gti-drawer-section-title"><i class="fas fa-cog"></i> Part Information</div>
                <div class="gti-drawer-row"><span class="gti-drawer-label">Part Number</span><span class="gti-drawer-value" id="drawer-partnum">-</span></div>
                <div class="gti-drawer-row"><span class="gti-drawer-label">Part Name</span><span class="gti-drawer-value" id="drawer-name">-</span></div>
                <div class="gti-drawer-row"><span class="gti-drawer-label">Category</span><span class="gti-drawer-value" id="drawer-category">-</span></div>
                <div class="gti-drawer-row"><span class="gti-drawer-label">Brand</span><span class="gti-drawer-value" id="drawer-brand">-</span></div>
            </div>
            <div class="gti-drawer-section" data-section="inventory">
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
            <div class="gti-drawer-section" data-section="additional">
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
            <button type="button" class="gti-drawer-btn gti-drawer-btn-primary" id="drawer-btn-edit"><i class="fas fa-edit"></i> Edit</button>
            <span class="gti-drawer-footer-spacer"></span>
            <button type="button" class="gti-drawer-btn" id="drawer-btn-delete" style="color:#b91c1c;border-color:#fca5a5;"><i class="fas fa-trash"></i> Delete</button>
        </div>
    </div>
            </div>
        </main>
    </div>

    
    <!-- ====== Edit Spare Part Modal (Fullscreen Popup) ====== -->
    <div class="gti-ue-edit-overlay" id="gtiEditOverlay">
        <div class="gti-edit-backdrop" onclick="closeEditModal()"></div>
        <div class="gti-ue-edit-container">
            <div class="gti-ue-edit-header">
                <div class="gti-ue-edit-header-left">
                    <h2><i class="fas fa-edit"></i> Edit Spare Part</h2>
                </div>
                <button class="gti-ue-edit-close" onclick="closeEditModal()" title="Close"><i class="fas fa-times"></i></button>
            </div>
            <div class="gti-ue-edit-body">
                <form id="gti-edit-form" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="id" id="edit-field-id" value="">
                    <input type="hidden" name="action" value="gti_save_spare_part">
                    <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('gti_nonce')); ?>">

                    <div class="gti-ae-form-card">
                        <div class="gti-ae-card-header"><h3><i class="fas fa-cog"></i> Part Information</h3></div>
                        <div class="gti-ae-card-body">
                            <div class="gti-ae-form-grid">
                                <div class="gti-ae-field"><label>Part Number <span class="required">*</span></label><input type="text" name="part_number" required></div>
                                <div class="gti-ae-field"><label>Part Name <span class="required">*</span></label><input type="text" name="name" required></div>
                            </div>
                            <div class="gti-ae-form-grid">
                                <div class="gti-ae-field"><label>Category <span class="required">*</span></label><select name="category" required><option value="">Select Category</option><option value="Filters">Filters</option><option value="Undercarriage">Undercarriage</option><option value="Hydraulics">Hydraulics</option><option value="Brakes">Brakes</option><option value="Engine Parts">Engine Parts</option><option value="Electrical">Electrical</option><option value="Transmission">Transmission</option><option value="Body &amp; Frame">Body &amp; Frame</option><option value="Other">Other</option></select></div>
                                <div class="gti-ae-field"><label>Brand <span class="required">*</span></label><select name="brand" required><option value="">Select Brand</option><option value="KOMATSU">KOMATSU</option><option value="CATERPILLAR">CATERPILLAR</option><option value="HITACHI">HITACHI</option><option value="VOLVO">VOLVO</option><option value="KOBELCO">KOBELCO</option><option value="DOOSAN">DOOSAN</option><option value="HYUNDAI">HYUNDAI</option></select></div>
                            </div>
                            <div class="gti-ae-field gti-ae-field-full"><label>Description</label><textarea name="description" rows="3"></textarea></div>
                        </div>
                    </div>

                    <div class="gti-ae-form-card">
                        <div class="gti-ae-card-header"><h3><i class="fas fa-boxes"></i> Inventory &amp; Pricing</h3></div>
                        <div class="gti-ae-card-body">
                            <div class="gti-ae-form-grid">
                                <div class="gti-ae-field"><label>Current Stock</label><input type="number" name="stock" min="0"></div>
                                <div class="gti-ae-field"><label>Minimum Stock</label><input type="number" name="minimum_stock" min="0" value="10"></div>
                                <div class="gti-ae-field"><label>Unit Price (IDR)</label><input type="text" name="unit_price" inputmode="numeric"></div>
                            </div>
                            <div class="gti-ae-form-grid">
                                <div class="gti-ae-field"><label>Supplier</label><input type="text" name="supplier"></div>
                                <div class="gti-ae-field"><label>Location</label><input type="text" name="location"></div>
                                <div class="gti-ae-field"><label>Status</label><select name="status"><option value="in_stock">In Stock</option><option value="low_stock">Low Stock</option><option value="out_of_stock">Out of Stock</option></select></div>
                            </div>
                        </div>
                    </div>

                    <div class="gti-ae-form-card">
                        <div class="gti-ae-card-header"><h3><i class="fas fa-images"></i> Image</h3></div>
                        <div class="gti-ae-card-body">
                            <div class="gti-ae-field gti-ae-field-full">
                                <label>Main Image</label>
                                <div class="gti-ae-upload-area" id="gti-edit-main-upload">
                                    <input type="file" name="image" id="gti-edit-main-image" accept="image/*" style="display:none;">
                                    <div class="gti-ae-upload-placeholder" id="gti-edit-upload-placeholder"><i class="fas fa-cloud-upload-alt"></i><p>Click or drag image here to upload</p></div>
                                    <div class="gti-ae-upload-preview" id="gti-edit-upload-preview" style="display:none;"><img id="gti-edit-preview-img" src="" alt="Preview"><button type="button" class="gti-ae-remove-img" id="gti-edit-remove-img"><i class="fas fa-times"></i></button></div>
                                </div>
                            </div>
                        </div>
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
                    <h3>Delete Spare Part</h3>
                    <p>This action cannot be undone.</p>
                </div>
            </div>
            <div class="gti-ue-delete-body">
                <div class="gti-ue-delete-eq-info">
                    <div class="gti-ue-delete-eq-thumb" id="delete-sp-thumb"><i class="fas fa-cog"></i></div>
                    <div>
                        <div class="gti-ue-delete-eq-name" id="delete-sp-name">&mdash;</div>
                        <div class="gti-ue-delete-eq-code" id="delete-sp-code">&mdash;</div>
                    </div>
                </div>
                <div class="gti-ue-delete-warning">
                    <i class="fas fa-info-circle"></i>
                    <span>Spare part will be permanently removed from inventory. This data cannot be recovered.</span>
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
        ajaxurl: '<?php echo esc_js(admin_url('admin-ajax.php')); ?>',
        nonce: '<?php echo esc_js(wp_create_nonce('gti_nonce')); ?>'
    };
    document.addEventListener('DOMContentLoaded', function() {
        // Sidebar collapse
        var collapseBtn = document.getElementById('gti-collapse-btn');
        var sidebar = document.getElementById('gti-sidebar');
        var mainEl = document.getElementById('gti-main');
        if (collapseBtn && sidebar) {
            if (localStorage.getItem('gti-sidebar-collapsed') === 'true') { sidebar.classList.add('collapsed'); if (mainEl) mainEl.classList.add('collapsed'); }
            collapseBtn.addEventListener('click', function(e) { e.preventDefault(); sidebar.classList.toggle('collapsed'); if (mainEl) mainEl.classList.toggle('collapsed'); localStorage.setItem('gti-sidebar-collapsed', sidebar.classList.contains('collapsed')); });
        }
        // Sidebar dropdown toggle
        document.querySelectorAll('[data-toggle="dropdown"]').forEach(function(toggle) {
            toggle.addEventListener('click', function(e) { e.preventDefault(); var group = this.closest('.gti-has-children'); if (group) group.classList.toggle('open'); });
        });
        // Action Dropdown Toggle — fixed positioning
        document.addEventListener('click', function(e) {
            var toggle = e.target.closest('.gti-ue-action-toggle');
            if (toggle) {
                e.preventDefault(); e.stopPropagation();
                var menu = toggle.closest('.gti-ue-action-menu');
                var dropdown = menu.querySelector('.gti-ue-action-dropdown');
                var isOpen = dropdown.classList.contains('show');
                document.querySelectorAll('.gti-ue-action-dropdown.show').forEach(function(d) { d.classList.remove('show'); });
                if (!isOpen) {
                    var rect = toggle.getBoundingClientRect();
                    var ddWidth = 160; var ddHeight = 110;
                    var top = rect.bottom + 4; var left = rect.right - ddWidth;
                    if (top + ddHeight > window.innerHeight) top = rect.top - ddHeight - 4;
                    if (left < 8) left = 8;
                    dropdown.style.top = top + 'px'; dropdown.style.left = left + 'px';
                    dropdown.classList.add('show');
                }
                return;
            }
            if (!e.target.closest('.gti-ue-action-menu')) { document.querySelectorAll('.gti-ue-action-dropdown.show').forEach(function(d) { d.classList.remove('show'); }); }
        });
        // VIEW BUTTON
        document.querySelectorAll('.gti-ue-btn-view').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault(); e.stopPropagation();
                var row = btn.closest('tr[data-sp]');
                if (!row) return;
                var sp; try { sp = JSON.parse(row.getAttribute('data-sp')); } catch(err) { return; }
                document.querySelectorAll('.gti-ue-action-dropdown.show').forEach(function(d) { d.classList.remove('show'); });
                showSparePartDetail(sp);
            });
        });
        // EDIT BUTTON
        document.querySelectorAll('.gti-ue-btn-edit').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault(); e.stopPropagation();
                var row = btn.closest('tr[data-sp]');
                if (!row) return;
                var sp; try { sp = JSON.parse(row.getAttribute('data-sp')); } catch(err) { return; }
                document.querySelectorAll('.gti-ue-action-dropdown.show').forEach(function(d) { d.classList.remove('show'); });
                openEditModal(sp);
            });
        });
        // DELETE BUTTON
        document.querySelectorAll('.gti-ue-btn-delete').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault(); e.stopPropagation();
                var row = btn.closest('tr[data-sp]');
                if (!row) return;
                var sp; try { sp = JSON.parse(row.getAttribute('data-sp')); } catch(err) { return; }
                document.querySelectorAll('.gti-ue-action-dropdown.show').forEach(function(d) { d.classList.remove('show'); });
                openDeleteModal(sp);
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
        // Search input — real-time search with debounce
        var searchInput = document.querySelector('.gti-ue-search input[name="search"]');
        var searchTimer = null;
        if (searchInput) {
            searchInput.addEventListener('input', function() { var self = this; clearTimeout(searchTimer); searchTimer = setTimeout(function() { self.form.submit(); }, 400); });
            if (searchInput.value) { searchInput.focus(); var len = searchInput.value.length; searchInput.setSelectionRange(len, len); }
        }
        // Drawer Edit button
        document.getElementById('drawer-btn-edit').addEventListener('click', function() {
            if (_currentDrawerSp) openEditModal(_currentDrawerSp);
        });
        // Drawer Delete button
        document.getElementById('drawer-btn-delete').addEventListener('click', function() {
            if (_currentDrawerSp) openDeleteModal(_currentDrawerSp);
        });
        // Row click → drawer
        document.querySelectorAll('.gti-ue-table tbody tr[data-sp]').forEach(function(row) {
            row.addEventListener('click', function(e) {
                if (e.target.closest('.gti-ue-action-menu') || e.target.type === 'checkbox') return;
                var sp; try { sp = JSON.parse(this.getAttribute('data-sp')); } catch(err) { return; }
                showSparePartDetail(sp);
            });
        });
        // Auto-populate drawer with first row
        var firstRow = document.querySelector('.gti-ue-table tbody tr[data-sp]');
        if (firstRow) { try { updateDrawerContent(JSON.parse(firstRow.getAttribute('data-sp'))); } catch(e) {} }
        // Edit modal image upload
        initEditImageUploads();
        // Edit modal submit
        var editSubmitBtn = document.getElementById('gti-edit-submit');
        if (editSubmitBtn) editSubmitBtn.addEventListener('click', handleEditSubmit);
        // Delete confirm
        document.getElementById('gti-delete-confirm-btn').addEventListener('click', handleDeleteConfirm);
    });

    var _currentDrawerSp = null;
    function setText(id, val) { var el = document.getElementById(id); if (el) el.textContent = val || '-'; }
    function updateDrawerContent(sp) {
        _currentDrawerSp = sp;
        setText('drawer-part-number', sp.part_number);
        setText('drawer-partnum', sp.part_number);
        var badge = document.getElementById('drawer-status-badge');
        var statusMap = { 'in_stock':'In Stock', 'low_stock':'Low Stock', 'out_of_stock':'Out of Stock' };
        var sl = statusMap[sp.status] || sp.status || 'In Stock';
        badge.textContent = sl; badge.className = 'gti-drawer-status status-' + (sp.status || 'in_stock');
        setText('drawer-name', sp.name);
        setText('drawer-category', sp.category);
        setText('drawer-brand', sp.brand);
        setText('drawer-stock', sp.stock || '0');
        setText('drawer-min-stock', sp.minimum_stock || '10');
        setText('drawer-status-text', sl);
        setText('drawer-unit-price', formatRupiah(sp.unit_price));
        setText('drawer-total-value', formatRupiah((sp.stock || 0) * (sp.unit_price || 0)));
        setText('drawer-supplier', sp.supplier);
        setText('drawer-location', sp.location);
        setText('drawer-description', sp.description);
        setText('drawer-created', formatDate(sp.created_at));
        setText('drawer-updated', formatDate(sp.updated_at));
        // Highlight active row
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
        // Reset stepper
        var steps = document.querySelectorAll('#spDetailDrawer .gti-drawer-step');
        var lines = document.querySelectorAll('#spDetailDrawer .gti-drawer-step-line');
        steps.forEach(function(s,i){ s.classList.remove('active','completed'); if(i===0) s.classList.add('active'); });
        lines.forEach(function(l){ l.classList.remove('active'); });
        document.querySelectorAll('#spDetailDrawer .gti-drawer-section[data-section]').forEach(function(s){ s.classList.remove('active'); });
        var first = document.querySelector('#spDetailDrawer .gti-drawer-section[data-section="info"]');
        if(first) first.classList.add('active');
        _currentDrawerSp = null;
    }
    function switchDrawerStep(step) {
        var section = step.getAttribute('data-section');
        var steps = document.querySelectorAll('#spDetailDrawer .gti-drawer-step');
        var lines = document.querySelectorAll('#spDetailDrawer .gti-drawer-step-line');
        var clickedIdx = Array.prototype.indexOf.call(steps, step);
        steps.forEach(function(s, i) { s.classList.remove('active','completed'); if (i < clickedIdx) s.classList.add('completed'); else if (i === clickedIdx) s.classList.add('active'); });
        lines.forEach(function(l, i) { l.classList.toggle('active', i < clickedIdx); });
        document.querySelectorAll('#spDetailDrawer .gti-drawer-section[data-section]').forEach(function(s) { s.classList.remove('active'); });
        var target = document.querySelector('#spDetailDrawer .gti-drawer-section[data-section="' + section + '"]');
        if (target) target.classList.add('active');
    }
    function formatRupiah(val) { if (!val) return '-'; return 'Rp ' + Number(val).toLocaleString('id-ID'); }
    function formatDate(dateStr) {
        if (!dateStr) return '-';
        var d = new Date(dateStr); if (isNaN(d.getTime())) return dateStr;
        var months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        var day = d.getDate(); var hours = d.getHours(); var minutes = String(d.getMinutes()).padStart(2, '0');
        var ampm = hours >= 12 ? 'PM' : 'AM'; hours = hours % 12 || 12;
        return day + ' ' + months[d.getMonth()] + ' ' + d.getFullYear() + ', ' + hours + ':' + minutes + ' ' + ampm;
    }
    function populateField(form, name, value) {
        var field = form.querySelector('[name="' + name + '"]');
        if (!field) return; if (value === null || value === undefined) value = ''; field.value = value;
    }
    function parseJson(val) {
        if (!val) return null; if (typeof val === 'object') return val;
        try { return JSON.parse(val); } catch(e) { return null; }
    }
    function showUeToast(message, type) {
        var toast = document.getElementById('gtiUeToast');
        toast.className = 'gti-ue-toast ' + (type || 'success');
        toast.innerHTML = '<i class="fas ' + (type === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle') + '"></i> ' + message;
        toast.classList.add('show'); setTimeout(function() { toast.classList.remove('show'); }, 3500);
    }
    // ====== EDIT MODAL ======
    var _editCurrentEq = null;
    function openEditModal(sp) {
        _editCurrentEq = sp;
        var overlay = document.getElementById('gtiEditOverlay');
        overlay.classList.add('show'); document.body.style.overflow = 'hidden';
        document.getElementById('edit-field-id').value = sp.id;
        var form = document.getElementById('gti-edit-form');
        populateField(form, 'part_number', sp.part_number);
        populateField(form, 'name', sp.name);
        populateField(form, 'category', sp.category);
        populateField(form, 'brand', sp.brand);
        populateField(form, 'description', sp.description);
        populateField(form, 'stock', sp.stock);
        populateField(form, 'minimum_stock', sp.minimum_stock);
        populateField(form, 'unit_price', sp.unit_price ? Number(sp.unit_price).toLocaleString('id-ID') : '');
        populateField(form, 'supplier', sp.supplier);
        populateField(form, 'location', sp.location);
        populateField(form, 'status', sp.status);
        // Image preview
        var previewEl = document.getElementById('gti-edit-upload-preview');
        var placeholderEl = document.getElementById('gti-edit-upload-placeholder');
        var previewImg = document.getElementById('gti-edit-preview-img');
        if (sp.image) { previewImg.src = sp.image; placeholderEl.style.display = 'none'; previewEl.style.display = ''; }
        else { previewImg.src = ''; placeholderEl.style.display = ''; previewEl.style.display = 'none'; }
    }
    function closeEditModal() {
        document.getElementById('gtiEditOverlay').classList.remove('show');
        document.body.style.overflow = ''; _editCurrentEq = null;
    }
    function handleEditSubmit(e) {
        if (e) e.preventDefault();
        var form = document.getElementById('gti-edit-form');
        var submitBtn = document.getElementById('gti-edit-submit');
        if (!form || !submitBtn) return;
        var formData = new FormData(form);
        if (!formData.has('action')) formData.append('action', 'gti_save_spare_part');
        if (!formData.has('nonce')) formData.append('nonce', gtiAjax.nonce);
        submitBtn.disabled = true; submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        fetch(gtiAjax.ajaxurl, { method: 'POST', body: formData })
            .then(function(r) { return r.text().then(function(text) { try { return JSON.parse(text); } catch(e) { throw new Error('Server returned non-JSON response'); } }); })
            .then(function(data) {
                submitBtn.disabled = false; submitBtn.innerHTML = '<i class="fas fa-save"></i> Save Changes';
                if (data.success) { showUeToast(data.data.message || 'Spare part updated!', 'success'); closeEditModal(); setTimeout(function() { window.location.reload(); }, 1200); }
                else { showUeToast(data.data.message || 'Failed to save', 'error'); }
            })
            .catch(function(err) {
                submitBtn.disabled = false; submitBtn.innerHTML = '<i class="fas fa-save"></i> Save Changes';
                showUeToast('An error occurred: ' + (err.message || 'Unknown'), 'error');
            });
    }
    // ====== DELETE MODAL ======
    var _deleteCurrentSp = null;
    function openDeleteModal(sp) {
        _deleteCurrentSp = sp;
        document.getElementById('delete-sp-name').textContent = sp.name || '\u2014';
        document.getElementById('delete-sp-code').textContent = sp.part_number || '\u2014';
        var thumb = document.getElementById('delete-sp-thumb');
        if (sp.image) { thumb.innerHTML = '<img src="' + sp.image + '" alt="">'; }
        else { thumb.innerHTML = '<i class="fas fa-cog"></i>'; }
        var confirmBtn = document.getElementById('gti-delete-confirm-btn');
        confirmBtn.disabled = false; confirmBtn.innerHTML = '<i class="fas fa-trash"></i> Delete';
        document.getElementById('gtiDeleteOverlay').classList.add('show');
    }
    function closeDeleteModal() { document.getElementById('gtiDeleteOverlay').classList.remove('show'); _deleteCurrentSp = null; }
    function handleDeleteConfirm(e) {
        e.preventDefault(); if (!_deleteCurrentSp) return;
        var confirmBtn = document.getElementById('gti-delete-confirm-btn');
        confirmBtn.disabled = true; confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';
        var formData = new FormData();
        formData.append('action', 'gti_delete_spare_part');
        formData.append('nonce', gtiAjax.nonce);
        formData.append('id', _deleteCurrentSp.id);
        fetch(gtiAjax.ajaxurl, { method: 'POST', body: formData })
            .then(function(r) { return r.text().then(function(text) { try { return JSON.parse(text); } catch(e) { if (r.ok) return { success: true, data: { message: 'Spare part deleted' } }; throw e; } }); })
            .then(function(data) {
                if (data.success) {
                    showUeToast(data.data.message || 'Spare part deleted', 'success');
                    var deletedId = _deleteCurrentSp ? _deleteCurrentSp.id : null;
                    closeDeleteModal();
                    if (deletedId) { var row = document.querySelector('tr[data-sp-id="' + deletedId + '"]'); if (row) { row.style.transition = 'opacity 0.3s, transform 0.3s'; row.style.opacity = '0'; row.style.transform = 'translateX(20px)'; setTimeout(function() { row.remove(); }, 350); } }
                } else { confirmBtn.disabled = false; confirmBtn.innerHTML = '<i class="fas fa-trash"></i> Delete'; showUeToast(data.data.message || 'Failed to delete', 'error'); }
            })
            .catch(function() { confirmBtn.disabled = false; confirmBtn.innerHTML = '<i class="fas fa-trash"></i> Delete'; showUeToast('An error occurred while deleting', 'error'); });
    }
    // ====== IMAGE UPLOADS ======
    function initEditImageUploads() {
        var mainUpload = document.getElementById('gti-edit-main-upload');
        var mainInput = document.getElementById('gti-edit-main-image');
        var mainPlaceholder = document.getElementById('gti-edit-upload-placeholder');
        var mainPreview = document.getElementById('gti-edit-upload-preview');
        var previewImg = document.getElementById('gti-edit-preview-img');
        var removeImg = document.getElementById('gti-edit-remove-img');
        if (mainUpload && mainInput) {
            mainUpload.addEventListener('click', function(e) { if (e.target.closest('.gti-ae-remove-img')) return; mainInput.click(); });
            mainInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    var reader = new FileReader();
                    reader.onload = function(ev) { previewImg.src = ev.target.result; mainPlaceholder.style.display = 'none'; mainPreview.style.display = ''; };
                    reader.readAsDataURL(this.files[0]);
                }
            });
        }
        if (removeImg) { removeImg.addEventListener('click', function(e) { e.stopPropagation(); mainInput.value = ''; previewImg.src = ''; mainPlaceholder.style.display = ''; mainPreview.style.display = 'none'; }); }
    }
    </script>
</body>
</html>
