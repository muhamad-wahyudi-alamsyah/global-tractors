<?php
/**
 * Template: Customers List (/dashboard/customers)
 * @package global-tractors
 */
if (!defined('ABSPATH')) exit;

// Database
global $wpdb;
gti_ensure_customers_table();
$table_name = gti_customers_table();

// Everyone who submitted a Request Equipment or a Request Quotation becomes a
// customer here. New submissions sync on arrival; this backfills anything that
// predates the sync hooks (throttled to once every 5 minutes).
gti_sync_customers_from_sources();

// Filters
$search        = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$source_filter = isset($_GET['source']) ? sanitize_text_field($_GET['source']) : '';

// Pagination — 'page_num' is used instead of 'paged' because WordPress reserves 'paged'
$paged    = isset($_GET['page_num']) ? max(1, intval($_GET['page_num'])) : 1;
$per_page = 10;
$offset   = ($paged - 1) * $per_page;

// Build query
$where  = "WHERE 1=1";
$params = [];

if ($search) {
    $where      .= " AND (name LIKE %s OR company LIKE %s OR email LIKE %s OR phone LIKE %s OR customer_id LIKE %s)";
    $search_like = '%' . $wpdb->esc_like($search) . '%';
    $params      = array_merge($params, [$search_like, $search_like, $search_like, $search_like, $search_like]);
}
if ($status_filter) {
    $where   .= " AND status = %s";
    $params[] = $status_filter;
}
if ($source_filter) {
    $where   .= " AND source = %s";
    $params[] = $source_filter;
}

// Count
$count_query = "SELECT COUNT(*) FROM {$table_name} {$where}";
$total       = !empty($params) ? $wpdb->get_var($wpdb->prepare($count_query, $params)) : $wpdb->get_var($count_query);
$total_pages = ceil($total / $per_page);

// Fetch
if (!empty($params)) {
    $customers = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$table_name} {$where} ORDER BY registered_date DESC LIMIT %d OFFSET %d",
        array_merge($params, [$per_page, $offset])
    ));
} else {
    $customers = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$table_name} {$where} ORDER BY registered_date DESC LIMIT %d OFFSET %d",
        $per_page, $offset
    ));
}

// Status counts — unfiltered, so the stat cards do not move with the search box
$total_customers = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
$active_count    = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE status = 'active'");
$inactive_count  = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE status <> 'active'");
$total_requests  = (int) $wpdb->get_var("SELECT COALESCE(SUM(total_transactions), 0) FROM {$table_name}");

// Where customers came from, for the filter dropdown
$sources = $wpdb->get_col("SELECT DISTINCT source FROM {$table_name} WHERE source <> '' ORDER BY source");
$source_labels = array(
    'request-equipment' => 'Request Equipment',
    'request-quotation' => 'Request Quotation',
    'sell-equipment'    => 'Sell Equipment',
);

// Display helpers live in inc/helpers/format-helpers.php (R-06).

