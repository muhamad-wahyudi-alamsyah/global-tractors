/**
 * Sell Equipment page glue (PRD §6.7).
 */
(function () {
    'use strict';

    if (!window.GTI || !GTI.inbox) return;

    var drawer = document.getElementById('detailDrawer');

    GTI.inbox({
        entity: 'sell',
        deleteAction: 'gti_delete_sell_request',
        deleteLabel: 'Delete Offer',
        paint: function (row, drawerEl) {
            var values = {
                'Name': row.customer_name,
                'Company': row.customer_company,
                'Email': row.customer_email,
                'Phone': row.customer_phone,
                'Equipment': row.equipment_name,
                'Brand': row.brand,
                'Model': row.model,
                'Year': row.year,
                'Hours': row.hours ? Number(row.hours).toLocaleString() + ' hrs' : '',
                'Location': row.equipment_location,
                'Offered Price': row.price_text,
                'Submission Date': row.submitted_text,
                'Last Updated': row.updated_text,
                'Invoice Requested': row.invoice_requested
            };

            drawerEl.querySelectorAll('.gti-drawer-row').forEach(function (el) {
                var label = el.querySelector('.gti-drawer-label');
                var value = el.querySelector('.gti-drawer-value');
                if (!label || !value || value.dataset.field === 'whatsapp') return;
                var key = label.textContent.trim();
                if (key in values) value.textContent = values[key] || '—';
                if (key === 'Condition') {
                    value.innerHTML = row.condition
                        ? '<span class="gti-condition-badge ' + GTI.fmt.escape(String(row.condition).toLowerCase()) + '">' +
                          GTI.fmt.escape(row.condition) + '</span>'
                        : '—';
                }
            });

            // Titled by the unit on offer, as the drawer was before PRD v2.
            var title = drawerEl.querySelector('[data-field="ref"]');
            if (title) title.textContent = row.equipment_name || '—';

            var message = drawerEl.querySelector('[data-field="message"]');
            if (message) message.textContent = row.message || '—';

            // Images may be stored as attachment IDs; the server resolved them to
            // URLs, so entries that used to be dropped now render.
            var gallery = drawerEl.querySelector('[data-field="images"]');
            if (gallery) {
                gallery.innerHTML = (row.images && row.images.length)
                    ? '<div class="gti-drawer-images">' + row.images.map(function (url) {
                        return '<a href="' + GTI.fmt.escape(url) + '" target="_blank" rel="noopener">' +
                               '<img src="' + GTI.fmt.escape(url) + '" alt="Equipment image" loading="lazy"></a>';
                      }).join('') + '</div>'
                    : '<div class="gti-drawer-no-images">No images uploaded</div>';
            }

            setWhatsApp(row);
        }
    });

    /**
     * A seller with no usable number gets a disabled button rather than a dead
     * wa.me link (PRD §6.7.1).
     */
    function setWhatsApp(row) {
        if (!drawer) return;

        var button = drawer.querySelector('[data-action="whatsapp"]');
        if (button) {
            if (row.whatsapp) {
                button.href = row.whatsapp;
                button.classList.remove('is-disabled');
                button.removeAttribute('aria-disabled');
                button.title = '';
            } else {
                button.href = '#';
                button.classList.add('is-disabled');
                button.setAttribute('aria-disabled', 'true');
                button.title = 'Nomor tidak tersedia';
            }
        }

        var link = drawer.querySelector('[data-field="whatsapp"]');
        if (link && link.tagName === 'A') {
            link.href = row.whatsapp || '#';
            link.textContent = row.whatsapp ? 'Chat' : '—';
        } else if (link) {
            link.textContent = row.whatsapp ? 'Chat' : '—';
        }
    }

    // Row-level WhatsApp action.
    document.addEventListener('click', function (e) {
        var trigger = e.target.closest('.js-whatsapp');
        if (!trigger) return;

        e.stopPropagation();
        document.querySelectorAll('.gti-ue-action-dropdown.show')
            .forEach(function (d) { d.classList.remove('show'); });

        var tr = trigger.closest('tr[data-row]');
        if (!tr) return;

        var row;
        try { row = JSON.parse(tr.dataset.row); } catch (err) { return; }

        if (!row.whatsapp) {
            GTI.ui.toast('Nomor WhatsApp tidak tersedia untuk penjual ini.', 'warning');
            return;
        }
        window.open(row.whatsapp, '_blank', 'noopener');
    });

    // A disabled WhatsApp button must not navigate.
    document.addEventListener('click', function (e) {
        var button = e.target.closest('[data-action="whatsapp"].is-disabled');
        if (button) e.preventDefault();
    });
})();
