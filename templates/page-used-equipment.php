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
$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
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

// Dummy data fallback when DB is empty
if (empty($equipments)) {
    $dummy_specs = '{"Engine":"Diesel Turbocharged","Operating Weight":"20,700 kg","Bucket Capacity":"1.0 m\u00b3","Max Digging Depth":"6,520 mm","Engine Power":"110 kW (150 HP)","Fuel Tank Capacity":"410 L"}';
    $equipments = array();

    $d = new stdClass();
    $d->id = 1001; $d->equipment_code = 'UE-2024-001'; $d->name = 'KOMATSU PC200-8';
    $d->type = 'used'; $d->category = 'Excavator'; $d->brand = 'KOMATSU';
    $d->model = 'PC200-8'; $d->year = 2020; $d->condition_status = 'Excellent';
    $d->status = 'available'; $d->price = 850000000; $d->price_type = 'sale';
    $d->location = 'Jakarta, Indonesia';
    $d->description = 'Well-maintained excavator with full service history.';
    $d->specifications = $dummy_specs;
    $d->images = '["https://picsum.photos/seed/ue1-g1/200/200","https://picsum.photos/seed/ue1-g2/200/200","https://picsum.photos/seed/ue1-g3/200/200"]';
    $d->main_image = 'https://picsum.photos/seed/ue1/400/300';
    $d->hours = 5200; $d->negotiable = 1; $d->warranty_info = '6 months / 1,000 hours';
    $d->last_service_date = '2026-03-15'; $d->next_service_due = '2026-09-15';
    $d->registration_number = 'B 1234 UE'; $d->insurance_status = 'Active';
    $d->notes = 'Previous owner was a mining company.';
    $d->created_at = '2026-01-15 10:30:00'; $d->updated_at = '2026-08-20 14:22:00';
    $equipments[] = $d;

    $d = new stdClass();
    $d->id = 1002; $d->equipment_code = 'UE-2024-002'; $d->name = 'CAT 320D';
    $d->type = 'used'; $d->category = 'Excavator'; $d->brand = 'CATERPILLAR';
    $d->model = '320D'; $d->year = 2019; $d->condition_status = 'Good';
    $d->status = 'available'; $d->price = 720000000; $d->price_type = 'sale';
    $d->location = 'Surabaya, Indonesia';
    $d->description = 'Reliable Caterpillar excavator in good working condition.';
    $d->specifications = $dummy_specs;
    $d->images = '["https://picsum.photos/seed/ue2-g1/200/200","https://picsum.photos/seed/ue2-g2/200/200","https://picsum.photos/seed/ue2-g3/200/200"]';
    $d->main_image = 'https://picsum.photos/seed/ue2/400/300';
    $d->hours = 8100; $d->negotiable = 1; $d->warranty_info = '3 months / 500 hours';
    $d->last_service_date = '2026-02-20'; $d->next_service_due = '2026-08-20';
    $d->registration_number = 'B 5678 UE'; $d->insurance_status = 'Active';
    $d->notes = 'Minor cosmetic wear. Engine in excellent condition.';
    $d->created_at = '2026-02-10 09:00:00'; $d->updated_at = '2026-08-18 11:45:00';
    $equipments[] = $d;

    $d = new stdClass();
    $d->id = 1003; $d->equipment_code = 'UE-2024-003'; $d->name = 'KOMATSU D65PX-18';
    $d->type = 'used'; $d->category = 'Bulldozer'; $d->brand = 'KOMATSU';
    $d->model = 'D65PX-18'; $d->year = 2021; $d->condition_status = 'Excellent';
    $d->status = 'sold'; $d->price = 1200000000; $d->price_type = 'sale';
    $d->location = 'Bandung, Indonesia';
    $d->description = 'Late model bulldozer. Sold to construction company.';
    $d->specifications = $dummy_specs;
    $d->images = '["https://picsum.photos/seed/ue3-g1/200/200","https://picsum.photos/seed/ue3-g2/200/200","https://picsum.photos/seed/ue3-g3/200/200"]';
    $d->main_image = 'https://picsum.photos/seed/ue3/400/300';
    $d->hours = 3800; $d->negotiable = 0; $d->warranty_info = '12 months / 2,000 hours';
    $d->last_service_date = '2026-04-01'; $d->next_service_due = '2026-10-01';
    $d->registration_number = 'B 9012 UE'; $d->insurance_status = 'Active';
    $d->notes = 'Low hours, well maintained.';
    $d->created_at = '2026-03-05 08:15:00'; $d->updated_at = '2026-08-15 16:30:00';
    $equipments[] = $d;

    $d = new stdClass();
    $d->id = 1004; $d->equipment_code = 'UE-2024-004'; $d->name = 'CAT 950GC';
    $d->type = 'used'; $d->category = 'Wheel Loader'; $d->brand = 'CATERPILLAR';
    $d->model = '950GC'; $d->year = 2022; $d->condition_status = 'Good';
    $d->status = 'reserved'; $d->price = 980000000; $d->price_type = 'sale';
    $d->location = 'Medan, Indonesia';
    $d->description = 'Reserved for PT construction project.';
    $d->specifications = $dummy_specs;
    $d->images = '["https://picsum.photos/seed/ue4-g1/200/200","https://picsum.photos/seed/ue4-g2/200/200","https://picsum.photos/seed/ue4-g3/200/200"]';
    $d->main_image = 'https://picsum.photos/seed/ue4/400/300';
    $d->hours = 2500; $d->negotiable = 1; $d->warranty_info = '6 months / 1,000 hours';
    $d->last_service_date = '2026-05-10'; $d->next_service_due = '2026-11-10';
    $d->registration_number = 'B 3456 UE'; $d->insurance_status = 'Active';
    $d->notes = 'Reserved for Medan project. Delivery September 2026.';
    $d->created_at = '2026-04-12 13:45:00'; $d->updated_at = '2026-08-22 09:10:00';
    $equipments[] = $d;

    $d = new stdClass();
    $d->id = 1005; $d->equipment_code = 'UE-2024-005'; $d->name = 'HITACHI ZX210LCH-5A';
    $d->type = 'used'; $d->category = 'Excavator'; $d->brand = 'HITACHI';
    $d->model = 'ZX210LCH-5A'; $d->year = 2020; $d->condition_status = 'Fair';
    $d->status = 'available'; $d->price = 780000000; $d->price_type = 'sale';
    $d->location = 'Semarang, Indonesia';
    $d->description = 'Used excavator in fair condition. Hydraulic recently serviced.';
    $d->specifications = $dummy_specs;
    $d->images = '["https://picsum.photos/seed/ue5-g1/200/200","https://picsum.photos/seed/ue5-g2/200/200","https://picsum.photos/seed/ue5-g3/200/200"]';
    $d->main_image = 'https://picsum.photos/seed/ue5/400/300';
    $d->hours = 6400; $d->negotiable = 1; $d->warranty_info = '3 months / 500 hours';
    $d->last_service_date = '2026-01-20'; $d->next_service_due = '2026-07-20';
    $d->registration_number = 'B 7890 UE'; $d->insurance_status = 'Active';
    $d->notes = 'Fair condition exterior. Engine runs smoothly.';
    $d->created_at = '2026-05-01 10:00:00'; $d->updated_at = '2026-08-25 08:30:00';
    $equipments[] = $d;

    $total_equipment = count($equipments);
}

