<?php
/**
 * Document upload modal — Send Proposal (§6.5.4) and Create Quotation (§6.6.3).
 *
 * The quotation-only fields are hidden by default and revealed by
 * GTI.upload.open() when mode === 'quotation', so one modal covers both flows.
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="gti-modal-overlay" id="gtiUploadOverlay" role="dialog" aria-modal="true" aria-labelledby="gti-upload-title">
    <div class="gti-modal gti-modal-lg">
        <form id="gti-upload-form" enctype="multipart/form-data">
            <div class="gti-modal-header">
                <div>
                    <h3 id="gti-upload-title">Send Proposal</h3>
                    <p id="gti-upload-sub">The document is attached to the email the customer receives.</p>
                </div>
                <button type="button" class="gti-modal-close" data-gti-close="gtiUploadOverlay" aria-label="Close">&times;</button>
            </div>

            <div class="gti-modal-body">
                <div class="gti-dropzone" id="gti-upload-dropzone">
                    <input type="file" id="gti-upload-file" name="file"
                           accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png" required>
                    <div class="gti-dropzone-empty" id="gti-upload-empty">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <strong>Klik atau jatuhkan berkas di sini</strong>
                        <span>PDF, DOC, DOCX, XLS, XLSX, JPG, PNG &mdash; maks 10 MB</span>
                    </div>
                    <div class="gti-dropzone-file" id="gti-upload-preview" hidden>
                        <i class="fas fa-file-alt"></i>
                        <div>
                            <strong id="gti-upload-filename">&mdash;</strong>
                            <span id="gti-upload-filesize">&mdash;</span>
                        </div>
                        <button type="button" class="gti-dropzone-remove" id="gti-upload-remove" aria-label="Remove file">&times;</button>
                    </div>
                </div>
                <p class="gti-field-error" id="gti-upload-error" hidden></p>

                <!-- Quotation-only fields (PRD §6.6.3) -->
                <div id="gti-upload-quotation-fields" hidden>
                    <div class="gti-field-grid">
                        <div class="gti-field">
                            <label for="gti-upload-number">Nomor Quotation <span class="required">*</span></label>
                            <input type="text" id="gti-upload-number" name="quotation_number">
                        </div>
                        <div class="gti-field">
                            <label for="gti-upload-total">Total Nilai (IDR) <span class="required">*</span></label>
                            <input type="number" id="gti-upload-total" name="total" min="0" step="1000">
                        </div>
                    </div>
                    <div class="gti-field-grid">
                        <div class="gti-field">
                            <label for="gti-upload-valid">Berlaku Sampai <span class="required">*</span></label>
                            <input type="date" id="gti-upload-valid" name="valid_until">
                        </div>
                        <div class="gti-field">
                            <label for="gti-upload-terms">Payment Terms</label>
                            <select id="gti-upload-terms" name="payment_terms">
                                <option value="">&mdash; Pilih &mdash;</option>
                                <option value="cash">Tunai</option>
                                <option value="transfer">Transfer Bank</option>
                                <option value="credit">Kredit / Cicilan</option>
                                <option value="leasing">Leasing</option>
                                <option value="other">Lainnya</option>
                            </select>
                        </div>
                    </div>
                    <div class="gti-field">
                        <label for="gti-upload-delivery">Delivery Location</label>
                        <input type="text" id="gti-upload-delivery" name="delivery_location">
                    </div>
                </div>

                <div class="gti-field">
                    <label for="gti-upload-note">Catatan untuk klien <span class="gti-field-hint">opsional &mdash; disisipkan ke badan email</span></label>
                    <textarea id="gti-upload-note" name="note" rows="4"></textarea>
                </div>

                <input type="hidden" name="id" id="gti-upload-entity-id">
                <input type="hidden" name="entity_type" id="gti-upload-entity-type">
            </div>

            <div class="gti-modal-footer">
                <button type="button" class="gti-btn-secondary" data-gti-close="gtiUploadOverlay">Cancel</button>
                <button type="submit" class="gti-btn-primary" id="gti-upload-submit" disabled>
                    <i class="fas fa-paper-plane"></i> <span id="gti-upload-submit-label">Kirim Proposal</span>
                </button>
            </div>
        </form>
    </div>
</div>
