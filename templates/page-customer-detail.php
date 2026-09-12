<?php
/**
 * Template: Customer Detail (/dashboard/customers/?page=gti-customer-detail&id=CUST-230112)
 * @package global-tractors
 */
if (!defined('ABSPATH')) exit;

// Get customer ID from URL
$customer_id_param = isset($_GET['id']) ? sanitize_text_field($_GET['id']) : '';

global $wpdb;
gti_ensure_customers_table();
$table_name = gti_customers_table();

$customer = null;
if ($customer_id_param) {
    $customer = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table_name} WHERE customer_id = %s",
        $customer_id_param
    ));
}

// Keep the stored counters honest before rendering them.
if ($customer) {
    gti_refresh_customer_stats($customer->customer_id);
    $customer = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table_name} WHERE customer_id = %s",
        $customer_id_param
    ));
}

// Display helpers live in inc/helpers/format-helpers.php (R-06).

$requests = array();
$quotations = array();
$activity_rows = array();
$stats = array('requests' => 0, 'quotations' => 0, 'total_spent' => 0.0, 'last_contact' => null);

if ($customer) {
    $stats = gti_customer_stats($customer);

    // Match on either identifier — inbound forms do not always capture both.
    $match  = array();
    $params = array();
    if ($customer->email) { $match[] = 'customer_email = %s'; $params[] = $customer->email; }
    if ($customer->phone) { $match[] = 'customer_phone = %s'; $params[] = $customer->phone; }
    $match_sql = $match ? '(' . implode(' OR ', $match) . ')' : '0';

    $requests_table = $wpdb->prefix . 'gti_requests';
    if ($params && gti_table_exists($requests_table)) {
        $requests = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$requests_table} WHERE {$match_sql} ORDER BY created_at DESC LIMIT 50",
            $params
        ));
    }

    $quotations_table = $wpdb->prefix . 'gti_quotations';
    if ($params && gti_table_exists($quotations_table)) {
        $quotations = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$quotations_table} WHERE {$match_sql} ORDER BY created_at DESC LIMIT 50",
            $params
        ));
    }

    // Everything the dashboard did to this customer record.
    $activity_table = gti_activity_log_table();
    $activity_rows = $wpdb->get_results($wpdb->prepare(
        "SELECT al.*, u.display_name, u.user_login
         FROM {$activity_table} al
         LEFT JOIN {$wpdb->users} u ON al.user_id = u.ID
         WHERE al.entity_type = 'customer' AND al.entity_id = %d
         ORDER BY al.created_at DESC
         LIMIT 50",
        $customer->id
    ));
}

// Recent activity — the customer's own submissions, newest first.
$recent_activities = array();
foreach ($requests as $r) {
    $recent_activities[] = array(
        'sort'     => strtotime($r->created_at),
        'date'     => gti_cd_fmt_datetime($r->created_at),
        'type'     => 'Request Equipment',
        'icon'     => 'fas fa-truck',
        'icon_bg'  => '#dbeafe',
        'icon_clr' => '#3b82f6',
        'detail'   => trim($r->request_id . ' — ' . gti_cd_val($r->equipment, 'Equipment request')
                      . ($r->quantity > 1 ? ' (x' . (int) $r->quantity . ')' : '')),
        'by'       => gti_cd_val($r->customer_name, 'Customer'),
        'status'   => $r->status,
    );
}
foreach ($quotations as $q) {
    $recent_activities[] = array(
        'sort'     => strtotime($q->created_at),
        'date'     => gti_cd_fmt_datetime($q->created_at),
        'type'     => 'Request Quotation',
        'icon'     => 'fas fa-file-invoice',
        'icon_bg'  => '#fef3c7',
        'icon_clr' => '#f59e0b',
        'detail'   => trim($q->quotation_id . ' — ' . gti_cd_fmt_currency($q->total)),
        'by'       => gti_cd_val($q->customer_name, 'Customer'),
        'status'   => $q->status,
    );
}
usort($recent_activities, function ($a, $b) { return $b['sort'] <=> $a['sort']; });
$recent_activities = array_slice($recent_activities, 0, 8);

