<?php
/**
 * Quotations List View
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
$quotations = $db->get_quotations($filters, $per_page, $offset);
$total = $db->get_quotations_count($filters);
$total_pages = ceil($total / $per_page);

// Get status counts
global $wpdb;
$status_counts = $wpdb->get_results(
    "SELECT status, COUNT(*) as count FROM {$wpdb->prefix}gti_quotations GROUP BY status"
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
            <h1 class="gti-page-title">Request Quotation</h1>
        </div>
        <a href="<?php echo admin_url('admin.php?page=gti-quotations-add'); ?>" class="gti-btn gti-btn-primary">
            + Add New Quotation
        </a>
    </div>
    
    <!-- Statistics Cards -->
    <div class="gti-stats-row">
        <div class="gti-stat-card stat-info">
            <div class="stat-value"><?php echo esc_html($status_count_map['new'] ?? 0); ?></div>
            <div class="stat-label">New</div>
        </div>
        <div class="gti-stat-card stat-warning">
            <div class="stat-value"><?php echo esc_html($status_count_map['processing'] ?? 0); ?></div>
            <div class="stat-label">Processing</div>
        </div>
        <div class="gti-stat-card stat-warning">
            <div class="stat-value"><?php echo esc_html($status_count_map['waiting_customer'] ?? 0); ?></div>
            <div class="stat-label">Waiting Customer</div>
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
        <input type="hidden" name="page" value="gti-quotations">
        
        <div class="gti-form-group">
            <input type="text" name="search" class="gti-form-control" 
                   placeholder="Search quotations..." value="<?php echo esc_attr($search); ?>">
        </div>
        
        <div class="gti-form-group">
            <select name="status" class="gti-form-control">
                <option value="">All Status</option>
                <option value="new" <?php selected($status, 'new'); ?>>New</option>
                <option value="processing" <?php selected($status, 'processing'); ?>>Processing</option>
                <option value="waiting_customer" <?php selected($status, 'waiting_customer'); ?>>Waiting Customer</option>
                <option value="approved" <?php selected($status, 'approved'); ?>>Approved</option>
                <option value="rejected" <?php selected($status, 'rejected'); ?>>Rejected</option>
                <option value="completed" <?php selected($status, 'completed'); ?>>Completed</option>
            </select>
        </div>
        
        <button type="submit" class="gti-btn gti-btn-primary">Search</button>
        <a href="<?php echo admin_url('admin.php?page=gti-quotations'); ?>" class="gti-btn gti-btn-secondary">Reset</a>
    </form>
    
    <!-- Quotations Table -->
    <div class="gti-table-wrap">
        <table class="gti-table">
            <thead>
                <tr>
                    <th>Quotation ID</th>
                    <th>Customer</th>
                    <th>Company</th>
                    <th>Items</th>
                    <th>Sales PIC</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th style="width: 100px; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($quotations)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px;">
                            <div class="gti-empty-state">
                                <div class="gti-empty-state-icon">📝</div>
                                <div class="gti-empty-state-title">No quotations found</div>
                                <div class="gti-empty-state-text">
                                    <?php if ($search || $status): ?>
                                        Try adjusting your filters
                                    <?php else: ?>
                                        No quotations yet
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($quotations as $quote): ?>
                        <tr>
                            <td><strong><?php echo esc_html($quote->quotation_id); ?></strong></td>
                            <td><?php echo esc_html($quote->customer_name); ?></td>
                            <td><?php echo esc_html($quote->customer_company); ?></td>
                            <td>
                                <?php
                                $items = json_decode($quote->items, true);
                                echo esc_html(count($items) . ' items');
                                ?>
                            </td>
                            <td><?php echo esc_html($quote->sales_pic); ?></td>
                            <td><?php echo GTI_Helpers::get_status_badge($quote->status); ?></td>
                            <td><?php echo esc_html(GTI_Helpers::format_date($quote->request_date)); ?></td>
                            <td style="text-align: right;">
                                <div class="gti-dropdown">
                                    <button type="button" class="gti-btn gti-btn-sm gti-dropdown-toggle">•••</button>
                                    <div class="gti-dropdown-menu">
                                        <a href="<?php echo admin_url('admin.php?page=gti-quotation-detail&id=' . $quote->id); ?>" class="gti-dropdown-item">
                                            View Details
                                        </a>
                                        <a href="<?php echo admin_url('admin.php?page=gti-quotations-edit&id=' . $quote->id); ?>" class="gti-dropdown-item">
                                            Edit
                                        </a>
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
                    Showing <?php echo esc_html($offset + 1); ?>-<?php echo esc_html(min($offset + $per_page, $total)); ?> of <?php echo esc_html($total); ?> quotations
                </div>
                <div class="gti-pagination">
                    <?php if ($paged > 1): ?>
                        <a href="?page=gti-quotations&paged=<?php echo esc_html($paged - 1); ?>">←</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $paged): ?>
                            <span class="current"><?php echo esc_html($i); ?></span>
                        <?php else: ?>
                            <a href="?page=gti-quotations&paged=<?php echo esc_html($i); ?>"><?php echo esc_html($i); ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($paged < $total_pages): ?>
                        <a href="?page=gti-quotations&paged=<?php echo esc_html($paged + 1); ?>">→</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>