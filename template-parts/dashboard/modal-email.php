<?php
/**
 * Compose Email modal (PRD §6.6.4).
 *
 * Shared by Request Equipment, Request Quotation, Sell Equipment and Customer
 * Detail — written once rather than three times, which is the whole reason R1
 * had to land before the inbox work (PRD §13.13).
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="gti-modal-overlay" id="gtiEmailOverlay" role="dialog" aria-modal="true" aria-labelledby="gti-email-title">
    <div class="gti-modal gti-modal-lg">
        <form id="gti-email-form" enctype="multipart/form-data">
            <div class="gti-modal-header">
                <div>
                    <h3 id="gti-email-title">Reply to Customer</h3>
                    <p id="gti-email-sub">The customer receives this straight away.</p>
                </div>
                <button type="button" class="gti-modal-close" data-gti-close="gtiEmailOverlay" aria-label="Close">&times;</button>
            </div>

            <div class="gti-modal-body">
                <div class="gti-modal-banner gti-modal-banner-error" id="gti-email-no-recipient" hidden>
                    <i class="fas fa-exclamation-circle"></i>
                    <span>Pelanggan ini tidak punya alamat email, jadi pesan tidak bisa dikirim.</span>
                </div>

                <div class="gti-field">
                    <label for="gti-email-to">To</label>
                    <input type="text" id="gti-email-to" name="to" readonly>
                </div>

                <div class="gti-field">
                    <label for="gti-email-cc">CC <span class="gti-field-hint">optional</span></label>
                    <input type="email" id="gti-email-cc" name="cc" placeholder="rekan@perusahaan.co.id">
                </div>

                <div class="gti-field">
                    <label for="gti-email-subject">Subject <span class="required">*</span></label>
                    <input type="text" id="gti-email-subject" name="subject" required>
                </div>

                <div class="gti-field">
                    <label for="gti-email-message">Message <span class="required">*</span></label>
                    <textarea id="gti-email-message" name="message" rows="8" required></textarea>
                </div>

                <div class="gti-field">
                    <label for="gti-email-files">Attachments <span class="gti-field-hint">PDF, DOC, XLS, JPG, PNG &mdash; max 10 MB each</span></label>
                    <input type="file" id="gti-email-files" name="files[]" multiple
                           accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                    <ul class="gti-file-list" id="gti-email-file-list"></ul>
                </div>

                <label class="gti-checkbox">
                    <input type="checkbox" name="signature" id="gti-email-signature" checked>
                    <span>Sisipkan tanda tangan saya</span>
                </label>

                <input type="hidden" name="entity_type" id="gti-email-entity-type">
                <input type="hidden" name="id" id="gti-email-entity-id">
            </div>

            <div class="gti-modal-footer">
                <button type="button" class="gti-btn-secondary" data-gti-close="gtiEmailOverlay">Cancel</button>
                <button type="submit" class="gti-btn-primary" id="gti-email-submit"><i class="fas fa-paper-plane"></i> Send Email</button>
            </div>
        </form>
    </div>
</div>
