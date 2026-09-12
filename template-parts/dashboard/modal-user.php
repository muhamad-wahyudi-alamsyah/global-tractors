<?php
/**
 * Add / edit team member.
 *
 * Recovered during the R1 layout refactor: this markup sat after </main> in
 * the page template and was rendered inline there. It is a part now, requested
 * via gti_dashboard_close(['modals' => [...]]).
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>
    <div class="gti-users-modal" id="gti-user-modal">
        <div class="gti-users-modal-box">
            <div class="gti-users-modal-head">
                <h3 id="gti-user-modal-title">Add User</h3>
                <button type="button" class="gti-users-modal-close" onclick="gtiCloseUserModal()"><i class="fas fa-times"></i></button>
            </div>
            <form id="gti-user-form">
                <input type="hidden" name="user_id" id="gti-user-id" value="0">
                <div id="gti-user-modal-alert" class="gti-alert" role="alert"></div>
                <div class="gti-profile-grid">
                    <div class="gti-profile-field">
                        <label for="gti-user-login">Username <span style="color:#ef4444">*</span></label>
                        <input type="text" id="gti-user-login" name="user_login" required>
                        <span class="field-hint" id="gti-user-login-hint">Cannot be changed later</span>
                    </div>
                    <div class="gti-profile-field">
                        <label for="gti-user-email">Email <span style="color:#ef4444">*</span></label>
                        <input type="email" id="gti-user-email" name="user_email" required>
                    </div>
                    <div class="gti-profile-field">
                        <label for="gti-user-first">First Name</label>
                        <input type="text" id="gti-user-first" name="first_name">
                    </div>
                    <div class="gti-profile-field">
                        <label for="gti-user-last">Last Name</label>
                        <input type="text" id="gti-user-last" name="last_name">
                    </div>
                    <div class="gti-profile-field">
                        <label for="gti-user-phone">Phone</label>
                        <input type="tel" id="gti-user-phone" name="phone">
                    </div>
                    <div class="gti-profile-field">
                        <label for="gti-user-department">Department</label>
                        <input type="text" id="gti-user-department" name="department">
                    </div>
                    <div class="gti-profile-field">
                        <label for="gti-user-role">Role <span style="color:#ef4444">*</span></label>
                        <select id="gti-user-role" name="role" required>
                            <?php foreach ($editable_roles as $role_key => $role_data): ?>
                                <option value="<?php echo esc_attr($role_key); ?>">
                                    <?php echo esc_html($role_labels[$role_key] ?? $role_data['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="gti-profile-field">
                        <label for="gti-user-pass">Password</label>
                        <input type="password" id="gti-user-pass" name="user_pass" minlength="8" autocomplete="new-password">
                        <span class="field-hint" id="gti-user-pass-hint">Required for new users, min. 8 characters</span>
                    </div>
                </div>
                <div class="gti-profile-actions">
                    <button type="button" class="gti-btn-secondary" onclick="gtiCloseUserModal()">Cancel</button>
                    <button type="submit" class="gti-btn-primary" id="gti-user-save-btn">
                        <i class="fas fa-save"></i> <span>Save User</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
