/**
 * Add Spare Part page script.
 *
 * Lifted out of the template's inline <script> during the R1/R3 refactor.
 * Shared behaviour — toast, drawer, modal, action dropdown, sidebar collapse,
 * date/currency formatting and the fetch wrapper — comes from
 * assets/js/dashboard-ui.js; gtiAjax comes from wp_localize_script().
 */

document.addEventListener('DOMContentLoaded', function() {
        // Collapse Menu
        var collapseBtn = document.getElementById('gti-collapse-btn');
        var sidebar = document.getElementById('gti-sidebar');
        var mainEl = document.querySelector('.gti-main');


        // Auto-generate spare part code on category change
        var spCatMap = (window.gtiPageData && gtiPageData.categoryMap) || {};
        var spCatSelect = document.querySelector('select[name="category"]');
        var spCodeInput = document.querySelector('input[name="part_number"]');
        if (spCatSelect && spCodeInput) {
            function generateSpCode(catVal) {
                if (!catVal) return;
                var abbr = spCatMap[catVal] || 'GEN';
                var year = new Date().getFullYear();
                var fd = new FormData();
                fd.append('action', 'gti_get_next_spare_part_code');
                fd.append('nonce', gtiAjax.nonce);
                fd.append('category', catVal);
                fetch(gtiAjax.ajaxurl, { method: 'POST', body: fd })
                    .then(function(r) { return r.json(); })
                    .then(function(d) {
                        if (d.success && d.data && d.data.code) {
                            spCodeInput.value = d.data.code;
                        } else {
                            var ts = Date.now().toString().slice(-4);
                            spCodeInput.value = 'SP-' + abbr + '-' + year + '-' + ts;
                        }
                    })
                    .catch(function() {
                        var ts = Date.now().toString().slice(-4);
                        spCodeInput.value = 'SP-' + abbr + '-' + year + '-' + ts;
                    });
            }
            spCatSelect.addEventListener('change', function() {
                generateSpCode(this.value);
            });
        }

        // Stock status mirrors what the server will derive on save
        var spStockInput = document.querySelector('input[name="stock"]');
        var spMinInput = document.querySelector('input[name="minimum_stock"]');
        var spStatusDisplay = document.getElementById('gti-sp-status-display');
        function refreshSpStatus() {
            if (!spStatusDisplay) return;
            var stock = parseInt(spStockInput && spStockInput.value, 10) || 0;
            var min = parseInt(spMinInput && spMinInput.value, 10);
            if (isNaN(min)) min = 10;
            spStatusDisplay.value = stock <= 0 ? 'Out of Stock' : (stock <= min ? 'Low Stock' : 'In Stock');
        }
        if (spStockInput) spStockInput.addEventListener('input', refreshSpStatus);
        if (spMinInput) spMinInput.addEventListener('input', refreshSpStatus);
        refreshSpStatus();

        // Image Upload
        var uploadArea = document.getElementById('gti-ae-upload-area');
        var fileInput = document.getElementById('gti-ae-file-input');
        var preview = document.getElementById('gti-ae-image-preview');
        var previewImg = document.getElementById('gti-ae-preview-img');
        var removeBtn = document.getElementById('gti-ae-remove-img');

        if (uploadArea && fileInput) {
            uploadArea.addEventListener('click', function() {
                fileInput.click();
            });

            uploadArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                uploadArea.style.borderColor = 'var(--gti-primary)';
                uploadArea.style.background = '#fffbeb';
            });

            uploadArea.addEventListener('dragleave', function() {
                uploadArea.style.borderColor = '#d1d5db';
                uploadArea.style.background = '';
            });

            uploadArea.addEventListener('drop', function(e) {
                e.preventDefault();
                uploadArea.style.borderColor = '#d1d5db';
                uploadArea.style.background = '';
                if (e.dataTransfer.files.length) {
                    fileInput.files = e.dataTransfer.files;
                    showPreview(e.dataTransfer.files[0]);
                }
            });

            fileInput.addEventListener('change', function() {
                if (this.files.length) {
                    showPreview(this.files[0]);
                }
            });
        }

        function showPreview(file) {
            if (file && file.type.startsWith('image/')) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    preview.style.display = 'flex';
                    uploadArea.style.display = 'none';
                };
                reader.readAsDataURL(file);
            }
        }

        if (removeBtn) {
            removeBtn.addEventListener('click', function() {
                fileInput.value = '';
                preview.style.display = 'none';
                uploadArea.style.display = '';
            });
        }

        // === AJAX Form Submission ===
        var spForm = document.getElementById('gti-sp-form');
        var spSubmitBtn = document.getElementById('gti-sp-submit');
        var spDraftBtn = document.getElementById('gti-sp-draft');

        function showSpToast(message, type) {
            var toast = document.getElementById('gti-sp-toast');
            if (!toast) {
                toast = document.createElement('div');
                toast.id = 'gti-sp-toast';
                toast.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:5000;padding:14px 20px;border-radius:10px;font-size:13px;font-weight:500;box-shadow:0 8px 24px rgba(0,0,0,0.15);display:flex;align-items:center;gap:10px;transform:translateY(120%);opacity:0;transition:all 0.3s cubic-bezier(0.4,0,0.2,1);';
                document.body.appendChild(toast);
            }
            var bgColor = type === 'error' ? '#991b1b' : type === 'warning' ? '#92400e' : '#059669';
            var icon = type === 'error' ? 'fa-exclamation-circle' : type === 'warning' ? 'fa-exclamation-triangle' : 'fa-check-circle';
            toast.style.background = bgColor;
            toast.style.color = '#fff';
            toast.innerHTML = '<i class="fas ' + icon + '"></i> ' + message;
            toast.classList.add('show');
            toast.style.transform = 'translateY(0)';
            toast.style.opacity = '1';
            setTimeout(function() {
                toast.style.transform = 'translateY(120%)';
                toast.style.opacity = '0';
            }, 3500);
        }

        function handleSpSubmit(isDraft) {
            if (!spForm) return;
            var btn = isDraft ? spDraftBtn : spSubmitBtn;

            // Client-side validation for required fields
            var requiredFields = spForm.querySelectorAll('[required]');
            var firstInvalid = null;
            requiredFields.forEach(function(field) {
                field.style.borderColor = '';
                if (!field.value || field.value.trim() === '') {
                    field.style.borderColor = '#ef4444';
                    if (!firstInvalid) firstInvalid = field;
                }
            });
            if (firstInvalid) {
                firstInvalid.focus();
                showSpToast('Please fill in all required fields', 'error');
                return;
            }

            var origHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

            var formData = new FormData(spForm);
            formData.append('action', 'gti_save_spare_part');
            formData.append('nonce', gtiAjax.nonce);
            if (isDraft) {
                // Override status directly to ensure draft status is saved
                formData.set('status', 'draft');
            }

            fetch(gtiAjax.ajaxurl, { method: 'POST', body: formData })
                .then(function(r) {
                    return r.text().then(function(text) {
                        try { return JSON.parse(text); }
                        catch(e) { console.error('Spare part save: non-JSON', text); throw new Error('Server returned non-JSON'); }
                    });
                })
                .then(function(data) {
                    btn.disabled = false;
                    btn.innerHTML = origHtml;
                    if (data.success) {
                        showSpToast(data.data.message || 'Spare part saved successfully!', 'success');
                        setTimeout(function() {
                            window.location.href = data.data.redirect || (gtiAjax.dashboard + '/spare-parts');
                        }, 1200);
                    } else {
                        showSpToast(data.data.message || 'Failed to save spare part', 'error');
                    }
                })
                .catch(function(err) {
                    btn.disabled = false;
                    btn.innerHTML = origHtml;
                    showSpToast('An error occurred: ' + (err.message || 'Unknown error'), 'error');
                });
        }

        if (spSubmitBtn) spSubmitBtn.addEventListener('click', function() { handleSpSubmit(false); });
        if (spDraftBtn) spDraftBtn.addEventListener('click', function() { handleSpSubmit(true); });
    });
