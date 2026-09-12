<?php
/**
 * Sell Equipment — /dashboard/sell-equipment  (PRD §6.7)
 *
 * Source: FluentForm ID 4 → wp_gti_sell_requests. These are offers *to* GTI, so
 * the outgoing action is a request for the seller's invoice, not an invoice we
 * issue (PRD §1.3).
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$filters = array(
    'status'          => isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '',
    'equipment_brand' => isset( $_GET['brand'] )  ? sanitize_text_field( wp_unslash( $_GET['brand'] ) )  : '',
);
$search   = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '';
$per_page = 10;

$query_args = array(
    'search'   => $search,
    'filters'  => $filters,
    'page'     => gti_current_page_num(),
    'per_page' => $per_page,
);

$result = gti_query_list( 'sell_requests', $query_args );
$counts = gti_count_by_status( 'sell_requests', $query_args );
$brands = gti_distinct_values( 'sell_requests', 'equipment_brand' );

$query_params = array_filter( array(
    'gti_page' => 'sell-equipment',
    'search'   => $search,
    'status'   => $filters['status'],
    'brand'    => $filters['equipment_brand'],
) );

gti_dashboard_open( array(
    'page'     => 'sell-equipment',
    'title'    => 'Sell Equipment',
    'subtitle' => 'Review equipment offered to GTI 🤝',
    'cap'      => 'gti_manage_requests',
    'js'       => array( 'sell-equipment' ),
) );
?>

<div class="gti-content">
    <div class="gti-content-left">

        <?php
        gti_render_stat_cards( array(
            array( 'label' => 'Total',             'value' => $counts['all'] ?? 0,               'icon' => 'fa-handshake',    'tone' => '' ),
            array( 'label' => 'New',               'value' => $counts['new'] ?? 0,               'icon' => 'fa-plus-circle',  'tone' => 'available' ),
            array( 'label' => 'Processing',        'value' => $counts['processing'] ?? 0,        'icon' => 'fa-spinner',      'tone' => 'reserved' ),
            array( 'label' => 'Approved',          'value' => $counts['approved'] ?? 0,          'icon' => 'fa-check-circle', 'tone' => 'available' ),
            array( 'label' => 'Invoice Requested', 'value' => $counts['invoice_requested'] ?? 0, 'icon' => 'fa-file-invoice', 'tone' => 'reserved' ),
            array( 'label' => 'Completed',         'value' => $counts['completed'] ?? 0,         'icon' => 'fa-flag-checkered', 'tone' => 'sold' ),
        ) );
        ?>

        <form class="gti-ue-toolbar" method="get">
            <input type="hidden" name="gti_page" value="sell-equipment">
            <div class="gti-ue-toolbar-left">
                <div class="gti-ue-search">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" placeholder="Search offers…" value="<?php echo esc_attr( $search ); ?>">
                </div>
                <div class="gti-ue-filter">
                    <select name="status">
                        <option value="">All Status</option>
                        <?php foreach ( gti_status_map( 'sell' ) as $key => $spec ) : ?>
                            <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $filters['status'], $key ); ?>>
                                <?php echo esc_html( $spec['label'] ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="gti-ue-filter">
                    <select name="brand">
                        <option value="">All Brands</option>
                        <?php foreach ( $brands as $brand ) : ?>
                            <option value="<?php echo esc_attr( $brand ); ?>" <?php selected( $filters['equipment_brand'], $brand ); ?>><?php echo esc_html( $brand ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="gti-ue-btn-reset"><i class="fas fa-filter"></i> Apply</button>
                <a href="<?php echo esc_url( gti_dashboard_url( 'sell-equipment' ) ); ?>" class="gti-ue-btn-reset">
                    <i class="fas fa-rotate-right"></i> Reset
                </a>
            </div>
        </form>

        <div class="gti-ue-table-card">
            <table class="gti-ue-table">
                <thead>
                    <tr>
                        <th class="col-customer">Seller</th>
                        <th class="col-equipment">Equipment</th>
                        <th class="col-brand">Brand</th>
                        <th class="col-year">Year</th>
                        <th class="col-budget">Offered Price</th>
                        <th class="col-status">Status</th>
                        <th class="col-date">Submitted</th>
                        <th class="col-actions"></th>
                    </tr>
                </thead>
                <tbody>
                <?php if ( ! $result['items'] ) : ?>
                    <tr>
                        <td colspan="8">
                            <?php gti_render_empty_state( 'fa-handshake', 'No offers found',
                                ( $search || array_filter( $filters ) )
                                    ? 'Try adjusting your filters.'
                                    : 'No one has offered equipment yet.' ); ?>
                        </td>
                    </tr>
                <?php else : ?>
                    <?php foreach ( $result['items'] as $sell ) :
                        $payload = gti_sell_row_payload( $sell );
                        ?>
                        <tr data-id="<?php echo (int) $sell['id']; ?>"
                            data-row="<?php echo esc_attr( wp_json_encode( $payload ) ); ?>">
                            <td class="col-customer">
                                <strong><?php echo esc_html( $sell['customer_name'] ); ?></strong>
                                <?php if ( ! empty( $sell['customer_company'] ) ) : ?>
                                    <br><small><?php echo esc_html( $sell['customer_company'] ); ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="col-equipment"><?php echo esc_html( $sell['equipment_name'] ?: '—' ); ?></td>
                            <td class="col-brand"><?php echo esc_html( $sell['equipment_brand'] ?: '—' ); ?></td>
                            <td class="col-year"><?php echo esc_html( $sell['equipment_year'] ?: '—' ); ?></td>
                            <td class="col-budget"><?php echo esc_html( $payload['price_text'] ); ?></td>
                            <td class="col-status"><?php gti_render_status_badge( 'sell', $sell['status'] ); ?></td>
                            <td class="col-date"><?php echo esc_html( $payload['submitted_text'] ); ?></td>
                            <td class="col-actions">
                                <?php gti_render_action_menu( array(
                                    array( 'label' => 'View Details',    'icon' => 'fa-eye',          'class' => 'js-view' ),
                                    array( 'label' => 'Contact on WhatsApp', 'icon' => 'fa-whatsapp', 'class' => 'js-whatsapp' ),
                                    array( 'label' => 'Reply by Email',  'icon' => 'fa-envelope',     'class' => 'js-reply' ),
                                    array( 'label' => 'Delete',          'icon' => 'fa-trash',        'class' => 'js-delete danger' ),
                                ) ); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>

            <?php if ( $result['pages'] > 1 ) : ?>
                <?php gti_render_pagination( array(
                    'total'    => $result['total'],
                    'per_page' => $per_page,
                    'current'  => $result['page'],
                    'base_url' => gti_dashboard_url( 'sell-equipment' ),
                    'params'   => $query_params,
                ) ); ?>
            <?php endif; ?>
        </div>
    </div><!-- /.gti-content-left -->

    <?php
    $first = $result['items'] ? $result['items'][0] : null;
    include GTI_CHILD_DIR . '/template-parts/dashboard/drawer-sell.php';
    ?>
</div>

<?php
gti_dashboard_close( array(
    'modals' => array( 'delete', 'email', 'invoice', 'assign' ),
) );
