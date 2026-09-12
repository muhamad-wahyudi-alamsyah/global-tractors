<?php
/**
 * Shared delete-confirmation modal.
 *
 * One copy replaces the six near-identical versions that were pasted into
 * individual templates. Labels are set from JS at open time via
 * GTI.ui.confirmDelete(), so the same markup serves every entity.
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="gti-ue-delete-overlay" id="gtiDeleteOverlay" role="dialog" aria-modal="true" aria-labelledby="gti-delete-title">
    <div class="gti-ue-delete-modal">
        <div class="gti-ue-delete-header">
            <div class="gti-ue-delete-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="gti-ue-delete-header-text">
                <h3 id="gti-delete-title">Delete Item</h3>
                <p>This action cannot be undone.</p>
            </div>
        </div>
        <div class="gti-ue-delete-body">
            <div class="gti-ue-delete-eq-info">
                <div class="gti-ue-delete-eq-thumb" id="delete-eq-thumb"><i class="fas fa-file-alt"></i></div>
                <div>
                    <div class="gti-ue-delete-eq-name" id="delete-eq-name">&mdash;</div>
                    <div class="gti-ue-delete-eq-code" id="delete-eq-code">&mdash;</div>
                </div>
            </div>
            <div class="gti-ue-delete-warning">
                <i class="fas fa-info-circle"></i>
                <span id="gti-delete-warning">This record will be permanently removed and cannot be recovered.</span>
            </div>
        </div>
        <div class="gti-ue-delete-footer">
            <button type="button" class="gti-ue-delete-cancel" data-gti-close="gtiDeleteOverlay">Cancel</button>
            <button type="button" class="gti-ue-delete-confirm" id="gti-delete-confirm-btn"><i class="fas fa-trash"></i> Delete</button>
        </div>
    </div>
</div>
