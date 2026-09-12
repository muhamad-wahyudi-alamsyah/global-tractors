/**
 * Shared dashboard UI behaviour (PRD §13.8 / R3).
 *
 * Replaces the copies of showUeToast(), closeDetailDrawer(), formatDateID(),
 * the action-dropdown positioning and the sidebar-collapse logic that were
 * pasted into 6-16 templates each. Page scripts in assets/js/pages/ now hold
 * only what is genuinely specific to one page.
 *
 * Everything here is vanilla — no framework, per PRD §13.4.
 */
(function (window, document) {
    'use strict';

    var GTI = window.GTI = window.GTI || {};
    var cfg = window.gtiAjax || {};

    // ── Formatting ──────────────────────────────────────────────────────────
    GTI.fmt = {
        /** "2026-09-12" → "12 Sep 2026". Returns '-' for empty/invalid input. */
        dateID: function (value) {
            if (!value || value === '0000-00-00' || value === '0000-00-00 00:00:00') return '-';
            var d = new Date(String(value).replace(' ', 'T'));
            if (isNaN(d.getTime())) return String(value);
            var months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            return d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
        },

        dateTimeID: function (value) {
            if (!value) return '-';
            var d = new Date(String(value).replace(' ', 'T'));
            if (isNaN(d.getTime())) return String(value);
            var pad = function (n) { return n < 10 ? '0' + n : String(n); };
            return GTI.fmt.dateID(value) + ', ' + pad(d.getHours()) + ':' + pad(d.getMinutes());
        },

        currencyIDR: function (value) {
            var n = parseFloat(value);
            if (!n || isNaN(n)) return '-';
            return 'Rp ' + n.toLocaleString('id-ID', { maximumFractionDigits: 0 });
        },

        number: function (value) {
            var n = parseFloat(value);
            return isNaN(n) ? '-' : n.toLocaleString('id-ID');
        },

        bytes: function (bytes) {
            bytes = parseInt(bytes, 10);
            if (!bytes || bytes <= 0) return '-';
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / 1048576).toFixed(1) + ' MB';
        },

        escape: function (value) {
            var div = document.createElement('div');
            div.textContent = value == null ? '' : String(value);
            return div.innerHTML;
        }
    };

    // ── Toast ───────────────────────────────────────────────────────────────
    var TOAST_ICONS = {
        success: 'fa-check-circle',
        error: 'fa-exclamation-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };

    GTI.ui = {
        /**
         * @param {string} message
         * @param {string} [type] success|error|warning|info
         */
        toast: function (message, type) {
            type = type || 'success';

            var host = document.getElementById('gti-toast-container');
            if (!host) {
                host = document.createElement('div');
                host.id = 'gti-toast-container';
                host.className = 'gti-toast-container';
                document.body.appendChild(host);
            }

            var el = document.createElement('div');
            el.className = 'gti-ue-toast gti-toast ' + type;
            el.setAttribute('role', type === 'error' ? 'alert' : 'status');
            el.innerHTML = '<i class="fas ' + (TOAST_ICONS[type] || TOAST_ICONS.info) + '"></i> ' +
                           '<span>' + GTI.fmt.escape(message) + '</span>';
            host.appendChild(el);

            // Next frame, so the transition actually runs.
            requestAnimationFrame(function () { el.classList.add('show'); });

            setTimeout(function () {
                el.classList.remove('show');
                setTimeout(function () { el.remove(); }, 300);
            }, type === 'error' ? 6000 : 3500);
        },

        // ── Modal ───────────────────────────────────────────────────────────
        modal: {
            open: function (id) {
                var el = document.getElementById(id);
                if (!el) return;
                el.classList.add('show');
                document.body.classList.add('gti-modal-open');
                var focusable = el.querySelector('input:not([type=hidden]), textarea, select, button');
                if (focusable) { try { focusable.focus(); } catch (e) {} }
            },
            close: function (id) {
                var el = document.getElementById(id);
                if (!el) return;
                el.classList.remove('show');
                if (!document.querySelector('.gti-modal-overlay.show, .gti-ue-delete-overlay.show')) {
                    document.body.classList.remove('gti-modal-open');
                }
            },
            closeAll: function () {
                document.querySelectorAll('.gti-modal-overlay.show, .gti-ue-delete-overlay.show')
                    .forEach(function (el) { el.classList.remove('show'); });
                document.body.classList.remove('gti-modal-open');
            },
            isOpen: function () {
                return !!document.querySelector('.gti-modal-overlay.show, .gti-ue-delete-overlay.show');
            }
        },

        // ── Drawer ──────────────────────────────────────────────────────────
        drawer: {
            open: function (id) {
                var el = document.getElementById(id || 'detailDrawer');
                if (!el) return;
                el.classList.add('open');
                var backdrop = document.getElementById('gti-drawer-backdrop') || document.getElementById('drawerBackdrop');
                if (backdrop) backdrop.classList.add('show');
            },
            close: function (id) {
                var el = document.getElementById(id || 'detailDrawer');
                if (el) el.classList.remove('open');
                var backdrop = document.getElementById('gti-drawer-backdrop') || document.getElementById('drawerBackdrop');
                if (backdrop) backdrop.classList.remove('show');
                document.querySelectorAll('.gti-ue-table tbody tr.active-row')
                    .forEach(function (r) { r.classList.remove('active-row'); });
            },
            isOpen: function (id) {
                var el = document.getElementById(id || 'detailDrawer');
                return !!(el && el.classList.contains('open'));
            }
        },

        /**
         * Promise-based confirmation, replacing native confirm() (PRD Lampiran A).
         *
         * @param {{title, name, code, warning, confirmLabel}} opts
         * @returns {Promise<boolean>}
         */
        confirm: function (opts) {
            opts = opts || {};

            return new Promise(function (resolve) {
                var overlay = document.getElementById('gtiDeleteOverlay');
                if (!overlay) { resolve(false); return; }

                var set = function (id, text) {
                    var el = document.getElementById(id);
                    if (el && text != null) el.textContent = text;
                };
                set('gti-delete-title', opts.title || 'Delete Item');
                set('delete-eq-name', opts.name || '—');
                set('delete-eq-code', opts.code || '—');
                set('gti-delete-warning', opts.warning ||
                    'This record will be permanently removed and cannot be recovered.');

                var btn = document.getElementById('gti-delete-confirm-btn');
                if (opts.confirmLabel && btn) {
                    btn.innerHTML = '<i class="fas fa-trash"></i> ' + GTI.fmt.escape(opts.confirmLabel);
                }

                // Replace the node to drop listeners from any earlier call.
                var fresh = btn.cloneNode(true);
                btn.parentNode.replaceChild(fresh, btn);

                var done = function (result) {
                    GTI.ui.modal.close('gtiDeleteOverlay');
                    overlay.removeEventListener('click', onBackdrop);
                    resolve(result);
                };
                var onBackdrop = function (e) { if (e.target === overlay) done(false); };

                fresh.addEventListener('click', function () { done(true); });
                overlay.addEventListener('click', onBackdrop);
                overlay.querySelectorAll('[data-gti-close]').forEach(function (el) {
                    el.addEventListener('click', function () { done(false); });
                });

                GTI.ui.modal.open('gtiDeleteOverlay');
            });
        },

        /** Disable a button and show a spinner while an async action runs. */
        lockButton: function (btn, label) {
            if (!btn) return function () {};
            var original = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + GTI.fmt.escape(label || 'Working…');
            return function () { btn.disabled = false; btn.innerHTML = original; };
        }
    };

    // ── AJAX wrapper ────────────────────────────────────────────────────────
    GTI.api = {
        /**
         * POST to admin-ajax with the nonce attached.
         *
         * Non-JSON responses are the important case: a stray PHP warning used to
         * make r.json() throw and the UI would sit there silently. Here that
         * surfaces as a real error message.
         *
         * @param {string} action
         * @param {Object|FormData} data
         * @param {{toastOnError?: boolean}} [opts]
         * @returns {Promise<Object>} resolves with response.data
         */
        post: function (action, data, opts) {
            opts = opts || {};

            var body;
            if (data instanceof FormData) {
                body = data;
            } else {
                body = new FormData();
                Object.keys(data || {}).forEach(function (k) {
                    var v = data[k];
                    if (Array.isArray(v)) {
                        v.forEach(function (item) { body.append(k + '[]', item); });
                    } else if (v != null) {
                        body.append(k, v);
                    }
                });
            }
            body.append('action', action);
            if (!body.has('nonce')) body.append('nonce', cfg.nonce || '');

            return fetch(cfg.ajaxurl, { method: 'POST', body: body, credentials: 'same-origin' })
                .then(function (response) {
                    return response.text().then(function (text) {
                        var payload;
                        try {
                            payload = JSON.parse(text);
                        } catch (e) {
                            throw new Error(
                                'Server mengirim respons yang tidak dikenali' +
                                (response.ok ? '' : ' (HTTP ' + response.status + ')') + '.'
                            );
                        }
                        if (!payload || payload.success !== true) {
                            var message = (payload && payload.data && payload.data.message) || 'Permintaan gagal.';
                            var err = new Error(message);
                            err.code = payload && payload.data && payload.data.code;
                            err.data = payload && payload.data;
                            throw err;
                        }
                        return payload.data || {};
                    });
                })
                .catch(function (err) {
                    if (opts.toastOnError !== false) GTI.ui.toast(err.message, 'error');
                    throw err;
                });
        }
    };

    // ── Global bindings ─────────────────────────────────────────────────────
    function initSidebar() {
        var sidebar = document.getElementById('gti-sidebar');
        var main = document.querySelector('.gti-main');
        var collapseBtn = document.getElementById('gti-collapse-btn');

        if (sidebar && safeGet('gti-sidebar-collapsed') === 'true') {
            sidebar.classList.add('collapsed');
            if (main) main.classList.add('collapsed');
        }

        if (collapseBtn && sidebar) {
            collapseBtn.addEventListener('click', function (e) {
                e.preventDefault();
                sidebar.classList.toggle('collapsed');
                if (main) main.classList.toggle('collapsed');
                safeSet('gti-sidebar-collapsed', sidebar.classList.contains('collapsed'));
            });
        }

        var menuToggle = document.getElementById('gti-menu-toggle');
        if (menuToggle && sidebar) {
            menuToggle.addEventListener('click', function (e) {
                e.preventDefault();
                sidebar.classList.toggle('mobile-open');
            });
        }

        document.querySelectorAll('[data-toggle="dropdown"]').forEach(function (toggle) {
            toggle.addEventListener('click', function (e) {
                e.preventDefault();
                var group = this.closest('.gti-has-children');
                if (group) group.classList.toggle('open');
            });
        });
    }

    // localStorage throws in some privacy modes; never let that break the page.
    function safeGet(key) { try { return localStorage.getItem(key); } catch (e) { return null; } }
    function safeSet(key, value) { try { localStorage.setItem(key, value); } catch (e) {} }
    GTI.storage = { get: safeGet, set: safeSet };

    /** Row action menus: open one at a time, positioned inside the viewport. */
    function initActionMenus() {
        document.addEventListener('click', function (e) {
            var toggle = e.target.closest('.gti-ue-action-toggle');

            if (toggle) {
                e.preventDefault();
                e.stopPropagation();

                var dropdown = toggle.closest('.gti-ue-action-menu').querySelector('.gti-ue-action-dropdown');
                var wasOpen = dropdown.classList.contains('show');

                document.querySelectorAll('.gti-ue-action-dropdown.show')
                    .forEach(function (d) { d.classList.remove('show'); });
                if (wasOpen) return;

                var rect = toggle.getBoundingClientRect();
                dropdown.classList.add('show');

                // Measure after it is visible, then flip up if it would overflow.
                var height = dropdown.offsetHeight || 110;
                var width = dropdown.offsetWidth || 160;
                var top = rect.bottom + 4;
                var left = rect.right - width;

                if (top + height > window.innerHeight) top = Math.max(8, rect.top - height - 4);
                if (left < 8) left = 8;

                dropdown.style.top = top + 'px';
                dropdown.style.left = left + 'px';
                return;
            }

            if (!e.target.closest('.gti-ue-action-menu')) {
                document.querySelectorAll('.gti-ue-action-dropdown.show')
                    .forEach(function (d) { d.classList.remove('show'); });
            }
        });
    }

    /** Header user menu, including the only Log Out control on the dashboard. */
    function initUserMenu() {
        var toggle = document.getElementById('gti-user-menu-toggle');
        var menu = document.getElementById('gti-user-dropdown');

        if (toggle && menu) {
            toggle.addEventListener('click', function (e) {
                e.stopPropagation();
                var open = menu.classList.toggle('show');
                toggle.setAttribute('aria-expanded', String(open));
            });
            document.addEventListener('click', function (e) {
                if (!e.target.closest('.gti-user-menu-wrap')) {
                    menu.classList.remove('show');
                    toggle.setAttribute('aria-expanded', 'false');
                }
            });
        }

        var logout = document.getElementById('gti-logout-btn');
        if (logout) {
            logout.addEventListener('click', function (e) {
                e.preventDefault();
                var unlock = GTI.ui.lockButton(logout, 'Keluar…');

                GTI.api.post('gti_logout', {})
                    .then(function (data) {
                        window.location.href = (data && data.redirect) || '/';
                    })
                    .catch(function () { unlock(); });
            });
        }
    }

    function initDismissers() {
        // Any [data-gti-close="overlayId"] closes that overlay.
        document.addEventListener('click', function (e) {
            var closer = e.target.closest('[data-gti-close]');
            if (closer) {
                e.preventDefault();
                GTI.ui.modal.close(closer.getAttribute('data-gti-close'));
                return;
            }
            // Click on the overlay itself, not its panel.
            if (e.target.classList && e.target.classList.contains('gti-modal-overlay')) {
                GTI.ui.modal.close(e.target.id);
            }
        });

        var backdrop = document.getElementById('gti-drawer-backdrop') || document.getElementById('drawerBackdrop');
        if (backdrop) {
            backdrop.addEventListener('click', function () { GTI.ui.drawer.close(); });
        }

        // Escape closes the topmost layer first: modal, then drawer.
        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape') return;
            if (GTI.ui.modal.isOpen()) {
                GTI.ui.modal.closeAll();
            } else {
                GTI.ui.drawer.close();
            }
        });
    }

    /** Highlight a row linked from an email (?highlight=123). */
    function initHighlight() {
        var id = new URLSearchParams(window.location.search).get('highlight');
        if (!id) return;

        var row = document.querySelector('tr[data-id="' + CSS.escape(id) + '"]');
        if (!row) return;

        row.classList.add('active-row');
        row.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initSidebar();
        initUserMenu();
        initActionMenus();
        initDismissers();
        initHighlight();
    });

    // Back-compat shims: templates still call these by their old global names.
    window.showUeToast = function (message, type) { GTI.ui.toast(message, type); };
    window.formatDateID = function (value) { return GTI.fmt.dateID(value); };
    window.closeDetailDrawer = function () { GTI.ui.drawer.close(); };
    window.closeDeleteModal = function () { GTI.ui.modal.close('gtiDeleteOverlay'); };
})(window, document);
