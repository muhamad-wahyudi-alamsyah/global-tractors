<?php
/**
 * GTI Dashboard Frontend Template
 * 
 * This template is rendered when accessing /dashboard/
 *
 * @package Global_Tractors
 */

defined('ABSPATH') || exit;

// Get current user
$current_user = wp_get_current_user();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php bloginfo('name'); ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        /* Reset & Base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f3f4f6;
            color: #374151;
            min-height: 100vh;
        }
        
        /* Layout */
        .gti-dashboard {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .gti-sidebar {
            width: 260px;
            background: #1a1f36;
            color: white;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            overflow-y: auto;
            z-index: 100;
        }
        
        .gti-sidebar-logo {
            padding: 24px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }
        
        .gti-sidebar-logo img {
            max-width: 140px;
            height: auto;
        }
        
        .gti-sidebar-menu {
            padding: 16px 0;
        }
        
        .gti-menu-item {
            display: block;
            padding: 12px 24px;
            color: #a0aec0;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .gti-menu-item:hover {
            background: rgba(255,255,255,0.05);
            color: white;
        }
        
        .gti-menu-item.active {
            background: #F5A623;
            color: #1a1f36;
            font-weight: 600;
        }
        
        .gti-menu-section {
            padding: 16px 24px 8px;
            font-size: 11px;
            text-transform: uppercase;
            color: #6b7280;
            letter-spacing: 0.5px;
        }
        
        .gti-menu-divider {
            border-top: 1px solid rgba(255,255,255,0.1);
            margin: 16px 0;
        }
        
        /* Main Content */
        .gti-main {
            flex: 1;
            margin-left: 260px;
            padding: 24px 32px;
        }
        
        /* Header */
        .gti-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
        }
        
        .gti-page-title {
            font-size: 24px;
            font-weight: 700;
            color: #1a1f36;
        }
        
        .gti-page-subtitle {
            font-size: 14px;
            color: #6b7280;
            margin-top: 4px;
        }
        
        /* User Info */
        .gti-user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .gti-user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #F5A623;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: #1a1f36;
        }
        
        .gti-user-name {
            font-weight: 500;
        }
        
        .gti-user-role {
            font-size: 12px;
            color: #6b7280;
        }
        
        /* Stat Cards */
        .gti-stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }
        
        .gti-stat-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .gti-stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        }
        
        .gti-stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #1a1f36;
        }
        
        .gti-stat-label {
            font-size: 13px;
            color: #6b7280;
            margin-top: 8px;
        }
        
        .gti-stat-card.primary { border-left: 4px solid #F5A623; }
        .gti-stat-card.success { border-left: 4px solid #10b981; }
        .gti-stat-card.warning { border-left: 4px solid #f59e0b; }
        .gti-stat-card.danger { border-left: 4px solid #ef4444; }
        .gti-stat-card.info { border-left: 4px solid #3b82f6; }
        
        /* Cards */
        .gti-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            margin-bottom: 24px;
        }
        
        .gti-card-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .gti-card-title {
            font-size: 16px;
            font-weight: 600;
            color: #1a1f36;
        }
        
        .gti-card-body {
            padding: 24px;
        }
        
        /* Grid */
        .gti-grid {
            display: grid;
            gap: 24px;
        }
        
        .gti-grid-2 {
            grid-template-columns: repeat(2, 1fr);
        }
        
        /* Tables */
        .gti-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .gti-table th {
            background: #f9fafb;
            padding: 12px 16px;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .gti-table td {
            padding: 16px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .gti-table tr:last-child td {
            border-bottom: none;
        }
        
        .gti-table tr:hover td {
            background: #f9fafb;
        }
        
        /* Badges */
        .gti-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .gti-badge-success { background: #d1fae5; color: #065f46; }
        .gti-badge-warning { background: #fef3c7; color: #92400e; }
        .gti-badge-danger { background: #fee2e2; color: #991b1b; }
        .gti-badge-info { background: #dbeafe; color: #1e40af; }
        
        /* Buttons */
        .gti-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 500;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }
        
        .gti-btn-primary {
            background: #F5A623;
            color: #1a1f36;
        }
        
        .gti-btn-primary:hover {
            background: #e6951a;
        }
        
        .gti-btn-secondary {
            background: white;
            color: #374151;
            border: 1px solid #d1d5db;
        }
        
        .gti-btn-secondary:hover {
            background: #f9fafb;
        }
        
        /* Timeline */
        .gti-timeline {
            position: relative;
            padding-left: 28px;
        }
        
        .gti-timeline::before {
            content: '';
            position: absolute;
            left: 8px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e5e7eb;
        }
        
        .gti-timeline-item {
            position: relative;
            padding-bottom: 24px;
        }
        
        .gti-timeline-item::before {
            content: '';
            position: absolute;
            left: -24px;
            top: 4px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #F5A623;
            border: 3px solid white;
            box-shadow: 0 0 0 2px #e5e7eb;
        }
        
        .gti-timeline-item.success::before { background: #10b981; }
        .gti-timeline-item.warning::before { background: #f59e0b; }
        .gti-timeline-item.danger::before { background: #ef4444; }
        
        .gti-timeline-content {
            font-size: 14px;
            color: #374151;
        }
        
        .gti-timeline-time {
            font-size: 12px;
            color: #9ca3af;
            margin-top: 4px;
        }
        
        /* Empty State */
        .gti-empty-state {
            text-align: center;
            padding: 48px 24px;
        }
        
        .gti-empty-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }
        
        .gti-empty-title {
            font-size: 16px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }
        
        .gti-empty-text {
            font-size: 14px;
            color: #6b7280;
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .gti-sidebar {
                width: 70px;
            }
            
            .gti-sidebar-logo img {
                max-width: 40px;
            }
            
            .gti-menu-item {
                padding: 12px;
                text-align: center;
                font-size: 0;
            }
            
            .gti-menu-item::before {
                font-size: 18px;
            }
            
            .gti-menu-section {
                display: none;
            }
            
            .gti-main {
                margin-left: 70px;
            }
            
            .gti-grid-2 {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="gti-dashboard">
        <!-- Sidebar -->
        <aside class="gti-sidebar">
            <div class="gti-sidebar-logo">
                <img src="<?php echo GTI_URL; ?>/assets/images/logo.png" alt="GTI Logo" 
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                <div style="display:none; font-size: 18px; font-weight: 700; color: #F5A623;">GTI</div>
            </div>
            
            <nav class="gti-sidebar-menu">
                <div class="gti-menu-section">Main Menu</div>
                <a href="<?php echo admin_url('admin.php?page=gti-dashboard'); ?>" class="gti-menu-item active">
                    Dashboard
                </a>
                
                <div class="gti-menu-section">Inventory</div>
                <a href="<?php echo admin_url('admin.php?page=gti-equipment&type=used'); ?>" class="gti-menu-item">
                    Used Equipment
                </a>
                <a href="<?php echo admin_url('admin.php?page=gti-equipment&type=rental'); ?>" class="gti-menu-item">
                    Rental Equipment
                </a>
                <a href="<?php echo admin_url('admin.php?page=gti-spare-parts'); ?>" class="gti-menu-item">
                    Spare Parts
                </a>
                
                <div class="gti-menu-divider"></div>
                
                <div class="gti-menu-section">Business</div>
                <a href="<?php echo admin_url('admin.php?page=gti-requests'); ?>" class="gti-menu-item">
                    Request Equipment
                </a>
                <a href="<?php echo admin_url('admin.php?page=gti-quotations'); ?>" class="gti-menu-item">
                    Request Quotation
                </a>
                <a href="<?php echo admin_url('admin.php?page=gti-sell'); ?>" class="gti-menu-item">
                    Sell Equipment
                </a>
                <a href="<?php echo admin_url('admin.php?page=gti-messages'); ?>" class="gti-menu-item">
                    Messages
                </a>
                
                <div class="gti-menu-divider"></div>
                
                <div class="gti-menu-section">Management</div>
                <a href="<?php echo admin_url('admin.php?page=gti-customers'); ?>" class="gti-menu-item">
                    Customers
                </a>
                <?php if (current_user_can('gti_manage_users')): ?>
                    <a href="<?php echo admin_url('admin.php?page=gti-users'); ?>" class="gti-menu-item">
                        Users
                    </a>
                <?php endif; ?>
                
                <div class="gti-menu-divider"></div>
                
                <a href="<?php echo home_url(); ?>" class="gti-menu-item">
                    ← Back to Website
                </a>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="gti-main">
            <!-- Header -->
            <header class="gti-header">
                <div>
                    <h1 class="gti-page-title">Dashboard</h1>
                    <p class="gti-page-subtitle">Welcome back, <?php echo esc_html($current_user->display_name); ?>! Here's your business overview.</p>
                </div>
                <div class="gti-user-info">
                    <div>
                        <div class="gti-user-name"><?php echo esc_html($current_user->display_name); ?></div>
                        <div class="gti-user-role"><?php echo esc_html(ucfirst(str_replace('gti_', '', $current_user->roles[0] ?? 'admin'))); ?></div>
                    </div>
                    <div class="gti-user-avatar">
                        <?php echo strtoupper(substr($current_user->display_name, 0, 2)); ?>
                    </div>
                </div>
            </header>
            
            <!-- Statistics Cards -->
            <div class="gti-stats-row">
                <div class="gti-stat-card primary">
                    <div class="gti-stat-value"><?php echo esc_html($stats['equipment_used']); ?></div>
                    <div class="gti-stat-label">Used Equipment</div>
                </div>
                <div class="gti-stat-card info">
                    <div class="gti-stat-value"><?php echo esc_html($stats['equipment_rental']); ?></div>
                    <div class="gti-stat-label">Rental Equipment</div>
                </div>
                <div class="gti-stat-card success">
                    <div class="gti-stat-value"><?php echo esc_html($stats['spare_parts']); ?></div>
                    <div class="gti-stat-label">Spare Parts</div>
                </div>
                <div class="gti-stat-card warning">
                    <div class="gti-stat-value"><?php echo esc_html($stats['requests']); ?></div>
                    <div class="gti-stat-label">Request Equipment</div>
                </div>
                <div class="gti-stat-card info">
                    <div class="gti-stat-value"><?php echo esc_html($stats['quotations']); ?></div>
                    <div class="gti-stat-label">Request Quotation</div>
                </div>
                <div class="gti-stat-card success">
                    <div class="gti-stat-value"><?php echo esc_html($stats['customers']); ?></div>
                    <div class="gti-stat-label">Customers</div>
                </div>
            </div>
            
            <!-- Charts & Activity -->
            <div class="gti-grid gti-grid-2">
                <!-- Request Overview Chart -->
                <div class="gti-card">
                    <div class="gti-card-header">
                        <h3 class="gti-card-title">Request Overview</h3>
                    </div>
                    <div class="gti-card-body">
                        <div style="height: 300px;">
                            <canvas id="requestChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="gti-card">
                    <div class="gti-card-header">
                        <h3 class="gti-card-title">Recent Activity</h3>
                    </div>
                    <div class="gti-card-body" style="max-height: 340px; overflow-y: auto;">
                        <?php if (empty($recent_activity)): ?>
                            <div class="gti-empty-state">
                                <div class="gti-empty-icon">📋</div>
                                <div class="gti-empty-title">No recent activity</div>
                            </div>
                        <?php else: ?>
                            <div class="gti-timeline">
                                <?php foreach ($recent_activity as $activity): ?>
                                    <?php
                                    $action_text = '';
                                    $class = '';
                                    
                                    switch ($activity->action) {
                                        case 'create':
                                            $action_text = 'Created a new';
                                            $class = 'success';
                                            break;
                                        case 'update':
                                            $action_text = 'Updated';
                                            break;
                                        case 'delete':
                                            $action_text = 'Deleted';
                                            $class = 'danger';
                                            break;
                                        case 'status_change':
                                            $action_text = 'Changed status of';
                                            $class = 'warning';
                                            break;
                                        default:
                                            $action_text = 'Modified';
                                    }
                                    ?>
                                    <div class="gti-timeline-item <?php echo esc_attr($class); ?>">
                                        <div class="gti-timeline-content">
                                            <strong><?php echo esc_html($activity->user_name ?: 'System'); ?></strong>
                                            <?php echo esc_html($action_text); ?>
                                            <strong><?php echo esc_html(ucfirst(str_replace('_', ' ', $activity->entity_type))); ?></strong>
                                        </div>
                                        <div class="gti-timeline-time">
                                            <?php echo esc_html(GTI_Helpers::format_datetime($activity->created_at)); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Latest Data -->
            <div class="gti-grid gti-grid-2">
                <!-- Latest Equipment -->
                <div class="gti-card">
                    <div class="gti-card-header">
                        <h3 class="gti-card-title">Latest Equipment</h3>
                        <a href="<?php echo admin_url('admin.php?page=gti-equipment'); ?>" class="gti-btn gti-btn-secondary">View All</a>
                    </div>
                    <div class="gti-card-body" style="padding: 0;">
                        <table class="gti-table">
                            <thead>
                                <tr>
                                    <th>Equipment</th>
                                    <th>Brand</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($latest_equipment)): ?>
                                    <tr>
                                        <td colspan="3" style="text-align: center; padding: 24px;">
                                            No equipment found
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($latest_equipment as $eq): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo esc_html($eq->name); ?></strong>
                                                <br><small style="color: #6b7280;"><?php echo esc_html($eq->equipment_code); ?></small>
                                            </td>
                                            <td><?php echo esc_html($eq->brand); ?></td>
                                            <td><?php echo GTI_Helpers::get_status_badge($eq->status); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Latest Requests -->
                <div class="gti-card">
                    <div class="gti-card-header">
                        <h3 class="gti-card-title">Latest Requests</h3>
                        <a href="<?php echo admin_url('admin.php?page=gti-requests'); ?>" class="gti-btn gti-btn-secondary">View All</a>
                    </div>
                    <div class="gti-card-body" style="padding: 0;">
                        <table class="gti-table">
                            <thead>
                                <tr>
                                    <th>Request ID</th>
                                    <th>Customer</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($latest_requests)): ?>
                                    <tr>
                                        <td colspan="3" style="text-align: center; padding: 24px;">
                                            No requests found
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($latest_requests as $req): ?>
                                        <tr>
                                            <td><strong><?php echo esc_html($req->request_id); ?></strong></td>
                                            <td>
                                                <?php echo esc_html($req->customer_name); ?>
                                                <br><small style="color: #6b7280;"><?php echo esc_html($req->customer_company); ?></small>
                                            </td>
                                            <td><?php echo GTI_Helpers::get_status_badge($req->status); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Scripts -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Request Overview Chart
            var ctx = document.getElementById('requestChart');
            if (ctx) {
                new Chart(ctx.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                        datasets: [{
                            label: 'Requests',
                            data: [12, 19, 15, 25, 22, 30],
                            borderColor: '#F5A623',
                            backgroundColor: 'rgba(245, 166, 35, 0.1)',
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#F5A623',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 5
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: { color: '#f3f4f6' }
                            },
                            x: {
                                grid: { display: false }
                            }
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>