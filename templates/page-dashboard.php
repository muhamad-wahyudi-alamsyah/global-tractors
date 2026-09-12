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
    'spare'      => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}gti_spare_parts WHERE deleted_at IS NULL" ),
    'customers'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}gti_customers" ),
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
    . gti_scope_where_sql( 'request' ) . ' ORDER BY created_at ASC LIMIT 10',
    ARRAY_A
);

$stale_quotations = $wpdb->get_results(
    "SELECT id, quotation_id AS ref, customer_name, created_at, 'quotation' AS entity
       FROM {$wpdb->prefix}gti_quotations
      WHERE ( status = 'new' AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR) )
         OR ( status = 'waiting_customer' AND valid_until IS NOT NULL
              AND valid_until <> '0000-00-00' AND valid_until < CURDATE() )"
    . gti_scope_where_sql( 'quotation' ) . ' ORDER BY created_at ASC LIMIT 10',
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

gti_dashboard_open( array(
    'page'     => 'dashboard',
    'title'    => 'Dashboard',
    'subtitle' => 'Overview of activity across GTI 📊',
    'cap'      => 'gti_access',
) );
?>

<div class="gti-content">
    <div class="gti-content-left">

        <div class="gti-stats-grid">
            <?php
            $cards = array();

            if ( current_user_can( 'gti_manage_equipment' ) ) {
                $cards[] = array( 'title' => 'Used Equipment',   'value' => $counts['used'],   'label' => 'Total unit', 'icon' => 'fa-truck',          'slug' => 'used-equipment' );
                $cards[] = array( 'title' => 'Rental Equipment', 'value' => $counts['rental'], 'label' => 'Total unit', 'icon' => 'fa-calendar-check', 'slug' => 'rental-equipment' );
            }
            if ( current_user_can( 'gti_manage_spare_parts' ) ) {
                $cards[] = array( 'title' => 'Spare Parts', 'value' => $counts['spare'], 'label' => 'Total part', 'icon' => 'fa-cogs', 'slug' => 'spare-parts' );
            }
            if ( current_user_can( 'gti_manage_customers' ) ) {
                $cards[] = array( 'title' => 'Customers', 'value' => $counts['customers'], 'label' => 'Registered', 'icon' => 'fa-users', 'slug' => 'customers' );
            }
            if ( current_user_can( 'gti_manage_requests' ) ) {
                $cards[] = array( 'title' => 'Request Equipment', 'value' => $requests['all'] ?? 0,   'label' => ( $requests['new'] ?? 0 ) . ' new',   'icon' => 'fa-file-alt',       'slug' => 'request-equipment' );
                $cards[] = array( 'title' => 'Sell Equipment',    'value' => $sells['all'] ?? 0,      'label' => ( $sells['new'] ?? 0 ) . ' new',      'icon' => 'fa-handshake',      'slug' => 'sell-equipment' );
            }
            if ( current_user_can( 'gti_manage_quotations' ) ) {
                $cards[] = array( 'title' => 'Request Quotation', 'value' => $quotations['all'] ?? 0, 'label' => ( $quotations['new'] ?? 0 ) . ' new', 'icon' => 'fa-clipboard-list', 'slug' => 'request-quotation' );
            }

            foreach ( $cards as $card ) :
                ?>
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

        <div class="gti-dash-columns">
            <div class="gti-card">
                <div class="gti-card-header">
                    <h3>Needs Attention</h3>
                    <span class="gti-card-hint"><?php echo count( $needs_attention ); ?> item(s)</span>
                </div>
                <div class="gti-card-body">
                    <?php if ( ! $needs_attention ) : ?>
                        <p class="gti-drawer-empty">Nothing is waiting. Everything new has been picked up.</p>
                    <?php else : ?>
                        <ul class="gti-dash-list">
                            <?php foreach ( $needs_attention as $item ) : ?>
                                <li>
                                    <a href="<?php echo esc_url( gti_dashboard_url( $slug[ $item['entity'] ] ) . '?highlight=' . (int) $item['id'] ); ?>">
                                        <strong><?php echo esc_html( $item['ref'] ?: '#' . $item['id'] ); ?></strong>
                                        <span><?php echo esc_html( $item['customer_name'] ); ?></span>
                                    </a>
                                    <time><?php echo esc_html( gti_time_ago( $item['created_at'] ) ); ?></time>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <div class="gti-card">
                <div class="gti-card-header">
                    <h3>Recent Activity</h3>
                    <?php if ( current_user_can( 'gti_manage_settings' ) ) : ?>
                        <a href="<?php echo esc_url( gti_dashboard_url( 'activity-log' ) ); ?>" class="gti-card-hint">View all</a>
                    <?php endif; ?>
                </div>
                <div class="gti-card-body">
                    <?php if ( ! $recent_activity ) : ?>
                        <p class="gti-drawer-empty">No activity recorded yet.</p>
                    <?php else : ?>
                        <ul class="gti-dash-list">
                            <?php foreach ( $recent_activity as $entry ) :
                                $icon = gti_get_action_icon( $entry['action'] );
                                ?>
                                <li>
                                    <span class="gti-dash-icon" style="background:<?php echo esc_attr( $icon['bg'] ); ?>;color:<?php echo esc_attr( $icon['color'] ); ?>">
                                        <i class="fas <?php echo esc_attr( $icon['icon'] ); ?>"></i>
                                    </span>
                                    <div>
                                        <strong><?php echo esc_html( $entry['description'] ?: ucwords( str_replace( '_', ' ', $entry['action'] ) ) ); ?></strong>
                                        <span><?php echo esc_html( $entry['display_name'] ?: 'System' ); ?></span>
                                    </div>
                                    <time><?php echo esc_html( gti_time_ago( $entry['created_at'] ) ); ?></time>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ( $inbox_total > 0 ) : ?>
            <div class="gti-card">
                <div class="gti-card-header"><h3>Inbox Summary</h3></div>
                <div class="gti-card-body">
                    <div class="gti-summary-rows">
                        <div class="gti-summary-row"><span>Total incoming</span><strong><?php echo esc_html( number_format_i18n( $inbox_total ) ); ?></strong></div>
                        <div class="gti-summary-row"><span>Awaiting first response</span><strong><?php echo esc_html( number_format_i18n( $inbox_new ) ); ?></strong></div>
                        <div class="gti-summary-row"><span>Quotations awaiting approval</span><strong><?php echo esc_html( number_format_i18n( $quotations['waiting_customer'] ?? 0 ) ); ?></strong></div>
                        <div class="gti-summary-row"><span>Offers approved</span><strong><?php echo esc_html( number_format_i18n( $sells['approved'] ?? 0 ) ); ?></strong></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
gti_dashboard_close();
