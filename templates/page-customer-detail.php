<?php
/**
 * Template: Customer Detail (/dashboard/customers/?page=gti-customer-detail&id=CUST-230112)
 * @package global-tractors
 */
if (!defined('ABSPATH')) exit;
gti_require_login();

$current_user = wp_get_current_user();
$user_name = $current_user->display_name ?: $current_user->user_login;
$user_avatar = get_avatar_url($current_user->ID, ['size' => 80]);

// Get customer ID from URL
$customer_id_param = isset($_GET['id']) ? sanitize_text_field($_GET['id']) : '';

// Database lookup
global $wpdb;
$table_name = $wpdb->prefix . 'gti_customers';
$customer = null;
if ($customer_id_param) {
    $customer = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table_name} WHERE customer_id = %s",
        $customer_id_param
    ));
}

// Fallback: use demo data if no DB record found
if (!$customer) {
    $customer = new stdClass();
    $customer->customer_id         = 'CUST-230112';
    $customer->name                = 'Budi Santoso';
    $customer->email               = 'budi@ptabadi.com';
    $customer->phone               = '+62 812-3456-7890';
    $customer->company             = 'PT Abadi Sentosa';
    $customer->industry            = 'Construction';
    $customer->city                = 'Jakarta';
    $customer->province            = 'DKI Jakarta';
    $customer->country             = 'Indonesia';
    $customer->address             = 'Jl. Jend. Sudirman No. 52, Jakarta Selatan, DKI Jakarta 12190';
    $customer->website             = 'www.ptabadi.com';
    $customer->npwp                = '01.234.567.8-009.000';
    $customer->contact_person      = 'Budi Santoso';
    $customer->contact_position    = 'Project Manager';
    $customer->contact_phone       = '+62 812-3456-7890';
    $customer->contact_email       = 'budi@ptabadi.com';
    $customer->contact_whatsapp    = '+62 812-3456-7890';
    $customer->status              = 'active';
    $customer->rating              = 4.8;
    $customer->total_transactions  = 12;
    $customer->total_spent         = 2450750000;
    $customer->registered_date     = '2023-01-12 09:00:00';
    $customer->last_contact        = '2024-05-31 10:25:00';
}

// Helpers
function gti_cd_initials($name) {
    $parts = explode(' ', trim($name));
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $p) {
        $initials .= mb_strtoupper(mb_substr($p, 0, 1));
    }
    return $initials;
}
function gti_cd_fmt_date($date) {
    if (!$date) return '-';
    return date('d M Y', strtotime($date));
}
function gti_cd_fmt_datetime($datetime) {
    if (!$datetime) return '-';
    return date('d M Y, h:i A', strtotime($datetime));
}
function gti_cd_fmt_currency($amount) {
    return 'IDR ' . number_format((float)$amount, 0, '.', '.');
}

$initials = gti_cd_initials($customer->name);
$customer_since = gti_cd_fmt_date($customer->registered_date);
$last_contact   = gti_cd_fmt_date($customer->last_contact);
$full_location   = trim($customer->city . ', ' . $customer->province . ', ' . $customer->country, ', ');

