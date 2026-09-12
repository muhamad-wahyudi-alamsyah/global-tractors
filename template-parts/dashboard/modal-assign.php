<?php
/**
 * Assign PIC modal (PRD §6.6.5).
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="gti-modal-overlay" id="gtiAssignOverlay" role="dialog" aria-modal="true" aria-labelledby="gti-assign-title">
    <div class="gti-modal gti-modal-sm">
        <form id="gti-assign-form">
            <div class="gti-modal-header">
                <div>
                    <h3 id="gti-assign-title">Assign PIC</h3>
                    <p>Pilih penanggung jawab pesanan ini.</p>
                </div>
                <button type="button" class="gti-modal-close" data-gti-close="gtiAssignOverlay" aria-label="Close">&times;</button>
            </div>

            <div class="gti-modal-body">
                <div class="gti-field">
                    <label for="gti-assign-user">Sales PIC</label>
                    <select id="gti-assign-user" name="user_id">
                        <option value="0">&mdash; Unassigned &mdash;</option>
                        <?php foreach ( gti_assignable_users() as $gti_pic ) : ?>
                            <option value="<?php echo (int) $gti_pic->ID; ?>"><?php echo esc_html( $gti_pic->display_name ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <label class="gti-checkbox">
                    <input type="checkbox" name="notify" value="1" checked>
                    <span>Kirim notifikasi email ke PIC baru</span>
                </label>

                <input type="hidden" name="id" id="gti-assign-id">
                <input type="hidden" name="entity_type" id="gti-assign-entity-type">
            </div>

            <div class="gti-modal-footer">
                <button type="button" class="gti-btn-secondary" data-gti-close="gtiAssignOverlay">Cancel</button>
                <button type="submit" class="gti-btn-primary"><i class="fas fa-user-check"></i> Assign</button>
            </div>
        </form>
    </div>
</div>
