<?php
/**
 * Delete-user modal with content reassignment (PRD §6.12 gap 4).
 *
 * wp_delete_user() without a reassign target deletes everything the user
 * authored. The picker is only shown when they actually own articles, and the
 * server refuses the delete without one in that case.
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="gti-modal-overlay" id="gtiUserDeleteOverlay" role="dialog" aria-modal="true" aria-labelledby="gti-user-delete-title">
    <div class="gti-modal">
        <form id="gti-user-delete-form">
            <div class="gti-modal-header">
                <div>
                    <h3 id="gti-user-delete-title">Delete User</h3>
                    <p>This cannot be undone.</p>
                </div>
                <button type="button" class="gti-modal-close" data-gti-close="gtiUserDeleteOverlay" aria-label="Close">&times;</button>
            </div>

            <div class="gti-modal-body">
                <div class="gti-summary-rows">
                    <div class="gti-summary-row"><span>User</span><strong id="gti-user-delete-name">&mdash;</strong></div>
                    <div class="gti-summary-row"><span>Email</span><strong id="gti-user-delete-email">&mdash;</strong></div>
                </div>

                <div class="gti-modal-banner gti-modal-banner-warning" id="gti-user-delete-owns" hidden>
                    <i class="fas fa-exclamation-triangle"></i>
                    <span><strong id="gti-user-delete-count">0</strong> article(s) belong to this user.
                          Choose who inherits them, or they will be lost.</span>
                </div>

                <div class="gti-field" id="gti-user-delete-reassign-field" hidden>
                    <label for="gti-user-delete-reassign">Reassign content to <span class="required">*</span></label>
                    <select id="gti-user-delete-reassign" name="reassign_to">
                        <option value="">&mdash; Choose a user &mdash;</option>
                        <?php
                        foreach ( get_users( array( 'capability' => 'edit_posts', 'orderby' => 'display_name' ) ) as $gti_user ) :
                            ?>
                            <option value="<?php echo (int) $gti_user->ID; ?>"><?php echo esc_html( $gti_user->display_name ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <input type="hidden" name="id" id="gti-user-delete-id">
            </div>

            <div class="gti-modal-footer">
                <button type="button" class="gti-btn-secondary" data-gti-close="gtiUserDeleteOverlay">Cancel</button>
                <button type="submit" class="gti-btn-primary" style="background:#dc2626;border-color:#dc2626;color:#fff">
                    <i class="fas fa-trash"></i> Delete User
                </button>
            </div>
        </form>
    </div>
</div>
