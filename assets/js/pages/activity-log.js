/**
 * Activity Log page script (PRD §6.13).
 *
 * Adds the CSV export and the row detail drawer; filters are a plain GET form
 * so they survive bookmarking and the back button.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        // ── Export the current filter set (§6.13 gap 4) ──────────────────────
        var exportBtn = document.getElementById('gti-export-log');
        if (exportBtn) {
            exportBtn.addEventListener('click', function () {
                var params = new URLSearchParams(window.location.search);
                var unlock = GTI.ui.lockButton(exportBtn, 'Menyiapkan…');

                GTI.api.post('gti_export_activity_log', {
                    search: params.get('search') || '',
                    action_filter: params.get('action_type') || '',
                    user_id: params.get('user_id') || '',
                    date_from: params.get('date_from') || '',
                    date_to: params.get('date_to') || ''
                })
                    .then(function (data) {
                        // admin-ajax has already sent headers, so the file is built
                        // here from the base64 payload rather than streamed.
                        var bytes = atob(data.content);
                        var buf = new Uint8Array(bytes.length);
                        for (var i = 0; i < bytes.length; i++) buf[i] = bytes.charCodeAt(i);

                        var url = URL.createObjectURL(new Blob([buf], { type: 'text/csv;charset=utf-8' }));
                        var a = document.createElement('a');
                        a.href = url;
                        a.download = data.filename;
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        URL.revokeObjectURL(url);

                        GTI.ui.toast(data.rows + ' baris diekspor.', 'success');
                    })
                    .catch(function () {})
                    .then(unlock);
            });
        }

        // ── Row detail drawer (§6.13 gap 2) ──────────────────────────────────
        var drawer = document.getElementById('detailDrawer');
        if (!drawer) return;

        document.querySelectorAll('.gti-ue-table tbody tr[data-log-id]').forEach(function (row) {
            row.addEventListener('click', function () {
                var id = this.dataset.logId;

                document.querySelectorAll('.gti-ue-table tbody tr.active-row')
                    .forEach(function (r) { r.classList.remove('active-row'); });
                this.classList.add('active-row');

                GTI.ui.drawer.open('detailDrawer');
                paint({ loading: true });

                GTI.api.post('gti_get_activity_detail', { id: id })
                    .then(function (data) { paint(data.entry); })
                    .catch(function () {});
            });
        });

        function set(field, value) {
            var el = drawer.querySelector('[data-field="' + field + '"]');
            if (el) el.textContent = value || '—';
        }

        function paint(entry) {
            if (entry.loading) {
                set('action', 'Memuat…');
                return;
            }

            set('action', (entry.action || '').replace(/_/g, ' '));
            set('actor', entry.display_name);
            set('entity', [entry.entity_type, entry.entity_id].filter(Boolean).join(' #'));
            set('when', GTI.fmt.dateTimeID(entry.created_at));
            set('ip', entry.ip_address);
            set('description', entry.description);

            var host = drawer.querySelector('[data-field="details"]');
            if (!host) return;

            var details = entry.details_parsed;
            if (!details || typeof details !== 'object' || !Object.keys(details).length) {
                host.innerHTML = '<p class="gti-drawer-empty">Tidak ada detail tambahan.</p>';
                return;
            }

            // A status_change carries from/to, so show it as a before → after pair
            // rather than raw JSON.
            if (details.from !== undefined || details.to !== undefined) {
                host.innerHTML =
                    '<div class="gti-diff">' +
                        '<span class="gti-diff-before">' + GTI.fmt.escape(details.from || '—') + '</span>' +
                        '<i class="fas fa-arrow-right"></i>' +
                        '<span class="gti-diff-after">' + GTI.fmt.escape(details.to || '—') + '</span>' +
                    '</div>';
                return;
            }

            host.innerHTML = Object.keys(details).map(function (key) {
                var value = details[key];
                if (value && typeof value === 'object') value = JSON.stringify(value);
                return '<div class="gti-drawer-row">' +
                    '<span class="gti-drawer-label">' + GTI.fmt.escape(key.replace(/_/g, ' ')) + '</span>' +
                    '<span class="gti-drawer-value">' + GTI.fmt.escape(String(value)) + '</span>' +
                '</div>';
            }).join('');
        }
    });
})();
