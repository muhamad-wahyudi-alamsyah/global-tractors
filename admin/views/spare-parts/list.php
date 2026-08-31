<?php
/**
 * Spare Parts List View
 *
 * @package Global_Tractors
 */

defined('ABSPATH') || exit;

// Get filters
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$category = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';
$brand = isset($_GET['brand']) ? sanitize_text_field($_GET['brand']) : '';
$status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';

// Pagination
$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 20;
$offset = ($paged - 1) * $per_page;

// Build filters
$filters = array();
if ($search) $filters['search'] = $search;
if ($category) $filters['category'] = $category;
if ($brand) $filters['brand'] = $brand;
if ($status) $filters['status'] = $status;

// Get data
$db = GTI_Database::get_instance();
$spare_parts = $db->get_spare_parts($filters, $per_page, $offset);
$total = $db->get_spare_parts_count($filters);
$total_pages = ceil($total / $per_page);

// Get status counts
$status_counts = $db->get_spare_parts_by_status();
$status_count_map = array();
foreach ($status_counts as $sc) {
    $status_count_map[$sc->status] = $sc->count;
}

// Get inventory value
global $wpdb;
$inventory_value = $wpdb->get_var("SELECT SUM(stock * unit_price) FROM {$wpdb->prefix}gti_spare_parts");

// Get categories and brands
$categories = $wpdb->get_col("SELECT DISTINCT category FROM {$wpdb->prefix}gti_spare_parts WHERE category != '' ORDER BY category");
$brands = $wpdb->get_col("SELECT DISTINCT brand FROM {$wpdb->prefix}gti_spare_parts WHERE brand != '' ORDER BY brand");
?>

