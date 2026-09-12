<?php
/**
 * Shared inquiry form for the three catalogue shortcodes (PRD §7.2).
 *
 * The old form collected five fields, which is why so many columns in the
 * admin drawer were permanently blank. This renders the full field set, with
 * the advanced half behind a disclosure toggle so a visitor still sees a short
 * form first (§7.4).
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Payment term options (§7.2).
 */
function gti_payment_terms_options() {
    return array(
        'cash'     => 'Tunai',
        'transfer' => 'Transfer Bank',
        'credit'   => 'Kredit / Cicilan',
        'leasing'  => 'Leasing',
        'other'    => 'Lainnya',
    );
}

/**
 * Render the inquiry form.
 *
 * @param array $args {
 *     @type string $type         used|rental|spare_part
 *     @type int    $equipment_id
 *     @type string $equipment_name
 *     @type string $part_number  Spare parts only.
 * }
 */
function gti_render_inquiry_form( array $args ) {
    $args = array_merge( array(
        'type' => 'used', 'equipment_id' => 0, 'equipment_name' => '', 'part_number' => '',
    ), $args );

    $type  = $args['type'];
    $today = date( 'Y-m-d' );
    ?>
    <div class="gti-ed-inquiry-card">
        <h3 class="gti-ed-inquiry-title">
            <?php echo $type === 'spare_part' ? 'BUTUH PART INI?' : 'INTERESTED IN THIS UNIT?'; ?>
        </h3>
        <p class="gti-ed-inquiry-desc">Isi formulir dan tim kami akan menghubungi Anda.</p>

        <form id="gti-ed-inquiry-form" class="gti-ed-form" novalidate>
            <input type="hidden" name="gti_quot_nonce" value="<?php echo esc_attr( wp_create_nonce( 'gti_customer_quotation' ) ); ?>">
            <input type="hidden" name="equipment_id" value="<?php echo (int) $args['equipment_id']; ?>">
            <input type="hidden" name="equipment_name" value="<?php echo esc_attr( $args['equipment_name'] ); ?>">
            <input type="hidden" name="equipment_type" value="<?php echo esc_attr( $type ); ?>">
            <input type="hidden" name="source_url" value="<?php echo esc_url( home_url( add_query_arg( array() ) ) ); ?>">

            <?php /* Honeypot: hidden from people, irresistible to bots (§7.3). */ ?>
            <div class="gti-ed-hp" aria-hidden="true">
                <label for="ed-website">Website</label>
                <input type="text" name="ed_website" id="ed-website" tabindex="-1" autocomplete="off">
            </div>

            <div class="gti-ed-form-group">
                <input type="text" name="ed_name" id="ed-name" placeholder="Nama Lengkap *" required>
                <span class="gti-ed-form-error" data-for="ed_name"></span>
            </div>
            <div class="gti-ed-form-group">
                <input type="text" name="ed_company" id="ed-company" placeholder="Perusahaan">
            </div>
            <div class="gti-ed-form-group">
                <input type="tel" name="ed_phone" id="ed-phone" placeholder="Telepon / WhatsApp *" required>
                <span class="gti-ed-form-error" data-for="ed_phone"></span>
            </div>
            <div class="gti-ed-form-group">
                <input type="email" name="ed_email" id="ed-email" placeholder="Email *" required>
                <span class="gti-ed-form-error" data-for="ed_email"></span>
            </div>

            <?php if ( $type === 'spare_part' ) : ?>
                <div class="gti-ed-form-group">
                    <input type="text" name="ed_part_number" id="ed-part-number"
                           value="<?php echo esc_attr( $args['part_number'] ); ?>"
                           placeholder="Part Number" readonly>
                </div>
            <?php endif; ?>

            <div class="gti-ed-form-group">
                <label class="gti-ed-form-label" for="ed-quantity">Jumlah <?php echo $type === 'spare_part' ? 'Part' : 'Unit'; ?> *</label>
                <input type="number" name="ed_quantity" id="ed-quantity" value="1" min="1" step="1" required>
            </div>

            <button type="button" class="gti-ed-form-toggle" id="gti-ed-more-toggle"
                    aria-expanded="false" aria-controls="gti-ed-more">
                <i class="fas fa-chevron-down"></i> Detail kebutuhan (opsional)
            </button>

            <div class="gti-ed-form-more" id="gti-ed-more" hidden>
                <div class="gti-ed-form-group">
                    <label class="gti-ed-form-label" for="ed-needed-date">Dibutuhkan Tanggal</label>
                    <input type="date" name="ed_needed_date" id="ed-needed-date" min="<?php echo esc_attr( $today ); ?>">
                </div>

                <?php if ( $type === 'rental' ) : ?>
                    <div class="gti-ed-form-group">
                        <label class="gti-ed-form-label" for="ed-rental-duration">Durasi Sewa</label>
                        <select name="ed_rental_duration" id="ed-rental-duration">
                            <option value="">&mdash; Pilih &mdash;</option>
                            <option value="1 bulan">1 bulan</option>
                            <option value="3 bulan">3 bulan</option>
                            <option value="6 bulan">6 bulan</option>
                            <option value="12 bulan">12 bulan</option>
                            <option value="custom">Custom</option>
                        </select>
                    </div>
                    <div class="gti-ed-form-row">
                        <div class="gti-ed-form-group">
                            <label class="gti-ed-form-label" for="ed-rental-start">Mulai Sewa</label>
                            <input type="date" name="ed_rental_start" id="ed-rental-start" min="<?php echo esc_attr( $today ); ?>">
                        </div>
                        <div class="gti-ed-form-group">
                            <label class="gti-ed-form-label" for="ed-rental-end">Selesai Sewa</label>
                            <input type="date" name="ed_rental_end" id="ed-rental-end" min="<?php echo esc_attr( $today ); ?>">
                            <span class="gti-ed-form-error" data-for="ed_rental_end"></span>
                        </div>
                    </div>
                    <div class="gti-ed-form-group">
                        <label class="gti-ed-form-label" for="ed-operator">Butuh Operator?</label>
                        <select name="ed_operator_needed" id="ed-operator">
                            <option value="">&mdash; Pilih &mdash;</option>
                            <option value="ya">Ya</option>
                            <option value="tidak">Tidak</option>
                        </select>
                    </div>
                <?php endif; ?>

                <?php if ( $type === 'spare_part' ) : ?>
                    <div class="gti-ed-form-group">
                        <label class="gti-ed-form-label" for="ed-unit-model">Model Unit Terpasang</label>
                        <input type="text" name="ed_unit_model" id="ed-unit-model" placeholder="mis. Komatsu PC200-8">
                    </div>
                    <div class="gti-ed-form-group">
                        <label class="gti-ed-form-label" for="ed-urgency">Tingkat Urgensi</label>
                        <select name="ed_urgency" id="ed-urgency">
                            <option value="normal">Normal</option>
                            <option value="urgent">Urgent</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="gti-ed-form-group">
                    <label class="gti-ed-form-label" for="ed-delivery">Lokasi Pengiriman</label>
                    <input type="text" name="ed_delivery_location" id="ed-delivery" placeholder="mis. Balikpapan, Kalimantan Timur">
                </div>
                <div class="gti-ed-form-group">
                    <label class="gti-ed-form-label" for="ed-payment">Metode Pembayaran</label>
                    <select name="ed_payment_terms" id="ed-payment">
                        <option value="">&mdash; Pilih &mdash;</option>
                        <?php foreach ( gti_payment_terms_options() as $gti_value => $gti_label ) : ?>
                            <option value="<?php echo esc_attr( $gti_value ); ?>"><?php echo esc_html( $gti_label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="gti-ed-form-group">
                    <label class="gti-ed-form-label" for="ed-budget">Estimasi Budget (IDR)</label>
                    <input type="text" name="ed_budget" id="ed-budget" inputmode="numeric" placeholder="mis. 850.000.000">
                </div>
                <div class="gti-ed-form-group">
                    <label class="gti-ed-form-label" for="ed-address">Alamat</label>
                    <textarea name="ed_address" id="ed-address" rows="2" placeholder="Alamat perusahaan / pengiriman"></textarea>
                </div>
            </div>

            <div class="gti-ed-form-group">
                <textarea name="ed_message" id="ed-message" rows="3" placeholder="Pesan / kebutuhan spesifik"></textarea>
            </div>

            <label class="gti-ed-form-consent">
                <input type="checkbox" name="ed_consent" id="ed-consent" value="1" required>
                <span>Saya setuju dihubungi oleh tim PT Global Tractors Indonesia. *</span>
            </label>
            <span class="gti-ed-form-error" data-for="ed_consent"></span>

            <button type="submit" class="gti-ed-form-submit">
                <i class="fas fa-paper-plane"></i> KIRIM PERMINTAAN
            </button>
            <div class="gti-ed-form-privacy"><i class="fas fa-lock"></i> Data Anda aman bersama kami.</div>
        </form>
    </div>
    <?php
}
