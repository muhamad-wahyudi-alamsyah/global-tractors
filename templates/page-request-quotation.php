<?php
/**
 * Request Quotation — /dashboard/request-quotation  (PRD §6.6)
 *
 * Source: the three catalogue shortcodes → wp_gti_quotations.
 *
 * Row-level scoping matters here: a gti_sales user without gti_view_all_requests
 * sees only rows assigned to them, and because the list, the count and the stat
 * cards all go through gti_query_list()/gti_count_by_status() with the same
 * args, the cards cannot drift out of step with the table (§6.6.1).
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$filters = array(
    'status'         => isset( $_GET['status'] )         ? sanitize_text_field( wp_unslash( $_GET['status'] ) )         : '',
    'sales_pic'      => isset( $_GET['sales_pic'] )      ? sanitize_text_field( wp_unslash( $_GET['sales_pic'] ) )      : '',
    'equipment_type' => isset( $_GET['equipment_type'] ) ? sanitize_text_field( wp_unslash( $_GET['equipment_type'] ) ) : '',
);
$search    = isset( $_GET['search'] )    ? sanitize_text_field( wp_unslash( $_GET['search'] ) )    : '';
$date_from = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '';
$date_to   = isset( $_GET['date_to'] )   ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) )   : '';
$per_page  = 10;

$query_args = array(
    'search'    => $search,
    'filters'   => $filters,
    'page'      => gti_current_page_num(),
    'per_page'  => $per_page,
    'date_from' => $date_from,
    'date_to'   => $date_to,
);

$result = gti_query_list( 'quotations', $query_args );
$counts = gti_count_by_status( 'quotations', $query_args );
$pics   = gti_distinct_values( 'quotations', 'sales_pic' );

$query_params = array_filter( array_merge(
    array(
        'gti_page'  => 'request-quotation',
        'search'    => $search,
        'date_from' => $date_from,
        'date_to'   => $date_to,
    ),
    $filters
) );

gti_dashboard_open( array(
    'page'     => 'request-quotation',
    'title'    => 'Request Quotation',
    'subtitle' => 'Prepare and send quotations to customers 📄',
    'cap'      => 'gti_manage_quotations',
    'js'       => array( 'request-quotation' ),
) );
?>

<div class="gti-content">
    <div class="gti-content-left">

        <?php
        gti_render_stat_cards( array(
            array( 'label' => 'Total',        'value' => $counts['all'] ?? 0,              'icon' => 'fa-clipboard-list', 'tone' => '' ),
            array( 'label' => 'New',          'value' => $counts['new'] ?? 0,              'icon' => 'fa-plus-circle',    'tone' => 'available' ),
            array( 'label' => 'Processing',   'value' => $counts['processing'] ?? 0,       'icon' => 'fa-spinner',        'tone' => 'reserved' ),
            // Label per §5.1: the DB key stays 'waiting_customer' so existing rows remain valid.
            array( 'label' => 'Waiting Approval', 'value' => $counts['waiting_customer'] ?? 0, 'icon' => 'fa-hourglass-half', 'tone' => 'reserved' ),
            array( 'label' => 'Approved',     'value' => $counts['approved'] ?? 0,         'icon' => 'fa-check-circle',   'tone' => 'available' ),
            array( 'label' => 'Rejected',     'value' => $counts['rejected'] ?? 0,         'icon' => 'fa-times-circle',   'tone' => 'sold' ),
        ) );
        ?>

        <form class="gti-ue-toolbar" method="get">
            <input type="hidden" name="gti_page" value="request-quotation">
            <div class="gti-ue-toolbar-left">
                <div class="gti-ue-search">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" placeholder="Search quotations…" value="<?php echo esc_attr( $search ); ?>">
                </div>
                <div class="gti-ue-filter">
                    <select name="status">
                        <option value="">All Status</option>
                        <?php foreach ( gti_status_map( 'quotation' ) as $key => $spec ) : ?>
                            <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $filters['status'], $key ); ?>>
                                <?php echo esc_html( $spec['label'] ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="gti-ue-filter">
                    <select name="equipment_type">
                        <option value="">All Types</option>
                        <option value="used"       <?php selected( $filters['equipment_type'], 'used' ); ?>>Used Equipment</option>
                        <option value="rental"     <?php selected( $filters['equipment_type'], 'rental' ); ?>>Rental Equipment</option>
                        <option value="spare_part" <?php selected( $filters['equipment_type'], 'spare_part' ); ?>>Spare Parts</option>
                    </select>
                </div>
                <?php if ( $pics && gti_can_view_all( 'quotation' ) ) : ?>
                    <div class="gti-ue-filter">
                        <select name="sales_pic">
                            <option value="">All PIC</option>
                            <?php foreach ( $pics as $name ) : ?>
                                <option value="<?php echo esc_attr( $name ); ?>" <?php selected( $filters['sales_pic'], $name ); ?>><?php echo esc_html( $name ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                <div class="gti-ue-filter">
                    <input type="date" name="date_from" value="<?php echo esc_attr( $date_from ); ?>" aria-label="From date">
                </div>
                <div class="gti-ue-filter">
                    <input type="date" name="date_to" value="<?php echo esc_attr( $date_to ); ?>" aria-label="To date">
                </div>
                <button type="submit" class="gti-ue-btn-reset"><i class="fas fa-filter"></i> Apply</button>
                <a href="<?php echo esc_url( gti_dashboard_url( 'request-quotation' ) ); ?>" class="gti-ue-btn-reset">
                    <i class="fas fa-rotate-right"></i> Reset
                </a>
            </div>
        </form>

        <div class="gti-ue-table-card">
            <table class="gti-ue-table">
                <thead>
                    <tr>
                        <th class="col-reqid">Quotation ID</th>
                        <th class="col-customer">Customer</th>
                        <th class="col-company">Company</th>
                        <th class="col-equipment">Requested Items</th>
                        <th class="col-pic">Sales PIC</th>
                        <th class="col-status">Status</th>
                        <th class="col-date">Request Date</th>
                        <th class="col-actions"></th>
                    </tr>
                </thead>
                <tbody>
                <?php if ( ! $result['items'] ) : ?>
                    <tr>
                        <td colspan="8">
                            <?php gti_render_empty_state( 'fa-clipboard-list', 'No quotations found',
                                ( $search || array_filter( $filters ) )
                                    ? 'Try adjusting your filters.'
                                    : 'No quotation requests have come in yet.' ); ?>
                        </td>
                    </tr>
                <?php else : ?>
                    <?php foreach ( $result['items'] as $quot ) :
                        $payload = gti_quotation_row_payload( $quot );
                        ?>
                        <tr data-id="<?php echo (int) $quot['id']; ?>"
                            data-row="<?php echo esc_attr( wp_json_encode( $payload ) ); ?>">
                            <td class="col-reqid"><strong><?php echo esc_html( $quot['quotation_id'] ); ?></strong></td>
                            <td class="col-customer"><?php echo esc_html( $quot['customer_name'] ); ?></td>
                            <td class="col-company"><?php echo esc_html( $quot['customer_company'] ?: '—' ); ?></td>
                            <td class="col-equipment">
                                <?php
                                $first_item = $payload['items'][0]['name'] ?? '';
                                echo esc_html( $first_item ?: '—' );
                                if ( count( $payload['items'] ) > 1 ) {
                                    echo ' <small>+' . ( count( $payload['items'] ) - 1 ) . '</small>';
                                }
                                ?>
                            </td>
                            <td class="col-pic"><?php echo esc_html( $quot['sales_pic'] ?: 'Unassigned' ); ?></td>
                            <td class="col-status"><?php gti_render_status_badge( 'quotation', $quot['status'] ); ?></td>
                            <td class="col-date"><?php echo esc_html( $payload['request_date_text'] ); ?></td>
                            <td class="col-actions">
                                <?php gti_render_action_menu( array(
                                    array( 'label' => 'View Details',     'icon' => 'fa-eye',          'class' => 'js-view' ),
                                    array( 'label' => 'Create Quotation', 'icon' => 'fa-file-invoice', 'class' => 'js-quotation' ),
                                    array( 'label' => 'Assign PIC',       'icon' => 'fa-user-check',   'class' => 'js-assign' ),
                                    array( 'label' => 'Reply',            'icon' => 'fa-envelope',     'class' => 'js-reply' ),
                                    array( 'label' => 'Delete',           'icon' => 'fa-trash',        'class' => 'js-delete danger' ),
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
                    'base_url' => gti_dashboard_url( 'request-quotation' ),
                    'params'   => $query_params,
                ) ); ?>
            <?php endif; ?>
        </div>
    </div><!-- /.gti-content-left -->

    <?php
    $first = $result['items'] ? $result['items'][0] : null;
    include GTI_CHILD_DIR . '/template-parts/dashboard/drawer-quotation.php';
    ?>
</div>

<?php
gti_dashboard_close( array(
    'modals' => array( 'delete', 'email', 'upload', 'assign' ),
) );
