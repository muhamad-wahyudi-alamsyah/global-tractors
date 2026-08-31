<?php
/**
 * Equipment List View
 *
 * @package Global_Tractors
 */

defined('ABSPATH') || exit;

// Get current filters
$type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : 'used';
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$category = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';
$brand = isset($_GET['brand']) ? sanitize_text_field($_GET['brand']) : '';
$status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$condition = isset($_GET['condition']) ? sanitize_text_field($_GET['condition']) : '';

// Pagination
$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 20;
$offset = ($paged - 1) * $per_page;

// Build filters
$filters = array('type' => $type);
if ($search) $filters['search'] = $search;
if ($category) $filters['category'] = $category;
if ($brand) $filters['brand'] = $brand;
if ($status) $filters['status'] = $status;
if ($condition) $filters['condition_status'] = $condition;

// Get data
$db = GTI_Database::get_instance();
$equipment = $db->get_equipment($filters, $per_page, $offset);
$total = $db->get_equipment_count($filters);
$total_pages = ceil($total / $per_page);

// Get status counts
$status_counts = $db->get_equipment_by_status($type);
$status_count_map = array();
foreach ($status_counts as $sc) {
    $status_count_map[$sc->status] = $sc->count;
}

// Categories and brands for filters
global $wpdb;
$categories = $wpdb->get_col("SELECT DISTINCT category FROM {$wpdb->prefix}gti_equipment WHERE type = '{$type}' AND category != '' ORDER BY category");
$brands = $wpdb->get_col("SELECT DISTINCT brand FROM {$wpdb->prefix}gti_equipment WHERE type = '{$type}' AND brand != '' ORDER BY brand");

// Page title
$page_title = $type === 'used' ? 'Used Equipment' : 'Rental Equipment';
?>

