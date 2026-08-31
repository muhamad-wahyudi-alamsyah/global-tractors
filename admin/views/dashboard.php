<?php
/**
 * GTI Dashboard View
 *
 * @package Global_Tractors
 */

defined('ABSPATH') || exit;

// Get statistics
$db = GTI_Database::get_instance();
$stats = $db->get_dashboard_stats();

// Get recent activity
global $wpdb;
$recent_activity = $wpdb->get_results(
    "SELECT al.*, u.display_name as user_name 
     FROM {$wpdb->prefix}gti_activity_log al 
     LEFT JOIN {$wpdb->users} u ON al.user_id = u.ID 
     ORDER BY al.created_at DESC 
     LIMIT 10"
);

// Get latest equipment
$latest_equipment = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}gti_equipment 
     WHERE deleted_at IS NULL 
     ORDER BY created_at DESC 
     LIMIT 5"
);

// Get latest requests
$latest_requests = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}gti_requests 
     ORDER BY created_at DESC 
     LIMIT 5"
);
?>

<div class="gti-admin-wrap">
    <!-- Page Header -->
    <div class="gti-page-header">
        <div>
            <h1 class="gti-page-title">Dashboard</h1>
            <p class="gti-page-subtitle">Welcome back! Here's an overview of your business.</p>
        </div>
        <div>
            <button type="button" class="gti-btn gti-btn-secondary" onclick="location.reload();">
                ↻ Refresh
            </button>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="gti-stats-row">
        <div class="gti-stat-card stat-primary">
            <div class="stat-value"><?php echo esc_html($stats['equipment_used']); ?></div>
            <div class="stat-label">Used Equipment</div>
        </div>
        <div class="gti-stat-card stat-info">
            <div class="stat-value"><?php echo esc_html($stats['equipment_rental']); ?></div>
            <div class="stat-label">Rental Equipment</div>
        </div>
        <div class="gti-stat-card stat-success">
            <div class="stat-value"><?php echo esc_html($stats['spare_parts']); ?></div>
            <div class="stat-label">Spare Parts</div>
        </div>
        <div class="gti-stat-card stat-warning">
            <div class="stat-value"><?php echo esc_html($stats['requests']); ?></div>
            <div class="stat-label">Request Equipment</div>
        </div>
        <div class="gti-stat-card stat-info">
            <div class="stat-value"><?php echo esc_html($stats['quotations']); ?></div>
            <div class="stat-label">Request Quotation</div>
        </div>
        <div class="gti-stat-card stat-success">
            <div class="stat-value"><?php echo esc_html($stats['customers']); ?></div>
            <div class="stat-label">Customers</div>
        </div>
    </div>
    
    <!-- Charts Row -->
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
                <a href="#" class="gti-btn gti-btn-sm gti-btn-secondary">View All</a>
            </div>
            <div class="gti-card-body" style="max-height: 300px; overflow-y: auto;">
                <?php if (empty($recent_activity)): ?>
                    <div class="gti-empty-state" style="padding: 20px;">
                        <p class="gti-empty-state-text">No recent activity</p>
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
    
    <!-- Latest Data Row -->
    <div class="gti-grid gti-grid-2">
        <!-- Latest Equipment -->
        <div class="gti-card">
            <div class="gti-card-header">
                <h3 class="gti-card-title">Latest Equipment</h3>
                <a href="<?php echo admin_url('admin.php?page=gti-equipment'); ?>" class="gti-btn gti-btn-sm gti-btn-secondary">View All</a>
            </div>
            <div class="gti-card-body" style="padding: 0;">
                <table class="gti-table">
                    <thead>
                        <tr>
                            <th>Equipment</th>
                            <th>Brand</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($latest_equipment)): ?>
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 20px;">
                                    No equipment found
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($latest_equipment as $equipment): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo esc_html($equipment->name); ?></strong>
                                        <br><small style="color: #6b7280;"><?php echo esc_html($equipment->equipment_code); ?></small>
                                    </td>
                                    <td><?php echo esc_html($equipment->brand); ?></td>
                                    <td><?php echo GTI_Helpers::get_status_badge($equipment->status); ?></td>
                                    <td><?php echo esc_html(GTI_Helpers::format_date($equipment->created_at)); ?></td>
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
                <a href="<?php echo admin_url('admin.php?page=gti-requests'); ?>" class="gti-btn gti-btn-sm gti-btn-secondary">View All</a>
            </div>
            <div class="gti-card-body" style="padding: 0;">
                <table class="gti-table">
                    <thead>
                        <tr>
                            <th>Request ID</th>
                            <th>Customer</th>
                            <th>Equipment</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($latest_requests)): ?>
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 20px;">
                                    No requests found
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($latest_requests as $request): ?>
                                <tr>
                                    <td><strong><?php echo esc_html($request->request_id); ?></strong></td>
                                    <td>
                                        <?php echo esc_html($request->customer_name); ?>
                                        <br><small style="color: #6b7280;"><?php echo esc_html($request->customer_company); ?></small>
                                    </td>
                                    <td><?php echo esc_html($request->equipment); ?></td>
                                    <td><?php echo GTI_Helpers::get_status_badge($request->status); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>