<?php
/**
 * Template: Used Equipment (/dashboard/used-equipment)
 * @package global-tractors
 */
if (!defined('ABSPATH')) exit;

// Auto-generate equipment code
global $wpdb;
$table = $wpdb->prefix . 'gti_equipment';
$year = date('Y');
$cat_abbrev_map = [
    'Excavator'     => 'EXC', 'Bulldozer'     => 'BLD', 'Wheel Loader'  => 'WLD',
    'Dump Truck'    => 'DMP', 'Motor Grader'  => 'MGR', 'Crane'         => 'CRN',
    'Compactor'     => 'CMP',
];
$gti_next_code = '';
// Default first code
$gti_next_code = 'GTI-GEN-' . $year . '-001';

// Category abbrev map for JS
// Handed to the page script as gtiPageData.categoryMap, rather than
// interpolated into an inline <script> (PRD §13.8).
gti_page_data( array( 'categoryMap' => $cat_abbrev_map ) );

// AJAX endpoint to get next code
$gti_ajax_url = admin_url('admin-ajax.php');

// Database queries
global $wpdb;
$table = $wpdb->prefix . 'gti_equipment';

// Filters
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$category = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';
$brand = isset($_GET['brand']) ? sanitize_text_field($_GET['brand']) : '';
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$condition_filter = isset($_GET['condition']) ? sanitize_text_field($_GET['condition']) : '';

// Pagination
// Filters carried across pagination links.
$query_params = array_filter( array(
    'gti_page'  => 'used-equipment',
    'search'    => $search,
    'category'  => $category,
    'brand'     => $brand,
    'status'    => $status_filter,
    'condition' => $condition_filter,
) );

$paged = isset($_GET['page_num']) ? max(1, intval($_GET['page_num'])) : 1;
$per_page = 10;
$offset = ($paged - 1) * $per_page;

// Build WHERE clause
$where = "WHERE type = 'used' AND deleted_at IS NULL";
$params = array();

if ($search) {
    $where .= ' AND (name LIKE %s OR equipment_code LIKE %s OR brand LIKE %s)';
    $search_like = '%' . $wpdb->esc_like($search) . '%';
    $params[] = $search_like;
    $params[] = $search_like;
    $params[] = $search_like;
}
if ($category) {
    $where .= ' AND category = %s';
    $params[] = $category;
}
if ($brand) {
    $where .= ' AND brand = %s';
    $params[] = $brand;
}
if ($status_filter) {
    $where .= ' AND status = %s';
    $params[] = $status_filter;
}
if ($condition_filter) {
    $where .= ' AND condition_status = %s';
    $params[] = $condition_filter;
}

// Get total count
$count_sql = "SELECT COUNT(*) FROM {$table} {$where}";
$total = !empty($params) ? (int) $wpdb->get_var($wpdb->prepare($count_sql, $params)) : (int) $wpdb->get_var($count_sql);
$total_pages = ceil($total / $per_page);

// Get equipment
if (!empty($params)) {
    $equipments = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$table} {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d",
        array_merge($params, array($per_page, $offset))
    ));
} else {
    $equipments = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$table} {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d",
        $per_page, $offset
    ));
}

// Status counts
$status_counts = $wpdb->get_results(
    "SELECT status, COUNT(*) as count FROM {$table} WHERE type = 'used' AND deleted_at IS NULL GROUP BY status"
);
$status_count_map = array();
foreach ($status_counts as $sc) {
    $status_count_map[$sc->status] = $sc->count;
}
$total_equipment = array_sum(array_column($status_counts, 'count'));

// Filter options
$categories = $wpdb->get_col("SELECT DISTINCT category FROM {$table} WHERE type = 'used' AND category != '' AND deleted_at IS NULL ORDER BY category");
$brands = $wpdb->get_col("SELECT DISTINCT brand FROM {$table} WHERE type = 'used' AND brand != '' AND deleted_at IS NULL ORDER BY brand");
$years = $wpdb->get_col("SELECT DISTINCT year FROM {$table} WHERE type = 'used' AND year IS NOT NULL AND deleted_at IS NULL ORDER BY year DESC");

// Format currency inline to avoid redeclaration errors
$_gti_eq_fmt = function($amount) {
    return 'Rp ' . number_format((float)$amount, 0, ',', '.');
};


