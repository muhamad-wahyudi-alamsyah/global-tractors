<?php
/**
 * Request Equipment detail drawer (PRD §6.5.2 / §6.5.3).
 *
 * Rendered server-side for the first row; the page script repaints the same
 * nodes when another row is selected or a status changes.
 *
 * @var array|null $first
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$gti_id     = $first ? (int) $first['id'] : 0;
$gti_status = $first ? $first['status'] : 'new';
?>
<div class="gti-drawer" id="detailDrawer" data-entity="request" data-id="<?php echo $gti_id; ?>">
    <div class="gti-drawer-header">
        <div class="gti-drawer-header-left">
            <h2 data-field="ref"><?php echo esc_html( $first ? $first['request_id'] : '—' ); ?></h2>
            <span class="gti-drawer-status" data-field="status-badge"><?php
                echo esc_html( gti_status_label( 'request', $gti_status ) );
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
            gti_render_drawer_row( 'Name',    $first['customer_name']    ?? '', '', false );
            gti_render_drawer_row( 'Company', $first['customer_company'] ?? '' );
            gti_render_drawer_row( 'Email',   $first['customer_email']   ?? '', 'is-link' );
            gti_render_drawer_row( 'Phone',   $first['customer_phone']   ?? '' );
            gti_render_drawer_row( 'Address', $first['customer_address'] ?? '' );
            ?>
        </div>

        <div class="gti-drawer-section">
            <div class="gti-drawer-section-title"><i class="fas fa-file-alt"></i> Request Information</div>
            <?php
            gti_render_drawer_row( 'Equipment',     $first['equipment'] ?? '' );
            gti_render_drawer_row( 'Category',      $first['category']  ?? '' );
            gti_render_drawer_row( 'Brand',         $first['brand']     ?? '' );
            gti_render_drawer_row( 'Quantity',      $first['quantity']  ?? '' );
            gti_render_drawer_row( 'Location',      $first['location']  ?? '' );
            gti_render_drawer_row( 'Budget',        $first && $first['budget'] ? 'Rp ' . number_format( (float) $first['budget'], 0, ',', '.' ) : '' );
            gti_render_drawer_row( 'Required Date', $first && ! empty( $first['required_date'] ) ? gti_format_date( $first['required_date'], 'd M Y' ) : '' );
            // B-01: these two had no column at all before v1.3.0, so the drawer
            // fell back to `notes` and the labels were effectively decorative.
            gti_render_drawer_row( 'Usage Purpose', $first['usage_purpose'] ?? '' );
            gti_render_drawer_row( 'Request Date',  $first && ! empty( $first['request_date'] ) ? gti_format_date( $first['request_date'], 'd M Y' ) : '' );
            gti_render_drawer_row( 'PIC',           $first['sales_pic'] ?? '' );
            ?>
            <div class="gti-drawer-row gti-drawer-row-stacked">
                <span class="gti-drawer-label">Message</span>
                <span class="gti-drawer-value is-message" data-field="message"><?php
                    echo esc_html( $first['message'] ?? $first['notes'] ?? '—' );
                ?></span>
            </div>
        </div>

        <div class="gti-drawer-section">
            <div class="gti-drawer-section-title"><i class="fas fa-paperclip"></i> Documents</div>
            <div data-field="documents">
                <?php $gti_id ? gti_render_documents( 'request', $gti_id, 'proposal' ) : print( '<p class="gti-drawer-empty">Belum ada dokumen.</p>' ); ?>
            </div>
        </div>

        <div class="gti-drawer-section">
            <div class="gti-drawer-section-title"><i class="fas fa-clock"></i> Timeline</div>
            <div data-field="timeline">
                <?php if ( $gti_id ) { gti_render_timeline( 'request', $gti_id ); } ?>
            </div>
        </div>

        <div class="gti-drawer-section">
            <div class="gti-drawer-section-title"><i class="fas fa-envelope"></i> Email History</div>
            <div data-field="emails">
                <?php if ( $gti_id ) { gti_render_email_history( 'request', $gti_id ); } ?>
            </div>
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
                <button type="button" class="gti-drawer-dropdown-item danger" data-action="delete">
                    <i class="fas fa-trash"></i> Delete Request
                </button>
            </div>
        </div>
    </div>
</div>
