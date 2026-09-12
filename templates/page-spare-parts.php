<?php
/**
 * Template: Spare Parts (/dashboard/spare-parts)
 * @package global-tractors
 */
if (!defined('ABSPATH')) exit;

// Auto-generate spare part code
global $wpdb;

$sp_table = $wpdb->prefix . 'gti_spare_parts';

$sp_year = date('Y');

$sp_cat_abbrev_map = [];

foreach (gti_spare_part_categories() as $sp_cat_name => $sp_cat_meta) {

    $sp_cat_abbrev_map[$sp_cat_name] = $sp_cat_meta['abbr'];

}

$gti_next_sp_code = 'SP-GEN-' . $sp_year . '-001';

// Handed to the page script as gtiPageData.categoryMap, rather than
// interpolated into an inline <script> (PRD §13.8).
gti_page_data( array( 'categoryMap' => $sp_cat_abbrev_map ) );

// Database queries
global $wpdb;
$table = $wpdb->prefix . 'gti_spare_parts';

// Filters
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$category = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';
$brand = isset($_GET['brand']) ? sanitize_text_field($_GET['brand']) : '';
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$supplier_filter = isset($_GET['supplier']) ? sanitize_text_field($_GET['supplier']) : '';

// Pagination — 'page_num' is used instead of 'paged' because WordPress reserves 'paged'
// Filters carried across pagination links.
$query_params = array_filter( array(
    'gti_page' => 'spare-parts',
    'search'   => $search,
    'category' => $category,
    'brand'    => $brand,
    'status'   => $status_filter,
    'supplier' => $supplier_filter,
) );

$paged = isset($_GET['page_num']) ? max(1, intval($_GET['page_num'])) : 1;
$per_page = 10;
$offset = ($paged - 1) * $per_page;

// Build WHERE clause
$where = "WHERE 1=1";
$params = array();

if ($search) {
    $where .= " AND (name LIKE %s OR part_number LIKE %s OR brand LIKE %s)";
    $search_term = '%' . $wpdb->esc_like($search) . '%';
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}
if ($category) {
    $where .= " AND category = %s";
    $params[] = $category;
}
if ($brand) {
    $where .= " AND brand = %s";
    $params[] = $brand;
}
if ($status_filter) {
    $where .= " AND status = %s";
    $params[] = $status_filter;
}
if ($supplier_filter) {
    $where .= " AND supplier = %s";
    $params[] = $supplier_filter;
}

// Get total count
$count_sql = "SELECT COUNT(*) FROM {$table} {$where}";
$total = !empty($params) ? (int) $wpdb->get_var($wpdb->prepare($count_sql, $params)) : (int) $wpdb->get_var($count_sql);
$total_pages = (int) ceil($total / $per_page);

// Get spare parts
$spare_parts = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$table} {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d",
    array_merge($params, array($per_page, $offset))
));

// Status counts
$status_counts = $wpdb->get_results("SELECT status, COUNT(*) as count FROM {$table} GROUP BY status");
$status_count_map = array();
foreach ($status_counts as $sc) {
    $status_count_map[$sc->status] = (int) $sc->count;
}
$total_parts     = array_sum($status_count_map);
$in_stock        = isset($status_count_map['in_stock']) ? $status_count_map['in_stock'] : 0;
$low_stock       = isset($status_count_map['low_stock']) ? $status_count_map['low_stock'] : 0;
$out_of_stock    = isset($status_count_map['out_of_stock']) ? $status_count_map['out_of_stock'] : 0;
$draft_count     = isset($status_count_map['draft']) ? $status_count_map['draft'] : 0;
$inventory_value = (float) $wpdb->get_var("SELECT SUM(stock * unit_price) FROM {$table} WHERE status != 'draft'");

// Filter options
$categories = $wpdb->get_col("SELECT DISTINCT category FROM {$table} WHERE category != '' ORDER BY category");
$brands = $wpdb->get_col("SELECT DISTINCT brand FROM {$table} WHERE brand != '' ORDER BY brand");
$suppliers = $wpdb->get_col("SELECT DISTINCT supplier FROM {$table} WHERE supplier IS NOT NULL AND supplier != '' ORDER BY supplier");

// Format currency inline to avoid redeclaration errors
$_gti_sp_fmt = function($amount) {
    return 'Rp ' . number_format((float)$amount, 0, ',', '.');
};

// Status label helper
$_gti_sp_status_label = function($status) {
    $map = array(
        'in_stock'     => 'In Stock',
        'low_stock'    => 'Low Stock',
        'out_of_stock' => 'Out of Stock',
        'draft'        => 'Draft',
    );
    return isset($map[$status]) ? $map[$status] : ucfirst(str_replace('_', ' ', (string) $status));
};

