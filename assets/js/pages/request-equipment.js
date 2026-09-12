/**
 * Request Equipment page glue (PRD §6.5).
 *
 * Everything shared with the other two inboxes lives in inbox-ui.js; this file
 * only says which fields this page's drawer shows.
 */
(function () {
    'use strict';

    if (!window.GTI || !GTI.inbox) return;

    GTI.inbox({
        entity: 'request',
        deleteAction: 'gti_delete_request',
        deleteLabel: 'Delete Request',
        paint: function (row, drawer) {
            var rows = drawer.querySelectorAll('.gti-drawer-row');

            // Label-driven: the drawer markup pairs a label with its value, so
            // the mapping stays readable next to the template.
            var values = {
                'Name': row.customer_name,
                'Company': row.customer_company,
                'Email': row.customer_email,
                'Phone': row.customer_phone,
                'Address': row.customer_address,
                'Equipment': row.equipment,
                'Category': row.category,
                'Brand': row.brand,
                'Quantity': row.quantity,
                'Location': row.location,
                'Budget': row.budget_text,
                'Required Date': row.required_date ? GTI.fmt.dateID(row.required_date) : '',
                'Usage Purpose': row.usage_purpose,
                'Request Date': row.request_date_text,
                'PIC': row.sales_pic || 'Unassigned'
            };

            rows.forEach(function (el) {
                var label = el.querySelector('.gti-drawer-label');
                var value = el.querySelector('.gti-drawer-value');
                if (!label || !value) return;
                var key = label.textContent.trim();
                if (key in values) value.textContent = values[key] || '—';
            });

            var message = drawer.querySelector('[data-field="message"]');
            if (message) message.textContent = row.message || '—';
        }
    });
})();
