/**
 * Customers page script.
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


        // Action Dropdown Toggle

        // Backdrop click → close drawer
        document.getElementById('drawerBackdrop').addEventListener('click', function() {
            closeDetailDrawer();
        });

        // Close drawer on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeDetailDrawer();
        });

        // Row click → open drawer
        document.querySelectorAll('.gti-ue-table tbody tr[data-cust]').forEach(function(row) {
            row.addEventListener('click', function(e) {
                if (e.target.closest('.gti-ue-action-menu')) return;
                var cust;
                try { cust = JSON.parse(this.getAttribute('data-cust')); } catch(err) { return; }
                showCustomerDetail(cust);
            });
        });

        // Auto-populate drawer with first row on page load
        var firstRow = document.querySelector('.gti-ue-table tbody tr[data-cust]');
        if (firstRow) {
            try {
                var firstCust = JSON.parse(firstRow.getAttribute('data-cust'));
                updateDrawerContent(firstCust);
            } catch(e) {}
        }

        // ── Add / Edit / Delete ──────────────────────────────────────────────
        // Moved here from customer-detail.js: /dashboard/customer-detail has no
        // link pointing at it any more, which left the customer table with no
        // way at all to correct or remove a record.
        document.addEventListener('click', function (e) {
            var trigger = e.target.closest('.js-cust-add, .js-cust-edit, .js-cust-delete');
            if (!trigger) return;
            e.preventDefault();

            var cust = rowCustomer(trigger);

            if (trigger.classList.contains('js-cust-add')) {
                openCustomerForm({});
            } else if (trigger.classList.contains('js-cust-edit')) {
                if (cust) openCustomerForm(cust);
            } else if (cust) {
                deleteCustomer(cust);
            }
        });

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
                        setTimeout(function () { window.location.reload(); }, 600);
                    })
                    .catch(function () {})
                    .then(unlock);
            });
        }
    });

    /** The row a button sits in, or the drawer's current customer. */
    function rowCustomer(el) {
        var row = el.closest('tr[data-cust]');
        if (!row) return _currentDrawerCust;
        try { return JSON.parse(row.getAttribute('data-cust')); } catch (err) { return null; }
    }

    function openCustomerForm(cust) {
        var form = document.getElementById('gti-customer-form');
        if (!form) return;

        form.reset();
        document.getElementById('cf-id').value = cust.id || 0;
        Object.keys(cust).forEach(function (key) {
            var field = form.querySelector('[name="' + key + '"]');
            if (field) field.value = cust[key] == null ? '' : cust[key];
        });

        var title = document.getElementById('gti-customer-title');
        if (title) title.textContent = cust.id ? 'Edit Customer' : 'Add Customer';
        GTI.ui.modal.open('gtiCustomerOverlay');
    }

    function deleteCustomer(cust) {
        GTI.ui.confirm({
            title: 'Delete Customer',
            name: cust.name,
            code: cust.customer_id,
            warning: 'Customer akan dihapus permanen. Request dan quotation ' +
                     'terkait TIDAK ikut terhapus.',
            confirmLabel: 'Delete Customer'
        }).then(function (ok) {
            if (!ok) return;
            return GTI.api.post('gti_delete_customer', { id: cust.id })
                .then(function (res) {
                    GTI.ui.toast(res.message || 'Customer dihapus.', 'success');
                    setTimeout(function () { window.location.reload(); }, 900);
                });
        }).catch(function () {});
    }

    var _currentDrawerCust = null;

    function updateDrawerContent(cust) {
        _currentDrawerCust = cust;
        document.getElementById('drawer-cust-name').textContent = cust.name || '-';

        document.getElementById('drawer-cust-id').textContent = cust.customer_id || '-';
        document.getElementById('drawer-email').textContent = cust.email || '-';
        document.getElementById('drawer-phone').textContent = cust.phone || '-';
        document.getElementById('drawer-since').textContent = cust.registered_date ? formatDateID(cust.registered_date) : '-';

        document.getElementById('drawer-company').textContent = cust.company || '-';
        document.getElementById('drawer-location').textContent = [cust.city, cust.province, cust.country].filter(Boolean).join(', ') || '-';

        document.getElementById('drawer-transactions').textContent = cust.total_transactions || '0';
        document.getElementById('drawer-spent').textContent = cust.total_spent ? formatCurrencyID(cust.total_spent) : '-';
        document.getElementById('drawer-last-contact').textContent = cust.last_contact ? formatDateID(cust.last_contact) : '-';

        var emailUrl = 'mailto:' + (cust.email || '');
        document.getElementById('drawer-btn-email').href = emailUrl;

        document.querySelectorAll('.gti-ue-table tbody tr').forEach(function(r) { r.classList.remove('active-row'); });
        var activeRow = document.querySelector('.gti-ue-table tbody tr[data-cust-id="' + cust.customer_id + '"]');
        if (activeRow) activeRow.classList.add('active-row');
    }

    function showCustomerDetail(cust) {
        updateDrawerContent(cust);
        if (window.innerWidth < 1600) {
            document.getElementById('custDetailDrawer').classList.add('open');
            document.getElementById('drawerBackdrop').classList.add('show');
            document.body.style.overflow = 'hidden';
        }
        document.querySelectorAll('.gti-ue-action-dropdown.show').forEach(function(d) { d.classList.remove('show'); });
    }

    function formatCurrencyID(amount) {
        return 'IDR ' + Number(amount).toLocaleString('id-ID');
    }