// Status badge helper
$_gti_eq_status_badge = function($status, $price_valid_until = null) {
    if ( ! gti_is_price_valid( $price_valid_until ) ) {
        return '<span class="gti-badge-status price-no-longer-valid">Expired</span>';
    }

    $map = array(
        'available' => array('class' => 'available', 'label' => 'Available'),
        'sold' => array('class' => 'sold', 'label' => 'Sold'),
        'reserved' => array('class' => 'reserved', 'label' => 'Reserved'),
        'rented' => array('class' => 'sold', 'label' => 'Rented'),
        'maintenance' => array('class' => 'reserved', 'label' => 'Maintenance'),
        'draft' => array('class' => 'draft', 'label' => 'Draft'),
    );
    $info = isset($map[$status]) ? $map[$status] : array('class' => 'available', 'label' => ucfirst($status));
    return '<span class="gti-badge-status ' . esc_attr($info['class']) . '">' . esc_html($info['label']) . '</span>';
};

gti_dashboard_open( array(
    'page'     => 'used-equipment',
    'title'    => 'Used Equipment',
    'subtitle' => 'Manage your used equipment inventory 🚜',
    'cap'      => 'gti_manage_equipment',
    'js'       => array( 'used-equipment' ),
) );
?>


            <!-- Content -->
            <div class="gti-content">
                <div class="gti-content-left">
                <!-- Used Equipment Stats -->
                <div class="gti-ue-stats-row">
                    <div class="gti-ue-stat-card">
                        <div class="gti-ue-stat-icon"><i class="fas fa-truck"></i></div>
                        <div class="gti-ue-stat-info">
                            <p class="gti-ue-stat-label">Total Equipment</p>
                            <p class="gti-ue-stat-value"><?php echo esc_html($total_equipment); ?></p>
                        </div>
                    </div>
                    <div class="gti-ue-stat-card">
                        <div class="gti-ue-stat-icon available"><i class="fas fa-check-circle"></i></div>
                        <div class="gti-ue-stat-info">
                            <p class="gti-ue-stat-label">Available</p>
                            <p class="gti-ue-stat-value"><?php echo esc_html($status_count_map['available'] ?? 0); ?></p>
                        </div>
                    </div>
                    <div class="gti-ue-stat-card">
                        <div class="gti-ue-stat-icon sold"><i class="fas fa-handshake"></i></div>
                        <div class="gti-ue-stat-info">
                            <p class="gti-ue-stat-label">Sold</p>
                            <p class="gti-ue-stat-value"><?php echo esc_html($status_count_map['sold'] ?? 0); ?></p>
                        </div>
                    </div>
                    <div class="gti-ue-stat-card">
                        <div class="gti-ue-stat-icon reserved"><i class="fas fa-clock"></i></div>
                        <div class="gti-ue-stat-info">
                            <p class="gti-ue-stat-label">Reserved</p>
                            <p class="gti-ue-stat-value"><?php echo esc_html($status_count_map['reserved'] ?? 0); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Toolbar -->
                <form class="gti-ue-toolbar" method="get">
                    <input type="hidden" name="gti_page" value="used-equipment">
                    <div class="gti-ue-toolbar-left">
                        <div class="gti-ue-search">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" placeholder="Search equipment..." value="<?php echo esc_attr($search); ?>">
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
                                <option value="available" <?php selected($status_filter, 'available'); ?>>Available</option>
                                <option value="sold" <?php selected($status_filter, 'sold'); ?>>Sold</option>
                                <option value="reserved" <?php selected($status_filter, 'reserved'); ?>>Reserved</option>
                                <option value="draft" <?php selected($status_filter, 'draft'); ?>>Draft</option>
                            </select>
                        </div>
                        <div class="gti-ue-filter">
                            <select name="condition" onchange="this.form.submit()">
                                <option value="">All Conditions</option>
                                <option value="Excellent" <?php selected($condition_filter, 'Excellent'); ?>>Excellent</option>
                                <option value="Good" <?php selected($condition_filter, 'Good'); ?>>Good</option>
                                <option value="Fair" <?php selected($condition_filter, 'Fair'); ?>>Fair</option>
                            </select>
                        </div>
                        <a href="<?php echo esc_url(gti_dashboard_url('used-equipment')); ?>" class="gti-ue-btn-reset">
                            <i class="fas fa-rotate-right"></i> Reset
                        </a>
                    </div>
                    <a href="<?php echo esc_url(gti_dashboard_url('used-equipment/add')); ?>" class="gti-ue-btn-add">
                        <i class="fas fa-plus"></i> Add Equipment
                    </a>
                </form>

                <!-- Table -->
                <div class="gti-ue-table-card">
                    <table class="gti-ue-table">
                        <thead>
                            <tr>
                                <th class="col-image">Image</th>
                                <th class="col-name">Equipment Name</th>
                                <th class="col-category">Category</th>
                                <th class="col-brand">Brand</th>
                                <th class="col-condition">Condition</th>
                                <th class="col-year">Year</th>
                                <th class="col-price">Price</th>
                                <th class="col-status">Status</th>
                                <th class="col-actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($equipments)): ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 60px 20px;">
                                        <div style="color: #9ca3af;">
                                            <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 16px; display: block;"></i>
                                            <p style="font-size: 16px; font-weight: 500; margin-bottom: 8px;">No equipment found</p>
                                            <p style="font-size: 14px;">
                                                <?php echo ($search || $category || $brand || $status_filter || $condition_filter) ? 'Try adjusting your filters' : 'Get started by adding your first equipment'; ?>
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($equipments as $eq): ?>
                                    <tr data-eq-id="<?php echo esc_attr($eq->id); ?>" data-eq='<?php echo esc_attr(json_encode($eq)); ?>'>
                                        <td class="col-image">
                                            <div class="gti-ue-thumb">
                                                <?php if (!empty($eq->main_image)): ?>
                                                    <img src="<?php echo esc_url($eq->main_image); ?>" alt="<?php echo esc_attr($eq->name); ?>">
                                                <?php else: ?>
                                                    <i class="fas fa-truck"></i>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="col-name">
                                            <strong><?php echo esc_html($eq->name); ?></strong><br>
                                            <small><?php echo esc_html($eq->year); ?> Model<?php echo $eq->hours ? ', ' . number_format($eq->hours) . ' Hours' : ''; ?></small>
                                        </td>
                                        <td class="col-category"><?php echo esc_html($eq->category); ?></td>
                                        <td class="col-brand"><?php echo esc_html($eq->brand); ?></td>
                                        <td class="col-condition">
                                            <?php
                                            $cond_lower = strtolower($eq->condition_status ?? '');
                                            $cond_class = $cond_lower === 'excellent' ? 'excellent' : ($cond_lower === 'good' ? 'good' : 'fair');
                                            ?>
                                            <span class="gti-condition <?php echo esc_attr($cond_class); ?>"><?php echo esc_html($eq->condition_status); ?></span>
                                        </td>
                                        <td class="col-year"><?php echo esc_html($eq->year); ?></td>
                                        <td class="col-price"><?php echo esc_html($_gti_eq_fmt($eq->price)); ?></td>
                                        <td class="col-status"><?php echo $_gti_eq_status_badge($eq->status, $eq->price_valid_until); ?></td>
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
                            'base_url' => gti_dashboard_url( 'used-equipment' ),
                            'params'   => $query_params,
                        ) );
                        ?>
                        </div>
                    <?php endif; ?>
                </div>
                </div><!-- /.gti-content-left -->
                <!-- Detail Drawer -->
                <div class="gti-drawer-backdrop" id="drawerBackdrop"></div>
                <div class="gti-drawer" id="eqDetailDrawer">
        <div class="gti-drawer-header">
            <div class="gti-drawer-header-left">
                <h2 id="drawer-eq-code">-</h2>
                <span class="gti-drawer-status" id="drawer-status-badge">Available</span>
            </div>
            <button type="button" class="gti-drawer-close" onclick="closeDetailDrawer()"><i class="fas fa-times"></i></button>
        </div>
        <div class="gti-drawer-body">
            <!-- Stepper Navigation -->
            <div class="gti-drawer-stepper">
                <div class="gti-drawer-step active" data-section="info" onclick="switchDrawerStep(this)">
                    <div class="gti-drawer-step-circle">1</div>
                    <div class="gti-drawer-step-label">Info</div>
                </div>
                <div class="gti-drawer-step-line"></div>
                <div class="gti-drawer-step" data-section="specs" onclick="switchDrawerStep(this)">
                    <div class="gti-drawer-step-circle">2</div>
                    <div class="gti-drawer-step-label">Specs</div>
                </div>
                <div class="gti-drawer-step-line"></div>
                <div class="gti-drawer-step" data-section="pricing" onclick="switchDrawerStep(this)">
                    <div class="gti-drawer-step-circle">3</div>
                    <div class="gti-drawer-step-label">Pricing</div>
                </div>
                <div class="gti-drawer-step-line"></div>
                <div class="gti-drawer-step" data-section="media" onclick="switchDrawerStep(this)">
                    <div class="gti-drawer-step-circle">4</div>
                    <div class="gti-drawer-step-label">Media</div>
                </div>
                <div class="gti-drawer-step-line"></div>
                <div class="gti-drawer-step" data-section="additional" onclick="switchDrawerStep(this)">
                    <div class="gti-drawer-step-circle">5</div>
                    <div class="gti-drawer-step-label">More</div>
                </div>
            </div>

            <!-- Section: 1 - Info (General + Basic) -->
            <div class="gti-drawer-section active" data-section="info">
                <div class="gti-drawer-section-title"><i class="fas fa-info-circle"></i> General Information</div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Name</span><span class="gti-drawer-value" id="drawer-name">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Equipment Code</span><span class="gti-drawer-value" id="drawer-eq-code-info">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Category</span><span class="gti-drawer-value" id="drawer-category">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Brand</span><span class="gti-drawer-value" id="drawer-brand">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Model</span><span class="gti-drawer-value" id="drawer-model">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Year</span><span class="gti-drawer-value" id="drawer-year">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Hours</span><span class="gti-drawer-value" id="drawer-hours">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Serial Number</span><span class="gti-drawer-value" id="drawer-serial-number">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Condition</span><span class="gti-drawer-value" id="drawer-condition">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Status</span><span class="gti-drawer-value" id="drawer-status-text">-</span></div>
                <div class="gti-drawer-section-title" style="margin-top:18px;"><i class="fas fa-engine"></i> Engine & Origin</div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Engine</span><span class="gti-drawer-value" id="drawer-engine">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Engine Power</span><span class="gti-drawer-value" id="drawer-engine-power">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Origin</span><span class="gti-drawer-value" id="drawer-origin">-</span></div>
                <div class="gti-drawer-section-title" style="margin-top:18px;"><i class="fas fa-clipboard-list"></i> Basic Information</div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Type</span><span class="gti-drawer-value" id="drawer-type">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Operating Weight</span><span class="gti-drawer-value" id="drawer-operating-weight">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Bucket Capacity</span><span class="gti-drawer-value" id="drawer-bucket-capacity">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Location</span><span class="gti-drawer-value" id="drawer-location">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Stock Number</span><span class="gti-drawer-value" id="drawer-stock-number">-</span></div>
                    <div class="gti-drawer-row" style="flex-direction:column;">
                        <span class="gti-drawer-label">Description</span>
                        <span class="gti-drawer-value is-message" id="drawer-description">-</span>
                    </div>
            </div>

            <!-- Section: 2 - Specs (Technical + Features) -->
            <div class="gti-drawer-section" data-section="specs">
                <div class="gti-drawer-section-title"><i class="fas fa-cogs"></i> Technical Specifications</div>
                    <div id="drawer-specs-list"><div class="gti-drawer-row"><span class="gti-drawer-value" style="text-align:center;color:#9ca3af;font-weight:400;">No specifications available</span></div></div>
                <div class="gti-drawer-section-title" style="margin-top:18px;"><i class="fas fa-sliders-h"></i> Features & Configurations</div>
                    <div id="drawer-features-list" class="gti-drawer-features"><span class="gti-drawer-value" style="text-align:center;color:#9ca3af;font-weight:400;width:100%;display:block;">No features</span></div>
            </div>

            <!-- Section: 3 - Pricing & Status -->
            <div class="gti-drawer-section" data-section="pricing">
                <div class="gti-drawer-section-title"><i class="fas fa-tag"></i> Pricing Information</div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Selling Price</span><span class="gti-drawer-value" id="drawer-selling-price">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Rental Price</span><span class="gti-drawer-value" id="drawer-rental-price">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Price Type</span><span class="gti-drawer-value" id="drawer-price-type">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">VAT Included</span><span class="gti-drawer-value" id="drawer-vat">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Currency</span><span class="gti-drawer-value" id="drawer-currency">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Price Valid Until</span><span class="gti-drawer-value" id="drawer-price-valid-until">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Negotiable</span><span class="gti-drawer-value" id="drawer-negotiable">-</span></div>
                <div class="gti-drawer-section-title" style="margin-top:18px;"><i class="fas fa-clipboard-check"></i> Availability</div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Availability</span><span class="gti-drawer-value" id="drawer-availability-status">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Ready to Use</span><span class="gti-drawer-value" id="drawer-ready-to-use">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Service History</span><span class="gti-drawer-value" id="drawer-service-history">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Warranty Available</span><span class="gti-drawer-value" id="drawer-warranty-avail">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Warranty Period</span><span class="gti-drawer-value" id="drawer-warranty-period">-</span></div>
                    <div class="gti-drawer-row" style="flex-direction:column;">
                        <span class="gti-drawer-label">Buyer Notes</span>
                        <span class="gti-drawer-value is-message" id="drawer-buyer-notes">-</span>
                    </div>
            </div>

            <!-- Section: 4 - Media (Images + Video) -->
            <div class="gti-drawer-section" data-section="media">
                <div class="gti-drawer-section-title"><i class="fas fa-images"></i> Images</div>
                    <div class="gti-drawer-row" style="flex-direction:column;">
                        <span class="gti-drawer-label">Main Image</span>
                        <div id="drawer-main-image" style="margin-top:8px;"><span class="gti-drawer-value" style="text-align:center;color:#9ca3af;font-weight:400;">No image</span></div>
                    </div>
                    <div class="gti-drawer-row" style="flex-direction:column;">
                        <span class="gti-drawer-label">Gallery</span>
                        <div id="drawer-gallery" style="margin-top:8px;display:flex;gap:6px;flex-wrap:wrap;"><span class="gti-drawer-value" style="text-align:center;color:#9ca3af;font-weight:400;">No images</span></div>
                    </div>
                <div class="gti-drawer-section-title" style="margin-top:18px;"><i class="fas fa-video"></i> Video</div>
                    <div class="gti-drawer-row" style="flex-direction:column;">
                        <div id="drawer-video"><span class="gti-drawer-value" style="text-align:center;color:#9ca3af;font-weight:400;">No video</span></div>
                    </div>
            </div>

            <!-- Section: 5 - Additional (History, Docs, Location, System) -->
            <div class="gti-drawer-section" data-section="additional">
                <div class="gti-drawer-section-title"><i class="fas fa-history"></i> Equipment History</div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Previous Usage</span><span class="gti-drawer-value" id="drawer-previous-usage">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Working Condition</span><span class="gti-drawer-value" id="drawer-working-condition">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Maintenance Record</span><span class="gti-drawer-value" id="drawer-maintenance-record">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Ownership</span><span class="gti-drawer-value" id="drawer-ownership">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Operator Hours</span><span class="gti-drawer-value" id="drawer-operator-hours">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Last Service Date</span><span class="gti-drawer-value" id="drawer-last-service">-</span></div>
                    <div class="gti-drawer-row" style="flex-direction:column;">
                        <span class="gti-drawer-label">Equipment History</span>
                        <span class="gti-drawer-value is-message" id="drawer-equipment-history">-</span>
                    </div>
                    <div class="gti-drawer-row" style="flex-direction:column;">
                        <span class="gti-drawer-label">Detailed Description</span>
                        <span class="gti-drawer-value is-message" id="drawer-detailed-description">-</span>
                    </div>
                <div class="gti-drawer-section-title" style="margin-top:18px;"><i class="fas fa-file-alt"></i> Documents</div>
                    <div id="drawer-documents-list" class="gti-drawer-docs"><span class="gti-drawer-value" style="text-align:center;color:#9ca3af;font-weight:400;width:100%;display:block;">No documents</span></div>
                <div class="gti-drawer-section-title" style="margin-top:18px;"><i class="fas fa-map-marker-alt"></i> Location Details</div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Country</span><span class="gti-drawer-value" id="drawer-location-country">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Province</span><span class="gti-drawer-value" id="drawer-location-province">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">City</span><span class="gti-drawer-value" id="drawer-location-city">-</span></div>
                    <div class="gti-drawer-row" style="flex-direction:column;">
                        <span class="gti-drawer-label">Address</span>
                        <span class="gti-drawer-value is-message" id="drawer-address">-</span>
                    </div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Map Link</span><span class="gti-drawer-value" id="drawer-map-location">-</span></div>
                    <div class="gti-drawer-row"><span class="gti-drawer-label">Location Notes</span><span class="gti-drawer-value" id="drawer-location-notes">-</span></div>
                <div class="gti-drawer-section-title" style="margin-top:18px;"><i class="fas fa-database"></i> System</div>
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
            </div> <!-- /.gti-content -->

<?php
gti_dashboard_close( array(
    'modals' => array( 'delete', 'edit-equipment' ),
) );
