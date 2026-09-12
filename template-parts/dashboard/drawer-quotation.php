<?php
/**
 * Request Quotation detail drawer (PRD §6.6.6).
 *
 * @var array|null $first
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$gti_id      = $first ? (int) $first['id'] : 0;
$gti_status  = $first ? $first['status'] : 'new';
$gti_payload = $first ? gti_quotation_row_payload( $first ) : array();
?>
<div class="gti-drawer" id="detailDrawer" data-entity="quotation" data-id="<?php echo $gti_id; ?>">
    <div class="gti-drawer-header">
        <div class="gti-drawer-header-left">
            <h2 data-field="ref"><?php echo esc_html( $first ? $first['quotation_id'] : '—' ); ?></h2>
            <span class="gti-drawer-status" data-field="status-badge"><?php
                echo esc_html( gti_status_label( 'quotation', $gti_status ) );
            ?></span>
        </div>
        <button type="button" class="gti-drawer-close" data-gti-drawer-close aria-label="Close">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <div class="gti-drawer-body">
        <div class="gti-drawer-section">
            <div class="gti-drawer-section-title"><i class="fas fa-user"></i> Customer Information</div>
            <?php
            gti_render_drawer_row( 'Name',    $first['customer_name']    ?? '' );
            gti_render_drawer_row( 'Company', $first['customer_company'] ?? '' );
            gti_render_drawer_row( 'Email',   $first['customer_email']   ?? '', 'is-link' );
            gti_render_drawer_row( 'Phone',   $first['customer_phone']   ?? '' );
            gti_render_drawer_row( 'Address', $first['customer_address'] ?? '' );
            ?>
        </div>

        <div class="gti-drawer-section">
            <div class="gti-drawer-section-title"><i class="fas fa-file-invoice"></i> Quotation Details</div>
            <?php
            gti_render_drawer_row( 'Requested Date',    $gti_payload['request_date_text'] ?? '' );
            gti_render_drawer_row( 'Needed Date',       $gti_payload['needed_date'] ?? '' );
            gti_render_drawer_row( 'Valid Until',       $gti_payload['valid_until'] ?? '' );
            // B-02: payment_terms had no column before v1.3.0 — the label was decorative.
            gti_render_drawer_row( 'Payment Terms',     $gti_payload['payment_terms'] ?? '' );
            gti_render_drawer_row( 'Delivery Location', $gti_payload['delivery_location'] ?? '' );
            gti_render_drawer_row( 'Rental Period',     $gti_payload['rental_period'] ?? '' );
            gti_render_drawer_row( 'Budget',            $gti_payload['budget_text'] ?? '' );
            ?>
            <div class="gti-drawer-row gti-drawer-row-stacked">
                <span class="gti-drawer-label">Notes</span>
                <span class="gti-drawer-value is-message" data-field="notes"><?php
                    echo esc_html( $gti_payload['notes'] ?? '—' );
                ?></span>
            </div>
        </div>

        <div class="gti-drawer-section">
            <div class="gti-drawer-section-title"><i class="fas fa-boxes"></i> Items</div>
            <div data-field="items">
                <?php if ( ! empty( $gti_payload['items'] ) ) : ?>
                    <?php foreach ( $gti_payload['items'] as $gti_item ) : ?>
                        <div class="gti-drawer-item">
                            <strong><?php echo esc_html( $gti_item['name'] ?? '—' ); ?></strong>
                            <span><?php echo esc_html( ( $gti_item['quantity'] ?? 1 ) . ' unit' ); ?>
                                <?php if ( ! empty( $gti_item['part_number'] ) ) : ?>
                                    &middot; <?php echo esc_html( $gti_item['part_number'] ); ?>
                                <?php endif; ?>
                            </span>
                            <?php if ( ! empty( $gti_item['notes'] ) ) : ?>
                                <p><?php echo esc_html( $gti_item['notes'] ); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <p class="gti-drawer-empty">Tidak ada item.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="gti-drawer-section">
            <div class="gti-drawer-section-title"><i class="fas fa-calculator"></i> Summary</div>
            <?php
            gti_render_drawer_row( 'Total Items',     $gti_payload['total_items'] ?? '' );
            gti_render_drawer_row( 'Est. Total Value', $gti_payload['total_text'] ?? '' );
            gti_render_drawer_row( 'Sales PIC',       $gti_payload['sales_pic'] ?? 'Unassigned' );
            ?>
        </div>

        <div class="gti-drawer-section">
            <div class="gti-drawer-section-title"><i class="fas fa-paperclip"></i> Documents</div>
            <div data-field="documents">
                <?php $gti_id ? gti_render_documents( 'quotation', $gti_id, 'quotation' ) : print( '<p class="gti-drawer-empty">Belum ada dokumen.</p>' ); ?>
            </div>
        </div>

        <div class="gti-drawer-section">
            <div class="gti-drawer-section-title"><i class="fas fa-clock"></i> Timeline</div>
            <div data-field="timeline"><?php if ( $gti_id ) { gti_render_timeline( 'quotation', $gti_id ); } ?></div>
        </div>

        <div class="gti-drawer-section">
            <div class="gti-drawer-section-title"><i class="fas fa-envelope"></i> Email History</div>
            <div data-field="emails"><?php if ( $gti_id ) { gti_render_email_history( 'quotation', $gti_id ); } ?></div>
        </div>
    </div>

    <div class="gti-drawer-footer">
        <div class="gti-drawer-btn-group">
            <button type="button" class="gti-drawer-btn gti-drawer-btn-primary" data-action="status-menu">
                <i class="fas fa-sync-alt"></i> Update Status
            </button>
            <div class="gti-drawer-dropdown" data-field="status-menu"></div>
        </div>
        <button type="button" class="gti-drawer-btn" data-action="reply"><i class="fas fa-envelope"></i> Reply</button>
        <button type="button" class="gti-drawer-btn" data-action="assign"><i class="fas fa-user-check"></i> PIC</button>
        <span class="gti-drawer-footer-spacer"></span>
        <div class="gti-drawer-btn-group">
            <button type="button" class="gti-drawer-btn gti-drawer-btn-icon" data-action="more" title="More actions">
                <i class="fas fa-ellipsis-v"></i>
            </button>
            <div class="gti-drawer-dropdown" data-field="more-menu">
                <button type="button" class="gti-drawer-dropdown-item" data-action="quotation">
                    <i class="fas fa-file-invoice"></i> Create Quotation
                </button>
                <button type="button" class="gti-drawer-dropdown-item danger" data-action="delete">
                    <i class="fas fa-trash"></i> Delete Quotation
                </button>
            </div>
        </div>
    </div>
</div>
