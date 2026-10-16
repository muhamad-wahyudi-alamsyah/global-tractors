<?php
/**
 * Settings modals: rename a field, add/edit a dropdown option.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$gti_scopes = array( 'both' => 'Used & Rental (terhubung)', 'used' => 'Used saja', 'rental' => 'Rental saja' );
?>
<div class="gti-modal-overlay" id="gtiFieldOverlay" role="dialog" aria-modal="true" aria-labelledby="gti-field-title">
    <div class="gti-modal">
        <form id="gti-field-form">
            <div class="gti-modal-header">
                <div><h3 id="gti-field-title">Rename Field</h3><p>Nama field yang tampil di form dan halaman terkait.</p></div>
                <button type="button" class="gti-modal-close" data-gti-close="gtiFieldOverlay" aria-label="Close">&times;</button>
            </div>
            <div class="gti-modal-body">
                <input type="hidden" name="field">
                <div class="gti-field">
                    <label for="gti-field-scope">Berlaku untuk</label>
                    <select id="gti-field-scope" name="scope">
                        <?php foreach ( $gti_scopes as $v => $l ) : ?><option value="<?php echo esc_attr( $v ); ?>"><?php echo esc_html( $l ); ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="gti-field">
                    <label for="gti-field-label">Nama field</label>
                    <input type="text" id="gti-field-label" name="label" maxlength="60" required>
                </div>
            </div>
            <div class="gti-modal-footer">
                <button type="button" class="gti-btn-secondary" data-gti-close="gtiFieldOverlay">Cancel</button>
                <button type="submit" class="gti-btn-primary"><i class="fas fa-save"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>

<div class="gti-modal-overlay" id="gtiOptionOverlay" role="dialog" aria-modal="true" aria-labelledby="gti-option-title">
    <div class="gti-modal">
        <form id="gti-option-form">
            <div class="gti-modal-header">
                <div><h3 id="gti-option-title">Add Option</h3><p id="gti-option-sub"></p></div>
                <button type="button" class="gti-modal-close" data-gti-close="gtiOptionOverlay" aria-label="Close">&times;</button>
            </div>
            <div class="gti-modal-body">
                <input type="hidden" name="field">
                <input type="hidden" name="id">
                <div class="gti-field">
                    <label for="gti-option-label">Nama pilihan</label>
                    <input type="text" id="gti-option-label" name="label" maxlength="100" required>
                </div>
                <div class="gti-field">
                    <label for="gti-option-scope">Tampil di</label>
                    <select id="gti-option-scope" name="scope">
                        <?php foreach ( $gti_scopes as $v => $l ) : ?><option value="<?php echo esc_attr( $v ); ?>"><?php echo esc_html( $l ); ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="gti-field" id="gti-option-abbr-wrap" hidden>
                    <label for="gti-option-abbr">Singkatan kode <span class="gti-field-hint">(untuk kode otomatis, mis. EXC &rarr; GTI-EXC-2026-001; kosong = GEN)</span></label>
                    <input type="text" id="gti-option-abbr" name="abbr" maxlength="4">
                </div>
            </div>
            <div class="gti-modal-footer">
                <button type="button" class="gti-btn-secondary" data-gti-close="gtiOptionOverlay">Cancel</button>
                <button type="submit" class="gti-btn-primary"><i class="fas fa-save"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>