<div class="gti-admin-wrap">
    <!-- Page Header -->
    <div class="gti-page-header">
        <div>
            <h1 class="gti-page-title"><?php echo esc_html($page_title); ?></h1>
        </div>
        <a href="<?php echo admin_url('admin.php?page=gti-equipment-add&type=' . $type); ?>" class="gti-btn gti-btn-primary">
            + Add New Equipment
        </a>
    </div>
    
    <!-- Statistics Cards -->
    <div class="gti-stats-row">
        <div class="gti-stat-card stat-primary">
            <div class="stat-value"><?php echo esc_html($total); ?></div>
            <div class="stat-label">Total Equipment</div>
        </div>
        <div class="gti-stat-card stat-success">
            <div class="stat-value"><?php echo esc_html($status_count_map['available'] ?? 0); ?></div>
            <div class="stat-label">Available</div>
        </div>
        <?php if ($type === 'used'): ?>
            <div class="gti-stat-card stat-danger">
                <div class="stat-value"><?php echo esc_html($status_count_map['sold'] ?? 0); ?></div>
                <div class="stat-label">Sold</div>
            </div>
        <?php else: ?>
            <div class="gti-stat-card stat-info">
                <div class="stat-value"><?php echo esc_html($status_count_map['rented'] ?? 0); ?></div>
                <div class="stat-label">Rented</div>
            </div>
        <?php endif; ?>
        <div class="gti-stat-card stat-warning">
            <div class="stat-value"><?php echo esc_html($status_count_map['maintenance'] ?? 0); ?></div>
            <div class="stat-label">Maintenance</div>
        </div>
    </div>
    
    <!-- Filter Bar -->
    <form class="gti-filter-bar gti-filter-form" method="get">
        <input type="hidden" name="page" value="gti-equipment">
        <input type="hidden" name="type" value="<?php echo esc_attr($type); ?>">
        
        <div class="gti-form-group">
            <input type="text" 
                   name="search" 
                   class="gti-form-control" 
                   placeholder="Search equipment..." 
                   value="<?php echo esc_attr($search); ?>">
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
        
        <?php if ($type === 'used'): ?>
            <div class="gti-form-group">
                <select name="condition" class="gti-form-control">
                    <option value="">All Conditions</option>
                    <option value="excellent" <?php selected($condition, 'excellent'); ?>>Excellent</option>
                    <option value="good" <?php selected($condition, 'good'); ?>>Good</option>
                    <option value="fair" <?php selected($condition, 'fair'); ?>>Fair</option>
                    <option value="poor" <?php selected($condition, 'poor'); ?>>Poor</option>
                </select>
            </div>
        <?php endif; ?>
        
        <div class="gti-form-group">
            <select name="status" class="gti-form-control">
                <option value="">All Status</option>
                <option value="available" <?php selected($status, 'available'); ?>>Available</option>
                <?php if ($type === 'used'): ?>
                    <option value="sold" <?php selected($status, 'sold'); ?>>Sold</option>
                <?php else: ?>
                    <option value="rented" <?php selected($status, 'rented'); ?>>Rented</option>
                <?php endif; ?>
                <option value="reserved" <?php selected($status, 'reserved'); ?>>Reserved</option>
                <option value="maintenance" <?php selected($status, 'maintenance'); ?>>Maintenance</option>
            </select>
        </div>
        
        <button type="submit" class="gti-btn gti-btn-primary">Search</button>
        <a href="<?php echo admin_url('admin.php?page=gti-equipment&type=' . $type); ?>" class="gti-btn gti-btn-secondary">Reset</a>
    </form>
    
    <!-- Equipment Table -->
    <div class="gti-table-wrap">
        <table class="gti-table">
            <thead>
                <tr>
                    <th style="width: 80px;">Image</th>
                    <th>Equipment</th>
                    <th>Brand</th>
                    <th>Model</th>
                    <th>Year</th>
                    <th>Condition</th>
                    <th>Status</th>
                    <th style="text-align: right;">Price</th>
                    <th style="width: 100px; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($equipment)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 40px;">
                            <div class="gti-empty-state">
                                <div class="gti-empty-state-icon">📦</div>
                                <div class="gti-empty-state-title">No equipment found</div>
                                <div class="gti-empty-state-text">
                                    <?php if ($search || $category || $brand || $status || $condition): ?>
                                        Try adjusting your filters
                                    <?php else: ?>
                                        Get started by adding your first equipment
                                    <?php endif; ?>
                                </div>
                                <?php if (!$search && !$category && !$brand && !$status && !$condition): ?>
                                    <a href="<?php echo admin_url('admin.php?page=gti-equipment-add&type=' . $type); ?>" class="gti-btn gti-btn-primary">
                                        + Add Equipment
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($equipment as $item): ?>
                        <tr>
                            <td>
                                <img src="<?php echo esc_url(GTI_Helpers::get_image_url($item->main_image)); ?>" 
                                     alt="<?php echo esc_attr($item->name); ?>"
                                     class="gti-table-img">
                            </td>
                            <td>
                                <strong><?php echo esc_html($item->name); ?></strong>
                                <br><small style="color: #6b7280;"><?php echo esc_html($item->equipment_code); ?></small>
                            </td>
                            <td><?php echo esc_html($item->brand); ?></td>
                            <td><?php echo esc_html($item->model); ?></td>
                            <td><?php echo esc_html($item->year); ?></td>
                            <td><?php echo GTI_Helpers::get_condition_badge($item->condition_status); ?></td>
                            <td><?php echo GTI_Helpers::get_status_badge($item->status); ?></td>
                            <td style="text-align: right;">
                                <?php echo esc_html(GTI_Helpers::format_currency($item->price)); ?>
                            </td>
                            <td style="text-align: right;">
                                <div class="gti-dropdown">
                                    <button type="button" class="gti-btn gti-btn-sm gti-dropdown-toggle">•••</button>
                                    <div class="gti-dropdown-menu">
                                        <a href="<?php echo admin_url('admin.php?page=gti-equipment-edit&id=' . $item->id); ?>" class="gti-dropdown-item">
                                            Edit
                                        </a>
                                        <a href="#" class="gti-dropdown-item gti-confirm-delete" 
                                           data-action="gti_delete_equipment"
                                           data-id="<?php echo esc_attr($item->id); ?>"
                                           data-message="Are you sure you want to delete this equipment?">
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
                    Showing <?php echo esc_html($offset + 1); ?>-<?php echo esc_html(min($offset + $per_page, $total)); ?> of <?php echo esc_html($total); ?> equipment
                </div>
                <div class="gti-pagination">
                    <?php if ($paged > 1): ?>
                        <a href="?page=gti-equipment&type=<?php echo esc_attr($type); ?>&paged=<?php echo esc_html($paged - 1); ?>">←</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $paged): ?>
                            <span class="current"><?php echo esc_html($i); ?></span>
                        <?php else: ?>
                            <a href="?page=gti-equipment&type=<?php echo esc_attr($type); ?>&paged=<?php echo esc_html($i); ?>"><?php echo esc_html($i); ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($paged < $total_pages): ?>
                        <a href="?page=gti-equipment&type=<?php echo esc_attr($type); ?>&paged=<?php echo esc_html($paged + 1); ?>">→</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>