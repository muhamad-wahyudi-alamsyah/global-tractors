<?php
/**
 * Shared delete-confirmation modal.
 *
 * One copy replaces the six near-identical versions that were pasted into
 * individual templates. Labels can be set from JS at open time via
 * GTI.ui.confirm(), so the same markup serves every entity; a page passes the
 * defaults its own copy used to show:
 *     'modals' => array( 'delete' => array( 'title' => 'Delete Equipment', 'icon' => 'fa-truck' ) )
 *
 * @var array $gti_modal_args title, icon, warning
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$gti_delete = array_merge( array(
    'title'   => 'Delete Item',
    'icon'    => 'fa-file-alt',
    'warning' => 'This record will be permanently removed and cannot be recovered.',
), isset( $gti_modal_args ) ? (array) $gti_modal_args : array() );
?>
<div class="gti-ue-delete-overlay" id="gtiDeleteOverlay" role="dialog" aria-modal="true" aria-labelledby="gti-delete-title">
    <div class="gti-ue-delete-modal">
        <div class="gti-ue-delete-header">
            <div class="gti-ue-delete-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="gti-ue-delete-header-text">
                <h3 id="gti-delete-title"><?php echo esc_html( $gti_delete['title'] ); ?></h3>
                <p id="gti-delete-subtitle">This action cannot be undone.</p>
            </div>
        </div>
        <div class="gti-ue-delete-body">
            <div class="gti-ue-delete-eq-info">
                <div class="gti-ue-delete-eq-thumb" id="delete-eq-thumb"><i class="fas <?php echo esc_attr( $gti_delete['icon'] ); ?>"></i></div>
                <div>
                    <div class="gti-ue-delete-eq-name" id="delete-eq-name">&mdash;</div>
                    <div class="gti-ue-delete-eq-code" id="delete-eq-code">&mdash;</div>
                </div>
            </div>
            <div class="gti-ue-delete-warning">
                <i class="fas fa-info-circle"></i>
                <span id="gti-delete-warning"><?php echo esc_html( $gti_delete['warning'] ); ?></span>
            </div>
        </div>
        <div class="gti-ue-delete-footer">
            <button type="button" class="gti-ue-delete-cancel" data-gti-close="gtiDeleteOverlay">Cancel</button>
            <button type="button" class="gti-ue-delete-confirm" id="gti-delete-confirm-btn"><i class="fas fa-trash"></i> Delete</button>
        </div>
    </div>
</div>
