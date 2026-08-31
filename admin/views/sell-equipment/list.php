<?php
/**
 * Sell Equipment List View
 *
 * @package Global_Tractors
 */

defined('ABSPATH') || exit;

// Get filters
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';

// Pagination
$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 20;
$offset = ($paged - 1) * $per_page;

// Build filters
$filters = array();
if ($search) $filters['search'] = $search;
if ($status) $filters['status'] = $status;

// Get data
$db = GTI_Database::get_instance();
$sell_requests = $db->get_sell_requests($filters, $per_page, $offset);
$total = $db->get_sell_requests_count($filters);
$total_pages = ceil($total / $per_page);

// Get status counts
global $wpdb;
$status_counts = $wpdb->get_results(
    "SELECT status, COUNT(*) as count FROM {$wpdb->prefix}gti_sell_requests GROUP BY status"
);
$status_count_map = array();
foreach ($status_counts as $sc) {
    $status_count_map[$sc->status] = $sc->count;
}
?>

<div class="gti-admin-wrap">
    <!-- Page Header -->
    <div class="gti-page-header">
        <div>
            <h1 class="gti-page-title">Sell Equipment</h1>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="gti-stats-row">
        <div class="gti-stat-card stat-primary">
            <div class="stat-value"><?php echo esc_html($total); ?></div>
            <div class="stat-label">Total Submissions</div>
        </div>
        <div class="gti-stat-card stat-info">
            <div class="stat-value"><?php echo esc_html($status_count_map['new'] ?? 0); ?></div>
            <div class="stat-label">New</div>
        </div>
        <div class="gti-stat-card stat-warning">
            <div class="stat-value"><?php echo esc_html($status_count_map['processing'] ?? 0); ?></div>
            <div class="stat-label">Processing</div>
        </div>
        <div class="gti-stat-card stat-success">
            <div class="stat-value"><?php echo esc_html($status_count_map['approved'] ?? 0); ?></div>
            <div class="stat-label">Approved</div>
        </div>
        <div class="gti-stat-card stat-danger">
            <div class="stat-value"><?php echo esc_html($status_count_map['rejected'] ?? 0); ?></div>
            <div class="stat-label">Rejected</div>
        </div>
        <div class="gti-stat-card stat-neutral">
            <div class="stat-value"><?php echo esc_html($status_count_map['completed'] ?? 0); ?></div>
            <div class="stat-label">Completed</div>
        </div>
    </div>
    
    <!-- Filter Bar -->
    <form class="gti-filter-bar gti-filter-form" method="get">
        <input type="hidden" name="page" value="gti-sell">
        
        <div class="gti-form-group" style="margin-bottom: 0; flex: 1; min-width: 200px;">
            <input type="text" name="search" class="gti-form-control" 
                   placeholder="Search by name, company, equipment..." value="<?php echo esc_attr($search); ?>">
        </div>
        
        <div class="gti-form-group" style="margin-bottom: 0;">
            <select name="status" class="gti-form-control">
                <option value="">All Status</option>
                <option value="new" <?php selected($status, 'new'); ?>>New</option>
                <option value="processing" <?php selected($status, 'processing'); ?>>Processing</option>
                <option value="approved" <?php selected($status, 'approved'); ?>>Approved</option>
                <option value="rejected" <?php selected($status, 'rejected'); ?>>Rejected</option>
                <option value="completed" <?php selected($status, 'completed'); ?>>Completed</option>
            </select>
        </div>
        
        <button type="submit" class="gti-btn gti-btn-primary">Search</button>
        <a href="<?php echo admin_url('admin.php?page=gti-sell'); ?>" class="gti-btn gti-btn-secondary">Reset</a>
    </form>
    
    <!-- Sell Requests Table -->
    <div class="gti-table-wrap">
        <table class="gti-table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Equipment</th>
                    <th>Year</th>
                    <th>Condition</th>
                    <th>Offered Price</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th style="width: 100px; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($sell_requests)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px;">
                            <div class="gti-empty-state">
                                <div class="gti-empty-state-icon">🛒</div>
                                <div class="gti-empty-state-title">No sell requests found</div>
                                <div class="gti-empty-state-text">
                                    <?php if ($search || $status): ?>
                                        Try adjusting your filters
                                    <?php else: ?>
                                        No equipment sell submissions yet
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($sell_requests as $req): ?>
                        <tr class="gti-sell-row" data-id="<?php echo esc_attr($req->id); ?>" style="cursor: pointer;">
                            <td>
                                <?php echo esc_html($req->customer_name); ?>
                                <br><small style="color: #6b7280;"><?php echo esc_html($req->customer_company); ?></small>
                            </td>
                            <td>
                                <?php echo esc_html($req->equipment_name); ?>
                                <br><small style="color: #6b7280;"><?php echo esc_html($req->equipment_brand . ' ' . $req->equipment_model); ?></small>
                            </td>
                            <td><?php echo esc_html($req->equipment_year ?: '-'); ?></td>
                            <td><?php echo GTI_Helpers::get_condition_badge($req->equipment_condition); ?></td>
                            <td><?php echo esc_html(GTI_Helpers::format_currency($req->offered_price)); ?></td>
                            <td><?php echo GTI_Helpers::get_status_badge($req->status); ?></td>
                            <td><?php echo esc_html(GTI_Helpers::format_date($req->created_at)); ?></td>
                            <td style="text-align: right;">
                                <div class="gti-dropdown">
                                    <button type="button" class="gti-btn gti-btn-sm gti-dropdown-toggle" onclick="event.stopPropagation();">•••</button>
                                    <div class="gti-dropdown-menu">
                                        <button type="button" class="gti-dropdown-item gti-open-drawer" data-id="<?php echo esc_attr($req->id); ?>">
                                            View Details
                                        </button>
                                        <select class="gti-dropdown-item gti-status-select" 
                                                data-id="<?php echo esc_attr($req->id); ?>"
                                                data-action="gti_update_sell_request_status"
                                                onclick="event.stopPropagation();"
                                                style="width: 100%; border: none; background: none; cursor: pointer; padding: 6px 12px; font-size: 13px;">
                                            <option value="new" <?php selected($req->status, 'new'); ?>>New</option>
                                            <option value="processing" <?php selected($req->status, 'processing'); ?>>Processing</option>
                                            <option value="approved" <?php selected($req->status, 'approved'); ?>>Approved</option>
                                            <option value="rejected" <?php selected($req->status, 'rejected'); ?>>Rejected</option>
                                            <option value="completed" <?php selected($req->status, 'completed'); ?>>Completed</option>
                                        </select>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <?php if ($total_pages > 1): ?>
            <div class="gti-table-footer">
                <div>
                    Showing <?php echo esc_html($offset + 1); ?>-<?php echo esc_html(min($offset + $per_page, $total)); ?> of <?php echo esc_html($total); ?> submissions
                </div>
                <div class="gti-pagination">
                    <?php if ($paged > 1): ?>
                        <a href="?page=gti-sell&paged=<?php echo esc_html($paged - 1); ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $status ? '&status=' . urlencode($status) : ''; ?>">←</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $paged): ?>
                            <span class="current"><?php echo esc_html($i); ?></span>
                        <?php else: ?>
                            <a href="?page=gti-sell&paged=<?php echo esc_html($i); ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $status ? '&status=' . urlencode($status) : ''; ?>"><?php echo esc_html($i); ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($paged < $total_pages): ?>
                        <a href="?page=gti-sell&paged=<?php echo esc_html($paged + 1); ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $status ? '&status=' . urlencode($status) : ''; ?>">→</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Right Drawer Overlay -->
<div class="gti-drawer-overlay" id="sellDrawerOverlay"></div>

<!-- Right Drawer -->
<div class="gti-drawer" id="sellDrawer">
    <div class="gti-drawer-header">
        <h2 class="gti-drawer-title">Sell Request Detail</h2>
        <button type="button" class="gti-drawer-close" id="sellDrawerClose">&times;</button>
    </div>
    <div class="gti-drawer-body" id="sellDrawerBody">
        <!-- Loading State -->
        <div class="gti-drawer-loading">
            <div class="gti-spinner"></div>
            <p>Loading details...</p>
        </div>
    </div>
</div>