// Dummy recent activity data
$recent_activities = [
    [
        'date'     => '31 May 2024, 10:25 AM',
        'type'     => 'Request Quotation',
        'icon'     => 'fas fa-file-invoice',
        'icon_bg'  => '#fef3c7',
        'icon_clr' => '#f59e0b',
        'detail'   => 'RFQ-2405-0012 - 6 items (Total: IDR 1.248.750.000)',
        'by'       => 'Andi Pratama',
    ],
    [
        'date'     => '30 May 2024, 03:15 PM',
        'type'     => 'Request Equipment',
        'icon'     => 'fas fa-truck',
        'icon_bg'  => '#dbeafe',
        'icon_clr' => '#3b82f6',
        'detail'   => 'Komatsu PC200-8 - Project Balikpapan',
        'by'       => 'Dewi Lestari',
    ],
    [
        'date'     => '28 May 2024, 02:20 PM',
        'type'     => 'Quotation Approved',
        'icon'     => 'fas fa-check-circle',
        'icon_bg'  => '#d1fae5',
        'icon_clr' => '#10b981',
        'detail'   => 'RFQ-2404-0032 has been approved',
        'by'       => 'Andi Pratama',
    ],
    [
        'date'     => '20 May 2024, 11:08 AM',
        'type'     => 'Rental Completed',
        'icon'     => 'fas fa-flag-checkered',
        'icon_bg'  => '#fee2e2',
        'icon_clr' => '#ef4444',
        'detail'   => 'Rental ID: RENT-2404-0015',
        'by'       => 'System',
    ],
];
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($customer->name); ?> - Customer Detail - <?php bloginfo('name'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo GTI_CHILD_URL; ?>/assets/css/dashboard.css">
    <style>
        /* Back Link */
        .gti-cd-top-bar{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px}
        .gti-cd-back{display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#6b7280;text-decoration:none}
        .gti-cd-back:hover{color:#f9b204}

        /* Action Bar */
        .gti-cd-action-bar{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
        .gti-cd-btn{display:inline-flex;align-items:center;gap:7px;padding:9px 18px;border-radius:8px;font-size:13px;font-weight:600;font-family:Inter,sans-serif;cursor:pointer;text-decoration:none;border:1px solid transparent;transition:all .15s}
        .gti-cd-btn-outline{background:#fff;color:#374151;border-color:#d1d5db}
        .gti-cd-btn-outline:hover{background:#f9fafb;border-color:#9ca3af}
        .gti-cd-btn-primary{background:#f9b204;color:#000;border-color:#f9b204}
        .gti-cd-btn-primary:hover{background:#e0a200}
        .gti-cd-btn i{font-size:13px}
        .gti-cd-more-wrap{position:relative;display:inline-block}
        .gti-cd-more-btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;border-radius:8px;font-size:13px;font-weight:600;font-family:Inter,sans-serif;cursor:pointer;background:#f9b204;color:#000;border:1px solid #f9b204}
        .gti-cd-more-btn:hover{background:#e0a200}
        .gti-cd-more-dropdown{display:none;position:absolute;right:0;top:100%;margin-top:6px;background:#fff;border:1px solid #e5e7eb;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.12);min-width:180px;z-index:50;padding:6px}
        .gti-cd-more-dropdown.show{display:block}
        .gti-cd-more-item{display:flex;align-items:center;gap:8px;padding:8px 12px;font-size:13px;color:#374151;border-radius:6px;text-decoration:none;cursor:pointer;border:none;background:none;width:100%;text-align:left;font-family:Inter,sans-serif}
        .gti-cd-more-item:hover{background:#f3f4f6}
        .gti-cd-more-item i{width:16px;font-size:13px;color:#9ca3af}

        /* Profile Card */
        .gti-cd-profile{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:28px 32px;margin-bottom:24px}
        .gti-cd-profile-top{display:flex;gap:24px;align-items:flex-start;flex-wrap:wrap}
        .gti-cd-profile-left{display:flex;gap:16px;align-items:center;flex:1;min-width:0}
        .gti-cd-avatar-lg{width:64px;height:64px;border-radius:14px;background:#f9b204;display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:700;color:#000;flex-shrink:0}
        .gti-cd-profile-info{display:flex;flex-direction:column;gap:4px}
        .gti-cd-profile-name{font-size:22px;font-weight:700;color:#111827}
        .gti-cd-profile-status{display:inline-flex;align-items:center;padding:3px 12px;border-radius:20px;font-size:12px;font-weight:600;gap:5px;width:fit-content}
        .gti-cd-profile-status.active{background:#d1fae5;color:#065f46}
        .gti-cd-profile-status.active::before{content:'';width:6px;height:6px;border-radius:50%;background:#10b981}
        .gti-cd-profile-status.inactive{background:#fee2e2;color:#991b1b}
        .gti-cd-profile-status.inactive::before{content:'';width:6px;height:6px;border-radius:50%;background:#ef4444}
        .gti-cd-profile-company{font-size:14px;color:#6b7280;margin-top:2px}
        .gti-cd-kpi-box{text-align:center;padding:16px 24px;background:#fafafa;border:1px solid #f3f4f6;border-radius:10px;min-width:140px}
        .gti-cd-kpi-value{font-size:20px;font-weight:700;color:#111827}
        .gti-cd-kpi-label{font-size:11px;color:#9ca3af;margin-top:2px;text-transform:uppercase;letter-spacing:.03em}
        .gti-cd-profile-right{display:flex;gap:12px;align-items:flex-start}

        /* Quick Contact Meta */
        .gti-cd-meta-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:14px;margin-top:20px;padding-top:20px;border-top:1px solid #f3f4f6}
        .gti-cd-meta-item{display:flex;align-items:center;gap:10px;font-size:13px;color:#374151}
        .gti-cd-meta-item i{width:20px;text-align:center;color:#9ca3af;font-size:14px}
        .gti-cd-meta-item a{color:#2563eb;text-decoration:none}
        .gti-cd-meta-item a:hover{text-decoration:underline}
        .gti-cd-meta-group{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;margin-top:16px;padding-top:16px;border-top:1px solid #f3f4f6}
        .gti-cd-meta-label{font-size:11px;color:#9ca3af;text-transform:uppercase;letter-spacing:.03em;margin-bottom:2px}

        /* Tabs */
        .gti-cd-tabs{display:flex;gap:0;border-bottom:2px solid #f3f4f6;margin-bottom:24px;overflow-x:auto}
        .gti-cd-tab{padding:12px 20px;font-size:13px;font-weight:600;color:#6b7280;cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;text-decoration:none;white-space:nowrap;transition:all .15s}
        .gti-cd-tab:hover{color:#374151}
        .gti-cd-tab.active{color:#f9b204;border-bottom-color:#f9b204}

        /* Section Cards */
        .gti-cd-section{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:24px;margin-bottom:20px}
        .gti-cd-section-title{font-size:15px;font-weight:700;color:#111827;margin-bottom:18px;display:flex;align-items:center;gap:8px}
        .gti-cd-section-title i{color:#f9b204;font-size:16px}
        .gti-cd-info-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px}
        .gti-cd-info-field{display:flex;flex-direction:column;gap:3px}
        .gti-cd-info-field label{font-size:11px;color:#9ca3af;text-transform:uppercase;letter-spacing:.03em}
        .gti-cd-info-field span{font-size:14px;color:#374151;font-weight:500}
        .gti-cd-info-field span a{color:#2563eb;text-decoration:none}
        .gti-cd-info-field span a:hover{text-decoration:underline}
        .gti-cd-info-field-full{grid-column:1/-1}

        /* Stats Grid */
        .gti-cd-stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px}
        .gti-cd-stat-card{background:#fafafa;border:1px solid #f3f4f6;border-radius:10px;padding:20px;display:flex;flex-direction:column;gap:8px}
        .gti-cd-stat-card-header{display:flex;align-items:center;justify-content:space-between}
        .gti-cd-stat-card-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px}
        .gti-cd-stat-card-icon.q{background:#fef3c7;color:#f59e0b}
        .gti-cd-stat-card-icon.p{background:#dbeafe;color:#3b82f6}
        .gti-cd-stat-card-icon.r{background:#ede9fe;color:#8b5cf6}
        .gti-cd-stat-card-icon.i{background:#d1fae5;color:#10b981}
        .gti-cd-stat-card-value{font-size:24px;font-weight:700;color:#111827}
        .gti-cd-stat-card-label{font-size:13px;color:#6b7280}
        .gti-cd-stat-card-link{font-size:12px;color:#2563eb;text-decoration:none;display:inline-flex;align-items:center;gap:4px}
        .gti-cd-stat-card-link:hover{text-decoration:underline}

        /* Activity Table */
        .gti-cd-activity-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px}
        .gti-cd-activity-header h3{font-size:15px;font-weight:700;color:#111827;display:flex;align-items:center;gap:8px}
        .gti-cd-activity-header h3 i{color:#f9b204}
        .gti-cd-activity-link{font-size:13px;color:#2563eb;text-decoration:none;font-weight:600}
        .gti-cd-activity-link:hover{text-decoration:underline}
        .gti-cd-activity-table{width:100%;border-collapse:collapse}
        .gti-cd-activity-table thead th{font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.03em;padding:10px 14px;text-align:left;border-bottom:1px solid #f3f4f6;background:#fafafa}
        .gti-cd-activity-table tbody td{padding:12px 14px;font-size:13px;color:#374151;border-bottom:1px solid #f3f4f6}
        .gti-cd-activity-table tbody tr:last-child td{border-bottom:none}
        .gti-cd-activity-type{display:flex;align-items:center;gap:8px;font-weight:600}
        .gti-cd-activity-type-icon{width:28px;height:28px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:12px;flex-shrink:0}
        .gti-cd-activity-detail{color:#6b7280;max-width:350px}
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
                        <h1 class="gti-page-title">Customer Detail</h1>
                        <p class="gti-welcome">Welcome back, <?php echo esc_html($user_name); ?>! <span>&#128075;</span></p>
                    </div>
                </div>
                <div class="gti-header-right">
                    <div class="gti-date-filter">
                        <i class="fas fa-calendar"></i>
                        <span><?php echo date('M Y'); ?></span>
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
            <div class="gti-content" style="flex-direction:column">

                <!-- Back Link + Actions (inline) -->
                <div class="gti-cd-top-bar">
                    <a href="<?php echo esc_url(gti_dashboard_url('customers')); ?>" class="gti-cd-back">
                        <i class="fas fa-arrow-left"></i> Back to Customers
                    </a>

                    <div class="gti-cd-action-bar">
                        <a href="mailto:<?php echo esc_attr($customer->email); ?>" class="gti-cd-btn gti-cd-btn-outline">
                            <i class="fas fa-envelope"></i> Send Email
                        </a>
                        <a href="#" class="gti-cd-btn gti-cd-btn-outline">
                            <i class="fas fa-pen"></i> Edit Customer
                        </a>
                    <div class="gti-cd-more-wrap">
                        <button class="gti-cd-more-btn" id="cd-more-toggle">
                            More Actions <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="gti-cd-more-dropdown" id="cd-more-dropdown">
                            <a href="#" class="gti-cd-more-item"><i class="fas fa-file-invoice"></i> Create Quotation</a>
                            <a href="#" class="gti-cd-more-item"><i class="fas fa-file-alt"></i> Request Equipment</a>
                            <a href="#" class="gti-cd-more-item"><i class="fas fa-phone"></i> Log Call</a>
                            <a href="#" class="gti-cd-more-item"><i class="fas fa-tag"></i> Change Status</a>
                            <a href="#" class="gti-cd-more-item"><i class="fas fa-download"></i> Export Data</a>
                            <a href="#" class="gti-cd-more-item" style="color:#dc2626"><i class="fas fa-trash" style="color:#dc2626"></i> Delete Customer</a>
                        </div>
                    </div>
                    </div>
                </div>

                <!-- 1. Customer Profile Card -->
                <div class="gti-cd-profile">
                    <div class="gti-cd-profile-top">
                        <div class="gti-cd-profile-left">
                            <div class="gti-cd-avatar-lg"><?php echo esc_html($initials); ?></div>
                            <div class="gti-cd-profile-info">
                                <div class="gti-cd-profile-name"><?php echo esc_html($customer->name); ?></div>
                                <span class="gti-cd-profile-status <?php echo esc_attr($customer->status); ?>">
                                    <?php echo $customer->status === 'active' ? 'Active Customer' : 'Inactive Customer'; ?>
                                </span>
                                <div class="gti-cd-profile-company"><?php echo esc_html($customer->company); ?></div>
                            </div>
                        </div>
                        <div class="gti-cd-profile-right">
                            <div class="gti-cd-kpi-box">
                                <div class="gti-cd-kpi-value" style="color:#f59e0b">
                                    <i class="fas fa-star" style="font-size:16px"></i>
                                    <?php echo esc_html(number_format((float)$customer->rating, 1)); ?>
                                    <span style="font-size:13px;color:#9ca3af;font-weight:400">/ 5.0</span>
                                </div>
                                <div class="gti-cd-kpi-label">Customer Rating</div>
                            </div>
                            <div class="gti-cd-kpi-box">
                                <div class="gti-cd-kpi-value"><?php echo (int) $customer->total_transactions; ?></div>
                                <div class="gti-cd-kpi-label">Total Transactions</div>
                            </div>
                            <div class="gti-cd-kpi-box">
                                <div class="gti-cd-kpi-value" style="font-size:17px"><?php echo esc_html(gti_cd_fmt_currency($customer->total_spent)); ?></div>
                                <div class="gti-cd-kpi-label">Total Spent</div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Contact Meta -->
                    <div class="gti-cd-meta-grid">
                        <div class="gti-cd-meta-item">
                            <i class="fas fa-envelope"></i>
                            <a href="mailto:<?php echo esc_attr($customer->email); ?>"><?php echo esc_html($customer->email); ?></a>
                        </div>
                        <div class="gti-cd-meta-item">
                            <i class="fas fa-phone"></i>
                            <span><?php echo esc_html($customer->phone); ?></span>
                        </div>
                        <div class="gti-cd-meta-item">
                            <i class="fas fa-building"></i>
                            <span><?php echo esc_html($customer->industry); ?></span>
                        </div>
                        <div class="gti-cd-meta-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?php echo esc_html($full_location); ?></span>
                        </div>
                    </div>

                    <!-- Metadata Group -->
                    <div class="gti-cd-meta-group">
                        <div class="gti-cd-info-field">
                            <label><i class="fas fa-calendar" style="margin-right:4px"></i> Customer Since</label>
                            <span><?php echo esc_html($customer_since); ?></span>
                        </div>
                        <div class="gti-cd-info-field">
                            <label><i class="fas fa-phone-volume" style="margin-right:4px"></i> Last Contact</label>
                            <span><?php echo esc_html($last_contact); ?></span>
                        </div>
                        <div class="gti-cd-info-field">
                            <label><i class="fas fa-fingerprint" style="margin-right:4px"></i> Customer ID</label>
                            <span style="font-family:monospace"><?php echo esc_html($customer->customer_id); ?></span>
                        </div>
                    </div>
                </div>

                <!-- 2. Navigation Tabs -->
                <div class="gti-cd-tabs">
                    <a href="#tab-overview" class="gti-cd-tab active" data-tab="overview">Overview</a>
                    <a href="#tab-request" class="gti-cd-tab" data-tab="request">Request &amp; Inquiry</a>
                    <a href="#tab-quotations" class="gti-cd-tab" data-tab="quotations">Quotations</a>
                    <a href="#tab-transactions" class="gti-cd-tab" data-tab="transactions">Transactions</a>
                    <a href="#tab-rentals" class="gti-cd-tab" data-tab="rentals">Rentals</a>
                    <a href="#tab-documents" class="gti-cd-tab" data-tab="documents">Documents</a>
                    <a href="#tab-activity" class="gti-cd-tab" data-tab="activity">Activity Log</a>
                </div>

                <!-- Tab: Overview (default visible) -->
                <div class="gti-cd-tab-content" id="tab-overview">

                    <!-- 3. Company Information -->
                    <div class="gti-cd-section">
                        <div class="gti-cd-section-title"><i class="fas fa-building"></i> Company Information</div>
                        <div class="gti-cd-info-grid">
                            <div class="gti-cd-info-field">
                                <label>Company Name</label>
                                <span><?php echo esc_html($customer->company); ?></span>
                            </div>
                            <div class="gti-cd-info-field">
                                <label>NPWP</label>
                                <span><?php echo esc_html($customer->npwp); ?></span>
                            </div>
                            <div class="gti-cd-info-field">
                                <label>Industry</label>
                                <span><?php echo esc_html($customer->industry); ?></span>
                            </div>
                            <div class="gti-cd-info-field">
                                <label>Company Phone</label>
                                <span><?php echo esc_html($customer->phone); ?></span>
                            </div>
                            <div class="gti-cd-info-field">
                                <label>Company Email</label>
                                <span><a href="mailto:<?php echo esc_attr($customer->email); ?>"><?php echo esc_html($customer->email); ?></a></span>
                            </div>
                            <div class="gti-cd-info-field">
                                <label>Website</label>
                                <span><a href="https://<?php echo esc_attr($customer->website); ?>" target="_blank"><?php echo esc_html($customer->website); ?></a></span>
                            </div>
                            <div class="gti-cd-info-field gti-cd-info-field-full">
                                <label>Address</label>
                                <span><?php echo esc_html($customer->address); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Contact Person -->
                    <div class="gti-cd-section">
                        <div class="gti-cd-section-title"><i class="fas fa-user-tie"></i> Contact Person</div>
                        <div class="gti-cd-info-grid">
                            <div class="gti-cd-info-field">
                                <label>Contact Name</label>
                                <span><?php echo esc_html($customer->contact_person); ?></span>
                            </div>
                            <div class="gti-cd-info-field">
                                <label>Position</label>
                                <span><?php echo esc_html($customer->contact_position); ?></span>
                            </div>
                            <div class="gti-cd-info-field">
                                <label>Phone</label>
                                <span><?php echo esc_html($customer->contact_phone); ?></span>
                            </div>
                            <div class="gti-cd-info-field">
                                <label>Email</label>
                                <span><a href="mailto:<?php echo esc_attr($customer->contact_email); ?>"><?php echo esc_html($customer->contact_email); ?></a></span>
                            </div>
                            <div class="gti-cd-info-field">
                                <label>WhatsApp</label>
                                <span><a href="https://wa.me/<?php echo esc_attr(preg_replace('/[^0-9]/', '', $customer->contact_whatsapp)); ?>" target="_blank"><?php echo esc_html($customer->contact_whatsapp); ?></a></span>
                            </div>
                        </div>
                    </div>

                    <!-- 7. Customer Statistics -->
                    <div class="gti-cd-section">
                        <div class="gti-cd-section-title"><i class="fas fa-chart-bar"></i> Customer Statistics</div>
                        <div class="gti-cd-stats-grid">
                            <div class="gti-cd-stat-card">
                                <div class="gti-cd-stat-card-header">
                                    <div class="gti-cd-stat-card-icon q"><i class="fas fa-file-invoice"></i></div>
                                    <a href="#tab-quotations" class="gti-cd-tab" style="padding:0;border:0;margin:0;font-size:12px" onclick="document.querySelector('[data-tab=quotations]').click()">View Details <i class="fas fa-arrow-right" style="font-size:10px"></i></a>
                                </div>
                                <div class="gti-cd-stat-card-value">8</div>
                                <div class="gti-cd-stat-card-label">Total Quotation</div>
                            </div>
                            <div class="gti-cd-stat-card">
                                <div class="gti-cd-stat-card-header">
                                    <div class="gti-cd-stat-card-icon p"><i class="fas fa-truck"></i></div>
                                    <a href="#tab-transactions" class="gti-cd-tab" style="padding:0;border:0;margin:0;font-size:12px" onclick="document.querySelector('[data-tab=transactions]').click()">View Details <i class="fas fa-arrow-right" style="font-size:10px"></i></a>
                                </div>
                                <div class="gti-cd-stat-card-value">4</div>
                                <div class="gti-cd-stat-card-label">Total Purchase</div>
                            </div>
                            <div class="gti-cd-stat-card">
                                <div class="gti-cd-stat-card-header">
                                    <div class="gti-cd-stat-card-icon r"><i class="fas fa-car"></i></div>
                                    <a href="#tab-rentals" class="gti-cd-tab" style="padding:0;border:0;margin:0;font-size:12px" onclick="document.querySelector('[data-tab=rentals]').click()">View Details <i class="fas fa-arrow-right" style="font-size:10px"></i></a>
                                </div>
                                <div class="gti-cd-stat-card-value">3</div>
                                <div class="gti-cd-stat-card-label">Total Rental</div>
                            </div>
                            <div class="gti-cd-stat-card">
                                <div class="gti-cd-stat-card-header">
                                    <div class="gti-cd-stat-card-icon i"><i class="fas fa-search"></i></div>
                                    <a href="#tab-request" class="gti-cd-tab" style="padding:0;border:0;margin:0;font-size:12px" onclick="document.querySelector('[data-tab=request]').click()">View Details <i class="fas fa-arrow-right" style="font-size:10px"></i></a>
                                </div>
                                <div class="gti-cd-stat-card-value">7</div>
                                <div class="gti-cd-stat-card-label">Total Inquiry</div>
                            </div>
                        </div>
                    </div>

                    <!-- 5. Recent Activity -->
                    <div class="gti-cd-section">
                        <div class="gti-cd-activity-header">
                            <h3><i class="fas fa-history"></i> Recent Activity</h3>
                            <a href="#tab-activity" class="gti-cd-activity-link" onclick="document.querySelector('[data-tab=activity]').click()">View All Activity <i class="fas fa-arrow-right" style="font-size:11px"></i></a>
                        </div>
                        <table class="gti-cd-activity-table">
                            <thead>
                                <tr>
                                    <th style="width:180px">Date</th>
                                    <th style="width:200px">Activity</th>
                                    <th>Details</th>
                                    <th style="width:140px">By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_activities as $act): ?>
                                <tr>
                                    <td style="white-space:nowrap;color:#6b7280;font-size:12px"><?php echo esc_html($act['date']); ?></td>
                                    <td>
                                        <div class="gti-cd-activity-type">
                                            <div class="gti-cd-activity-type-icon" style="background:<?php echo $act['icon_bg']; ?>;color:<?php echo $act['icon_clr']; ?>">
                                                <i class="<?php echo $act['icon']; ?>"></i>
                                            </div>
                                            <?php echo esc_html($act['type']); ?>
                                        </div>
                                    </td>
                                    <td class="gti-cd-activity-detail"><?php echo esc_html($act['detail']); ?></td>
                                    <td style="color:#6b7280;font-size:12px"><?php echo esc_html($act['by']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Placeholder tabs -->
                <div class="gti-cd-tab-content" id="tab-request" style="display:none">
                    <div class="gti-cd-section">
                        <div class="gti-cd-section-title"><i class="fas fa-file-alt"></i> Request &amp; Inquiry</div>
                        <p style="color:#9ca3af;text-align:center;padding:40px 0">No requests found for this customer.</p>
                    </div>
                </div>
                <div class="gti-cd-tab-content" id="tab-quotations" style="display:none">
                    <div class="gti-cd-section">
                        <div class="gti-cd-section-title"><i class="fas fa-file-invoice"></i> Quotations</div>
                        <p style="color:#9ca3af;text-align:center;padding:40px 0">No quotations found for this customer.</p>
                    </div>
                </div>
                <div class="gti-cd-tab-content" id="tab-transactions" style="display:none">
                    <div class="gti-cd-section">
                        <div class="gti-cd-section-title"><i class="fas fa-shopping-cart"></i> Transactions</div>
                        <p style="color:#9ca3af;text-align:center;padding:40px 0">No transactions found for this customer.</p>
                    </div>
                </div>
                <div class="gti-cd-tab-content" id="tab-rentals" style="display:none">
                    <div class="gti-cd-section">
                        <div class="gti-cd-section-title"><i class="fas fa-car"></i> Rentals</div>
                        <p style="color:#9ca3af;text-align:center;padding:40px 0">No rentals found for this customer.</p>
                    </div>
                </div>
                <div class="gti-cd-tab-content" id="tab-documents" style="display:none">
                    <div class="gti-cd-section">
                        <div class="gti-cd-section-title"><i class="fas fa-folder-open"></i> Documents</div>
                        <p style="color:#9ca3af;text-align:center;padding:40px 0">No documents found for this customer.</p>
                    </div>
                </div>
                <div class="gti-cd-tab-content" id="tab-activity" style="display:none">
                    <div class="gti-cd-section">
                        <div class="gti-cd-section-title"><i class="fas fa-history"></i> Activity Log</div>
                        <p style="color:#9ca3af;text-align:center;padding:40px 0">Full activity log coming soon.</p>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Sidebar Collapse
        var collapseBtn = document.getElementById('gti-collapse-btn');
        var sidebar     = document.getElementById('gti-sidebar');
        var mainEl      = document.querySelector('.gti-main');

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

        // Sidebar Dropdown
        document.querySelectorAll('[data-toggle="dropdown"]').forEach(function(toggle) {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                var group = this.closest('.gti-has-children');
                if (group) group.classList.toggle('open');
            });
        });

        // More Actions Dropdown
        var moreToggle = document.getElementById('cd-more-toggle');
        var moreDropdown = document.getElementById('cd-more-dropdown');
        if (moreToggle && moreDropdown) {
            moreToggle.addEventListener('click', function(e) {
                e.preventDefault();
                moreDropdown.classList.toggle('show');
            });
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.gti-cd-more-wrap')) {
                    moreDropdown.classList.remove('show');
                }
            });
        }

        // Tab Switching
        document.querySelectorAll('.gti-cd-tab[data-tab]').forEach(function(tab) {
            tab.addEventListener('click', function(e) {
                e.preventDefault();
                var target = this.getAttribute('data-tab');

                // Update active tab
                document.querySelectorAll('.gti-cd-tab[data-tab]').forEach(function(t) {
                    t.classList.remove('active');
                });
                document.querySelectorAll('.gti-cd-tab[data-tab="' + target + '"]').forEach(function(t) {
                    t.classList.add('active');
                });

                // Show/hide content
                document.querySelectorAll('.gti-cd-tab-content').forEach(function(c) {
                    c.style.display = 'none';
                });
                var content = document.getElementById('tab-' + target);
                if (content) content.style.display = 'block';
            });
        });
    });
    </script>
</body>
</html>
