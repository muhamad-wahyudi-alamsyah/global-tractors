<?php
/**
 * Dashboard home — /dashboard  (PRD §6.14)
 *
 * Every figure on this page used to be a literal in the markup: 148 used units,
 * 86 rentals, 256 spare parts, and "View More" links pointing at "#". They are
 * now real counts, scoped the same way the inbox pages are — a sales user sees
 * their own workload, not the company's.
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;

$counts = array(
    'used'       => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}gti_equipment WHERE type = 'used' AND deleted_at IS NULL" ),
    'rental'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}gti_equipment WHERE type = 'rental' AND deleted_at IS NULL" ),
    'spare'      => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}gti_spare_parts" ),
    'customers'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}gti_customers WHERE deleted_at IS NULL" ),
);

$requests   = gti_count_by_status( 'requests' );
$quotations = gti_count_by_status( 'quotations' );
$sells      = gti_count_by_status( 'sell_requests' );

$inbox_total = ( $requests['all'] ?? 0 ) + ( $quotations['all'] ?? 0 ) + ( $sells['all'] ?? 0 );
$inbox_new   = ( $requests['new'] ?? 0 ) + ( $quotations['new'] ?? 0 ) + ( $sells['new'] ?? 0 );

// Rows that have gone quiet: new for over a day, or a quotation past its
// validity date with no answer (PRD §6.14).
$stale_requests = $wpdb->get_results(
    "SELECT id, request_id AS ref, customer_name, created_at, 'request' AS entity
       FROM {$wpdb->prefix}gti_requests
      WHERE status = 'new' AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)"
    . gti_scope_where_sql( 'request' ) . ' ORDER BY created_at ASC',
    ARRAY_A
);

$stale_quotations = $wpdb->get_results(
    "SELECT id, quotation_id AS ref, customer_name, created_at, 'quotation' AS entity
       FROM {$wpdb->prefix}gti_quotations
      WHERE ( ( status = 'new' AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR) )
         OR ( status = 'waiting_customer' AND valid_until IS NOT NULL
              AND valid_until <> '0000-00-00' AND valid_until < CURDATE() ) )"
    . gti_scope_where_sql( 'quotation' ) . ' ORDER BY created_at ASC',
    ARRAY_A
);

$needs_attention = array_merge( (array) $stale_requests, (array) $stale_quotations );

$recent_activity = $wpdb->get_results(
    "SELECT l.*, u.display_name
       FROM {$wpdb->prefix}gti_activity_log l
  LEFT JOIN {$wpdb->users} u ON u.ID = l.user_id
   ORDER BY l.created_at DESC
      LIMIT 10",
    ARRAY_A
);

$slug = array( 'request' => 'request-equipment', 'quotation' => 'request-quotation', 'sell' => 'sell-equipment' );

$can_inbox = current_user_can( 'gti_manage_requests' ) || current_user_can( 'gti_manage_quotations' );

// Laid out as the dashboard was before PRD v2 — five headline cards, a row of
// smaller cards, two charts, three cards side by side — holding the real
// figures PRD v2 put in place of the placeholder ones. The charts once drew
// invented numbers; the donut now counts the inbox and the line comes from
// Histats, and each card says so when its figures are missing (§6.14).
$primary = array();
if ( current_user_can( 'gti_manage_equipment' ) ) {
    $primary[] = array( 'title' => 'Used Equipment',   'value' => $counts['used'],   'label' => 'Total unit', 'icon' => 'fa-truck',          'slug' => 'used-equipment' );
    $primary[] = array( 'title' => 'Rental Equipment', 'value' => $counts['rental'], 'label' => 'Total unit', 'icon' => 'fa-calendar-check', 'slug' => 'rental-equipment' );
}
if ( current_user_can( 'gti_manage_spare_parts' ) ) {
    $primary[] = array( 'title' => 'Spare Parts', 'value' => $counts['spare'], 'label' => 'Total part', 'icon' => 'fa-cogs', 'slug' => 'spare-parts' );
}
if ( current_user_can( 'gti_manage_customers' ) ) {
    $primary[] = array( 'title' => 'Customers', 'value' => $counts['customers'], 'label' => 'Registered', 'icon' => 'fa-users', 'slug' => 'customers' );
}
if ( current_user_can( 'gti_manage_requests' ) ) {
    $primary[] = array( 'title' => 'Request Equipment', 'value' => $requests['all'] ?? 0, 'label' => ( $requests['new'] ?? 0 ) . ' new', 'icon' => 'fa-file-alt', 'slug' => 'request-equipment' );
}

$secondary = array();
if ( current_user_can( 'gti_manage_quotations' ) ) {
    $secondary[] = array( 'title' => 'Request Quotation', 'value' => $quotations['all'] ?? 0, 'label' => ( $quotations['new'] ?? 0 ) . ' new', 'icon' => 'fa-clipboard-list', 'slug' => 'request-quotation' );
}
if ( current_user_can( 'gti_manage_requests' ) ) {
    $secondary[] = array( 'title' => 'Sell Equipment', 'value' => $sells['all'] ?? 0, 'label' => ( $sells['new'] ?? 0 ) . ' new', 'icon' => 'fa-handshake', 'slug' => 'sell-equipment' );
}
if ( $can_inbox ) {
    $secondary[] = array( 'title' => 'Needs Attention', 'value' => count( $needs_attention ), 'label' => 'Waiting too long', 'icon' => 'fa-exclamation-circle', 'slug' => '' );
    $secondary[] = array( 'title' => 'Awaiting Response', 'value' => $inbox_new, 'label' => 'New in the inbox', 'icon' => 'fa-inbox', 'slug' => '' );
}

// Chart cards. The donut re-uses the counts above, so it costs no extra query;
// the line is whatever Histats knows about the public site, empty until the
// gti_histats_sid option holds the site's Histats id.
$visitors = gti_histats_daily( 30 );

$overview = array(
    array( 'label' => 'Request Equipment', 'value' => (int) ( $requests['all'] ?? 0 ),   'color' => '#F5A623' ),
    array( 'label' => 'Request Quotation', 'value' => (int) ( $quotations['all'] ?? 0 ), 'color' => '#1a1f36' ),
    array( 'label' => 'Sell Equipment',    'value' => (int) ( $sells['all'] ?? 0 ),      'color' => '#6b7280' ),
);
$overview_total = array_sum( array_column( $overview, 'value' ) );
$show_overview  = $can_inbox && $overview_total > 0;

gti_page_data( array(
    'visitors' => $visitors,
    'overview' => $show_overview ? array_column( $overview, 'value' ) : array(),
) );

gti_dashboard_open( array(
    'page'     => 'dashboard',
    'title'    => 'Dashboard',
    'subtitle' => gti_welcome_back(),
    'js'       => array( 'dashboard' ),
    'cap'      => 'gti_access',
) );
?>

<!-- Content -->
<div class="gti-content">
    <?php if ( $primary ) : ?>
        <!-- Stats Row 1 -->
        <div class="gti-stats-grid">
            <?php foreach ( $primary as $card ) : ?>
                <div class="gti-stat-card">
                    <div class="gti-stat-content">
                        <div class="gti-stat-icon"><i class="fas <?php echo esc_attr( $card['icon'] ); ?>"></i></div>
                        <div class="gti-stat-info">
                            <p class="gti-stat-title"><?php echo esc_html( $card['title'] ); ?></p>
                            <p class="gti-stat-value"><?php echo esc_html( number_format_i18n( $card['value'] ) ); ?></p>
                            <p class="gti-stat-label"><?php echo esc_html( $card['label'] ); ?></p>
                        </div>
                    </div>
                    <div class="gti-stat-footer">
                        <a href="<?php echo esc_url( gti_dashboard_url( $card['slug'] ) ); ?>">View More</a>
                        <i class="fas fa-arrow-right"></i>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ( $secondary ) : ?>
        <!-- Stats Row 2 -->
        <div class="gti-stats-row">
            <?php foreach ( $secondary as $card ) :
                $tag = $card['slug'] ? 'a' : 'div';
                ?>
                <<?php echo $tag; ?> class="gti-stat-card"<?php if ( $card['slug'] ) : ?> href="<?php echo esc_url( gti_dashboard_url( $card['slug'] ) ); ?>"<?php endif; ?>>
                    <div class="gti-stat-icon"><i class="fas <?php echo esc_attr( $card['icon'] ); ?>"></i></div>
                    <div class="gti-stat-info">
                        <p><?php echo esc_html( $card['title'] ); ?></p>
                        <strong><?php echo esc_html( number_format_i18n( $card['value'] ) ); ?></strong>
                        <span><?php echo esc_html( $card['label'] ); ?></span>
                    </div>
                </<?php echo $tag; ?>>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Charts Row -->
    <div class="gti-charts-row"<?php if ( ! $show_overview ) : ?> style="grid-template-columns:1fr"<?php endif; ?>>
        <div class="gti-card gti-chart-card">
            <div class="gti-card-header">
                <h3>Website Visitors</h3>
                <span class="gti-dash-more">Last 30 Days</span>
            </div>
            <div class="gti-card-body">
                <?php if ( $visitors ) : ?>
                    <canvas id="visitorsChart"></canvas>
                <?php else : ?>
                    <p class="gti-dash-empty">Data pengunjung belum tersedia.</p>
                <?php endif; ?>
            </div>
        </div>

        <?php if ( $show_overview ) : ?>
            <div class="gti-card gti-chart-card">
                <div class="gti-card-header">
                    <h3>Request Overview</h3>
                    <span class="gti-dash-more">All time</span>
                </div>
                <div class="gti-card-body gti-donut-wrapper">
                    <div class="gti-donut-chart">
                        <canvas id="requestChart"></canvas>
                        <div class="gti-donut-center">
                            <strong><?php echo esc_html( number_format_i18n( $overview_total ) ); ?></strong>
                            <span>Total</span>
                        </div>
                    </div>
                    <div class="gti-donut-legend">
                        <?php foreach ( $overview as $segment ) : ?>
                            <div class="gti-legend-item">
                                <span class="gti-legend-dot" style="background:<?php echo esc_attr( $segment['color'] ); ?>"></span>
                                <span class="gti-legend-label"><?php echo esc_html( $segment['label'] ); ?></span>
                                <span class="gti-legend-value"><?php echo esc_html( number_format_i18n( $segment['value'] ) . ' (' . round( $segment['value'] / $overview_total * 100 ) . '%)' ); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bottom Row -->
    <div class="gti-bottom-row">
        <!-- Recent Activity -->
        <div class="gti-card">
            <div class="gti-card-header"><h3>Recent Activity</h3></div>
            <div class="gti-card-body">
                <?php if ( ! $recent_activity ) : ?>
                    <p class="gti-dash-empty">No activity recorded yet.</p>
                <?php else : ?>
                    <div class="gti-activity-list">
                        <?php foreach ( array_slice( $recent_activity, 0, 5 ) as $entry ) :
                            $icon = gti_get_action_icon( $entry['action'] );
                            ?>
                            <div class="gti-activity-item">
                                <div class="gti-activity-icon" style="background:<?php echo esc_attr( $icon['bg'] ); ?>;color:<?php echo esc_attr( $icon['color'] ); ?>"><i class="fas <?php echo esc_attr( $icon['icon'] ); ?>"></i></div>
                                <div class="gti-activity-info">
                                    <p><strong><?php echo esc_html( $entry['description'] ?: ucwords( str_replace( '_', ' ', $entry['action'] ) ) ); ?></strong></p>
                                    <small><?php echo esc_html( $entry['display_name'] ?: 'System' ); ?></small>
                                </div>
                                <div class="gti-activity-time"><?php echo esc_html( gti_time_ago( $entry['created_at'] ) ); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if ( current_user_can( 'gti_manage_settings' ) ) : ?>
                    <div class="gti-card-footer"><a href="<?php echo esc_url( gti_dashboard_url( 'activity-log' ) ); ?>">View All Activities <i class="fas fa-arrow-right"></i></a></div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ( $can_inbox ) : ?>
            <!-- Needs Attention: new for over a day, or a quotation past its validity date -->
            <div class="gti-card">
                <div class="gti-card-header"><h3>Needs Attention</h3></div>
                <div class="gti-card-body">
                    <?php if ( ! $needs_attention ) : ?>
                        <p class="gti-dash-empty">Nothing is waiting. Everything new has been picked up.</p>
                    <?php else : ?>
                        <div class="gti-pending-list">
                            <?php foreach ( array_slice( $needs_attention, 0, 5 ) as $item ) :
                                $is_quotation = $item['entity'] === 'quotation';
                                ?>
                                <div class="gti-pending-item">
                                    <div class="gti-pending-thumb <?php echo $is_quotation ? 'blue' : 'orange'; ?>"><i class="fas <?php echo $is_quotation ? 'fa-clipboard-list' : 'fa-file-alt'; ?>"></i></div>
                                    <div class="gti-pending-info">
                                        <p><strong><?php echo $is_quotation ? 'Request Quotation' : 'Request Equipment'; ?></strong></p>
                                        <p class="detail"><?php echo esc_html( ( $item['ref'] ?: '#' . $item['id'] ) . ' - ' . $item['customer_name'] ); ?></p>
                                        <small><?php echo esc_html( gti_time_ago( $item['created_at'] ) ); ?></small>
                                    </div>
                                    <a href="<?php echo esc_url( gti_dashboard_url( $slug[ $item['entity'] ] ) . '?highlight=' . (int) $item['id'] ); ?>" class="gti-btn-review">Review</a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if ( count( $needs_attention ) > 5 ) : ?>
                            <div class="gti-card-footer"><span class="gti-dash-more">+<?php echo (int) ( count( $needs_attention ) - 5 ); ?> more waiting</span></div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Inbox Summary -->
            <div class="gti-card">
                <div class="gti-card-header"><h3>Inbox Summary</h3></div>
                <div class="gti-card-body">
                    <div class="gti-summary-list">
                        <div class="gti-summary-item"><span>Total incoming</span><strong><?php echo esc_html( number_format_i18n( $inbox_total ) ); ?></strong></div>
                        <div class="gti-summary-item"><span>Awaiting first response</span><strong><?php echo esc_html( number_format_i18n( $inbox_new ) ); ?></strong></div>
                        <div class="gti-summary-item"><span>Quotations awaiting approval</span><strong><?php echo esc_html( number_format_i18n( $quotations['waiting_customer'] ?? 0 ) ); ?></strong></div>
                        <div class="gti-summary-item"><span>Offers approved</span><strong><?php echo esc_html( number_format_i18n( $sells['approved'] ?? 0 ) ); ?></strong></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
gti_dashboard_close();