<div class="gti-admin-wrap">
    <!-- Page Header -->
    <div class="gti-page-header">
        <div>
            <h1 class="gti-page-title">Spare Parts</h1>
        </div>
        <a href="<?php echo admin_url('admin.php?page=gti-spare-parts-add'); ?>" class="gti-btn gti-btn-primary">
            + Add New Spare Part
        </a>
    </div>
    
    <!-- Statistics Cards -->
    <div class="gti-stats-row">
        <div class="gti-stat-card stat-primary">
            <div class="stat-value"><?php echo esc_html($total); ?></div>
            <div class="stat-label">Total Parts</div>
        </div>
        <div class="gti-stat-card stat-success">
            <div class="stat-value"><?php echo esc_html($status_count_map['in_stock'] ?? 0); ?></div>
            <div class="stat-label">In Stock</div>
        </div>
        <div class="gti-stat-card stat-warning">
            <div class="stat-value"><?php echo esc_html($status_count_map['low_stock'] ?? 0); ?></div>
            <div class="stat-label">Low Stock</div>
        </div>
        <div class="gti-stat-card stat-danger">
            <div class="stat-value"><?php echo esc_html($status_count_map['out_of_stock'] ?? 0); ?></div>
            <div class="stat-label">Out of Stock</div>
        </div>
        <div class="gti-stat-card stat-info">
            <div class="stat-value"><?php echo esc_html(GTI_Helpers::format_currency($inventory_value ?? 0)); ?></div>
            <div class="stat-label">Inventory Value</div>
        </div>
    </div>
    
    <!-- Filter Bar -->
    <form class="gti-filter-bar gti-filter-form" method="get">
        <input type="hidden" name="page" value="gti-spare-parts">
        
        <div class="gti-form-group">
            <input type="text" name="search" class="gti-form-control" 
                   placeholder="Search spare parts..." value="<?php echo esc_attr($search); ?>">
        </div>
        
        <div class="gti-form-group">
            <select name="category" class="gti-form-control">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo esc_attr($cat); ?>" <?php selected($category, $cat); ?>>
                        <?php echo esc_html($cat); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="gti-form-group">
            <select name="brand" class="gti-form-control">
                <option value="">All Brands</option>
                <?php foreach ($brands as $b): ?>
                    <option value="<?php echo esc_attr($b); ?>" <?php selected($brand, $b); ?>>
                        <?php echo esc_html($b); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="gti-form-group">
            <select name="status" class="gti-form-control">
                <option value="">All Status</option>
                <option value="in_stock" <?php selected($status, 'in_stock'); ?>>In Stock</option>
                <option value="low_stock" <?php selected($status, 'low_stock'); ?>>Low Stock</option>
                <option value="out_of_stock" <?php selected($status, 'out_of_stock'); ?>>Out of Stock</option>
            </select>
        </div>
        
        <button type="submit" class="gti-btn gti-btn-primary">Search</button>
        <a href="<?php echo admin_url('admin.php?page=gti-spare-parts'); ?>" class="gti-btn gti-btn-secondary">Reset</a>
    </form>
    
    <!-- Spare Parts Table -->
    <div class="gti-table-wrap">
        <table class="gti-table">
            <thead>
                <tr>
                    <th style="width: 60px;">Image</th>
                    <th>Part Number</th>
                    <th>Part Name</th>
                    <th>Category</th>
                    <th>Brand</th>
                    <th>Stock</th>
                    <th>Unit Price</th>
                    <th>Status</th>
                    <th style="width: 100px; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($spare_parts)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 40px;">
                            <div class="gti-empty-state">
                                <div class="gti-empty-state-icon">🔧</div>
                                <div class="gti-empty-state-title">No spare parts found</div>
                                <div class="gti-empty-state-text">
                                    <?php if ($search || $category || $brand || $status): ?>
                                        Try adjusting your filters
                                    <?php else: ?>
                                        Get started by adding your first spare part
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($spare_parts as $part): ?>
                        <tr>
                            <td>
                                <img src="<?php echo esc_url(GTI_Helpers::get_image_url($part->image)); ?>" 
                                     alt="<?php echo esc_attr($part->name); ?>"
                                     class="gti-table-img">
                            </td>
                            <td><strong><?php echo esc_html($part->part_number); ?></strong></td>
                            <td><?php echo esc_html($part->name); ?></td>
                            <td><?php echo esc_html($part->category); ?></td>
                            <td><?php echo esc_html($part->brand); ?></td>
                            <td>
                                <strong><?php echo esc_html($part->stock); ?></strong>
                                <?php if ($part->stock <= $part->minimum_stock && $part->stock > 0): ?>
                                    <br><small style="color: #f59e0b;">Min: <?php echo esc_html($part->minimum_stock); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html(GTI_Helpers::format_currency($part->unit_price)); ?></td>
                            <td><?php echo GTI_Helpers::get_status_badge($part->status); ?></td>
                            <td style="text-align: right;">
                                <div class="gti-dropdown">
                                    <button type="button" class="gti-btn gti-btn-sm gti-dropdown-toggle">•••</button>
                                    <div class="gti-dropdown-menu">
                                        <a href="<?php echo admin_url('admin.php?page=gti-spare-parts-edit&id=' . $part->id); ?>" class="gti-dropdown-item">
                                            Edit
                                        </a>
                                        <a href="#" class="gti-dropdown-item gti-confirm-delete"
                                           data-action="gti_delete_spare_part"
                                           data-id="<?php echo esc_attr($part->id); ?>"
                                           data-message="Are you sure you want to delete this spare part?">
                                            Delete
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
                    Showing <?php echo esc_html($offset + 1); ?>-<?php echo esc_html(min($offset + $per_page, $total)); ?> of <?php echo esc_html($total); ?> parts
                </div>
                <div class="gti-pagination">
                    <?php if ($paged > 1): ?>
                        <a href="?page=gti-spare-parts&paged=<?php echo esc_html($paged - 1); ?>">←</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $paged): ?>
                            <span class="current"><?php echo esc_html($i); ?></span>
                        <?php else: ?>
                            <a href="?page=gti-spare-parts&paged=<?php echo esc_html($i); ?>"><?php echo esc_html($i); ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($paged < $total_pages): ?>
                        <a href="?page=gti-spare-parts&paged=<?php echo esc_html($paged + 1); ?>">→</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>