$initials       = $customer ? gti_cd_initials($customer->name) : '?';
$customer_since = $customer ? gti_cd_fmt_date($customer->registered_date) : '-';
$last_contact   = $customer ? gti_cd_fmt_date($stats['last_contact'] ?: $customer->last_contact) : '-';
$full_location  = $customer
    ? trim(implode(', ', array_filter(array($customer->city, $customer->province, $customer->country))), ', ')
    : '';

// The edit modal and the row actions read the record from here rather than
// from PHP interpolated into a <script> block (PRD §13.8).
gti_page_data( array(
    'customer' => $customer ? array_map( 'strval', (array) $customer ) : array(),
) );

gti_dashboard_open( array(
    'page'     => 'customer-detail',
    'title'    => $customer ? $customer->name : 'Customer Detail',
    'subtitle' => $customer && $customer->company ? $customer->company : '',
    'cap'      => 'gti_manage_customers',
    'js'       => array( 'customer-detail' ),
) );
?>


            <!-- Content -->
            <div class="gti-content" style="flex-direction:column">

                <?php if (!$customer): ?>
                    <div class="gti-cd-top-bar">
                        <a href="<?php echo esc_url(gti_dashboard_url('customers')); ?>" class="gti-cd-back">
                            <i class="fas fa-arrow-left"></i> Back to Customers
                        </a>
                    </div>
                    <div class="gti-cd-section" style="text-align:center;padding:64px 24px">
                        <i class="fas fa-user-slash" style="font-size:48px;color:#d1d5db;display:block;margin-bottom:16px"></i>
                        <div style="font-size:16px;font-weight:600;color:#374151;margin-bottom:6px">Customer not found</div>
                        <p style="color:#9ca3af;font-size:14px;margin:0">
                            <?php echo $customer_id_param
                                ? 'No customer matches the id "' . esc_html($customer_id_param) . '".'
                                : 'No customer id was supplied.'; ?>
                        </p>
                        <a href="<?php echo esc_url(gti_dashboard_url('customers')); ?>" class="gti-cd-btn gti-cd-btn-primary" style="margin-top:20px">
                            <i class="fas fa-users"></i> Browse Customers
                        </a>
                    </div>
                <?php else: ?>

                <!-- Back Link + Actions (inline) -->
                <div class="gti-cd-top-bar">
                    <a href="<?php echo esc_url(gti_dashboard_url('customers')); ?>" class="gti-cd-back">
                        <i class="fas fa-arrow-left"></i> Back to Customers
                    </a>

                    <div class="gti-cd-action-bar">
                        <?php if ($customer->email): ?>
                        <button type="button" class="gti-cd-btn gti-cd-btn-outline" id="cd-email-btn">
                            <i class="fas fa-envelope"></i> Send Email
                        </button>
                        <?php endif; ?>
                        <button type="button" class="gti-cd-btn gti-cd-btn-outline" id="cd-edit-btn">
                            <i class="fas fa-pen"></i> Edit Customer
                        </button>
                    <div class="gti-cd-more-wrap">
                        <button class="gti-cd-more-btn" id="cd-more-toggle">
                            More Actions <i class="fas fa-chevron-down"></i>
                        </button>
                        <?php /* Create Quotation, Request Equipment, Log Call and Export Data
                                 were href="#" with nothing behind them and no module to build
                                 on, so they are gone rather than left as decoration (B-05). */ ?>
                        <div class="gti-cd-more-dropdown" id="cd-more-dropdown">
                            <button type="button" class="gti-cd-more-item" id="cd-status-btn">
                                <i class="fas fa-tag"></i>
                                <?php echo $customer->status === 'active' ? 'Set Inactive' : 'Set Active'; ?>
                            </button>
                            <button type="button" class="gti-cd-more-item" id="cd-delete-btn" style="color:#dc2626">
                                <i class="fas fa-trash" style="color:#dc2626"></i> Delete Customer
                            </button>
                        </div>
                    </div>
                    </div>
                </div>

                <!-- 1. Customer Profile Card -->
                <div class="gti-cd-profile">
                    <div class="gti-cd-profile-top">
                        <div class="gti-cd-profile-left">
                            <div class="gti-cd-avatar-lg"><?php echo esc_html($initials); ?></div>
                            <div class="gti-cd-profile-info">
                                <div class="gti-cd-profile-name"><?php echo esc_html($customer->name); ?></div>
                                <span class="gti-cd-profile-status <?php echo esc_attr($customer->status === 'active' ? 'active' : 'inactive'); ?>">
                                    <?php echo $customer->status === 'active' ? 'Active Customer' : 'Inactive Customer'; ?>
                                </span>
                                <div class="gti-cd-profile-company"><?php echo esc_html(gti_cd_val($customer->company, 'Individual customer')); ?></div>
                            </div>
                        </div>
                        <div class="gti-cd-profile-right">
                            <div class="gti-cd-kpi-box">
                                <div class="gti-cd-kpi-value"><?php echo (int) ($stats['requests'] + $stats['quotations']); ?></div>
                                <div class="gti-cd-kpi-label">Total Transactions</div>
                            </div>
                            <div class="gti-cd-kpi-box">
                                <div class="gti-cd-kpi-value" style="font-size:17px"><?php echo esc_html(gti_cd_fmt_currency($stats['total_spent'])); ?></div>
                                <div class="gti-cd-kpi-label">Total Spent</div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Contact Meta -->
                    <div class="gti-cd-meta-grid">
                        <div class="gti-cd-meta-item">
                            <i class="fas fa-envelope"></i>
                            <a href="mailto:<?php echo esc_attr($customer->email); ?>"><?php echo esc_html($customer->email); ?></a>
                        </div>
                        <div class="gti-cd-meta-item">
                            <i class="fas fa-phone"></i>
                            <span><?php echo esc_html(gti_cd_val($customer->phone)); ?></span>
                        </div>
                        <div class="gti-cd-meta-item">
                            <i class="fas fa-building"></i>
                            <span><?php echo esc_html(gti_cd_val($customer->industry)); ?></span>
                        </div>
                        <div class="gti-cd-meta-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?php echo esc_html(gti_cd_val($full_location)); ?></span>
                        </div>
                    </div>

                    <!-- Metadata Group -->
                    <div class="gti-cd-meta-group">
                        <div class="gti-cd-info-field">
                            <label><i class="fas fa-calendar" style="margin-right:4px"></i> Customer Since</label>
                            <span><?php echo esc_html($customer_since); ?></span>
                        </div>
                        <div class="gti-cd-info-field">
                            <label><i class="fas fa-phone-volume" style="margin-right:4px"></i> Last Contact</label>
                            <span><?php echo esc_html($last_contact); ?></span>
                        </div>
                        <div class="gti-cd-info-field">
                            <label><i class="fas fa-fingerprint" style="margin-right:4px"></i> Customer ID</label>
                            <span style="font-family:monospace"><?php echo esc_html($customer->customer_id); ?></span>
                        </div>
                    </div>
                </div>

                <!-- 2. Navigation Tabs -->
                <div class="gti-cd-tabs">
                    <a href="#tab-overview" class="gti-cd-tab active" data-tab="overview">Overview</a>
                    <a href="#tab-request" class="gti-cd-tab" data-tab="request">Request &amp; Inquiry</a>
                    <a href="#tab-quotations" class="gti-cd-tab" data-tab="quotations">Quotations</a>
                    <a href="#tab-transactions" class="gti-cd-tab" data-tab="transactions">Transactions</a>
                    <a href="#tab-activity" class="gti-cd-tab" data-tab="activity">Activity Log</a>
                </div>

                <!-- Tab: Overview (default visible) -->
                <div class="gti-cd-tab-content" id="tab-overview">

                    <!-- 3. Company Information -->
                    <div class="gti-cd-section">
                        <div class="gti-cd-section-title"><i class="fas fa-building"></i> Company Information</div>
                        <div class="gti-cd-info-grid">
                            <div class="gti-cd-info-field">
                                <label>Company Name</label>
                                <span><?php echo esc_html(gti_cd_val($customer->company)); ?></span>
                            </div>
                            <div class="gti-cd-info-field">
                                <label>NPWP</label>
                                <span><?php echo esc_html(gti_cd_val($customer->npwp)); ?></span>
                            </div>
                            <div class="gti-cd-info-field">
                                <label>Industry</label>
                                <span><?php echo esc_html(gti_cd_val($customer->industry)); ?></span>
                            </div>
                            <div class="gti-cd-info-field">
                                <label>Company Phone</label>
                                <span><?php echo esc_html(gti_cd_val($customer->phone)); ?></span>
                            </div>
                            <div class="gti-cd-info-field">
                                <label>Company Email</label>
                                <span><a href="mailto:<?php echo esc_attr($customer->email); ?>"><?php echo esc_html($customer->email); ?></a></span>
                            </div>
                            <div class="gti-cd-info-field">
                                <label>Website</label>
                                <span>
                                    <?php if ($customer->website): ?>
                                        <a href="<?php echo esc_url(preg_match('#^https?://#', $customer->website) ? $customer->website : 'https://' . $customer->website); ?>" target="_blank" rel="noopener"><?php echo esc_html($customer->website); ?></a>
                                    <?php else: ?>—<?php endif; ?>
                                </span>
                            </div>
                            <div class="gti-cd-info-field gti-cd-info-field-full">
                                <label>Address</label>
                                <span><?php echo esc_html(gti_cd_val($customer->address)); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Contact Person -->
                    <div class="gti-cd-section">
                        <div class="gti-cd-section-title"><i class="fas fa-user-tie"></i> Contact Person</div>
                        <div class="gti-cd-info-grid">
                            <div class="gti-cd-info-field">
                                <label>Contact Name</label>
                                <span><?php echo esc_html(gti_cd_val($customer->contact_person ?: $customer->name)); ?></span>
                            </div>
                            <div class="gti-cd-info-field">
                                <label>Position</label>
                                <span><?php echo esc_html(gti_cd_val($customer->contact_position)); ?></span>
                            </div>
                            <div class="gti-cd-info-field">
                                <label>Phone</label>
                                <span><?php echo esc_html(gti_cd_val($customer->contact_phone ?: $customer->phone)); ?></span>
                            </div>
                            <?php $contact_email = $customer->contact_email ?: $customer->email; ?>
                            <?php $contact_wa = $customer->contact_whatsapp ?: $customer->phone; ?>
                            <div class="gti-cd-info-field">
                                <label>Email</label>
                                <span>
                                    <?php if ($contact_email): ?>
                                        <a href="mailto:<?php echo esc_attr($contact_email); ?>"><?php echo esc_html($contact_email); ?></a>
                                    <?php else: ?>—<?php endif; ?>
                                </span>
                            </div>
                            <div class="gti-cd-info-field">
                                <label>WhatsApp</label>
                                <span>
                                    <?php if ($contact_wa): ?>
                                        <a href="https://wa.me/<?php echo esc_attr(preg_replace('/[^0-9]/', '', $contact_wa)); ?>" target="_blank" rel="noopener"><?php echo esc_html($contact_wa); ?></a>
                                    <?php else: ?>—<?php endif; ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- 7. Customer Statistics -->
                    <div class="gti-cd-section">
