<?php
/**
 * Update Offered Price modal — Sell Equipment.
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="gti-modal-overlay" id="gtiPriceOverlay" role="dialog" aria-modal="true" aria-labelledby="gti-price-title">
    <div class="gti-modal">
        <form id="gti-price-form">
            <div class="gti-modal-header">
                <div>
                    <h3 id="gti-price-title">Update Offered Price</h3>
                    <p>Ubah harga penawaran unit ini.</p>
                </div>
                <button type="button" class="gti-modal-close" data-gti-close="gtiPriceOverlay" aria-label="Close">&times;</button>
            </div>

            <div class="gti-modal-body">
                <div class="gti-summary-rows">
                    <div class="gti-summary-row"><span>Unit</span><strong id="gti-price-unit">&mdash;</strong></div>
                    <div class="gti-summary-row"><span>Harga saat ini</span><strong id="gti-price-current">&mdash;</strong></div>
                </div>

                <div class="gti-field">
                    <label for="gti-price-value">Harga penawaran baru (IDR)</label>
                    <input type="text" id="gti-price-value" name="price" inputmode="numeric"
                           placeholder="mis. 750.000.000" required>
                </div>

                <input type="hidden" name="id" id="gti-price-id">
            </div>

            <div class="gti-modal-footer">
                <button type="button" class="gti-btn-secondary" data-gti-close="gtiPriceOverlay">Cancel</button>
                <button type="submit" class="gti-btn-primary"><i class="fas fa-tag"></i> Simpan Harga</button>
            </div>
        </form>
    </div>
</div>
