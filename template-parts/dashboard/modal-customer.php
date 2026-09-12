<?php
/**
 * Edit / Add Customer modal (PRD §6.9).
 *
 * Fields are exactly the columns wp_gti_customers actually has — the previous
 * "Edit Customer" link went nowhere, and the AJAX endpoint it should have used
 * (gti_save_customer) already existed with no UI attached to it.
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="gti-modal-overlay" id="gtiCustomerOverlay" role="dialog" aria-modal="true" aria-labelledby="gti-customer-title">
    <div class="gti-modal gti-modal-lg">
        <form id="gti-customer-form">
            <div class="gti-modal-header">
                <div>
                    <h3 id="gti-customer-title">Edit Customer</h3>
                    <p>Update the customer record.</p>
                </div>
                <button type="button" class="gti-modal-close" data-gti-close="gtiCustomerOverlay" aria-label="Close">&times;</button>
            </div>

            <div class="gti-modal-body">
                <div class="gti-field-grid">
                    <div class="gti-field">
                        <label for="cf-name">Name <span class="required">*</span></label>
                        <input type="text" id="cf-name" name="name" required>
                    </div>
                    <div class="gti-field">
                        <label for="cf-company">Company</label>
                        <input type="text" id="cf-company" name="company">
                    </div>
                </div>
                <div class="gti-field-grid">
                    <div class="gti-field">
                        <label for="cf-email">Email</label>
                        <input type="email" id="cf-email" name="email">
                    </div>
                    <div class="gti-field">
                        <label for="cf-phone">Phone</label>
                        <input type="tel" id="cf-phone" name="phone">
                    </div>
                </div>
                <div class="gti-field">
                    <label for="cf-address">Address</label>
                    <textarea id="cf-address" name="address" rows="2"></textarea>
                </div>
                <div class="gti-field-grid">
                    <div class="gti-field">
                        <label for="cf-city">City</label>
                        <input type="text" id="cf-city" name="city">
                    </div>
                    <div class="gti-field">
                        <label for="cf-province">Province</label>
                        <input type="text" id="cf-province" name="province">
                    </div>
                </div>
                <div class="gti-field-grid">
                    <div class="gti-field">
                        <label for="cf-country">Country</label>
                        <input type="text" id="cf-country" name="country">
                    </div>
                    <div class="gti-field">
                        <label for="cf-industry">Industry</label>
                        <input type="text" id="cf-industry" name="industry">
                    </div>
                </div>
                <div class="gti-field-grid">
                    <div class="gti-field">
                        <label for="cf-website">Website</label>
                        <input type="text" id="cf-website" name="website">
                    </div>
                    <div class="gti-field">
                        <label for="cf-npwp">NPWP</label>
                        <input type="text" id="cf-npwp" name="npwp">
                    </div>
                </div>

                <div class="gti-field-grid">
                    <div class="gti-field">
                        <label for="cf-contact-person">Contact Person</label>
                        <input type="text" id="cf-contact-person" name="contact_person">
                    </div>
                    <div class="gti-field">
                        <label for="cf-contact-position">Position</label>
                        <input type="text" id="cf-contact-position" name="contact_position">
                    </div>
                </div>
                <div class="gti-field-grid">
                    <div class="gti-field">
                        <label for="cf-contact-phone">Contact Phone</label>
                        <input type="tel" id="cf-contact-phone" name="contact_phone">
                    </div>
                    <div class="gti-field">
                        <label for="cf-contact-email">Contact Email</label>
                        <input type="email" id="cf-contact-email" name="contact_email">
                    </div>
                </div>
                <div class="gti-field-grid">
                    <div class="gti-field">
                        <label for="cf-contact-whatsapp">Contact WhatsApp</label>
                        <input type="tel" id="cf-contact-whatsapp" name="contact_whatsapp">
                    </div>
                    <div class="gti-field">
                        <label for="cf-status">Status</label>
                        <select id="cf-status" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <input type="hidden" name="id" id="cf-id" value="0">
                <input type="hidden" name="customer_id" id="cf-customer-id">
            </div>

            <div class="gti-modal-footer">
                <button type="button" class="gti-btn-secondary" data-gti-close="gtiCustomerOverlay">Cancel</button>
                <button type="submit" class="gti-btn-primary"><i class="fas fa-save"></i> Save Customer</button>
            </div>
        </form>
    </div>
</div>
