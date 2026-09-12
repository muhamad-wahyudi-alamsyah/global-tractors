<?php
/**
 * Sell Equipment detail drawer (PRD §6.7.3).
 *
 * @var array|null $first
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$gti_id      = $first ? (int) $first['id'] : 0;
$gti_status  = $first ? $first['status'] : 'new';
$gti_payload = $first ? gti_sell_row_payload( $first ) : array();
?>
<div class="gti-drawer" id="detailDrawer" data-entity="sell" data-id="<?php echo $gti_id; ?>">
    <div class="gti-drawer-header">
        <div class="gti-drawer-header-left">
            <h2 data-field="ref"><?php echo esc_html( $gti_payload['ref'] ?? '—' ); ?></h2>
            <span class="gti-drawer-status" data-field="status-badge"><?php
                echo esc_html( gti_status_label( 'sell', $gti_status ) );
            ?></span>
        </div>
        <button type="button" class="gti-drawer-close" data-gti-drawer-close aria-label="Close">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <div class="gti-drawer-body">
        <div class="gti-drawer-section">
            <div class="gti-drawer-section-title"><i class="fas fa-user"></i> Seller Information</div>
            <?php
            gti_render_drawer_row( 'Name',     $first['customer_name']    ?? '' );
            gti_render_drawer_row( 'Company',  $first['customer_company'] ?? '' );
            gti_render_drawer_row( 'Email',    $first['customer_email']   ?? '', 'is-link' );
            gti_render_drawer_row( 'Phone',    $first['customer_phone']   ?? '' );
            ?>
            <div class="gti-drawer-row">
                <span class="gti-drawer-label">WhatsApp</span>
                <span class="gti-drawer-value">
                    <?php if ( ! empty( $gti_payload['whatsapp'] ) ) : ?>
                        <a href="<?php echo esc_url( $gti_payload['whatsapp'] ); ?>" target="_blank" rel="noopener"
                           class="gti-drawer-value is-link" data-field="whatsapp">Chat</a>
                    <?php else : ?>
                        <span data-field="whatsapp" title="Nomor tidak tersedia">&mdash;</span>
                    <?php endif; ?>
                </span>
            </div>
        </div>

        <div class="gti-drawer-section">
            <div class="gti-drawer-section-title"><i class="fas fa-truck"></i> Equipment Information</div>
            <?php
            gti_render_drawer_row( 'Equipment', $first['equipment_name']      ?? '' );
            gti_render_drawer_row( 'Brand',     $first['equipment_brand']     ?? '' );
            gti_render_drawer_row( 'Model',     $first['equipment_model']     ?? '' );
            gti_render_drawer_row( 'Year',      $first['equipment_year']      ?? '' );
            gti_render_drawer_row( 'Hours',     $first['equipment_hours']     ?? '' );
            gti_render_drawer_row( 'Condition', $first['equipment_condition'] ?? '' );
            gti_render_drawer_row( 'Location',  $first['equipment_location']  ?? '' );
            ?>
        </div>

        <div class="gti-drawer-section">
            <div class="gti-drawer-section-title"><i class="fas fa-tag"></i> Offer Details</div>
            <?php
            gti_render_drawer_row( 'Offered Price',    $gti_payload['price_text'] ?? '' );
            gti_render_drawer_row( 'Submission Date',  $gti_payload['submitted_text'] ?? '' );
            gti_render_drawer_row( 'Last Updated',     $gti_payload['updated_text'] ?? '' );
            gti_render_drawer_row( 'Invoice Requested', $gti_payload['invoice_requested'] ?? '' );
            ?>
        </div>

        <div class="gti-drawer-section">
            <div class="gti-drawer-section-title"><i class="fas fa-images"></i> Equipment Images</div>
            <div class="gti-drawer-gallery" data-field="images">
                <?php if ( ! empty( $gti_payload['images'] ) ) : ?>
                    <?php foreach ( $gti_payload['images'] as $gti_image ) : ?>
                        <a href="<?php echo esc_url( $gti_image ); ?>" target="_blank" rel="noopener">
                            <img src="<?php echo esc_url( $gti_image ); ?>" alt="" loading="lazy">
                        </a>
                    <?php endforeach; ?>
                <?php else : ?>
                    <p class="gti-drawer-empty">Tidak ada gambar.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="gti-drawer-section">
            <div class="gti-drawer-section-title"><i class="fas fa-comment"></i> Message</div>
            <p class="gti-drawer-value is-message" data-field="message"><?php
                echo esc_html( $gti_payload['message'] ?: '—' );
            ?></p>
        </div>

        <div class="gti-drawer-section">
            <div class="gti-drawer-section-title"><i class="fas fa-clock"></i> Timeline</div>
            <div data-field="timeline"><?php if ( $gti_id ) { gti_render_timeline( 'sell', $gti_id ); } ?></div>
        </div>

        <div class="gti-drawer-section">
            <div class="gti-drawer-section-title"><i class="fas fa-envelope"></i> Email History</div>
            <div data-field="emails"><?php if ( $gti_id ) { gti_render_email_history( 'sell', $gti_id ); } ?></div>
        </div>

        <div class="gti-drawer-section" hidden>
            <div data-field="documents"></div>
        </div>
    </div>

    <div class="gti-drawer-footer">
        <div class="gti-drawer-btn-group">
            <button type="button" class="gti-drawer-btn gti-drawer-btn-primary" data-action="status-menu">
                <i class="fas fa-sync-alt"></i> Update Status
            </button>
            <div class="gti-drawer-dropdown" data-field="status-menu"></div>
        </div>
        <a class="gti-drawer-btn" data-action="whatsapp" target="_blank" rel="noopener" href="#">
            <i class="fab fa-whatsapp"></i> WhatsApp
        </a>
        <button type="button" class="gti-drawer-btn" data-action="reply"><i class="fas fa-envelope"></i> Email</button>
        <span class="gti-drawer-footer-spacer"></span>
        <div class="gti-drawer-btn-group">
            <button type="button" class="gti-drawer-btn gti-drawer-btn-icon" data-action="more" title="More actions">
                <i class="fas fa-ellipsis-v"></i>
            </button>
            <div class="gti-drawer-dropdown" data-field="more-menu">
                <button type="button" class="gti-drawer-dropdown-item" data-action="invoice">
                    <i class="fas fa-file-invoice"></i> Request Invoice
                </button>
                <button type="button" class="gti-drawer-dropdown-item danger" data-action="delete">
                    <i class="fas fa-trash"></i> Delete Offer
                </button>
            </div>
        </div>
    </div>
</div>