gti_dashboard_open( array(
    'page'     => 'customers',
    'title'    => 'Customers',
    'subtitle' => 'Manage customer data and relationships 👥',
    'cap'      => 'gti_manage_customers',
    'js'       => array( 'customers' ),
) );
?>


            <!-- Content -->
            <div class="gti-content">
                <div class="gti-content-left">
                <!-- Statistics Cards -->
                <div class="gti-ue-stats-row">
                    <div class="gti-ue-stat-card">
                        <div class="gti-ue-stat-icon"><i class="fas fa-users"></i></div>
                        <div class="gti-ue-stat-info">
                            <p class="gti-ue-stat-label">Total Customers</p>
                            <p class="gti-ue-stat-value"><?php echo esc_html($total_customers); ?></p>
                        </div>
                    </div>
                    <div class="gti-ue-stat-card">
                        <div class="gti-ue-stat-icon available"><i class="fas fa-user-check"></i></div>
                        <div class="gti-ue-stat-info">
                            <p class="gti-ue-stat-label">Active</p>
                            <p class="gti-ue-stat-value"><?php echo esc_html($active_count); ?></p>
                        </div>
                    </div>
                    <div class="gti-ue-stat-card">
                        <div class="gti-ue-stat-icon sold"><i class="fas fa-user-clock"></i></div>
                        <div class="gti-ue-stat-info">
                            <p class="gti-ue-stat-label">Inactive</p>
                            <p class="gti-ue-stat-value"><?php echo esc_html($inactive_count); ?></p>
                        </div>
                    </div>
                    <div class="gti-ue-stat-card">
                        <div class="gti-ue-stat-icon reserved"><i class="fas fa-file-signature"></i></div>
                        <div class="gti-ue-stat-info">
                            <p class="gti-ue-stat-label">Requests &amp; Quotations</p>
                            <p class="gti-ue-stat-value"><?php echo esc_html($total_requests); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Toolbar -->
                <form class="gti-ue-toolbar" method="get">
                    <input type="hidden" name="gti_page" value="customers">
                    <div class="gti-ue-toolbar-left">
                        <div class="gti-ue-search">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" placeholder="Search customers..." value="<?php echo esc_attr($search); ?>">
                        </div>
                        <div class="gti-ue-filter">
                            <select name="status">
                                <option value="">All Status</option>
                                <option value="active" <?php selected($status_filter, 'active'); ?>>Active</option>
                                <option value="inactive" <?php selected($status_filter, 'inactive'); ?>>Inactive</option>
                            </select>
                        </div>
                        <?php if (!empty($sources)): ?>
                        <div class="gti-ue-filter">
                            <select name="source">
                                <option value="">All Sources</option>
                                <?php foreach ($sources as $src): ?>
                                    <option value="<?php echo esc_attr($src); ?>" <?php selected($source_filter, $src); ?>>
                                        <?php echo esc_html($source_labels[$src] ?? ucwords(str_replace('-', ' ', $src))); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        <a href="<?php echo esc_url(gti_dashboard_url('customers')); ?>" class="gti-ue-btn-reset">
                            <i class="fas fa-rotate-right"></i> Reset
                        </a>
                    </div>
                </form>

                <!-- Table -->
                <div class="gti-ue-table-card">
                    <table class="gti-ue-table">
                        <thead>
                            <tr>
                                <th class="col-customer">Customer</th>
                                <th class="col-company">Company</th>
                                <th class="col-email">Email</th>
                                <th class="col-phone">Phone</th>
                                <th class="col-status">Status</th>
                                <th class="col-transactions">Transactions</th>
                                <th class="col-actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($customers)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 60px 20px;">
                                        <div style="color: #9ca3af;">
                                            <i class="fas fa-users" style="font-size: 48px; margin-bottom: 16px; display: block;"></i>
                                            <p style="font-size: 16px; font-weight: 500; margin-bottom: 8px;">No customers found</p>
                                            <p style="font-size: 14px;">
                                                <?php echo ($search || $status_filter || $source_filter)
                                                    ? 'Try adjusting your filters'
                                                    : 'Customers appear here automatically once someone submits a Request Equipment or Request Quotation'; ?>
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($customers as $i => $c): ?>
                                    <?php
                                    $initials = gti_customer_initials($c->name);
                                    $bg_class = 'bg-' . (($i % 7) + 1);
                                    ?>
                                    <tr data-cust-id="<?php echo esc_attr($c->customer_id); ?>" data-cust='<?php echo esc_attr(json_encode($c)); ?>'>
                                        <td class="col-customer">
                                            <div class="gti-cust-cell">
                                                <div class="gti-cust-avatar <?php echo esc_attr($bg_class); ?>"><?php echo esc_html($initials); ?></div>
                                                <div class="gti-cust-name-group">
                                                    <strong><?php echo esc_html($c->name); ?></strong>
                                                    <small><?php echo esc_html($c->customer_id); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="col-company">
                                            <strong style="color:#1a1f36"><?php echo esc_html($c->company ?: '—'); ?></strong>
                                        </td>
                                        <td class="col-email">
                                            <a href="mailto:<?php echo esc_attr($c->email); ?>" style="color:#2563eb;text-decoration:none;font-size:13px"><?php echo esc_html($c->email); ?></a>
                                        </td>
                                        <td class="col-phone"><?php echo esc_html($c->phone ?: '—'); ?></td>
                                        <td class="col-status">
                                            <span class="gti-badge-status <?php echo $c->status === 'active' ? 'available' : 'sold'; ?>"><?php echo esc_html(ucfirst($c->status)); ?></span>
                                        </td>
                                        <td class="col-transactions"><?php echo (int) $c->total_transactions; ?></td>
                                        <td class="col-actions">
                                            <div class="gti-ue-action-menu">
                                                <button class="gti-ue-action-toggle" title="Actions"><i class="fas fa-ellipsis-v"></i></button>
                                                <div class="gti-ue-action-dropdown">
                                                    <button type="button" class="gti-ue-action-item" onclick='showCustomerDetail(<?php echo esc_attr(json_encode($c)); ?>)'>
                                                        <i class="fas fa-eye"></i> View Details
                                                    </button>
                                                    <a href="mailto:<?php echo esc_attr($c->email); ?>" class="gti-ue-action-item">
                                                        <i class="fas fa-envelope"></i> Send Email
                                                    </a>
                                                    <a href="<?php echo esc_url(gti_dashboard_url('customer-detail') . '?' . http_build_query(['id' => $c->customer_id])); ?>" class="gti-ue-action-item">
                                                        <i class="fas fa-external-link-alt"></i> Full Profile
                                                    </a>
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
                            'base_url' => gti_dashboard_url( 'customers' ),
                            'params'   => array( 'gti_page' => 'customers', 'search' => $search, 'status' => $status_filter, 'source' => $source_filter ),
                        ) );
                        ?>
                        </div>
                    <?php endif; ?>
                </div>
                </div><!-- /.gti-content-left -->

                <!-- Detail Drawer (In-Flow ≥1600px / Fixed <1600px) -->
                <div class="gti-drawer-backdrop" id="drawerBackdrop"></div>
                <div class="gti-drawer" id="custDetailDrawer">
                    <div class="gti-drawer-header">
                        <div class="gti-drawer-header-left">
                            <h2 id="drawer-cust-name">-</h2>
                            <span class="gti-drawer-status" id="drawer-status-badge">Active</span>
                        </div>
                        <button type="button" class="gti-drawer-close" onclick="closeDetailDrawer()"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="gti-drawer-body">
                        <div class="gti-drawer-section">
                            <div class="gti-drawer-section-title"><i class="fas fa-user"></i> Profile Summary</div>
                            <div class="gti-drawer-row"><span class="gti-drawer-label">Customer ID</span><span class="gti-drawer-value" id="drawer-cust-id">-</span></div>
                            <div class="gti-drawer-row"><span class="gti-drawer-label">Email</span><span class="gti-drawer-value is-link" id="drawer-email">-</span></div>
                            <div class="gti-drawer-row"><span class="gti-drawer-label">Phone</span><span class="gti-drawer-value" id="drawer-phone">-</span></div>
                            <div class="gti-drawer-row"><span class="gti-drawer-label">Since</span><span class="gti-drawer-value" id="drawer-since">-</span></div>
                            <div class="gti-drawer-row"><span class="gti-drawer-label">Rating</span><span class="gti-drawer-value" id="drawer-rating">-</span></div>
                        </div>
                        <div class="gti-drawer-section">
                            <div class="gti-drawer-section-title"><i class="fas fa-building"></i> Company Information</div>
                            <div class="gti-drawer-row"><span class="gti-drawer-label">Company</span><span class="gti-drawer-value" id="drawer-company">-</span></div>
                            <div class="gti-drawer-row"><span class="gti-drawer-label">Industry</span><span class="gti-drawer-value" id="drawer-industry">-</span></div>
                            <div class="gti-drawer-row"><span class="gti-drawer-label">Location</span><span class="gti-drawer-value" id="drawer-location">-</span></div>
                            <div class="gti-drawer-row"><span class="gti-drawer-label">NPWP</span><span class="gti-drawer-value" id="drawer-npwp">-</span></div>
                        </div>
                        <div class="gti-drawer-section">
                            <div class="gti-drawer-section-title"><i class="fas fa-chart-bar"></i> Statistics</div>
                            <div class="gti-drawer-row"><span class="gti-drawer-label">Transactions</span><span class="gti-drawer-value" id="drawer-transactions">-</span></div>
                            <div class="gti-drawer-row"><span class="gti-drawer-label">Total Spent</span><span class="gti-drawer-value" id="drawer-spent">-</span></div>
                            <div class="gti-drawer-row"><span class="gti-drawer-label">Last Contact</span><span class="gti-drawer-value" id="drawer-last-contact">-</span></div>
                        </div>
                    </div>
                    <div class="gti-drawer-footer">
                        <a href="#" class="gti-drawer-btn gti-drawer-btn-primary" id="drawer-btn-profile"><i class="fas fa-external-link-alt"></i> Full Profile</a>
                        <a href="#" class="gti-drawer-btn" id="drawer-btn-email"><i class="fas fa-envelope"></i> Send Email</a>
                    </div>
                </div>
            </div>

<?php
gti_dashboard_close( array(
    'modals' => array( 'delete', 'email' ),
) );
