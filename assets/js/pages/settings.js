/**
 * Settings page: rename equipment form fields, CRUD their dropdown options.
 * The server re-renders the page, so every save just reloads it.
 */
(function () {
    'use strict';

    function q(id) { return document.getElementById(id); }
    function form(id) { return q(id); }

    function save(action, data, overlay) {
        return GTI.api.post(action, data).then(function (res) {
            GTI.ui.toast((res && res.message) || 'Tersimpan.', 'success');
            if (overlay) GTI.ui.modal.close(overlay);
            setTimeout(function () { window.location.reload(); }, 600);
        });
    }

    function submitter(id, action, overlay) {
        form(id).addEventListener('submit', function (e) {
            e.preventDefault();
            var f = e.target;
            var unlock = GTI.ui.lockButton(f.querySelector('[type=submit]'), 'Menyimpan…');
            var data = {};
            new FormData(f).forEach(function (v, k) { data[k] = v; });
            save(action, data, overlay).catch(unlock);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var fieldForm = form('gti-field-form');
        var optForm = form('gti-option-form');

        submitter('gti-field-form', 'gti_settings_save_label', 'gtiFieldOverlay');
        submitter('gti-option-form', 'gti_settings_save_option', 'gtiOptionOverlay');

        function labelFor(scope, d) { return scope === 'rental' ? d.rental : d.used; }

        document.addEventListener('click', function (e) {
            var rename = e.target.closest('[data-st-rename]');
            var add = e.target.closest('[data-st-add]');
            var edit = e.target.closest('[data-st-edit]');
            var del = e.target.closest('[data-st-delete]');

            if (rename) {
                var d = rename.dataset;
                fieldForm.field.value = d.field;
                // Same name for both types → default to the linked scope.
                fieldForm.scope.value = d.used === d.rental ? 'both' : 'used';
                fieldForm.label.value = d.used;
                fieldForm.scope.onchange = function () { fieldForm.label.value = labelFor(fieldForm.scope.value === 'rental' ? 'rental' : 'used', d); };
                GTI.ui.modal.open('gtiFieldOverlay');
            } else if (add || edit) {
                var o = (add || edit).dataset;
                optForm.field.value = o.field;
                optForm.id.value = edit ? o.id : '';
                optForm.label.value = edit ? o.label : '';
                optForm.scope.value = edit ? o.scope : 'both';
                optForm.abbr.value = edit ? (o.abbr || '') : '';
                q('gti-option-abbr-wrap').hidden = !o.abbrOn;
                q('gti-option-title').textContent = edit ? 'Edit Option' : 'Add Option';
                q('gti-option-sub').textContent = edit ? 'Mengubah nama juga memperbarui unit yang memakainya (untuk field teks).' : '';
                GTI.ui.modal.open('gtiOptionOverlay');
            } else if (del) {
                var n = parseInt(del.dataset.count, 10) || 0;
                GTI.ui.confirm({
                    title: 'Delete Option',
                    subtitle: 'Hapus dari dropdown',
                    name: del.dataset.label,
                    code: n + ' unit memakai pilihan ini',
                    warning: 'Unit yang sudah memakai pilihan ini tidak berubah dan tetap tampil di form edit.'
                }).then(function (ok) {
                    if (ok) save('gti_settings_delete_option', { field: del.dataset.field, id: del.dataset.id });
                });
            }
        });
    });
})();
