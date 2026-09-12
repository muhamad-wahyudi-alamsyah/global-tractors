/**
 * Customer Detail page script (PRD §6.9).
 *
 * Edit / Change Status / Delete / Send Email were all href="#" before this —
 * the markup promised actions that had nothing behind them (B-05). The save
 * endpoint (gti_save_customer) already existed; it just had no UI.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var data = window.gtiPageData || {};
        var customer = data.customer || {};

        // ── More Actions dropdown ────────────────────────────────────────────
        var moreToggle = document.getElementById('cd-more-toggle');
        var moreMenu = document.getElementById('cd-more-dropdown');
        if (moreToggle && moreMenu) {
            moreToggle.addEventListener('click', function (e) {
                e.preventDefault();
                moreMenu.classList.toggle('show');
            });
            document.addEventListener('click', function (e) {
                if (!e.target.closest('.gti-cd-more-wrap')) moreMenu.classList.remove('show');
            });
        }

        // ── Tabs ─────────────────────────────────────────────────────────────
        document.querySelectorAll('.gti-cd-tab[data-tab]').forEach(function (tab) {
            tab.addEventListener('click', function (e) {
                e.preventDefault();
                var target = this.getAttribute('data-tab');

                document.querySelectorAll('.gti-cd-tab[data-tab]').forEach(function (t) {
                    t.classList.toggle('active', t.getAttribute('data-tab') === target);
                });
                document.querySelectorAll('.gti-cd-tab-content').forEach(function (c) {
                    c.style.display = 'none';
                });
                var panel = document.getElementById('tab-' + target);
                if (panel) panel.style.display = 'block';
            });
        });

        // ── Edit ─────────────────────────────────────────────────────────────
        var editBtn = document.getElementById('cd-edit-btn');
        if (editBtn) {
            editBtn.addEventListener('click', function () {
                var form = document.getElementById('gti-customer-form');
                if (!form) return;

                Object.keys(customer).forEach(function (key) {
                    var field = form.querySelector('[name="' + key + '"]');
                    if (field) field.value = customer[key] == null ? '' : customer[key];
                });
                GTI.ui.modal.open('gtiCustomerOverlay');
            });
        }

        var form = document.getElementById('gti-customer-form');
        if (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();

                var submit = form.querySelector('button[type="submit"]');
                var unlock = GTI.ui.lockButton(submit, 'Menyimpan…');

                GTI.api.post('gti_save_customer', new FormData(form))
                    .then(function (res) {
                        GTI.ui.modal.close('gtiCustomerOverlay');
                        GTI.ui.toast(res.message || 'Customer tersimpan.', 'success');
                        // Fields are spread across the page; a reload is the honest
                        // way to repaint them all consistently.
                        setTimeout(function () { window.location.reload(); }, 600);
                    })
                    .catch(function () {})
                    .then(unlock);
            });
        }

        // ── Change status ────────────────────────────────────────────────────
        var statusBtn = document.getElementById('cd-status-btn');
        if (statusBtn) {
            statusBtn.addEventListener('click', function () {
                var next = customer.status === 'active' ? 'inactive' : 'active';

                GTI.api.post('gti_save_customer', {
                    id: customer.id,
                    customer_id: customer.customer_id,
                    name: customer.name,
                    status: next
                }).then(function () {
                    GTI.ui.toast('Status diubah menjadi ' + next + '.', 'success');
                    setTimeout(function () { window.location.reload(); }, 600);
                }).catch(function () {});
            });
        }

        // ── Delete ───────────────────────────────────────────────────────────
        var deleteBtn = document.getElementById('cd-delete-btn');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', function () {
                GTI.ui.confirm({
                    title: 'Delete Customer',
                    name: customer.name,
                    code: customer.customer_id,
                    // Orders are deliberately kept: deleting a contact record must not
                    // erase the history attached to it.
                    warning: 'Customer akan dihapus permanen. Request dan quotation ' +
                             'terkait TIDAK ikut terhapus.',
                    confirmLabel: 'Delete Customer'
                }).then(function (ok) {
                    if (!ok) return;
                    return GTI.api.post('gti_delete_customer', { id: customer.id })
                        .then(function (res) {
                            GTI.ui.toast(res.message || 'Customer dihapus.', 'success');
                            setTimeout(function () {
                                window.location.href = gtiAjax.dashboard + '/customers';
                            }, 900);
                        });
                }).catch(function () {});
            });
        }

        // ── Send email ───────────────────────────────────────────────────────
        var emailBtn = document.getElementById('cd-email-btn');
        if (emailBtn) {
            emailBtn.addEventListener('click', function () {
                document.getElementById('gti-email-to').value = customer.email || '';
                document.getElementById('gti-email-subject').value = '';
                document.getElementById('gti-email-message').value = '';
                document.getElementById('gti-email-entity-type').value = 'customer';
                document.getElementById('gti-email-entity-id').value = customer.id;
                document.getElementById('gti-email-no-recipient').hidden = !!customer.email;
                document.getElementById('gti-email-submit').disabled = !customer.email;
                GTI.ui.modal.open('gtiEmailOverlay');
            });
        }

        var emailForm = document.getElementById('gti-email-form');
        if (emailForm) {
            emailForm.addEventListener('submit', function (e) {
                e.preventDefault();

                var submit = document.getElementById('gti-email-submit');
                var unlock = GTI.ui.lockButton(submit, 'Mengirim…');

                GTI.api.post('gti_send_customer_email', new FormData(emailForm))
                    .then(function (res) {
                        GTI.ui.modal.close('gtiEmailOverlay');
                        emailForm.reset();
                        GTI.ui.toast(res.message, 'success');
                    })
                    .catch(function () {})
                    .then(unlock);
            });
        }
    });
})();
