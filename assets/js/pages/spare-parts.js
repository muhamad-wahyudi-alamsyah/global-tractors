/**
 * Spare Parts page script.
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
        
        // Auto-generate spare part code on category change
        var spCatMap = (window.gtiPageData && gtiPageData.categoryMap) || {};

        // Auto-generate spare part code for Edit Spare Part
        var editSpCatSelect = document.getElementById('edit-field-category');
        var editSpCodeInput = document.getElementById('edit-field-part-number');

        // Simpan category saat pertama kali modal/data dibuka
        var originalCategory = editSpCatSelect ? editSpCatSelect.value : '';

        if (editSpCatSelect && editSpCodeInput) {

            function generateEditSpCode(catVal) {

                if (!catVal) return;

                var abbr = spCatMap[catVal] || 'GEN';
                var year = new Date().getFullYear();

                var fd = new FormData();

                fd.append('action', 'gti_get_next_spare_part_code');
                fd.append('nonce', gtiAjax.nonce);
                fd.append('category', catVal);

                fetch(gtiAjax.ajaxurl, {
                    method: 'POST',
                    body: fd
                })
                .then(function(r) {
                    return r.json();
                })
                .then(function(d) {

                    if (d.success && d.data && d.data.code) {

                        editSpCodeInput.value = d.data.code;

                    } else {

                        var ts = Date.now().toString().slice(-4);

                        editSpCodeInput.value =
                            'SP-' + abbr + '-' + year + '-' + ts;
                    }

                })
                .catch(function() {

                    var ts = Date.now().toString().slice(-4);

                    editSpCodeInput.value =
                        'SP-' + abbr + '-' + year + '-' + ts;
                });
            }

            // Generate hanya jika category benar-benar berubah
            editSpCatSelect.addEventListener('change', function() {

                var newCategory = this.value;

                // Category sama dengan data existing
                if (newCategory === originalCategory) {
                    return;
                }

                // Category berbeda → generate Part Number baru
                generateEditSpCode(newCategory);

            });
        }

        // Action Dropdown Toggle — fixed positioning
        // VIEW BUTTON
        document.querySelectorAll('.gti-ue-btn-view').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault(); e.stopPropagation();
                var row = btn.closest('tr[data-sp]');
                if (!row) return;
                var sp; try { sp = JSON.parse(row.getAttribute('data-sp')); } catch(err) { return; }
                document.querySelectorAll('.gti-ue-action-dropdown.show').forEach(function(d) { d.classList.remove('show'); });
                showSparePartDetail(sp);
            });
        });
        // EDIT BUTTON
        document.querySelectorAll('.gti-ue-btn-edit').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault(); e.stopPropagation();
                var row = btn.closest('tr[data-sp]');
                if (!row) return;
                var sp; try { sp = JSON.parse(row.getAttribute('data-sp')); } catch(err) { return; }
                document.querySelectorAll('.gti-ue-action-dropdown.show').forEach(function(d) { d.classList.remove('show'); });
                openEditModal(sp);
            });
        });
        // DELETE BUTTON
        document.querySelectorAll('.gti-ue-btn-delete').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault(); e.stopPropagation();
                var row = btn.closest('tr[data-sp]');
                if (!row) return;
                var sp; try { sp = JSON.parse(row.getAttribute('data-sp')); } catch(err) { return; }
                document.querySelectorAll('.gti-ue-action-dropdown.show').forEach(function(d) { d.classList.remove('show'); });
                openDeleteModal(sp);
            });
        });
        // Drawer backdrop close
        document.getElementById('drawerBackdrop').addEventListener('click', closeDetailDrawer);
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                if (document.getElementById('gtiEditOverlay').classList.contains('show')) { closeEditModal(); return; }
                if (document.getElementById('gtiDeleteOverlay').classList.contains('show')) { closeDeleteModal(); return; }
                closeDetailDrawer();
            }
        });
        // Search input — real-time search with debounce
        var searchInput = document.querySelector('.gti-ue-search input[name="search"]');
        var searchTimer = null;
        if (searchInput) {
            searchInput.addEventListener('input', function() { var self = this; clearTimeout(searchTimer); searchTimer = setTimeout(function() { self.form.submit(); }, 400); });
            if (searchInput.value) { searchInput.focus(); var len = searchInput.value.length; searchInput.setSelectionRange(len, len); }
        }
        // Drawer Edit button
        document.getElementById('drawer-btn-edit').addEventListener('click', function() {
            if (_currentDrawerSp) openEditModal(_currentDrawerSp);
        });
        // Drawer Publish button
        document.getElementById('drawer-btn-publish').addEventListener('click', function() {
            if (_currentDrawerSp) handlePublishSparePart(_currentDrawerSp);
        });
        // Drawer Delete button
        document.getElementById('drawer-btn-delete').addEventListener('click', function() {
            if (_currentDrawerSp) openDeleteModal(_currentDrawerSp);
        });
        // Row click → drawer
        document.querySelectorAll('.gti-ue-table tbody tr[data-sp]').forEach(function(row) {
            row.addEventListener('click', function(e) {
                if (e.target.closest('.gti-ue-action-menu') || e.target.type === 'checkbox') return;
                var sp; try { sp = JSON.parse(this.getAttribute('data-sp')); } catch(err) { return; }
                showSparePartDetail(sp);
            });
        });
        // Auto-populate drawer with first row
        var firstRow = document.querySelector('.gti-ue-table tbody tr[data-sp]');
        if (firstRow) { try { updateDrawerContent(JSON.parse(firstRow.getAttribute('data-sp'))); } catch(e) {} }
        // Edit modal image upload
        initEditImageUploads();
        // Unit price — keep a thousand separator while typing
        var priceInput = document.querySelector('#gti-edit-form input[name="unit_price"]');
        if (priceInput) {
            priceInput.addEventListener('input', function() {
                var digits = this.value.replace(/\D/g, '');
                this.value = digits ? Number(digits).toLocaleString('id-ID') : '';
            });
        }
        // Edit modal submit
        var editSubmitBtn = document.getElementById('gti-edit-submit');
        if (editSubmitBtn) editSubmitBtn.addEventListener('click', handleEditSubmit);
        // Delete confirm
        document.getElementById('gti-delete-confirm-btn').addEventListener('click', handleDeleteConfirm);
    });

    var _currentDrawerSp = null;

    function setText(id, val) {
        var el = document.getElementById(id);
        if (!el) return;
        el.textContent = (val === 0 || val === '0') ? '0' : (val || '-');
    }
    function escHtml(val) {
        return String(val === null || val === undefined ? '' : val)
            .replace(/&/g, '&amp;').replace(/"/g, '&quot;')
            .replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }
    function number_format(val) { return Number(val || 0).toLocaleString('id-ID'); }
    function formatRupiah(val) {
        if (val === null || val === undefined || val === '') return '-';
        return 'Rp ' + Number(val).toLocaleString('id-ID');
    }
    function formatDateShort(dateStr) {
        if (!dateStr) return '-';
        var d = new Date(dateStr); if (isNaN(d.getTime())) return dateStr;
        var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        return d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
    }
    function formatDate(dateStr) {
        if (!dateStr) return '-';
        var d = new Date(dateStr); if (isNaN(d.getTime())) return dateStr;
        var months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        var day = d.getDate(); var hours = d.getHours(); var minutes = String(d.getMinutes()).padStart(2, '0');
        var ampm = hours >= 12 ? 'PM' : 'AM'; hours = hours % 12 || 12;
        return day + ' ' + months[d.getMonth()] + ' ' + d.getFullYear() + ', ' + hours + ':' + minutes + ' ' + ampm;
    }
    var SP_STATUS_LABELS = { 'in_stock':'In Stock', 'low_stock':'Low Stock', 'out_of_stock':'Out of Stock', 'draft':'Draft' };
    function spStatusLabel(status) { return SP_STATUS_LABELS[status] || status || 'In Stock'; }
    // Stock level derived from the quantities, independent of the saved status
    function spStockLevel(sp) {
        var stock = parseInt(sp.stock, 10) || 0;
        var min = parseInt(sp.minimum_stock, 10); if (isNaN(min)) min = 10;
        if (stock <= 0) return 'out_of_stock';
        if (stock <= min) return 'low_stock';
        return 'in_stock';
    }

    function updateDrawerContent(sp) {
        _currentDrawerSp = sp;
        var status = sp.status || 'in_stock';
        var statusLabel = spStatusLabel(status);
        // Header
        setText('drawer-part-number', sp.part_number);
        var badge = document.getElementById('drawer-status-badge');
        badge.textContent = statusLabel;
        badge.className = 'gti-drawer-status status-' + status;
        // Section 1 — Part information
        setText('drawer-partnum', sp.part_number);
        setText('drawer-name', sp.name);
        setText('drawer-category', sp.category);
        setText('drawer-brand', sp.brand);
        setText('drawer-status-text', statusLabel);
        setText('drawer-description', sp.description);
        // Section 2 — Inventory
        var stock = parseInt(sp.stock, 10) || 0;
        var minStock = parseInt(sp.minimum_stock, 10); if (isNaN(minStock)) minStock = 10;
        setText('drawer-stock', number_format(stock) + ' pcs');
        setText('drawer-min-stock', number_format(minStock) + ' pcs');
        var level = spStockLevel(sp);
        var levelEl = document.getElementById('drawer-stock-level');
        if (levelEl) {
            var levelClass = level === 'in_stock' ? 'level-ok' : (level === 'low_stock' ? 'level-low' : 'level-out');
            levelEl.innerHTML = '<span class="gti-drawer-stock-tag ' + levelClass + '">' + escHtml(spStatusLabel(level)) + '</span>';
        }
        setText('drawer-supplier', sp.supplier);
        setText('drawer-location', sp.location);
        // Section 3 — Pricing
        setText('drawer-unit-price', formatRupiah(sp.unit_price));
        setText('drawer-total-value', formatRupiah(stock * (parseFloat(sp.unit_price) || 0)));
        // Section 4 — Media
        var imgEl = document.getElementById('drawer-main-image');
        if (imgEl) {
            imgEl.innerHTML = sp.image
                ? '<img src="' + escHtml(sp.image) + '" alt="' + escHtml(sp.name) + '" style="width:100%;border-radius:8px;border:1px solid #e5e7eb;">'
                : '<span class="gti-drawer-value" style="text-align:center;color:#9ca3af;font-weight:400;">No image</span>';
        }
        // Section 5 — System
        setText('drawer-created', formatDate(sp.created_at));
        setText('drawer-updated', formatDate(sp.updated_at));
        // Publish only applies to drafts
        var publishBtn = document.getElementById('drawer-btn-publish');
        if (publishBtn) publishBtn.style.display = (status === 'draft') ? '' : 'none';
        // Highlight active row
        document.querySelectorAll('.gti-ue-table tbody tr').forEach(function(r) { r.classList.remove('active-row'); });
        var activeRow = document.querySelector('.gti-ue-table tbody tr[data-sp-id="' + sp.id + '"]');
        if (activeRow) activeRow.classList.add('active-row');
    }
    function showSparePartDetail(sp) {
        updateDrawerContent(sp);
        if (window.innerWidth < 1600) {
            document.getElementById('spDetailDrawer').classList.add('open');
            document.getElementById('drawerBackdrop').classList.add('show');
            document.body.style.overflow = 'hidden';
        }
        document.querySelectorAll('.gti-ue-action-dropdown.show').forEach(function(d) { d.classList.remove('show'); });
    }
    function populateField(form, name, value) {
        var field = form.querySelector('[name="' + name + '"]');
        if (!field) return;
        if (value === null || value === undefined) value = '';
        // A stored value that predates the current option list would otherwise be
        // silently replaced by the first option and saved back over the real one.
        if (field.tagName === 'SELECT' && value !== '') {
            var known = Array.prototype.some.call(field.options, function(o) { return o.value === value; });
            if (!known) {
                var opt = document.createElement('option');
                opt.value = value;
                opt.textContent = value;
                field.appendChild(opt);
            }
        }
        field.value = value;
    }
    function parseJson(val) {
        if (!val) return null; if (typeof val === 'object') return val;
        try { return JSON.parse(val); } catch(e) { return null; }
    }
    // ================================================================
    //  PUBLISH SPARE PART — Change draft to a live stock status
    // ================================================================
    function handlePublishSparePart(sp) {
        var btn = document.getElementById('drawer-btn-publish');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Publishing...';

        var formData = new FormData();
        formData.append('action', 'gti_publish_spare_part');
        formData.append('nonce', gtiAjax.nonce);
        formData.append('id', sp.id);
        formData.append('status', spStockLevel(sp));

        fetch(gtiAjax.ajaxurl, { method: 'POST', body: formData })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check-circle"></i> Publish';
                if (data.success) {
                    showUeToast((data.data && data.data.message) || 'Spare part published', 'success');
                    setTimeout(function() { window.location.reload(); }, 1200);
                } else {
                    showUeToast((data.data && data.data.message) || 'Failed to publish', 'error');
                }
            })
            .catch(function() {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check-circle"></i> Publish';
                showUeToast('An error occurred while publishing', 'error');
            });
    }

    // ================================================================
    //  EDIT MODAL — Fullscreen popup with multi-step form
    // ================================================================
    var _editCurrentStep = 1;
    var _editTotalSteps = 3;
    var _editCurrentSp = null;

    function openEditModal(sp) {
        _editCurrentSp = sp;
        var overlay = document.getElementById('gtiEditOverlay');
        overlay.classList.add('show');
        document.body.style.overflow = 'hidden';
        document.getElementById('edit-field-id').value = sp.id;

        var form = document.getElementById('gti-edit-form');
        // Step 1 — Part information
        populateField(form, 'part_number', sp.part_number);
        populateField(form, 'name', sp.name);
        populateField(form, 'category', sp.category);
        populateField(form, 'brand', sp.brand);
        populateField(form, 'description', sp.description);
        // Step 2 — Inventory & pricing
        populateField(form, 'stock', sp.stock);
        populateField(form, 'minimum_stock', sp.minimum_stock);
        populateField(form, 'unit_price', sp.unit_price ? number_format(Math.round(parseFloat(sp.unit_price))) : '');
        populateField(form, 'supplier', sp.supplier);
        populateField(form, 'location', sp.location);
        // The column stores the derived stock level too; only draft vs published
        // is editable here.
        populateField(form, 'status', sp.status === 'draft' ? 'draft' : 'published');
        // Step 3 — Image preview
        var previewEl = document.getElementById('gti-edit-upload-preview');
        var placeholderEl = document.getElementById('gti-edit-upload-placeholder');
        var previewImg = document.getElementById('gti-edit-preview-img');
        var fileInput = document.getElementById('gti-edit-main-image');
        if (fileInput) fileInput.value = '';
        if (sp.image) { previewImg.src = sp.image; placeholderEl.style.display = 'none'; previewEl.style.display = ''; }
        else { previewImg.src = ''; placeholderEl.style.display = ''; previewEl.style.display = 'none'; }

        editShowStep(1);
    }
    function closeEditModal() {
        document.getElementById('gtiEditOverlay').classList.remove('show');
        document.body.style.overflow = '';
        _editCurrentSp = null;
    }
    function editGoStep(step) {
        if (step > _editCurrentStep + 1) return; // can only go 1 ahead
        editShowStep(step);
    }
    function editNextStep() {
        if (_editCurrentStep < _editTotalSteps) editShowStep(_editCurrentStep + 1);
    }
    function editPrevStep() {
        if (_editCurrentStep > 1) editShowStep(_editCurrentStep - 1);
    }
    function editShowStep(step) {
        var overlay = document.getElementById('gtiEditOverlay');
        var steps = overlay.querySelectorAll('.gti-ae-step');
        var lines = overlay.querySelectorAll('.gti-ae-step-line');
        var contents = overlay.querySelectorAll('.gti-ae-step-content');
        steps.forEach(function(s, i) {
            s.classList.remove('active', 'completed');
            if (i + 1 < step) s.classList.add('completed');
            else if (i + 1 === step) s.classList.add('active');
        });
        lines.forEach(function(l, i) { l.classList.toggle('active', i + 1 < step); });
        contents.forEach(function(c) { c.classList.remove('active'); });
        var target = overlay.querySelector('.gti-ae-step-content[data-step="' + step + '"]');
        if (target) target.classList.add('active');
        overlay.scrollTop = 0;
        _editCurrentStep = step;
    }
    // A required field on a hidden step cannot be reported by the browser,
    // so jump to the step that holds it first.
    function editValidate() {
        var form = document.getElementById('gti-edit-form');
        var invalid = form.querySelector(':invalid');
        if (!invalid) return true;
        var content = invalid.closest('.gti-ae-step-content');
        if (content) editShowStep(parseInt(content.getAttribute('data-step'), 10));
        showUeToast('Please complete the required fields', 'warning');
        setTimeout(function() {
            if (invalid.reportValidity) invalid.reportValidity(); else invalid.focus();
        }, 60);
        return false;
    }
    function handleEditSubmit(e) {
        if (e) e.preventDefault();
        var form = document.getElementById('gti-edit-form');
        var submitBtn = document.getElementById('gti-edit-submit');
        if (!form || !submitBtn) return;
        if (!editValidate()) return;

        var formData = new FormData(form);
        if (!formData.has('action')) formData.append('action', 'gti_save_spare_part');
        if (!formData.has('nonce')) formData.append('nonce', gtiAjax.nonce);
        // The price field carries thousand separators — send digits only
        formData.set('unit_price', String(formData.get('unit_price') || '').replace(/\D/g, ''));
        // Don't send an empty file input as an upload attempt
        var fileInput = document.getElementById('gti-edit-main-image');
        if (fileInput && (!fileInput.files || !fileInput.files.length)) formData.delete('image');

        submitBtn.disabled = true; submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        fetch(gtiAjax.ajaxurl, { method: 'POST', body: formData })
            .then(function(r) { return r.text().then(function(text) { try { return JSON.parse(text); } catch(e) { throw new Error('Server returned non-JSON response'); } }); })
            .then(function(data) {
                submitBtn.disabled = false; submitBtn.innerHTML = '<i class="fas fa-save"></i> Save Changes';
                if (data.success) { showUeToast((data.data && data.data.message) || 'Spare part updated!', 'success'); closeEditModal(); setTimeout(function() { window.location.reload(); }, 1200); }
                else { showUeToast((data.data && data.data.message) || 'Failed to save', 'error'); }
            })
            .catch(function(err) {
                submitBtn.disabled = false; submitBtn.innerHTML = '<i class="fas fa-save"></i> Save Changes';
                showUeToast('An error occurred: ' + (err.message || 'Unknown'), 'error');
            });
    }
    // ====== DELETE MODAL ======
    var _deleteCurrentSp = null;
    function openDeleteModal(sp) {
        _deleteCurrentSp = sp;
        document.getElementById('delete-sp-name').textContent = sp.name || '\u2014';
        document.getElementById('delete-sp-code').textContent = sp.part_number || '\u2014';
        var thumb = document.getElementById('delete-sp-thumb');
        if (sp.image) { thumb.innerHTML = '<img src="' + sp.image + '" alt="">'; }
        else { thumb.innerHTML = '<i class="fas fa-cog"></i>'; }
        var confirmBtn = document.getElementById('gti-delete-confirm-btn');
        confirmBtn.disabled = false; confirmBtn.innerHTML = '<i class="fas fa-trash"></i> Delete';
        document.getElementById('gtiDeleteOverlay').classList.add('show');
    }
    function handleDeleteConfirm(e) {
        e.preventDefault(); if (!_deleteCurrentSp) return;
        var confirmBtn = document.getElementById('gti-delete-confirm-btn');
        confirmBtn.disabled = true; confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';
        var formData = new FormData();
        formData.append('action', 'gti_delete_spare_part');
        formData.append('nonce', gtiAjax.nonce);
        formData.append('id', _deleteCurrentSp.id);
        fetch(gtiAjax.ajaxurl, { method: 'POST', body: formData })
            .then(function(r) { return r.text().then(function(text) { try { return JSON.parse(text); } catch(e) { if (r.ok) return { success: true, data: { message: 'Spare part deleted' } }; throw e; } }); })
            .then(function(data) {
                if (data.success) {
                    showUeToast(data.data.message || 'Spare part deleted', 'success');
                    var deletedId = _deleteCurrentSp ? _deleteCurrentSp.id : null;
                    closeDeleteModal();
                    if (deletedId) { var row = document.querySelector('tr[data-sp-id="' + deletedId + '"]'); if (row) { row.style.transition = 'opacity 0.3s, transform 0.3s'; row.style.opacity = '0'; row.style.transform = 'translateX(20px)'; setTimeout(function() { row.remove(); }, 350); } }
                } else { confirmBtn.disabled = false; confirmBtn.innerHTML = '<i class="fas fa-trash"></i> Delete'; showUeToast(data.data.message || 'Failed to delete', 'error'); }
            })
            .catch(function() { confirmBtn.disabled = false; confirmBtn.innerHTML = '<i class="fas fa-trash"></i> Delete'; showUeToast('An error occurred while deleting', 'error'); });
    }
    // ====== IMAGE UPLOADS ======
    function initEditImageUploads() {
        var mainUpload = document.getElementById('gti-edit-main-upload');
        var mainInput = document.getElementById('gti-edit-main-image');
        var mainPlaceholder = document.getElementById('gti-edit-upload-placeholder');
        var mainPreview = document.getElementById('gti-edit-upload-preview');
        var previewImg = document.getElementById('gti-edit-preview-img');
        var removeImg = document.getElementById('gti-edit-remove-img');
        if (mainUpload && mainInput) {
            mainUpload.addEventListener('click', function(e) { if (e.target.closest('.gti-ae-remove-img')) return; mainInput.click(); });
            mainInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    var reader = new FileReader();
                    reader.onload = function(ev) { previewImg.src = ev.target.result; mainPlaceholder.style.display = 'none'; mainPreview.style.display = ''; };
                    reader.readAsDataURL(this.files[0]);
                }
            });
        }
        if (removeImg) { removeImg.addEventListener('click', function(e) { e.stopPropagation(); mainInput.value = ''; previewImg.src = ''; mainPlaceholder.style.display = ''; mainPreview.style.display = 'none'; }); }
    }
