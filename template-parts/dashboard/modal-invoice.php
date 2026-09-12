<?php
/**
 * Request Invoice modal — Sell Equipment (PRD §6.7.2).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$gti_invoice_docs = array(
    'Invoice'          => 'Invoice',
    'STNK / BPKB'      => 'STNK / BPKB',
    'Faktur'           => 'Faktur',
    'Form A'           => 'Form A',
    'Sertifikat Unit'  => 'Sertifikat Unit',
);
?>
<div class="gti-modal-overlay" id="gtiInvoiceOverlay" role="dialog" aria-modal="true" aria-labelledby="gti-invoice-title">
    <div class="gti-modal">
        <form id="gti-invoice-form">
            <div class="gti-modal-header">
                <div>
                    <h3 id="gti-invoice-title">Request Invoice</h3>
                    <p>Kirim permintaan invoice ke penjual.</p>
                </div>
                <button type="button" class="gti-modal-close" data-gti-close="gtiInvoiceOverlay" aria-label="Close">&times;</button>
            </div>

            <div class="gti-modal-body">
                <div class="gti-summary-rows">
                    <div class="gti-summary-row"><span>Unit</span><strong id="gti-invoice-unit">&mdash;</strong></div>
                    <div class="gti-summary-row"><span>Harga disepakati</span><strong id="gti-invoice-price">&mdash;</strong></div>
                    <div class="gti-summary-row"><span>Email tujuan</span><strong id="gti-invoice-email">&mdash;</strong></div>
                </div>

                <div class="gti-field">
                    <label for="gti-invoice-deadline">Batas waktu (hari)</label>
                    <input type="number" id="gti-invoice-deadline" name="deadline_days" value="7" min="1" max="90">
                </div>

                <div class="gti-field">
                    <label>Dokumen yang diminta</label>
                    <div class="gti-checkbox-grid">
                        <?php foreach ( $gti_invoice_docs as $gti_value => $gti_label ) : ?>
                            <label class="gti-checkbox">
                                <input type="checkbox" name="documents[]" value="<?php echo esc_attr( $gti_value ); ?>" checked>
                                <span><?php echo esc_html( $gti_label ); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="gti-field">
                    <label for="gti-invoice-note">Catatan tambahan</label>
                    <textarea id="gti-invoice-note" name="note" rows="3"></textarea>
                </div>

                <input type="hidden" name="id" id="gti-invoice-id">
            </div>

            <div class="gti-modal-footer">
                <button type="button" class="gti-btn-secondary" data-gti-close="gtiInvoiceOverlay">Cancel</button>
                <button type="submit" class="gti-btn-primary"><i class="fas fa-file-invoice"></i> Kirim Permintaan</button>
            </div>
        </form>
    </div>
</div>
