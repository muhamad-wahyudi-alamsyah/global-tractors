/**
 * Users page script.
 *
 * Lifted out of the template's inline <script> during the R1/R3 refactor.
 * Shared behaviour — toast, drawer, modal, action dropdown, sidebar collapse,
 * date/currency formatting and the fetch wrapper — comes from
 * assets/js/dashboard-ui.js; gtiAjax comes from wp_localize_script().
 */

document.addEventListener('DOMContentLoaded', function() {
        // Sidebar collapse
        var collapseBtn = document.getElementById('gti-collapse-btn');
        var sidebar = document.getElementById('gti-sidebar');
        var mainEl = document.getElementById('gti-main');


        // Mobile menu toggle
        var menuToggle = document.getElementById('gti-menu-toggle');
        if (menuToggle) {
            menuToggle.addEventListener('click', function() {
                sidebar.classList.toggle('mobile-open');
            });
        }

        // Profile form
        var profileForm = document.getElementById('gti-profile-form');
        var profileBtn = document.getElementById('gti-profile-btn');
        var profileAlert = document.getElementById('gti-profile-alert');

        if (profileForm) {
            profileForm.addEventListener('submit', function(e) {
                e.preventDefault();
                profileBtn.disabled = true;
                profileBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Saving...</span>';
                profileAlert.style.display = 'none';

                var data = new FormData(profileForm);
                data.append('action', 'gti_update_profile');

                fetch(gtiAjax.ajaxurl, { method: 'POST', body: data })
                    .then(function(r) { return r.json(); })
                    .then(function(res) {
                        profileBtn.disabled = false;
                        profileBtn.innerHTML = '<i class="fas fa-save"></i> <span>Save Changes</span>';
                        profileAlert.style.display = 'block';
                        if (res.success) {
                            profileAlert.className = 'gti-alert success';
                            profileAlert.textContent = res.data.message || 'Profile updated successfully!';
                        } else {
                            profileAlert.className = 'gti-alert error';
                            profileAlert.textContent = res.data.message || 'Failed to update profile.';
                        }
                        setTimeout(function() { profileAlert.style.display = 'none'; }, 5000);
                    })
                    .catch(function() {
                        profileBtn.disabled = false;
                        profileBtn.innerHTML = '<i class="fas fa-save"></i> <span>Save Changes</span>';
                        profileAlert.style.display = 'block';
                        profileAlert.className = 'gti-alert error';
                        profileAlert.textContent = 'Network error. Please try again.';
                    });
            });
        }

        // Password form
        var passwordForm = document.getElementById('gti-password-form');
        var passwordBtn = document.getElementById('gti-password-btn');
        var passwordAlert = document.getElementById('gti-password-alert');

        if (passwordForm) {
            passwordForm.addEventListener('submit', function(e) {
                e.preventDefault();
                var newPass = document.getElementById('gti-new-password').value;
                var confirmPass = document.getElementById('gti-confirm-password').value;

                if (newPass !== confirmPass) {
                    passwordAlert.style.display = 'block';
                    passwordAlert.className = 'gti-alert error';
                    passwordAlert.textContent = 'New passwords do not match.';
                    return;
                }
                if (newPass.length < 8) {
                    passwordAlert.style.display = 'block';
                    passwordAlert.className = 'gti-alert error';
                    passwordAlert.textContent = 'Password must be at least 8 characters.';
                    return;
                }

                passwordBtn.disabled = true;
                passwordBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Updating...</span>';
                passwordAlert.style.display = 'none';

                var data = new FormData(passwordForm);
                data.append('action', 'gti_change_password');

                fetch(gtiAjax.ajaxurl, { method: 'POST', body: data })
                    .then(function(r) { return r.json(); })
                    .then(function(res) {
                        passwordBtn.disabled = false;
                        passwordBtn.innerHTML = '<i class="fas fa-key"></i> <span>Update Password</span>';
                        passwordAlert.style.display = 'block';
                        if (res.success) {
                            passwordAlert.className = 'gti-alert success';
                            passwordAlert.textContent = res.data.message || 'Password updated successfully!';
                            passwordForm.reset();
                        } else {
                            passwordAlert.className = 'gti-alert error';
                            passwordAlert.textContent = res.data.message || 'Failed to update password.';
                        }
                        setTimeout(function() { passwordAlert.style.display = 'none'; }, 5000);
                    })
                    .catch(function() {
                        passwordBtn.disabled = false;
                        passwordBtn.innerHTML = '<i class="fas fa-key"></i> <span>Update Password</span>';
                        passwordAlert.style.display = 'block';
                        passwordAlert.className = 'gti-alert error';
                        passwordAlert.textContent = 'Network error. Please try again.';
                    });
            });
        }

        // ── Team directory ──────────────────────────────────────────────
        var addUserBtn = document.getElementById('gti-add-user-btn');
        if (addUserBtn) {
            addUserBtn.addEventListener('click', function() { gtiOpenUserModal(null); });
        }

        var userForm = document.getElementById('gti-user-form');
        if (userForm) {
            userForm.addEventListener('submit', gtiSubmitUserForm);
        }

        var userModal = document.getElementById('gti-user-modal');
        if (userModal) {
            // Click the backdrop, not the box, to dismiss.
            userModal.addEventListener('click', function(e) {
                if (e.target === userModal) gtiCloseUserModal();
            });
        }
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') gtiCloseUserModal();
        });
    });

    function gtiUsersAlert(id, type, message) {
        var el = document.getElementById(id);
        if (!el) return;
        el.style.display = 'block';
        el.className = 'gti-alert ' + type;
        el.textContent = message;
        if (type === 'success') {
            setTimeout(function() { el.style.display = 'none'; }, 5000);
        }
    }

    function gtiOpenUserModal(user) {
        var modal = document.getElementById('gti-user-modal');
        if (!modal) return;

        document.getElementById('gti-user-modal-alert').style.display = 'none';
        document.getElementById('gti-user-modal-title').textContent = user ? 'Edit User' : 'Add User';
        document.getElementById('gti-user-id').value = user ? user.id : 0;
        document.getElementById('gti-user-login').value = user ? user.login : '';
        document.getElementById('gti-user-email').value = user ? user.email : '';
        document.getElementById('gti-user-first').value = user ? (user.first_name || '') : '';
        document.getElementById('gti-user-last').value = user ? (user.last_name || '') : '';
        document.getElementById('gti-user-phone').value = user ? (user.phone || '') : '';
        document.getElementById('gti-user-department').value = user ? (user.department || '') : '';
        document.getElementById('gti-user-pass').value = '';

        var roleSelect = document.getElementById('gti-user-role');
        if (user && user.role) roleSelect.value = user.role;

        // WordPress does not allow renaming an account after creation.
        var loginField = document.getElementById('gti-user-login');
        loginField.readOnly = !!user;
        document.getElementById('gti-user-login-hint').textContent = user
            ? 'Usernames cannot be changed'
            : 'Cannot be changed later';
        document.getElementById('gti-user-pass-hint').textContent = user
            ? 'Leave blank to keep the current password'
            : 'Required for new users, min. 8 characters';
        document.getElementById('gti-user-pass').required = !user;

        modal.classList.add('open');
    }

    function gtiEditUser(btn) {
        var user;
        try { user = JSON.parse(btn.getAttribute('data-user')); } catch (e) { return; }
        gtiOpenUserModal(user);
    }

    function gtiCloseUserModal() {
        var modal = document.getElementById('gti-user-modal');
        if (modal) modal.classList.remove('open');
    }

    function gtiSubmitUserForm(e) {
        e.preventDefault();

        var form = e.target;
        var btn = document.getElementById('gti-user-save-btn');
        var pass = document.getElementById('gti-user-pass').value;
        var isNew = document.getElementById('gti-user-id').value === '0';

        if ((isNew || pass) && pass.length < 8) {
            gtiUsersAlert('gti-user-modal-alert', 'error', 'Password must be at least 8 characters.');
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Saving...</span>';

        var data = new FormData(form);
        data.append('action', 'gti_save_user');
        data.append('nonce', gtiAjax.nonce);

        fetch(gtiAjax.ajaxurl, { method: 'POST', body: data })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save"></i> <span>Save User</span>';
                if (res.success) {
                    location.reload();
                } else {
                    gtiUsersAlert('gti-user-modal-alert', 'error', (res.data && res.data.message) || 'Failed to save user.');
                }
            })
            .catch(function() {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save"></i> <span>Save User</span>';
                gtiUsersAlert('gti-user-modal-alert', 'error', 'Network error. Please try again.');
            });
    }