// Format currency inline to avoid redeclaration errors
$_gti_eq_fmt = function($amount) {
    return 'Rp ' . number_format((float)$amount, 0, ',', '.');
};

// Status badge helper
$_gti_eq_status_badge = function($status) {
    $map = array(
        'available' => array('class' => 'available', 'label' => 'Available'),
        'sold' => array('class' => 'sold', 'label' => 'Sold'),
        'reserved' => array('class' => 'reserved', 'label' => 'Reserved'),
        'rented' => array('class' => 'sold', 'label' => 'Rented'),
        'maintenance' => array('class' => 'reserved', 'label' => 'Maintenance'),
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
    <style>
        /* Border overrides to match request-equipment style */
        .gti-ue-stat-card { border: 1px solid #e5e7eb; }
        .gti-ue-search { border: 1px solid #d1d5db; }
        .gti-ue-filter select { border: 1px solid #d1d5db; }
        .gti-ue-btn-reset { border: 1px solid #d1d5db; }
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
        .gti-drawer-status.status-available { background: #d1fae5; color: #047857; }
        .gti-drawer-status.status-sold { background: #fee2e2; color: #b91c1c; }
        .gti-drawer-status.status-reserved { background: #fef3c7; color: #b45309; }
        .gti-drawer-status.status-rented { background: #fee2e2; color: #b91c1c; }
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
                                <option value="available" <?php selected($status_filter, 'available'); ?>>Available</option>
                                <option value="sold" <?php selected($status_filter, 'sold'); ?>>Sold</option>
                                <option value="reserved" <?php selected($status_filter, 'reserved'); ?>>Reserved</option>
                            </select>
                        </div>
                        <div class="gti-ue-filter">
                            <select name="condition">
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
                                <th class="col-checkbox"><input type="checkbox"></th>
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
                                    <td colspan="10" style="text-align: center; padding: 60px 20px;">
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
                                        <td class="col-checkbox"><input type="checkbox"></td>
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
                                        <td class="col-status"><?php echo $_gti_eq_status_badge($eq->status); ?></td>
                                        <td class="col-actions">
                                            <div class="gti-ue-action-menu">
                                                <button class="gti-ue-action-toggle" title="Actions"><i class="fas fa-ellipsis-v"></i></button>
                                                <div class="gti-ue-action-dropdown">
                                                    <a href="#" class="gti-ue-action-item"><i class="fas fa-eye"></i> View</a>
                                                    <a href="#" class="gti-ue-action-item"><i class="fas fa-edit"></i> Edit</a>
                                                    <a href="#" class="gti-ue-action-item delete"><i class="fas fa-trash"></i> Delete</a>
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
                                $query_params['gti_page'] = 'used-equipment';
                                $base_url = gti_dashboard_url('used-equipment');
                                ?>
                                <?php if ($paged > 1): ?>
                                    <a href="<?php echo esc_url($base_url . '?' . http_build_query(array_merge($query_params, ['paged' => $paged - 1]))); ?>" class="gti-ue-page-btn"><i class="fas fa-chevron-left"></i></a>
                                <?php else: ?>
                                    <button class="gti-ue-page-btn" disabled><i class="fas fa-chevron-left"></i></button>
                                <?php endif; ?>
                                <?php
                                $start = max(1, $paged - 2);
                                $end = min($total_pages, $paged + 2);
                                ?>
                                <?php if ($start > 1): ?>
                                    <a href="<?php echo esc_url($base_url . '?' . http_build_query(array_merge($query_params, ['paged' => 1]))); ?>" class="gti-ue-page-btn">1</a>
                                    <?php if ($start > 2): ?><span class="gti-ue-page-dots">...</span><?php endif; ?>
                                <?php endif; ?>
                                <?php for ($i = $start; $i <= $end; $i++): ?>
                                    <?php if ($i == $paged): ?>
                                        <button class="gti-ue-page-btn active"><?php echo $i; ?></button>
                                    <?php else: ?>
                                        <a href="<?php echo esc_url($base_url . '?' . http_build_query(array_merge($query_params, ['paged' => $i]))); ?>" class="gti-ue-page-btn"><?php echo $i; ?></a>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                <?php if ($end < $total_pages): ?>
                                    <?php if ($end < $total_pages - 1): ?><span class="gti-ue-page-dots">...</span><?php endif; ?>
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
                <div class="gti-drawer-step" data-section="additional" onclick="switchDrawerStep(this)">
                    <div class="gti-drawer-step-circle">3</div>
                    <div class="gti-drawer-step-label">More</div>
                </div>
            </div>

            <!-- Section: Info (General + Images) -->
            <div class="gti-drawer-section active" data-section="info">
                <div class="gti-drawer-section-title"><i class="fas fa-info-circle"></i> General Information</div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Name</span><span class="gti-drawer-value" id="drawer-name">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Brand</span><span class="gti-drawer-value" id="drawer-brand">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Category</span><span class="gti-drawer-value" id="drawer-category">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Model</span><span class="gti-drawer-value" id="drawer-model">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Year</span><span class="gti-drawer-value" id="drawer-year">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Condition</span><span class="gti-drawer-value" id="drawer-condition">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Hours</span><span class="gti-drawer-value" id="drawer-hours">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Price</span><span class="gti-drawer-value" id="drawer-price">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Status</span><span class="gti-drawer-value" id="drawer-status-text">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Location</span><span class="gti-drawer-value" id="drawer-location">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Negotiable</span><span class="gti-drawer-value" id="drawer-negotiable">-</span></div>
                    <div class="gti-drawer-row" style="flex-direction:column;">
                        <span class="gti-drawer-label">Description</span>
                        <span class="gti-drawer-value is-message" id="drawer-description">-</span>
                    </div>
                <div class="gti-drawer-section-title" style="margin-top:18px;"><i class="fas fa-images"></i> Images</div>
                    <div class="gti-drawer-row" style="flex-direction:column;">
                        <span class="gti-drawer-label">Main Image</span>
                        <div id="drawer-main-image" style="margin-top:8px;"><span class="gti-drawer-value" style="text-align:center;color:#9ca3af;font-weight:400;">No image</span></div>
                    </div>
                    <div class="gti-drawer-row" style="flex-direction:column;">
                        <span class="gti-drawer-label">Gallery</span>
                        <div id="drawer-gallery" style="margin-top:8px;display:flex;gap:6px;flex-wrap:wrap;"><span class="gti-drawer-value" style="text-align:center;color:#9ca3af;font-weight:400;">No images</span></div>
                    </div>
            </div>

            <!-- Section: Specs -->
            <div class="gti-drawer-section" data-section="specs">
                <div class="gti-drawer-section-title"><i class="fas fa-list-alt"></i> Technical Specifications</div>
                    <div id="drawer-specs-list"><div class="gti-drawer-row"><span class="gti-drawer-value" style="text-align:center;color:#9ca3af;font-weight:400;">No specifications available</span></div></div>
            </div>

            <!-- Section: Additional -->
            <div class="gti-drawer-section" data-section="additional">
                <div class="gti-drawer-section-title"><i class="fas fa-ellipsis-h"></i> Additional Info</div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Equipment Code</span><span class="gti-drawer-value" id="drawer-eq-code-alt">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Last Service</span><span class="gti-drawer-value" id="drawer-last-service">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Next Service</span><span class="gti-drawer-value" id="drawer-next-service">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Warranty</span><span class="gti-drawer-value" id="drawer-warranty">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Registration</span><span class="gti-drawer-value" id="drawer-registration">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Insurance</span><span class="gti-drawer-value" id="drawer-insurance">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Created</span><span class="gti-drawer-value" id="drawer-created">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Updated</span><span class="gti-drawer-value" id="drawer-updated">-</span></div>
                    <div class="gti-drawer-row" style="flex-direction:column;">
                        <span class="gti-drawer-label">Notes</span>
                        <span class="gti-drawer-value is-message" id="drawer-notes">-</span>
                    </div>
            </div>
        </div>
        <div class="gti-drawer-footer">
            <button type="button" class="gti-drawer-btn gti-drawer-btn-primary"><i class="fas fa-edit"></i> Edit</button>
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
            </div> <!-- /.gti-content -->
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
    });

    var _currentDrawerEq = null;
    function updateDrawerContent(eq) {
        _currentDrawerEq = eq;
        document.getElementById('drawer-eq-code').textContent = eq.equipment_code || '-';
        var badge = document.getElementById('drawer-status-badge');
        var statusMap = { 'available':'Available', 'sold':'Sold', 'reserved':'Reserved', 'rented':'Rented', 'maintenance':'Maintenance' };
        var sl = statusMap[eq.status] || eq.status || 'Available';
        badge.textContent = sl;
        badge.className = 'gti-drawer-status status-' + (eq.status || 'available');
        document.getElementById('drawer-name').textContent = eq.name || '-';
        document.getElementById('drawer-brand').textContent = eq.brand || '-';
        document.getElementById('drawer-category').textContent = eq.category || '-';
        document.getElementById('drawer-model').textContent = eq.model || '-';
        document.getElementById('drawer-year').textContent = eq.year || '-';
        document.getElementById('drawer-condition').textContent = eq.condition_status || '-';
        document.getElementById('drawer-hours').textContent = eq.hours ? number_format(eq.hours) + ' Hours' : '-';
        document.getElementById('drawer-price').textContent = formatRupiah(eq.price);
        document.getElementById('drawer-status-text').textContent = sl;
        document.getElementById('drawer-location').textContent = eq.location || '-';
        document.getElementById('drawer-negotiable').textContent = eq.negotiable ? 'Yes' : 'No';
        document.getElementById('drawer-description').textContent = eq.description || '-';
        // Images (with dummy fallback)
        var mainImgUrl = eq.main_image || 'https://picsum.photos/seed/' + (eq.equipment_code || 'eq') + '/400/300';
        var mainImgEl = document.getElementById('drawer-main-image');
        mainImgEl.innerHTML = '<img src="' + mainImgUrl + '" alt="Main" style="width:100%;border-radius:8px;border:1px solid #e5e7eb;">';
        var galleryUrls = [];
        if (eq.images) { try { var parsed = typeof eq.images === 'string' ? JSON.parse(eq.images) : eq.images; if (Array.isArray(parsed)) galleryUrls = parsed; } catch(e){} }
        if (!galleryUrls.length) { var seed = eq.equipment_code || 'eq'; galleryUrls = ['https://picsum.photos/seed/' + seed + '-g1/200/200','https://picsum.photos/seed/' + seed + '-g2/200/200','https://picsum.photos/seed/' + seed + '-g3/200/200']; }
        document.getElementById('drawer-gallery').innerHTML = galleryUrls.map(function(src){ return '<img src="' + src + '" alt="Gallery" style="width:56px;height:56px;object-fit:cover;border-radius:6px;border:1px solid #e5e7eb;">'; }).join('');
        // Specs (with dummy fallback)
        var specsHtml = '';
        var specs = null;
        if (eq.specifications) { try { specs = typeof eq.specifications === 'string' ? JSON.parse(eq.specifications) : eq.specifications; } catch(e){} }
        if (!specs || !Object.keys(specs).length) {
            specs = { 'Engine': 'Diesel Turbocharged', 'Operating Weight': '20,700 kg', 'Bucket Capacity': '1.0 m³', 'Max Digging Depth': '6,520 mm', 'Engine Power': '110 kW (150 HP)', 'Fuel Tank Capacity': '410 L' };
        }
        for (var key in specs) { if (specs.hasOwnProperty(key)) specsHtml += '<div class="gti-drawer-row"><span class="gti-drawer-label">' + key.charAt(0).toUpperCase() + key.slice(1) + '</span><span class="gti-drawer-value">' + specs[key] + '</span></div>'; }
        document.getElementById('drawer-specs-list').innerHTML = specsHtml;
        // Maintenance (with dummy fallback)
        document.getElementById('drawer-last-service').textContent = eq.last_service_date || '2026-03-15';
        document.getElementById('drawer-next-service').textContent = eq.next_service_due || '2026-09-15';
        document.getElementById('drawer-warranty').textContent = eq.warranty_info || '12 months / 2,000 hours';
        document.getElementById('drawer-registration').textContent = eq.registration_number || 'B 1234 UE';
        document.getElementById('drawer-insurance').textContent = eq.insurance_status || 'Active';
        // Additional
        document.getElementById('drawer-eq-code-alt').textContent = eq.equipment_code || '-';
        document.getElementById('drawer-created').textContent = formatDate(eq.created_at);
        document.getElementById('drawer-updated').textContent = formatDate(eq.updated_at);
        document.getElementById('drawer-notes').textContent = eq.notes || '-';
        document.querySelectorAll('.gti-ue-table tbody tr').forEach(function(r) { r.classList.remove('active-row'); });
        var activeRow = document.querySelector('.gti-ue-table tbody tr[data-eq-id="' + eq.id + '"]');
        if (activeRow) activeRow.classList.add('active-row');
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
        document.getElementById('drawer-more-dropdown').classList.remove('show');
        var steps = document.querySelectorAll('#eqDetailDrawer .gti-drawer-step');
        steps.forEach(function(s,i){ s.classList.remove('active','completed'); if(i===0) s.classList.add('active'); });
        document.querySelectorAll('#eqDetailDrawer .gti-drawer-step-line').forEach(function(l){ l.classList.remove('active'); });
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
    </script>
</body>
</html>
