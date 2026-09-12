<?php
/**
 * Request Equipment — /dashboard/request-equipment  (PRD §6.5)
 *
 * Source: FluentForm ID 3 → wp_gti_requests.
 *
 * The page is thin on purpose: layout comes from gti_dashboard_open(), the
 * query from gti_query_list(), pagination and badges from the render helpers,
 * behaviour from assets/js/pages/request-equipment.js. Nothing here is copied
 * from a sibling template any more.
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$filters = array(
    'status'   => isset( $_GET['status'] )   ? sanitize_text_field( wp_unslash( $_GET['status'] ) )   : '',
    'brand'    => isset( $_GET['brand'] )    ? sanitize_text_field( wp_unslash( $_GET['brand'] ) )    : '',
    'category' => isset( $_GET['category'] ) ? sanitize_text_field( wp_unslash( $_GET['category'] ) ) : '',
);
$search   = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '';
$pic      = isset( $_GET['sales_pic'] ) ? sanitize_text_field( wp_unslash( $_GET['sales_pic'] ) ) : '';
$per_page = 10;

if ( $pic !== '' ) {
    $filters['sales_pic'] = $pic;
}

$query_args = array(
    'search'   => $search,
    'filters'  => $filters,
    'page'     => gti_current_page_num(),
    'per_page' => $per_page,
);

$result = gti_query_list( 'requests', $query_args );
// Same args, so the cards always agree with the table beneath them (§5.4).
$counts = gti_count_by_status( 'requests', $query_args );

$brands     = gti_distinct_values( 'requests', 'brand' );
$categories = gti_distinct_values( 'requests', 'category' );
$pics       = gti_distinct_values( 'requests', 'sales_pic' );

$query_params = array_filter( array_merge(
    array( 'gti_page' => 'request-equipment', 'search' => $search, 'sales_pic' => $pic ),
    $filters
) );

gti_dashboard_open( array(
    'page'     => 'request-equipment',
    'title'    => 'Request Equipment',
    'subtitle' => 'Track and respond to incoming equipment requests 📋',
    'cap'      => 'gti_manage_requests',
    'js'       => array( 'request-equipment' ),
) );
?>

<div class="gti-content">
    <div class="gti-content-left">

        <?php
        gti_render_stat_cards( array(
            array( 'label' => 'Total Requests', 'value' => $counts['all'] ?? 0,           'icon' => 'fa-file-alt',    'tone' => '' ),
            array( 'label' => 'New',            'value' => $counts['new'] ?? 0,           'icon' => 'fa-plus-circle', 'tone' => 'available' ),
            array( 'label' => 'Processing',     'value' => $counts['processing'] ?? 0,    'icon' => 'fa-spinner',     'tone' => 'reserved' ),
            array( 'label' => 'Proposal Sent',  'value' => $counts['proposal_sent'] ?? 0, 'icon' => 'fa-paper-plane', 'tone' => 'available' ),
            array( 'label' => 'Closed',         'value' => $counts['closed'] ?? 0,        'icon' => 'fa-check',       'tone' => 'sold' ),
        ) );
        ?>

        <form class="gti-ue-toolbar" method="get">
            <input type="hidden" name="gti_page" value="request-equipment">
            <div class="gti-ue-toolbar-left">
                <div class="gti-ue-search">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" placeholder="Search requests…" value="<?php echo esc_attr( $search ); ?>">
                </div>
                <div class="gti-ue-filter">
                    <select name="status">
                        <option value="">All Status</option>
                        <?php foreach ( gti_status_map( 'request' ) as $key => $spec ) : ?>
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
                            <option value="<?php echo esc_attr( $brand ); ?>" <?php selected( $filters['brand'], $brand ); ?>><?php echo esc_html( $brand ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="gti-ue-filter">
                    <select name="category">
                        <option value="">All Categories</option>
                        <?php foreach ( $categories as $category ) : ?>
                            <option value="<?php echo esc_attr( $category ); ?>" <?php selected( $filters['category'], $category ); ?>><?php echo esc_html( $category ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if ( $pics ) : ?>
                    <div class="gti-ue-filter">
                        <select name="sales_pic">
                            <option value="">All PIC</option>
                            <?php foreach ( $pics as $name ) : ?>
                                <option value="<?php echo esc_attr( $name ); ?>" <?php selected( $pic, $name ); ?>><?php echo esc_html( $name ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                <button type="submit" class="gti-ue-btn-reset"><i class="fas fa-filter"></i> Apply</button>
                <a href="<?php echo esc_url( gti_dashboard_url( 'request-equipment' ) ); ?>" class="gti-ue-btn-reset">
                    <i class="fas fa-rotate-right"></i> Reset
                </a>
            </div>
        </form>

        <div class="gti-ue-table-card">
            <table class="gti-ue-table">
                <thead>
                    <tr>
                        <th class="col-reqid">Request ID</th>
                        <th class="col-customer">Customer</th>
                        <th class="col-equipment">Requested Equipment</th>
                        <th class="col-qty">Qty</th>
                        <th class="col-location">Location</th>
                        <th class="col-budget">Budget</th>
                        <th class="col-status">Status</th>
                        <th class="col-date">Request Date</th>
                        <th class="col-pic">PIC</th>
                        <th class="col-actions"></th>
                    </tr>
                </thead>
                <tbody>
                <?php if ( ! $result['items'] ) : ?>
                    <tr>
                        <td colspan="10">
                            <?php gti_render_empty_state( 'fa-inbox', 'No requests found',
                                ( $search || array_filter( $filters ) )
                                    ? 'Try adjusting your filters.'
                                    : 'No equipment requests have come in yet.' ); ?>
                        </td>
                    </tr>
                <?php else : ?>
                    <?php foreach ( $result['items'] as $req ) :
                        $payload = gti_request_row_payload( $req );
                        ?>
                        <tr data-id="<?php echo (int) $req['id']; ?>"
                            data-row="<?php echo esc_attr( wp_json_encode( $payload ) ); ?>">
                            <td class="col-reqid"><strong><?php echo esc_html( $req['request_id'] ); ?></strong></td>
                            <td class="col-customer">
                                <strong><?php echo esc_html( $req['customer_name'] ); ?></strong>
                                <?php if ( ! empty( $req['customer_company'] ) ) : ?>
                                    <br><small><?php echo esc_html( $req['customer_company'] ); ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="col-equipment"><?php echo esc_html( $req['equipment'] ); ?></td>
                            <td class="col-qty"><?php echo esc_html( $req['quantity'] ?: '—' ); ?></td>
                            <td class="col-location"><?php echo esc_html( $req['location'] ?: '—' ); ?></td>
                            <td class="col-budget"><?php echo esc_html( $payload['budget_text'] ); ?></td>
                            <td class="col-status"><?php gti_render_status_badge( 'request', $req['status'] ); ?></td>
                            <td class="col-date"><?php echo esc_html( $payload['request_date_text'] ); ?></td>
                            <td class="col-pic"><?php echo esc_html( $req['sales_pic'] ?: 'Unassigned' ); ?></td>
                            <td class="col-actions">
                                <?php
                                $actions = array(
                                    array( 'label' => 'View Details', 'icon' => 'fa-eye',        'class' => 'js-view' ),
                                    array( 'label' => 'Assign PIC',   'icon' => 'fa-user-check', 'class' => 'js-assign' ),
                                    array( 'label' => 'Reply',        'icon' => 'fa-envelope',   'class' => 'js-reply' ),
                                    array( 'label' => 'Delete',       'icon' => 'fa-trash',      'class' => 'js-delete danger' ),
                                );
                                gti_render_action_menu( $actions );
                                ?>
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
                    'base_url' => gti_dashboard_url( 'request-equipment' ),
                    'params'   => $query_params,
                ) ); ?>
            <?php endif; ?>
        </div>
    </div><!-- /.gti-content-left -->

    <?php
    // The drawer renders server-side for the first row and is repainted in place
    // by the page script — no reload, so scroll position and drawer state survive.
    $first = $result['items'] ? $result['items'][0] : null;
    include GTI_CHILD_DIR . '/template-parts/dashboard/drawer-request.php';
    ?>
</div>

<?php
gti_dashboard_close( array(
    'modals' => array( 'delete', 'email', 'upload', 'assign' ),
) );
