<?php
/**
 * Customers List View
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
$customers = $db->get_customers($filters, $per_page, $offset);
$total = $db->get_customers_count($filters);
$total_pages = ceil($total / $per_page);

// Get status counts
global $wpdb;
$active_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}gti_customers WHERE status = 'active'");
$new_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}gti_customers WHERE registered_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
?>

<div class="gti-admin-wrap">
    <!-- Page Header -->
    <div class="gti-page-header">
        <div>
            <h1 class="gti-page-title">Customers</h1>
        </div>
        <a href="<?php echo admin_url('admin.php?page=gti-customers-add'); ?>" class="gti-btn gti-btn-primary">
            + Add New Customer
        </a>
    </div>
    
    <!-- Statistics Cards -->
    <div class="gti-stats-row">
        <div class="gti-stat-card stat-primary">
            <div class="stat-value"><?php echo esc_html($total); ?></div>
            <div class="stat-label">Total Customers</div>
        </div>
        <div class="gti-stat-card stat-success">
            <div class="stat-value"><?php echo esc_html($active_count ?? 0); ?></div>
            <div class="stat-label">Active Customers</div>
        </div>
        <div class="gti-stat-card stat-info">
            <div class="stat-value"><?php echo esc_html($new_count ?? 0); ?></div>
            <div class="stat-label">New (30 days)</div>
        </div>
    </div>
    
    <!-- Filter Bar -->
    <form class="gti-filter-bar gti-filter-form" method="get">
        <input type="hidden" name="page" value="gti-customers">
        
        <div class="gti-form-group">
            <input type="text" name="search" class="gti-form-control" 
                   placeholder="Search customers..." value="<?php echo esc_attr($search); ?>">
        </div>
        
        <div class="gti-form-group">
            <select name="status" class="gti-form-control">
                <option value="">All Status</option>
                <option value="active" <?php selected($status, 'active'); ?>>Active</option>
                <option value="inactive" <?php selected($status, 'inactive'); ?>>Inactive</option>
            </select>
        </div>
        
        <button type="submit" class="gti-btn gti-btn-primary">Search</button>
        <a href="<?php echo admin_url('admin.php?page=gti-customers'); ?>" class="gti-btn gti-btn-secondary">Reset</a>
    </form>
    
    <!-- Customers Table -->
    <div class="gti-table-wrap">
        <table class="gti-table">
            <thead>
                <tr>
                    <th>Customer ID</th>
                    <th>Name</th>
                    <th>Company</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Industry</th>
                    <th>Status</th>
                    <th style="width: 100px; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customers)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px;">
                            <div class="gti-empty-state">
                                <div class="gti-empty-state-icon">👥</div>
                                <div class="gti-empty-state-title">No customers found</div>
                                <div class="gti-empty-state-text">
                                    <?php if ($search || $status): ?>
                                        Try adjusting your filters
                                    <?php else: ?>
                                        Get started by adding your first customer
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($customers as $customer): ?>
                        <tr>
                            <td><strong><?php echo esc_html($customer->customer_id); ?></strong></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=gti-customer-detail&id=' . $customer->id); ?>">
                                    <?php echo esc_html($customer->name); ?>
                                </a>
                            </td>
                            <td><?php echo esc_html($customer->company); ?></td>
                            <td><?php echo esc_html($customer->email); ?></td>
                            <td><?php echo esc_html($customer->phone); ?></td>
                            <td><?php echo esc_html($customer->industry); ?></td>
                            <td><?php echo GTI_Helpers::get_status_badge($customer->status); ?></td>
                            <td style="text-align: right;">
                                <div class="gti-dropdown">
                                    <button type="button" class="gti-btn gti-btn-sm gti-dropdown-toggle">•••</button>
                                    <div class="gti-dropdown-menu">
                                        <a href="<?php echo admin_url('admin.php?page=gti-customer-detail&id=' . $customer->id); ?>" class="gti-dropdown-item">
                                            View Profile
                                        </a>
                                        <a href="<?php echo admin_url('admin.php?page=gti-customers-edit&id=' . $customer->id); ?>" class="gti-dropdown-item">
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
                    Showing <?php echo esc_html($offset + 1); ?>-<?php echo esc_html(min($offset + $per_page, $total)); ?> of <?php echo esc_html($total); ?> customers
                </div>
                <div class="gti-pagination">
                    <?php if ($paged > 1): ?>
                        <a href="?page=gti-customers&paged=<?php echo esc_html($paged - 1); ?>">←</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $paged): ?>
                            <span class="current"><?php echo esc_html($i); ?></span>
                        <?php else: ?>
                            <a href="?page=gti-customers&paged=<?php echo esc_html($i); ?>"><?php echo esc_html($i); ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($paged < $total_pages): ?>
                        <a href="?page=gti-customers&paged=<?php echo esc_html($paged + 1); ?>">→</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>