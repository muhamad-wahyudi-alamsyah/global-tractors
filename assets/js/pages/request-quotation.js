/**
 * Request Quotation page glue (PRD §6.6).
 */
(function () {
    'use strict';

    if (!window.GTI || !GTI.inbox) return;

    var api = GTI.inbox({
        entity: 'quotation',
        deleteAction: 'gti_delete_quotation',
        deleteLabel: 'Delete Quotation',
        paint: function (row, drawer) {
            var values = {
                'Name': row.customer_name,
                'Company': row.customer_company,
                'Email': row.customer_email,
                'Phone': row.customer_phone,
                'Address': row.customer_address,
                'Requested Date': row.request_date_text,
                'Needed Date': row.needed_date,
                'Valid Until': row.valid_until,
                'Payment Terms': row.payment_terms,
                'Delivery Location': row.delivery_location,
                'Rental Period': row.rental_period,
                'Budget': row.budget_text,
                'Total Items': row.total_items,
                'Est. Total Value': row.total_text,
                'Sales PIC': row.sales_pic || 'Unassigned'
            };

            drawer.querySelectorAll('.gti-drawer-row').forEach(function (el) {
                var label = el.querySelector('.gti-drawer-label');
                var value = el.querySelector('.gti-drawer-value');
                if (!label || !value) return;
                var key = label.textContent.trim();
                if (key in values) value.textContent = values[key] || '—';
            });

            var notes = drawer.querySelector('[data-field="notes"]');
            if (notes) notes.textContent = row.notes || '—';

            var items = drawer.querySelector('[data-field="items"]');
            if (items) {
                items.innerHTML = (row.items && row.items.length)
                    ? row.items.map(function (item) {
                        return '<div class="gti-drawer-item">' +
                            '<strong>' + GTI.fmt.escape(item.name || '—') + '</strong>' +
                            '<span>' + GTI.fmt.escape(String(item.quantity || 1)) + ' unit' +
                                (item.part_number ? ' · ' + GTI.fmt.escape(item.part_number) : '') + '</span>' +
                            (item.notes ? '<p>' + GTI.fmt.escape(item.notes) + '</p>' : '') +
                        '</div>';
                      }).join('')
                    : '<p class="gti-drawer-empty">Tidak ada item.</p>';
            }
        }
    });

    // "Create Quotation" opens the upload modal in quotation mode — this is what
    // replaced the alert('TODO') that used to sit behind the button (B-09).
    document.addEventListener('click', function (e) {
        var trigger = e.target.closest('.js-quotation, [data-action="quotation"]');
        if (!trigger) return;

        e.stopPropagation();
        document.querySelectorAll('.gti-ue-action-dropdown.show, .gti-drawer-dropdown.show')
            .forEach(function (d) { d.classList.remove('show'); });

        var tr = trigger.closest('tr[data-row]');
        if (tr) tr.click();

        api.openUpload('quotation');
    });
})();
