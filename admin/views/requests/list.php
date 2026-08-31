<?php
/**
 * Requests List View
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
$requests = $db->get_requests($filters, $per_page, $offset);
$total = $db->get_requests_count($filters);
$total_pages = ceil($total / $per_page);

// Get status counts
global $wpdb;
$status_counts = $wpdb->get_results(
    "SELECT status, COUNT(*) as count FROM {$wpdb->prefix}gti_requests GROUP BY status"
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
            <h1 class="gti-page-title">Request Equipment</h1>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="gti-stats-row">
        <div class="gti-stat-card stat-primary">
            <div class="stat-value"><?php echo esc_html($total); ?></div>
            <div class="stat-label">All Requests</div>
        </div>
        <div class="gti-stat-card stat-info">
            <div class="stat-value"><?php echo esc_html($status_count_map['new'] ?? 0); ?></div>
            <div class="stat-label">New</div>
        </div>
        <div class="gti-stat-card stat-warning">
            <div class="stat-value"><?php echo esc_html($status_count_map['processing'] ?? 0); ?></div>
            <div class="stat-label">Processing</div>
        </div>
        <div class="gti-stat-card stat-info">
            <div class="stat-value"><?php echo esc_html($status_count_map['proposal_sent'] ?? 0); ?></div>
            <div class="stat-label">Proposal Sent</div>
        </div>
        <div class="gti-stat-card stat-neutral">
            <div class="stat-value"><?php echo esc_html($status_count_map['closed'] ?? 0); ?></div>
            <div class="stat-label">Closed</div>
        </div>
    </div>
    
    <!-- Filter Bar -->
    <form class="gti-filter-bar gti-filter-form" method="get">
        <input type="hidden" name="page" value="gti-requests">
        
        <div class="gti-form-group">
            <input type="text" name="search" class="gti-form-control" 
                   placeholder="Search requests..." value="<?php echo esc_attr($search); ?>">
        </div>
        
        <div class="gti-form-group">
            <select name="status" class="gti-form-control">
                <option value="">All Status</option>
                <option value="new" <?php selected($status, 'new'); ?>>New</option>
                <option value="processing" <?php selected($status, 'processing'); ?>>Processing</option>
                <option value="proposal_sent" <?php selected($status, 'proposal_sent'); ?>>Proposal Sent</option>
                <option value="closed" <?php selected($status, 'closed'); ?>>Closed</option>
            </select>
        </div>
        
        <button type="submit" class="gti-btn gti-btn-primary">Search</button>
        <a href="<?php echo admin_url('admin.php?page=gti-requests'); ?>" class="gti-btn gti-btn-secondary">Reset</a>
    </form>
    
    <!-- Requests Table -->
    <div class="gti-table-wrap">
        <table class="gti-table">
            <thead>
                <tr>
                    <th>Request ID</th>
                    <th>Customer</th>
                    <th>Equipment</th>
                    <th>Quantity</th>
                    <th>Location</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th style="width: 100px; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($requests)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px;">
                            <div class="gti-empty-state">
                                <div class="gti-empty-state-icon">📋</div>
                                <div class="gti-empty-state-title">No requests found</div>
                                <div class="gti-empty-state-text">
                                    <?php if ($search || $status): ?>
                                        Try adjusting your filters
                                    <?php else: ?>
                                        No equipment requests yet
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($requests as $request): ?>
                        <tr>
                            <td><strong><?php echo esc_html($request->request_id); ?></strong></td>
                            <td>
                                <?php echo esc_html($request->customer_name); ?>
                                <br><small style="color: #6b7280;"><?php echo esc_html($request->customer_company); ?></small>
                            </td>
                            <td><?php echo esc_html($request->equipment); ?></td>
                            <td><?php echo esc_html($request->quantity); ?> Units</td>
                            <td><?php echo esc_html($request->location); ?></td>
                            <td><?php echo GTI_Helpers::get_status_badge($request->status); ?></td>
                            <td><?php echo esc_html(GTI_Helpers::format_date($request->request_date)); ?></td>
                            <td style="text-align: right;">
                                <div class="gti-dropdown">
                                    <button type="button" class="gti-btn gti-btn-sm gti-dropdown-toggle">•••</button>
                                    <div class="gti-dropdown-menu">
                                        <a href="<?php echo admin_url('admin.php?page=gti-request-detail&id=' . $request->id); ?>" class="gti-dropdown-item">
                                            View Details
                                        </a>
                                        <select class="gti-dropdown-item gti-status-select" 
                                                data-id="<?php echo esc_attr($request->id); ?>"
                                                data-action="gti_update_request_status"
                                                style="width: 100%; border: none; background: none; cursor: pointer;">
                                            <option value="new" <?php selected($request->status, 'new'); ?>>New</option>
                                            <option value="processing" <?php selected($request->status, 'processing'); ?>>Processing</option>
                                            <option value="proposal_sent" <?php selected($request->status, 'proposal_sent'); ?>>Proposal Sent</option>
                                            <option value="closed" <?php selected($request->status, 'closed'); ?>>Closed</option>
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
                    Showing <?php echo esc_html($offset + 1); ?>-<?php echo esc_html(min($offset + $per_page, $total)); ?> of <?php echo esc_html($total); ?> requests
                </div>
                <div class="gti-pagination">
                    <?php if ($paged > 1): ?>
                        <a href="?page=gti-requests&paged=<?php echo esc_html($paged - 1); ?>">←</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $paged): ?>
                            <span class="current"><?php echo esc_html($i); ?></span>
                        <?php else: ?>
                            <a href="?page=gti-requests&paged=<?php echo esc_html($i); ?>"><?php echo esc_html($i); ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($paged < $total_pages): ?>
                        <a href="?page=gti-requests&paged=<?php echo esc_html($paged + 1); ?>">→</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>