<?php
                        $approved_quotations = array_values(array_filter($quotations, function ($q) {
                            return in_array($q->status, array('approved', 'completed'), true);
                        }));
                        ?>
                        <div class="gti-cd-section-title"><i class="fas fa-chart-bar"></i> Customer Statistics</div>
                        <div class="gti-cd-stats-grid">
                            <div class="gti-cd-stat-card">
                                <div class="gti-cd-stat-card-header">
                                    <div class="gti-cd-stat-card-icon q"><i class="fas fa-file-invoice"></i></div>
                                    <a href="#tab-quotations" class="gti-cd-tab" style="padding:0;border:0;margin:0;font-size:12px" onclick="document.querySelector('[data-tab=quotations]').click()">View Details <i class="fas fa-arrow-right" style="font-size:10px"></i></a>
                                </div>
                                <div class="gti-cd-stat-card-value"><?php echo (int) $stats['quotations']; ?></div>
                                <div class="gti-cd-stat-card-label">Total Quotation</div>
                            </div>
                            <div class="gti-cd-stat-card">
                                <div class="gti-cd-stat-card-header">
                                    <div class="gti-cd-stat-card-icon p"><i class="fas fa-truck"></i></div>
                                    <a href="#tab-transactions" class="gti-cd-tab" style="padding:0;border:0;margin:0;font-size:12px" onclick="document.querySelector('[data-tab=transactions]').click()">View Details <i class="fas fa-arrow-right" style="font-size:10px"></i></a>
                                </div>
                                <div class="gti-cd-stat-card-value"><?php echo count($approved_quotations); ?></div>
                                <div class="gti-cd-stat-card-label">Total Purchase</div>
                            </div>
                            <div class="gti-cd-stat-card">
                                <div class="gti-cd-stat-card-header">
                                    <div class="gti-cd-stat-card-icon r"><i class="fas fa-car"></i></div>
                                </div>
                                <div class="gti-cd-stat-card-value">0</div>
                                <div class="gti-cd-stat-card-label">Total Rental</div>
                            </div>
                            <div class="gti-cd-stat-card">
                                <div class="gti-cd-stat-card-header">
                                    <div class="gti-cd-stat-card-icon i"><i class="fas fa-search"></i></div>
                                    <a href="#tab-request" class="gti-cd-tab" style="padding:0;border:0;margin:0;font-size:12px" onclick="document.querySelector('[data-tab=request]').click()">View Details <i class="fas fa-arrow-right" style="font-size:10px"></i></a>
                                </div>
                                <div class="gti-cd-stat-card-value"><?php echo (int) $stats['requests']; ?></div>
                                <div class="gti-cd-stat-card-label">Total Inquiry</div>
                            </div>
                        </div>
                    </div>

                    <!-- 5. Recent Activity -->
                    <div class="gti-cd-section">
                        <div class="gti-cd-activity-header">
                            <h3><i class="fas fa-history"></i> Recent Activity</h3>
                            <a href="#tab-activity" class="gti-cd-activity-link" onclick="document.querySelector('[data-tab=activity]').click()">View All Activity <i class="fas fa-arrow-right" style="font-size:11px"></i></a>
                        </div>
                        <table class="gti-cd-activity-table">
                            <thead>
                                <tr>
                                    <th style="width:180px">Date</th>
                                    <th style="width:200px">Activity</th>
                                    <th>Details</th>
                                    <th style="width:120px">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recent_activities)): ?>
                                    <tr><td colspan="4" style="color:#9ca3af;text-align:center;padding:40px 0">
                                        No requests or quotations from this customer yet.
                                    </td></tr>
                                <?php endif; ?>
                                <?php foreach ($recent_activities as $act): ?>
                                <tr>
                                    <td style="white-space:nowrap;color:#6b7280;font-size:12px"><?php echo esc_html($act['date']); ?></td>
                                    <td>
                                        <div class="gti-cd-activity-type">
                                            <div class="gti-cd-activity-type-icon" style="background:<?php echo $act['icon_bg']; ?>;color:<?php echo $act['icon_clr']; ?>">
                                                <i class="<?php echo $act['icon']; ?>"></i>
                                            </div>
                                            <?php echo esc_html($act['type']); ?>
                                        </div>
                                    </td>
                                    <td class="gti-cd-activity-detail"><?php echo esc_html($act['detail']); ?></td>
                                    <td>
                                        <span class="gti-badge-status <?php echo esc_attr(gti_cd_status_class($act['status'])); ?>">
                                            <?php echo esc_html(gti_cd_status_label($act['status'])); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Placeholder tabs -->
                <div class="gti-cd-tab-content" id="tab-request" style="display:none">
                    <div class="gti-cd-section">
                        <div class="gti-cd-section-title"><i class="fas fa-file-alt"></i> Request &amp; Inquiry</div>
                        <?php if (empty($requests)): ?>
                            <p style="color:#9ca3af;text-align:center;padding:40px 0">No requests found for this customer.</p>
                        <?php else: ?>
                            <table class="gti-cd-activity-table">
                                <thead>
                                    <tr>
                                        <th style="width:130px">Request ID</th>
                                        <th>Equipment</th>
                                        <th style="width:110px">Quantity</th>
                                        <th style="width:150px">Budget</th>
                                        <th style="width:120px">Status</th>
                                        <th style="width:120px">Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($requests as $r): ?>
                                        <tr>
                                            <td style="font-family:monospace;font-size:12px"><?php echo esc_html($r->request_id); ?></td>
                                            <td>
                                                <strong><?php echo esc_html(gti_cd_val($r->equipment)); ?></strong>
                                                <?php if ($r->brand || $r->category): ?>
                                                    <div style="font-size:12px;color:#9ca3af">
                                                        <?php echo esc_html(trim(implode(' · ', array_filter(array($r->brand, $r->category))))); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo (int) $r->quantity; ?></td>
                                            <td><?php echo esc_html($r->budget > 0 ? gti_cd_fmt_currency($r->budget) : '—'); ?></td>
                                            <td>
                                                <span class="gti-badge-status <?php echo esc_attr(gti_cd_status_class($r->status)); ?>">
                                                    <?php echo esc_html(gti_cd_status_label($r->status)); ?>
                                                </span>
                                            </td>
                                            <td style="white-space:nowrap;color:#6b7280;font-size:12px"><?php echo esc_html(gti_cd_fmt_date($r->created_at)); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="gti-cd-tab-content" id="tab-quotations" style="display:none">
                    <div class="gti-cd-section">
                        <div class="gti-cd-section-title"><i class="fas fa-file-invoice"></i> Quotations</div>
                        <?php if (empty($quotations)): ?>
                            <p style="color:#9ca3af;text-align:center;padding:40px 0">No quotations found for this customer.</p>
                        <?php else: ?>
                            <table class="gti-cd-activity-table">
                                <thead>
                                    <tr>
                                        <th style="width:150px">Quotation ID</th>
                                        <th>Items</th>
                                        <th style="width:160px">Total</th>
                                        <th style="width:130px">Sales PIC</th>
                                        <th style="width:130px">Status</th>
                                        <th style="width:120px">Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($quotations as $q): ?>
                                        <?php
                                        $items = json_decode($q->items, true);
                                        $items = is_array($items) ? $items : array();
                                        $item_names = array();
                                        foreach ($items as $item) {
                                            if (!empty($item['name'])) $item_names[] = $item['name'];
                                        }
                                        ?>
                                        <tr>
                                            <td style="font-family:monospace;font-size:12px"><?php echo esc_html($q->quotation_id); ?></td>
                                            <td class="gti-cd-activity-detail">
                                                <?php echo $item_names
                                                    ? esc_html(implode(', ', $item_names))
                                                    : '<span style="color:#9ca3af">' . esc_html(gti_cd_val($q->additional_notes, 'No items listed')) . '</span>'; ?>
                                            </td>
                                            <td><strong><?php echo esc_html(gti_cd_fmt_currency($q->total)); ?></strong></td>
                                            <td><?php echo esc_html(gti_cd_val($q->sales_pic, 'Unassigned')); ?></td>
                                            <td>
                                                <span class="gti-badge-status <?php echo esc_attr(gti_cd_status_class($q->status)); ?>">
                                                    <?php echo esc_html(gti_cd_status_label($q->status)); ?>
                                                </span>
                                            </td>
                                            <td style="white-space:nowrap;color:#6b7280;font-size:12px"><?php echo esc_html(gti_cd_fmt_date($q->created_at)); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="gti-cd-tab-content" id="tab-transactions" style="display:none">
                    <div class="gti-cd-section">
                        <div class="gti-cd-section-title"><i class="fas fa-shopping-cart"></i> Transactions</div>
                        <?php if (empty($approved_quotations)): ?>
                            <p style="color:#9ca3af;text-align:center;padding:40px 0">No approved or completed quotations yet.</p>
                        <?php else: ?>
                            <table class="gti-cd-activity-table">
                                <thead>
                                    <tr>
                                        <th style="width:150px">Reference</th>
                                        <th>Delivery Location</th>
                                        <th style="width:160px">Amount</th>
                                        <th style="width:130px">Status</th>
                                        <th style="width:120px">Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($approved_quotations as $q): ?>
                                        <tr>
                                            <td style="font-family:monospace;font-size:12px"><?php echo esc_html($q->quotation_id); ?></td>
                                            <td class="gti-cd-activity-detail"><?php echo esc_html(gti_cd_val($q->delivery_location)); ?></td>
                                            <td><strong><?php echo esc_html(gti_cd_fmt_currency($q->total)); ?></strong></td>
                                            <td>
                                                <span class="gti-badge-status <?php echo esc_attr(gti_cd_status_class($q->status)); ?>">
                                                    <?php echo esc_html(gti_cd_status_label($q->status)); ?>
                                                </span>
                                            </td>
                                            <td style="white-space:nowrap;color:#6b7280;font-size:12px"><?php echo esc_html(gti_cd_fmt_date($q->created_at)); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="2" style="font-weight:600">Total Spent</td>
                                        <td colspan="3" style="font-weight:700"><?php echo esc_html(gti_cd_fmt_currency($stats['total_spent'])); ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="gti-cd-tab-content" id="tab-activity" style="display:none">
                    <div class="gti-cd-section">
                        <div class="gti-cd-section-title"><i class="fas fa-history"></i> Activity Log</div>
                        <?php if (empty($activity_rows)): ?>
                            <p style="color:#9ca3af;text-align:center;padding:40px 0">No dashboard actions recorded against this customer yet.</p>
                        <?php else: ?>
                            <table class="gti-cd-activity-table">
                                <thead>
                                    <tr>
                                        <th style="width:180px">Date</th>
                                        <th style="width:160px">Action</th>
                                        <th>Description</th>
                                        <th style="width:150px">By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($activity_rows as $row): ?>
                                        <tr>
                                            <td style="white-space:nowrap;color:#6b7280;font-size:12px"><?php echo esc_html(gti_cd_fmt_datetime($row->created_at)); ?></td>
                                            <td style="font-weight:600"><?php echo esc_html(ucwords(str_replace('_', ' ', $row->action))); ?></td>
                                            <td class="gti-cd-activity-detail"><?php echo esc_html(gti_activity_row_description($row)); ?></td>
                                            <td style="color:#6b7280;font-size:12px"><?php echo esc_html($row->display_name ?: $row->user_login ?: 'System'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>

                <?php endif; ?>

            </div>

<?php
gti_dashboard_close( array(
    'modals' => array( 'delete', 'email', 'customer' ),
) );
