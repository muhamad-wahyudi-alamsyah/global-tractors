/**
 * Add Used Equipment page script.
 *
 * Lifted out of the template's inline <script> during the R1/R3 refactor.
 * Shared behaviour — toast, drawer, modal, action dropdown, sidebar collapse,
 * date/currency formatting and the fetch wrapper — comes from
 * assets/js/dashboard-ui.js; gtiAjax comes from wp_localize_script().
 */

// Auto-generate equipment code on category change
        document.addEventListener('DOMContentLoaded', function() {
            var catMap = (window.gtiPageData && gtiPageData.categoryMap) || {};
            var catSelect = document.querySelector('select[name="category"]');
            var codeInput = document.querySelector('input[name="equipment_code"]');
            if (!catSelect || !codeInput) return;

            function generateCode(catVal) {
                if (!catVal) return;
                var abbr = catMap[catVal] || 'GEN';
                var year = new Date().getFullYear();
                var fd = new FormData();
                fd.append('action', 'gti_get_next_code');
                fd.append('nonce', gtiAjax.nonce);
                fd.append('category', catVal);
                fetch(gtiAjax.ajaxurl, { method: 'POST', body: fd })
                    .then(function(r) { return r.json(); })
                    .then(function(d) {
                        if (d.success && d.data && d.data.code) {
                            codeInput.value = d.data.code;
                        } else {
                            var ts = Date.now().toString().slice(-4);
                            codeInput.value = 'GTI-' + abbr + '-' + year + '-' + ts;
                        }
                    })
                    .catch(function(err) {
                        var ts = Date.now().toString().slice(-4);
                        codeInput.value = 'GTI-' + abbr + '-' + year + '-' + ts;
                    });
            }

            catSelect.addEventListener('change', function() {
                generateCode(this.value);
            });
        });
