/**
 * Shared behaviour for the three inbox pages (PRD §6.5–§6.7).
 *
 * Request Equipment, Request Quotation and Sell Equipment differ in their
 * fields, not in how they work: pick a row, repaint the drawer, change a
 * status, upload a document, compose an email, assign a PIC. That common part
 * lives here so it is written once instead of three times.
 *
 * Nothing in here reloads the page. Every action patches the DOM, which is what
 * keeps scroll position and the open drawer intact (PRD §6.5.4).
 */
(function (window, document) {
    'use strict';

    var GTI = window.GTI = window.GTI || {};

    /**
     * @param {Object} config
     * @param {string} config.entity        request|quotation|sell
     * @param {Function} config.paint       (row, drawer) => void — page-specific fields
     * @param {string} [config.deleteAction] AJAX action for row deletion
     * @param {string} [config.deleteLabel]  Title on the delete confirmation
     */
    GTI.inbox = function (config) {
        var drawer = document.getElementById('detailDrawer');
        if (!drawer) return;

        var entity = config.entity;
        var current = null;

        // ── Drawer painting ──────────────────────────────────────────────────
        function field(name) { return drawer.querySelector('[data-field="' + name + '"]'); }

        function setStatus(row) {
            var badge = field('status-badge');
            if (badge) {
                badge.textContent = row.status_label;
                badge.className = 'gti-drawer-status status-' + row.status_class;
            }
            buildStatusMenu(row);
        }

        /** Only transitions the state machine allows are offered (PRD §5.1). */
        function buildStatusMenu(row) {
            var menu = field('status-menu');
            if (!menu) return;

            menu.innerHTML = '';
            var next = row.next || {};
            var keys = Object.keys(next);

            if (!keys.length) {
                menu.innerHTML = '<div class="gti-drawer-dropdown-empty">No further status changes</div>';
                return;
            }

            keys.forEach(function (key) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'gti-drawer-dropdown-item';
                btn.innerHTML = '<i class="fas fa-arrow-right"></i> ' + GTI.fmt.escape(next[key]);
                btn.addEventListener('click', function () {
                    menu.classList.remove('show');
                    requestStatus(key, next[key]);
                });
                menu.appendChild(btn);
            });
        }

        function paint(row) {
            current = row;
            drawer.dataset.id = row.id;

            var ref = field('ref');
            if (ref) ref.textContent = row.ref || '—';

            setStatus(row);
            if (typeof config.paint === 'function') config.paint(row, drawer);

            refreshPanel('timeline', 'gti_get_timeline');
            refreshPanel('emails', 'gti_get_email_history');
        }

        /** Generic label/value setter used by the per-page paint functions. */
        GTI.inbox.setRows = function (drawerEl, map) {
            Object.keys(map).forEach(function (selector) {
                var el = drawerEl.querySelector('[data-field="' + selector + '"]');
                if (el) el.textContent = map[selector] || '—';
            });
        };

        function renderDocuments(documents) {
            var host = field('documents');
            if (!host || !documents) return;

            if (!documents.length) {
                host.innerHTML = '<p class="gti-drawer-empty">Belum ada dokumen.</p>';
                return;
            }

            host.innerHTML = '<div class="gti-doc-list">' + documents.map(function (doc) {
                return '<div class="gti-doc-item">' +
                    '<i class="fas fa-file-alt"></i>' +
                    '<div class="gti-doc-meta">' +
                        '<strong>' + GTI.fmt.escape(doc.name) + '</strong>' +
                        '<span>' + GTI.fmt.escape(doc.size) + ' · ' + GTI.fmt.dateID(doc.date) + '</span>' +
                    '</div>' +
                    (doc.url ? '<a href="' + GTI.fmt.escape(doc.url) + '" class="gti-doc-dl" download target="_blank" rel="noopener"><i class="fas fa-download"></i></a>' : '') +
                '</div>';
            }).join('') + '</div>';
        }

        function renderTimeline(items) {
            var host = field('timeline');
            if (!host) return;

            if (!items || !items.length) {
                host.innerHTML = '<p class="gti-drawer-empty">Belum ada riwayat.</p>';
                return;
            }

            host.innerHTML = '<div class="gti-drawer-timeline">' + items.map(function (item, i) {
                return '<div class="gti-timeline-item' + (i === 0 ? ' is-latest' : '') + '">' +
                    '<div class="gti-timeline-dot"></div>' +
                    '<div class="gti-timeline-content">' +
                        '<strong>' + GTI.fmt.escape(item.event) + '</strong>' +
                        (item.note ? '<p>' + GTI.fmt.escape(item.note) + '</p>' : '') +
                        '<span>' + GTI.fmt.dateTimeID(item.date) +
                            (item.actor ? ' · ' + GTI.fmt.escape(item.actor) : '') + '</span>' +
                    '</div>' +
                '</div>';
            }).join('') + '</div>';
        }

        function renderEmails(items) {
            var host = field('emails');
            if (!host) return;

            if (!items || !items.length) {
                host.innerHTML = '<p class="gti-drawer-empty">Belum ada email terkirim.</p>';
                return;
            }

            host.innerHTML = '<div class="gti-doc-list">' + items.map(function (item) {
                return '<div class="gti-doc-item">' +
                    '<i class="fas ' + (Number(item.send_result) ? 'fa-envelope-open-text' : 'fa-envelope') + '"' +
                        (Number(item.send_result) ? '' : ' style="color:#dc2626"') + '></i>' +
                    '<div class="gti-doc-meta">' +
                        '<strong>' + GTI.fmt.escape(item.subject) + '</strong>' +
                        '<span>' + GTI.fmt.escape(item.recipient_email) + ' · ' +
                            GTI.fmt.dateTimeID(item.sent_at) +
                            (Number(item.send_result) ? '' : ' · gagal') + '</span>' +
                    '</div>' +
                '</div>';
            }).join('') + '</div>';
        }

        function refreshPanel(name, action) {
            var host = field(name);
            if (!host || !drawer.dataset.id) return;

            GTI.api.post(action, { entity_type: entity, id: drawer.dataset.id }, { toastOnError: false })
                .then(function (data) {
                    if (name === 'timeline') {
                        renderTimeline(data.items);
                        renderDocuments(data.attachments);
                    } else {
                        renderEmails(data.items);
                    }
                })
                .catch(function () {});
        }

        // ── Applying a server response ───────────────────────────────────────
        function applyResponse(data) {
            GTI.ui.toast(data.message, data.toast_type || 'success');

            if (current) {
                current.status = data.status;
                current.status_label = data.status_label;
                current.status_class = data.status_class;
                current.next = data.next;
                setStatus(current);
            }

            if (data.attachments) renderDocuments(data.attachments);
            if (data.timeline) renderTimeline(data.timeline);
            refreshPanel('emails', 'gti_get_email_history');

            // Repaint the row's badge in the table too.
            var row = document.querySelector('tr[data-id="' + drawer.dataset.id + '"]');
            if (row) {
                var badge = row.querySelector('.gti-ue-status-badge');
                if (badge) {
                    badge.textContent = data.status_label;
                    badge.className = 'gti-ue-status-badge status-' + data.status_class;
                }
                var payload = readRow(row);
                if (payload) {
                    payload.status = data.status;
                    payload.status_label = data.status_label;
                    payload.status_class = data.status_class;
                    payload.next = data.next;
                    row.dataset.row = JSON.stringify(payload);
                }
            }
        }

        function requestStatus(status, label) {
            // Statuses that need a document open their upload modal instead.
            if (entity === 'request' && status === 'proposal_sent') { return openUpload('proposal'); }
            if (entity === 'quotation' && status === 'waiting_customer') { return openUpload('quotation'); }
            if (entity === 'sell' && status === 'invoice_requested') { return openInvoice(); }

            GTI.ui.confirm({
                title: 'Ubah status',
                name: current ? current.ref : '',
                code: label,
                warning: 'Pelanggan akan menerima email pemberitahuan.',
                confirmLabel: 'Ubah Status'
            }).then(function (ok) {
                if (!ok) return;
                return GTI.api.post('gti_change_status', {
                    entity_type: entity, id: drawer.dataset.id, status: status
                }).then(applyResponse);
            }).catch(function () {});
        }

        // ── Upload modal ─────────────────────────────────────────────────────
        function openUpload(mode) {
            var overlay = document.getElementById('gtiUploadOverlay');
            if (!overlay) return;

            var isQuotation = mode === 'quotation';
            document.getElementById('gti-upload-title').textContent =
                isQuotation ? 'Create Quotation' : 'Send Proposal';
            document.getElementById('gti-upload-submit-label').textContent =
                isQuotation ? 'Kirim Quotation' : 'Kirim Proposal';
            document.getElementById('gti-upload-quotation-fields').hidden = !isQuotation;
            document.getElementById('gti-upload-entity-id').value = drawer.dataset.id;
            document.getElementById('gti-upload-entity-type').value = entity;
            overlay.dataset.mode = mode;

            // Required only in the mode that uses them, so the browser does not
            // block submit on hidden fields.
            ['gti-upload-number', 'gti-upload-total', 'gti-upload-valid'].forEach(function (id) {
                var el = document.getElementById(id);
                if (el) el.required = isQuotation;
            });

            if (isQuotation && current) {
                var number = document.getElementById('gti-upload-number');
                if (number && !number.value) number.value = current.ref || '';
            }

            resetUpload();
            GTI.ui.modal.open('gtiUploadOverlay');
        }

        function resetUpload() {
            var input = document.getElementById('gti-upload-file');
            if (input) input.value = '';
            document.getElementById('gti-upload-preview').hidden = true;
            document.getElementById('gti-upload-empty').hidden = false;
            document.getElementById('gti-upload-error').hidden = true;
            document.getElementById('gti-upload-submit').disabled = true;
        }

        function initUpload() {
            var input = document.getElementById('gti-upload-file');
            var form = document.getElementById('gti-upload-form');
            var zone = document.getElementById('gti-upload-dropzone');
            if (!input || !form) return;

            var maxBytes = (window.gtiAjax && window.gtiAjax.docMax) || 10 * 1024 * 1024;
            var ALLOWED = /\.(pdf|docx?|xlsx?|jpe?g|png)$/i;

            function showError(message) {
                var el = document.getElementById('gti-upload-error');
                el.textContent = message;
                el.hidden = false;
                document.getElementById('gti-upload-submit').disabled = true;
            }

            input.addEventListener('change', function () {
                var file = this.files && this.files[0];
                if (!file) { resetUpload(); return; }

                if (!ALLOWED.test(file.name)) {
                    resetUpload();
                    showError('Tipe berkas tidak diizinkan. Gunakan PDF, DOC, DOCX, XLS, XLSX, JPG, atau PNG.');
                    return;
                }
                if (file.size > maxBytes) {
                    resetUpload();
                    showError('Ukuran berkas melebihi ' + GTI.fmt.bytes(maxBytes) + '.');
                    return;
                }

                document.getElementById('gti-upload-filename').textContent = file.name;
                document.getElementById('gti-upload-filesize').textContent = GTI.fmt.bytes(file.size);
                document.getElementById('gti-upload-preview').hidden = false;
                document.getElementById('gti-upload-empty').hidden = true;
                document.getElementById('gti-upload-error').hidden = true;
                document.getElementById('gti-upload-submit').disabled = false;
            });

            var remove = document.getElementById('gti-upload-remove');
            if (remove) {
                remove.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    resetUpload();
                });
            }

            if (zone) {
                ['dragenter', 'dragover'].forEach(function (type) {
                    zone.addEventListener(type, function (e) { e.preventDefault(); zone.classList.add('dragover'); });
                });
                ['dragleave', 'drop'].forEach(function (type) {
                    zone.addEventListener(type, function (e) { e.preventDefault(); zone.classList.remove('dragover'); });
                });
                zone.addEventListener('drop', function (e) {
                    if (e.dataTransfer && e.dataTransfer.files.length) {
                        input.files = e.dataTransfer.files;
                        input.dispatchEvent(new Event('change'));
                    }
                });
            }

            form.addEventListener('submit', function (e) {
                e.preventDefault();

                var overlay = document.getElementById('gtiUploadOverlay');
                var action = overlay.dataset.mode === 'quotation' ? 'gti_create_quotation' : 'gti_send_proposal';
                var submit = document.getElementById('gti-upload-submit');
                var unlock = GTI.ui.lockButton(submit, 'Mengirim…');

                GTI.api.post(action, new FormData(form))
                    .then(function (data) {
                        GTI.ui.modal.close('gtiUploadOverlay');
                        form.reset();
                        resetUpload();
                        applyResponse(data);
                    })
                    .catch(function () {})
                    .then(unlock);
            });
        }

        // ── Email modal ──────────────────────────────────────────────────────
        function openEmail(row) {
            row = row || current;
            if (!row) return;

            var to = document.getElementById('gti-email-to');
            var banner = document.getElementById('gti-email-no-recipient');
            var submit = document.getElementById('gti-email-submit');

            to.value = row.customer_email || '';
            banner.hidden = !!row.customer_email;
            submit.disabled = !row.customer_email;

            document.getElementById('gti-email-subject').value =
                'Re: ' + (row.ref || '') + (row.customer_company ? ' — ' + row.customer_company : '');
            document.getElementById('gti-email-message').value = '';
            document.getElementById('gti-email-cc').value = '';
            document.getElementById('gti-email-files').value = '';
            document.getElementById('gti-email-file-list').innerHTML = '';
            document.getElementById('gti-email-entity-type').value = entity;
            document.getElementById('gti-email-entity-id').value = row.id;

            GTI.ui.modal.open('gtiEmailOverlay');
        }

        function initEmail() {
            var form = document.getElementById('gti-email-form');
            if (!form) return;

            var files = document.getElementById('gti-email-files');
            files.addEventListener('change', function () {
                var list = document.getElementById('gti-email-file-list');
                list.innerHTML = Array.prototype.map.call(this.files, function (file) {
                    return '<li><i class="fas fa-paperclip"></i> ' + GTI.fmt.escape(file.name) +
                           ' <span>(' + GTI.fmt.bytes(file.size) + ')</span></li>';
                }).join('');
            });

            form.addEventListener('submit', function (e) {
                e.preventDefault();

                var submit = document.getElementById('gti-email-submit');
                var unlock = GTI.ui.lockButton(submit, 'Mengirim…');

                GTI.api.post('gti_send_customer_email', new FormData(form))
                    .then(function (data) {
                        GTI.ui.modal.close('gtiEmailOverlay');
                        form.reset();
                        GTI.ui.toast(data.message, 'success');
                        refreshPanel('emails', 'gti_get_email_history');
                    })
                    .catch(function () {})
                    .then(unlock);
            });
        }

        // ── Assign modal ─────────────────────────────────────────────────────
        function openAssign(row) {
            row = row || current;
            if (!row) return;

            document.getElementById('gti-assign-id').value = row.id;
            document.getElementById('gti-assign-entity-type').value = entity;

            var select = document.getElementById('gti-assign-user');
            if (select) select.value = String(row.assigned_to || 0);

            GTI.ui.modal.open('gtiAssignOverlay');
        }

        function initAssign() {
            var form = document.getElementById('gti-assign-form');
            if (!form) return;

            form.addEventListener('submit', function (e) {
                e.preventDefault();

                var submit = form.querySelector('button[type="submit"]');
                var unlock = GTI.ui.lockButton(submit, 'Menyimpan…');
                var id = document.getElementById('gti-assign-id').value;

                GTI.api.post('gti_assign_entity', new FormData(form))
                    .then(function (data) {
                        GTI.ui.modal.close('gtiAssignOverlay');
                        GTI.ui.toast(data.message, 'success');

                        var row = document.querySelector('tr[data-id="' + id + '"]');
                        if (row && data.row_removed) {
                            // Handed to someone else and out of this user's scope.
                            row.remove();
                            GTI.ui.drawer.close();
                        } else if (row) {
                            var cell = row.querySelector('.col-pic');
                            if (cell) cell.textContent = data.sales_pic || 'Unassigned';
                            var payload = readRow(row);
                            if (payload) {
                                payload.sales_pic = data.sales_pic;
                                payload.assigned_to = data.assigned_to;
                                row.dataset.row = JSON.stringify(payload);
                            }
                        }
                        if (data.timeline) renderTimeline(data.timeline);
                    })
                    .catch(function () {})
                    .then(unlock);
            });
        }

        // ── Invoice modal (Sell Equipment only) ──────────────────────────────
        function openInvoice() {
            var overlay = document.getElementById('gtiInvoiceOverlay');
            if (!overlay || !current) return;

            document.getElementById('gti-invoice-id').value = current.id;
            document.getElementById('gti-invoice-unit').textContent = current.unit || '—';
            document.getElementById('gti-invoice-price').textContent = current.price_text || '—';
            document.getElementById('gti-invoice-email').textContent = current.customer_email || '—';

            GTI.ui.modal.open('gtiInvoiceOverlay');
        }

        function initInvoice() {
            var form = document.getElementById('gti-invoice-form');
            if (!form) return;

            form.addEventListener('submit', function (e) {
                e.preventDefault();

                var submit = form.querySelector('button[type="submit"]');
                var unlock = GTI.ui.lockButton(submit, 'Mengirim…');

                GTI.api.post('gti_request_invoice', new FormData(form))
                    .then(function (data) {
                        GTI.ui.modal.close('gtiInvoiceOverlay');
                        applyResponse(data);
                    })
                    .catch(function () {})
                    .then(unlock);
            });
        }

        // ── Rows ─────────────────────────────────────────────────────────────
        function readRow(tr) {
            try { return JSON.parse(tr.dataset.row); } catch (e) { return null; }
        }

        function selectRow(tr, open) {
            var payload = readRow(tr);
            if (!payload) return;

            document.querySelectorAll('.gti-ue-table tbody tr.active-row')
                .forEach(function (r) { r.classList.remove('active-row'); });
            tr.classList.add('active-row');

            paint(payload);
            if (open) GTI.ui.drawer.open('detailDrawer');
        }

        function deleteRow(tr) {
            var payload = readRow(tr);
            if (!payload || !config.deleteAction) return;

            GTI.ui.confirm({
                title: config.deleteLabel || 'Delete',
                name: payload.ref,
                code: payload.customer_name,
                warning: 'Data ini akan dihapus permanen dan tidak dapat dipulihkan.',
                confirmLabel: 'Delete'
            }).then(function (ok) {
                if (!ok) return;
                return GTI.api.post(config.deleteAction, { id: payload.id }).then(function (data) {
                    tr.remove();
                    GTI.ui.drawer.close();
                    GTI.ui.toast((data && data.message) || 'Data dihapus.', 'success');
                });
            }).catch(function () {});
        }

        function initRows() {
            document.querySelectorAll('.gti-ue-table tbody tr[data-row]').forEach(function (tr) {
                tr.addEventListener('click', function (e) {
                    var action = e.target.closest('.js-view, .js-assign, .js-reply, .js-delete');
                    if (action) {
                        e.stopPropagation();
                        document.querySelectorAll('.gti-ue-action-dropdown.show')
                            .forEach(function (d) { d.classList.remove('show'); });

                        if (action.classList.contains('js-delete')) { deleteRow(tr); return; }

                        selectRow(tr, true);
                        if (action.classList.contains('js-assign')) openAssign();
                        if (action.classList.contains('js-reply')) openEmail();
                        return;
                    }
                    if (e.target.closest('.gti-ue-action-menu')) return;
                    selectRow(tr, true);
                });
            });

            // Populate the drawer from the first row without opening it.
            var first = document.querySelector('.gti-ue-table tbody tr[data-row]');
            if (first) {
                var payload = readRow(first);
                if (payload) { current = payload; setStatus(payload); }
            }
        }

        // ── Drawer footer ────────────────────────────────────────────────────
        function initDrawer() {
            drawer.addEventListener('click', function (e) {
                var trigger = e.target.closest('[data-action]');
                if (!trigger) return;

                var action = trigger.dataset.action;

                if (action === 'status-menu' || action === 'more') {
                    e.stopPropagation();
                    var name = action === 'more' ? 'more-menu' : 'status-menu';
                    var menu = field(name);
                    var wasOpen = menu.classList.contains('show');
                    drawer.querySelectorAll('.gti-drawer-dropdown.show')
                        .forEach(function (m) { m.classList.remove('show'); });
                    if (!wasOpen) menu.classList.add('show');
                    return;
                }

                if (action === 'reply') openEmail();
                if (action === 'assign') openAssign();
                if (action === 'invoice') openInvoice();
                if (action === 'delete') {
                    var tr = document.querySelector('tr[data-id="' + drawer.dataset.id + '"]');
                    if (tr) deleteRow(tr);
                }
            });

            var close = drawer.querySelector('[data-gti-drawer-close]');
            if (close) close.addEventListener('click', function () { GTI.ui.drawer.close(); });

            document.addEventListener('click', function (e) {
                if (!e.target.closest('.gti-drawer-btn-group')) {
                    drawer.querySelectorAll('.gti-drawer-dropdown.show')
                        .forEach(function (m) { m.classList.remove('show'); });
                }
            });
        }

        initRows();
        initDrawer();
        initUpload();
        initEmail();
        initAssign();
        initInvoice();

        return { paint: paint, openEmail: openEmail, openAssign: openAssign, openUpload: openUpload };
    };
})(window, document);