// Status badge helper
$_gti_sp_status_badge = function($status) use ($_gti_sp_status_label) {
    $map = array(
        'in_stock'     => 'available',
        'low_stock'    => 'reserved',
        'out_of_stock' => 'sold',
        'draft'        => 'draft',
    );
    $class = isset($map[$status]) ? $map[$status] : 'available';
    return '<span class="gti-badge-status ' . esc_attr($class) . '">' . esc_html($_gti_sp_status_label($status)) . '</span>';
};

gti_dashboard_open( array(
    'page'     => 'spare-parts',
    'title'    => 'Spare Parts',
    'subtitle' => 'Manage spare parts stock ⚙️',
    'cap'      => 'gti_manage_spare_parts',
    'js'       => array( 'spare-parts' ),
) );
?>


            <!-- Content -->
            <div class="gti-content">
                <div class="gti-content-left">
                <!-- Spare Parts Stats -->
                <div class="gti-ue-stats-row">
                    <div class="gti-ue-stat-card">
                        <div class="gti-ue-stat-icon"><i class="fas fa-cogs"></i></div>
                        <div class="gti-ue-stat-info">
                            <p class="gti-ue-stat-label">Total Parts</p>
                            <p class="gti-ue-stat-value"><?php echo esc_html($total_parts); ?></p>
                        </div>
                    </div>
                    <div class="gti-ue-stat-card">
                        <div class="gti-ue-stat-icon available"><i class="fas fa-check-circle"></i></div>
                        <div class="gti-ue-stat-info">
                            <p class="gti-ue-stat-label">In Stock</p>
                            <p class="gti-ue-stat-value"><?php echo esc_html($in_stock); ?></p>
                        </div>
                    </div>
                    <div class="gti-ue-stat-card">
                        <div class="gti-ue-stat-icon reserved"><i class="fas fa-exclamation-triangle"></i></div>
                        <div class="gti-ue-stat-info">
                            <p class="gti-ue-stat-label">Low Stock</p>
                            <p class="gti-ue-stat-value"><?php echo esc_html($low_stock); ?></p>
                        </div>
                    </div>
                    <div class="gti-ue-stat-card">
                        <div class="gti-ue-stat-icon sold"><i class="fas fa-times-circle"></i></div>
                        <div class="gti-ue-stat-info">
                            <p class="gti-ue-stat-label">Out of Stock</p>
                            <p class="gti-ue-stat-value"><?php echo esc_html($out_of_stock); ?></p>
                        </div>
                    </div>
                    <div class="gti-ue-stat-card">
                        <div class="gti-ue-stat-icon draft"><i class="fas fa-file-pen"></i></div>
                        <div class="gti-ue-stat-info">
                            <p class="gti-ue-stat-label">Draft</p>
                            <p class="gti-ue-stat-value"><?php echo esc_html($draft_count); ?></p>
                        </div>
                    </div>
                    <div class="gti-ue-stat-card">
                        <div class="gti-ue-stat-icon"><i class="fas fa-coins"></i></div>
                        <div class="gti-ue-stat-info">
                            <p class="gti-ue-stat-label">Inventory Value</p>
                            <p class="gti-ue-stat-value"><?php echo esc_html($_gti_sp_fmt($inventory_value)); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Toolbar -->
                <form class="gti-ue-toolbar" method="get">
                    <input type="hidden" name="gti_page" value="spare-parts">
                    <div class="gti-ue-toolbar-left">
                        <div class="gti-ue-search">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" placeholder="Search spare parts..." value="<?php echo esc_attr($search); ?>">
                        </div>
                        <div class="gti-ue-filter">
                            <select name="category" onchange="this.form.submit()">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo esc_attr($cat); ?>" <?php selected($category, $cat); ?>><?php echo esc_html($cat); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="gti-ue-filter">
                            <select name="brand" onchange="this.form.submit()">
                                <option value="">All Brands</option>
                                <?php foreach ($brands as $b): ?>
                                    <option value="<?php echo esc_attr($b); ?>" <?php selected($brand, $b); ?>><?php echo esc_html($b); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="gti-ue-filter">
                            <select name="status" onchange="this.form.submit()">
                                <option value="">All Status</option>
                                <option value="in_stock" <?php selected($status_filter, 'in_stock'); ?>>In Stock</option>
                                <option value="low_stock" <?php selected($status_filter, 'low_stock'); ?>>Low Stock</option>
                                <option value="out_of_stock" <?php selected($status_filter, 'out_of_stock'); ?>>Out of Stock</option>
                                <option value="draft" <?php selected($status_filter, 'draft'); ?>>Draft</option>
                            </select>
                        </div>
                        <div class="gti-ue-filter">
                            <select name="supplier" onchange="this.form.submit()">
                                <option value="">All Suppliers</option>
                                <?php foreach ($suppliers as $sup): ?>
                                    <option value="<?php echo esc_attr($sup); ?>" <?php selected($supplier_filter, $sup); ?>><?php echo esc_html($sup); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <a href="<?php echo esc_url(gti_dashboard_url('spare-parts')); ?>" class="gti-ue-btn-reset">
                            <i class="fas fa-rotate-right"></i> Reset
                        </a>
                    </div>
                    <a href="<?php echo esc_url(gti_dashboard_url('spare-parts/add')); ?>" class="gti-ue-btn-add">
                        <i class="fas fa-plus"></i> Add Spare Part
                    </a>
                </form>

                <!-- Table -->
                <div class="gti-ue-table-card">
                    <table class="gti-ue-table">
                        <thead>
                            <tr>
                                <th class="col-image">Image</th>
                                <th class="col-partnum">Part Number</th>
                                <th class="col-partname">Part Name</th>
                                <th class="col-category">Category</th>
                                <th class="col-brand">Brand</th>
                                <th class="col-stock">Stock</th>
                                <th class="col-price">Unit Price</th>
                                <th class="col-totalval">Total Value</th>
                                <th class="col-status">Status</th>
                                <th class="col-actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($spare_parts)): ?>
                                <tr>
                                    <td colspan="10" style="text-align: center; padding: 60px 20px;">
                                        <div style="color: #9ca3af;">
                                            <i class="fas fa-cogs" style="font-size: 48px; margin-bottom: 16px; display: block;"></i>
                                            <p style="font-size: 16px; font-weight: 500; margin-bottom: 8px;">No spare parts found</p>
                                            <p style="font-size: 14px;">
                                                <?php if ($search || $category || $brand || $status_filter || $supplier_filter): ?>
                                                    Try adjusting your filters
                                                <?php else: ?>
                                                    Get started by adding your first spare part
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($spare_parts as $part): ?>
                                    <tr data-sp-id="<?php echo esc_attr($part->id); ?>" data-sp='<?php echo esc_attr(json_encode($part)); ?>'>
                                        <td class="col-image">
                                            <div class="gti-ue-thumb">
                                                <?php if (!empty($part->image)): ?>
                                                    <img src="<?php echo esc_url($part->image); ?>" alt="<?php echo esc_attr($part->name); ?>">
                                                <?php else: ?>
                                                    <i class="fas fa-cog"></i>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="col-partnum"><strong><?php echo esc_html($part->part_number); ?></strong></td>
                                        <td class="col-partname">
                                            <strong><?php echo esc_html($part->name); ?></strong>
                                            <?php if (!empty($part->supplier)): ?><br><small><?php echo esc_html($part->supplier); ?></small><?php endif; ?>
                                        </td>
                                        <td class="col-category"><?php echo esc_html($part->category); ?></td>
                                        <td class="col-brand"><?php echo esc_html($part->brand); ?></td>
                                        <td class="col-stock">
                                            <strong><?php echo esc_html(number_format((int) $part->stock, 0, ',', '.')); ?></strong><br>
                                            <small>min. <?php echo esc_html(number_format((int) $part->minimum_stock, 0, ',', '.')); ?></small>
                                        </td>
                                        <td class="col-price"><?php echo esc_html($_gti_sp_fmt($part->unit_price)); ?></td>
                                        <td class="col-totalval"><strong><?php echo esc_html($_gti_sp_fmt($part->stock * $part->unit_price)); ?></strong></td>
                                        <td class="col-status"><?php echo $_gti_sp_status_badge($part->status); ?></td>
                                        <td class="col-actions">
                                            <div class="gti-ue-action-menu">
                                                <button class="gti-ue-action-toggle" title="Actions"><i class="fas fa-ellipsis-v"></i></button>
                                                <div class="gti-ue-action-dropdown">
                                                    <a href="#" class="gti-ue-action-item gti-ue-btn-view"><i class="fas fa-eye"></i> View</a>
                                                    <a href="#" class="gti-ue-action-item gti-ue-btn-edit"><i class="fas fa-edit"></i> Edit</a>
                                                    <a href="#" class="gti-ue-action-item delete gti-ue-btn-delete"><i class="fas fa-trash"></i> Delete</a>
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
                        <?php
                        gti_render_pagination( array(
                            'total'    => $total,
                            'per_page' => $per_page,
                            'current'  => $paged,
                            'base_url' => gti_dashboard_url( 'spare-parts' ),
                            'params'   => $query_params,
                        ) );
                        ?>
                        </div>
                    <?php endif; ?>
                </div>
                </div><!-- /.gti-content-left -->

                <!-- Detail Drawer -->
                <div class="gti-drawer-backdrop" id="drawerBackdrop"></div>
                <div class="gti-drawer" id="spDetailDrawer">
        <div class="gti-drawer-header">
            <div class="gti-drawer-header-left">
                <h2 id="drawer-part-number">-</h2>
                <span class="gti-drawer-status" id="drawer-status-badge">In Stock</span>
            </div>
            <button type="button" class="gti-drawer-close" onclick="closeDetailDrawer()"><i class="fas fa-times"></i></button>
        </div>
        <div class="gti-drawer-body">
            <div class="gti-drawer-section">
                <div class="gti-drawer-section-title"><i class="fas fa-cog"></i> Part Information</div>
                <div class="gti-drawer-row"><span class="gti-drawer-label">Part Number</span><span class="gti-drawer-value" id="drawer-partnum">-</span></div>
                <div class="gti-drawer-row"><span class="gti-drawer-label">Part Name</span><span class="gti-drawer-value" id="drawer-name">-</span></div>
                <div class="gti-drawer-row"><span class="gti-drawer-label">Category</span><span class="gti-drawer-value" id="drawer-category">-</span></div>
                <div class="gti-drawer-row"><span class="gti-drawer-label">Brand</span><span class="gti-drawer-value" id="drawer-brand">-</span></div>
                <div class="gti-drawer-row"><span class="gti-drawer-label">Status</span><span class="gti-drawer-value" id="drawer-status-text">-</span></div>
            </div>

            <div class="gti-drawer-section">
                <div class="gti-drawer-section-title"><i class="fas fa-boxes"></i> Inventory</div>
                <div class="gti-drawer-row"><span class="gti-drawer-label">Current Stock</span><span class="gti-drawer-value" id="drawer-stock">-</span></div>
                <div class="gti-drawer-row"><span class="gti-drawer-label">Minimum Stock</span><span class="gti-drawer-value" id="drawer-min-stock">-</span></div>
                <div class="gti-drawer-row"><span class="gti-drawer-label">Stock Level</span><span class="gti-drawer-value" id="drawer-stock-level">-</span></div>
            </div>

            <div class="gti-drawer-section">
                <div class="gti-drawer-section-title"><i class="fas fa-coins"></i> Pricing</div>
                <div class="gti-drawer-row"><span class="gti-drawer-label">Unit Price</span><span class="gti-drawer-value" id="drawer-unit-price">-</span></div>
                <div class="gti-drawer-row"><span class="gti-drawer-label">Total Value</span><span class="gti-drawer-value" id="drawer-total-value">-</span></div>
            </div>

            <div class="gti-drawer-section">
                <div class="gti-drawer-section-title"><i class="fas fa-warehouse"></i> Sourcing &amp; Storage</div>
                <div class="gti-drawer-row"><span class="gti-drawer-label">Supplier</span><span class="gti-drawer-value" id="drawer-supplier">-</span></div>
                <div class="gti-drawer-row"><span class="gti-drawer-label">Location</span><span class="gti-drawer-value" id="drawer-location">-</span></div>
            </div>

            <div class="gti-drawer-section">
                <div class="gti-drawer-section-title"><i class="fas fa-align-left"></i> Description</div>
                <div class="gti-drawer-row" style="flex-direction: column;">
                    <span class="gti-drawer-value is-message" id="drawer-description">-</span>
                </div>
            </div>

            <div class="gti-drawer-section">
                <div class="gti-drawer-section-title"><i class="fas fa-image"></i> Image</div>
                <div id="drawer-main-image" style="width:100%;"><span class="gti-drawer-value" style="text-align:center;color:#9ca3af;font-weight:400;">No image</span></div>
            </div>

            <div class="gti-drawer-section">
                <div class="gti-drawer-section-title"><i class="fas fa-database"></i> System</div>
                <div class="gti-drawer-row"><span class="gti-drawer-label">Created</span><span class="gti-drawer-value" id="drawer-created">-</span></div>
                <div class="gti-drawer-row"><span class="gti-drawer-label">Updated</span><span class="gti-drawer-value" id="drawer-updated">-</span></div>
            </div>
        </div>
        <div class="gti-drawer-footer">
            <button type="button" class="gti-drawer-btn gti-drawer-btn-primary" id="drawer-btn-edit"><i class="fas fa-edit"></i> Edit</button>
            <span class="gti-drawer-footer-spacer"></span>
            <button type="button" class="gti-drawer-btn" id="drawer-btn-publish" style="display:none;background:#059669;color:#fff;border-color:#059669;font-weight:600;"><i class="fas fa-check-circle"></i> Publish</button>
            <button type="button" class="gti-drawer-btn" id="drawer-btn-delete" style="color:#b91c1c;border-color:#fca5a5;"><i class="fas fa-trash"></i> Delete</button>
        </div>
    </div>
            </div>

<?php
gti_dashboard_close( array(
    'modals' => array( 'delete' ),
) );