/**
 * Delete a user, handing their articles to someone else first.
 *
 * wp_delete_user() with no reassign target deletes everything the user wrote,
 * so the server refuses when they own content and none is named (PRD §6.12).
 */
(function () {
    'use strict';

    document.addEventListener('click', function (e) {
        var trigger = e.target.closest('.js-delete-user');
        if (!trigger) return;

        var user;
        try { user = JSON.parse(trigger.dataset.user); } catch (err) { return; }

        document.getElementById('gti-user-delete-id').value = user.id;
        document.getElementById('gti-user-delete-name').textContent = user.name;
        document.getElementById('gti-user-delete-email').textContent = user.email || '—';
        document.getElementById('gti-user-delete-count').textContent = user.posts;

        var owns = Number(user.posts) > 0;
        document.getElementById('gti-user-delete-owns').hidden = !owns;
        document.getElementById('gti-user-delete-reassign-field').hidden = !owns;

        var select = document.getElementById('gti-user-delete-reassign');
        select.required = owns;
        select.value = '';
        // Never offer the account being deleted as its own inheritor.
        Array.prototype.forEach.call(select.options, function (opt) {
            opt.hidden = opt.value === String(user.id);
        });

        GTI.ui.modal.open('gtiUserDeleteOverlay');
    });

    var form = document.getElementById('gti-user-delete-form');
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            var submit = form.querySelector('button[type="submit"]');
            var unlock = GTI.ui.lockButton(submit, 'Menghapus…');

            GTI.api.post('gti_delete_user', new FormData(form))
                .then(function (res) {
                    GTI.ui.modal.close('gtiUserDeleteOverlay');
                    GTI.ui.toast(res.message || 'User dihapus.', 'success');
                    setTimeout(function () { window.location.reload(); }, 700);
                })
                .catch(function () {})
                .then(unlock);
        });
    }
})();


/**
 * My Profile / System Users tabs (PRD §6.12 gap 1).
 *
 * The two sections used to be stacked on one page, so reaching the directory
 * meant scrolling past the whole profile form.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var tabs = document.querySelectorAll('#gti-users-tabs .gti-cd-tab');
        if (!tabs.length) return;

        function show(name) {
            tabs.forEach(function (t) {
                t.classList.toggle('active', t.dataset.tab === name);
            });
            document.querySelectorAll('.gti-cd-tab-content').forEach(function (panel) {
                panel.style.display = panel.id === 'tab-' + name ? 'block' : 'none';
            });
            GTI.storage.set('gti-users-tab', name);
        }

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function (e) {
                e.preventDefault();
                show(this.dataset.tab);
            });
        });

        // A filter or page link lands back here; stay on the directory.
        var params = new URLSearchParams(window.location.search);
        if (params.has('user_search') || params.has('role') || params.has('page_num')) {
            show('team');
        } else {
            show(GTI.storage.get('gti-users-tab') || 'profile');
        }
    });
})();
