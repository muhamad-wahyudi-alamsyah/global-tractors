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
    });

    var _currentDrawerCust = null;

    function updateDrawerContent(cust) {
        _currentDrawerCust = cust;
        document.getElementById('drawer-cust-name').textContent = cust.name || '-';
        var badge = document.getElementById('drawer-status-badge');
        badge.textContent = (cust.status || 'active').charAt(0).toUpperCase() + (cust.status || 'active').slice(1);
        badge.className = 'gti-drawer-status status-' + (cust.status || 'active');

        document.getElementById('drawer-cust-id').textContent = cust.customer_id || '-';
        document.getElementById('drawer-email').textContent = cust.email || '-';
        document.getElementById('drawer-phone').textContent = cust.phone || '-';
        document.getElementById('drawer-since').textContent = cust.registered_date ? formatDateID(cust.registered_date) : '-';
        document.getElementById('drawer-rating').textContent = cust.rating ? cust.rating + ' / 5.0' : '-';

        document.getElementById('drawer-company').textContent = cust.company || '-';
        document.getElementById('drawer-industry').textContent = cust.industry || '-';
        document.getElementById('drawer-location').textContent = [cust.city, cust.province, cust.country].filter(Boolean).join(', ') || '-';
        document.getElementById('drawer-npwp').textContent = cust.npwp || '-';

        document.getElementById('drawer-transactions').textContent = cust.total_transactions || '0';
        document.getElementById('drawer-spent').textContent = cust.total_spent ? formatCurrencyID(cust.total_spent) : '-';
        document.getElementById('drawer-last-contact').textContent = cust.last_contact ? formatDateID(cust.last_contact) : '-';

        var profileUrl = (gtiAjax.dashboard + '/customer-detail') + '?id=' + (cust.customer_id || '');
        var emailUrl = 'mailto:' + (cust.email || '');
        document.getElementById('drawer-btn-profile').href = profileUrl;
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
