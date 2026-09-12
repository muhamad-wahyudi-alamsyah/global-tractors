/**
 * Used Equipment page script.
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

        // Auto-generate equipment code for Edit Modal (with change detection)
        var catMap = (window.gtiPageData && gtiPageData.categoryMap) || {};
        var editEqCatSelect = document.getElementById('gti-edit-form') ? document.querySelector('#gti-edit-form select[name="category"]') : null;
        var editEqCodeInput = document.getElementById('gti-edit-form') ? document.querySelector('#gti-edit-form input[name="equipment_code"]') : null;
        var lastSelectedCategory = '';

        if (editEqCatSelect && editEqCodeInput) {
            function generateEditEqCode(catVal) {
                if (!catVal) return;
                var abbr = catMap[catVal] || 'GEN';
                var year = new Date().getFullYear();
                var fd = new FormData();
                fd.append('action', 'gti_get_next_code');
                fd.append('nonce', gtiAjax.nonce);
                fd.append('category', catVal);
                fetch(gtiAjax.ajaxurl, {
                    method: 'POST',
                    body: fd
                })
                .then(function(r) { return r.json(); })
                .then(function(d) {
                    if (d.success && d.data && d.data.code) {
                        editEqCodeInput.value = d.data.code;
                    } else {
                        var ts = Date.now().toString().slice(-4);
                        editEqCodeInput.value = 'GTI-' + abbr + '-' + year + '-' + ts;
                    }
                })
                .catch(function() {
                    var ts = Date.now().toString().slice(-4);
                    editEqCodeInput.value = 'GTI-' + abbr + '-' + year + '-' + ts;
                });
            }

            // Regenerate code when category changes from last selected value
            editEqCatSelect.addEventListener('change', function() {
                var newCategory = this.value;
                if (newCategory !== lastSelectedCategory) {
                    lastSelectedCategory = newCategory;
                    generateEditEqCode(newCategory);
                }
            });

            // Initialize tracking when modal opens
            var origOpenEditModal = window.openEditModal;
            window.openEditModal = function(eq) {
                lastSelectedCategory = eq.category || '';
                return origOpenEditModal.call(this, eq);
            };
        }
    document.addEventListener('DOMContentLoaded', function() {
        // Sidebar collapse
        var collapseBtn = document.getElementById('gti-collapse-btn');
        var sidebar = document.getElementById('gti-sidebar');
        var mainEl = document.getElementById('gti-main');

        // Action Dropdown Toggle — fixed positioning outside gti-content

        // ====== VIEW BUTTON ======
        document.querySelectorAll('.gti-ue-btn-view').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var row = btn.closest('tr[data-eq]');
                if (!row) return;
                var eq;
                try { eq = JSON.parse(row.getAttribute('data-eq')); } catch(err) { return; }
                document.querySelectorAll('.gti-ue-action-dropdown.show').forEach(function(d) { d.classList.remove('show'); });
                showEquipmentDetail(eq);
            });
        });

        // ====== EDIT BUTTON ======
        document.querySelectorAll('.gti-ue-btn-edit').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var row = btn.closest('tr[data-eq]');
                if (!row) return;
                var eq;
                try { eq = JSON.parse(row.getAttribute('data-eq')); } catch(err) { return; }
                document.querySelectorAll('.gti-ue-action-dropdown.show').forEach(function(d) { d.classList.remove('show'); });
                openEditModal(eq);
            });
        });

        // ====== DELETE BUTTON ======
        document.querySelectorAll('.gti-ue-btn-delete').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var row = btn.closest('tr[data-eq]');
                if (!row) return;
                var eq;
                try { eq = JSON.parse(row.getAttribute('data-eq')); } catch(err) { return; }
                document.querySelectorAll('.gti-ue-action-dropdown.show').forEach(function(d) { d.classList.remove('show'); });
                openDeleteModal(eq);
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

        // Search input — real-time search with debounce + maintain focus after submit
        var searchInput = document.querySelector('.gti-ue-search input[name="search"]');
        var searchTimer = null;
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                var self = this;
                clearTimeout(searchTimer);
                searchTimer = setTimeout(function() {
                    self.form.submit();
                }, 400);
            });
            // Re-focus search input after page reload if it had a value
            if (searchInput.value) {
                searchInput.focus();
                var len = searchInput.value.length;
                searchInput.setSelectionRange(len, len);
            }
        }

        // Drawer Edit button
        document.getElementById('drawer-btn-edit').addEventListener('click', function() {
            if (_currentDrawerEq) openEditModal(_currentDrawerEq);
        });
        // Drawer Publish button
        document.getElementById('drawer-btn-publish').addEventListener('click', function() {
            if (_currentDrawerEq) handlePublishEquipment(_currentDrawerEq);
        });
        // Drawer Delete button
        document.getElementById('drawer-btn-delete').addEventListener('click', function() {
            if (_currentDrawerEq) openDeleteModal(_currentDrawerEq);
        });

        // Row click → drawer
        document.querySelectorAll('.gti-ue-table tbody tr[data-eq]').forEach(function(row) {
            row.addEventListener('click', function(e) {
                if (e.target.closest('.gti-ue-action-menu') || e.target.type === 'checkbox') return;
                var eq;
                try { eq = JSON.parse(this.getAttribute('data-eq')); } catch(err) { return; }
                showEquipmentDetail(eq);
            });
        });

        // Auto-populate drawer with first row
        var firstRow = document.querySelector('.gti-ue-table tbody tr[data-eq]');
        if (firstRow) {
            try { updateDrawerContent(JSON.parse(firstRow.getAttribute('data-eq'))); } catch(e) {}
        }

        // Edit modal image upload handlers
        initEditImageUploads();

        // Edit modal submit button — bind to click
        var editSubmitBtn = document.getElementById('gti-edit-submit');
        if (editSubmitBtn) {
            editSubmitBtn.addEventListener('click', handleEditSubmit);
        }

        // Delete confirm button
        document.getElementById('gti-delete-confirm-btn').addEventListener('click', handleDeleteConfirm);
    });

    var _currentDrawerEq = null;
    function updateDrawerContent(eq) {
        _currentDrawerEq = eq;
        // Header
        document.getElementById('drawer-eq-code').textContent = eq.equipment_code || '-';
        var badge = document.getElementById('drawer-status-badge');
        var statusMap = { 'available':'Available', 'sold':'Sold', 'reserved':'Reserved', 'rented':'Rented', 'maintenance':'Maintenance', 'draft':'Draft' };
        var sl = '';
        var isPriceValid = true;
        if (eq.price_valid_until) {
            var validDate = new Date(eq.price_valid_until);
            var today = new Date();
            today.setHours(0, 0, 0, 0);
            isPriceValid = validDate >= today;
        }
        if (!isPriceValid) {
            sl = 'Price No Longer Valid';
            badge.textContent = sl;
            badge.className = 'gti-drawer-status status-price-no-longer-valid';
        } else {
            sl = statusMap[eq.status] || eq.status || 'Available';
            badge.textContent = sl;
            badge.className = 'gti-drawer-status status-' + (eq.status || 'available');
        }
        // === Section 1: Info ===
        setText('drawer-name', eq.name);
        setText('drawer-eq-code-info', eq.equipment_code);
        setText('drawer-category', eq.category);
        setText('drawer-brand', eq.brand);
        setText('drawer-model', eq.model);
        setText('drawer-year', eq.year);
        setText('drawer-hours', eq.hours ? number_format(eq.hours) + ' Hours' : null);
        setText('drawer-serial-number', eq.serial_number);
        setText('drawer-condition', eq.condition_status);
        setText('drawer-status-text', sl);
        setText('drawer-engine', eq.engine);
        setText('drawer-engine-power', eq.engine_power);
        setText('drawer-origin', eq.origin_country);
        setText('drawer-type', eq.type);
        setText('drawer-operating-weight', eq.operating_weight);
        setText('drawer-bucket-capacity', eq.bucket_capacity);
        setText('drawer-location', eq.location);
        setText('drawer-stock-number', eq.stock_number);
        setText('drawer-description', eq.description);
        // === Section 2: Specs + Features ===
        var specsHtml = '';
        var specs = null;
        if (eq.specifications) { try { specs = typeof eq.specifications === 'string' ? JSON.parse(eq.specifications) : eq.specifications; } catch(e){} }
        if (specs && typeof specs === 'object') {
            for (var key in specs) { if (specs.hasOwnProperty(key)) specsHtml += '<div class="gti-drawer-row"><span class="gti-drawer-label">' + key.charAt(0).toUpperCase() + key.slice(1).replace(/_/g,' ') + '</span><span class="gti-drawer-value">' + specs[key] + '</span></div>'; }
        }
        document.getElementById('drawer-specs-list').innerHTML = specsHtml || '<div class="gti-drawer-row"><span class="gti-drawer-value" style="text-align:center;color:#9ca3af;font-weight:400;">No specifications available</span></div>';
        // Features
        var featuresHtml = '';
        var features = null;
        if (eq.features) { try { features = typeof eq.features === 'string' ? JSON.parse(eq.features) : eq.features; } catch(e){} }
        var featureLabels = { air_conditioner:'Air Conditioner', backup_alarm:'Backup Alarm', led_work_light:'LED Work Light', camera:'Camera', auto_idle:'Auto Idle', hammer_line:'Hammer Line', quick_coupler:'Quick Coupler', gps_system:'GPS System', centralized_greasing:'Centralized Greasing' };
        if (features && typeof features === 'object') {
            for (var fk in features) { if (features[fk]) featuresHtml += '<span class="gti-drawer-feature-tag"><i class="fas fa-check-circle"></i> ' + (featureLabels[fk] || fk.replace(/_/g,' ')) + '</span>'; }
        }
        document.getElementById('drawer-features-list').innerHTML = featuresHtml || '<span class="gti-drawer-value" style="text-align:center;color:#9ca3af;font-weight:400;width:100%;display:block;">No features</span>';
        // === Section 3: Pricing & Availability ===
        setText('drawer-selling-price', eq.selling_price ? formatRupiah(eq.selling_price) : null);
        setText('drawer-rental-price', eq.rental_price ? formatRupiah(eq.rental_price) + '/Month' : null);
        var priceTypeMap = { 'sale':'For Sale', 'rental':'For Rental', 'sale_and_rental':'Sale & Rental', 'price_on_ask':'Price on Ask' };
        setText('drawer-price-type', priceTypeMap[eq.price_type] || eq.price_type);
        setText('drawer-vat', eq.vat_included ? eq.vat_included.charAt(0).toUpperCase() + eq.vat_included.slice(1) : null);
        setText('drawer-currency', eq.currency);
        setText('drawer-price-valid-until', eq.price_valid_until ? formatDateShort(eq.price_valid_until) : null);
        setText('drawer-negotiable', eq.negotiable ? eq.negotiable.charAt(0).toUpperCase() + eq.negotiable.slice(1) : null);
        setText('drawer-availability-status', eq.availability_status ? eq.availability_status.charAt(0).toUpperCase() + eq.availability_status.slice(1).replace(/_/g,' ') : null);
        setText('drawer-ready-to-use', eq.ready_to_use ? eq.ready_to_use.charAt(0).toUpperCase() + eq.ready_to_use.slice(1).replace(/_/g,' ') : null);
        var shMap = { 'full':'Full Service History', 'partial':'Partial Service History', 'none':'No Service History' };
        setText('drawer-service-history', shMap[eq.service_history] || eq.service_history);
        setText('drawer-warranty-avail', eq.warranty_available ? eq.warranty_available.charAt(0).toUpperCase() + eq.warranty_available.slice(1) : null);
        setText('drawer-warranty-period', eq.warranty_period);
        setText('drawer-buyer-notes', eq.buyer_notes);
        // === Section 4: Media ===
        var mainImgUrl = eq.main_image || '';
        var mainImgEl = document.getElementById('drawer-main-image');
        if (mainImgUrl) {
            mainImgEl.innerHTML = '<img src="' + mainImgUrl + '" alt="Main" style="width:100%;border-radius:8px;border:1px solid #e5e7eb;">';
        } else {
            mainImgEl.innerHTML = '<span class="gti-drawer-value" style="text-align:center;color:#9ca3af;font-weight:400;">No image</span>';
        }
        var galleryUrls = [];
        if (eq.images) { try { var parsed = typeof eq.images === 'string' ? JSON.parse(eq.images) : eq.images; if (Array.isArray(parsed)) galleryUrls = parsed; } catch(e){} }
        var galleryEl = document.getElementById('drawer-gallery');
        if (galleryUrls.length > 0) {
            galleryEl.innerHTML = galleryUrls.map(function(src){ return '<img src="' + src + '" alt="Gallery" style="width:56px;height:56px;object-fit:cover;border-radius:6px;border:1px solid #e5e7eb;">'; }).join('');
        } else {
            galleryEl.innerHTML = '<span class="gti-drawer-value" style="text-align:center;color:#9ca3af;font-weight:400;">No images</span>';
        }
        // Video
        var videoEl = document.getElementById('drawer-video');
        if (eq.video_url) {
            videoEl.innerHTML = '<div class="gti-drawer-video-thumb"><i class="fas fa-play-circle"></i><div><span>Equipment Video</span><small><a href="' + eq.video_url + '" target="_blank" style="color:#2563eb;text-decoration:none;">Open video →</a></small></div></div>';
        } else if (eq.video_file) {
            videoEl.innerHTML = '<div class="gti-drawer-video-thumb"><i class="fas fa-play-circle"></i><div><span>' + (eq.video_file_name || 'Equipment Video') + '</span><small>Uploaded video</small></div></div>';
        } else {
            videoEl.innerHTML = '<span class="gti-drawer-value" style="text-align:center;color:#9ca3af;font-weight:400;">No video</span>';
        }
        // === Section 5: Additional ===
        setText('drawer-previous-usage', eq.previous_usage);
        setText('drawer-working-condition', eq.working_condition ? eq.working_condition.charAt(0).toUpperCase() + eq.working_condition.slice(1) : null);
        var mrMap = { 'full':'Full Record', 'partial':'Partial Record', 'none':'No Record' };
        setText('drawer-maintenance-record', mrMap[eq.maintenance_record] || eq.maintenance_record);
        var ownMap = { 'first_owner':'First Owner', 'second_owner':'Second Owner', 'third_plus':'Third Owner or More', 'company':'Company Fleet' };
        setText('drawer-ownership', ownMap[eq.ownership] || eq.ownership);
        setText('drawer-operator-hours', eq.operator_hours ? number_format(eq.operator_hours) + ' Hours' : null);
        setText('drawer-last-service', eq.last_service_date ? formatDateShort(eq.last_service_date) : null);
        setText('drawer-equipment-history', eq.equipment_history);
        setText('drawer-detailed-description', eq.detailed_description);
        // Documents
        var docsHtml = '';
        var docs = null;
        if (eq.documents) { try { docs = typeof eq.documents === 'string' ? JSON.parse(eq.documents) : eq.documents; } catch(e){} }
        var docLabels = { unit_certificate:'Unit Certificate (STNK/BPKB)', import_document:'Import Document', service_maintenance_record:'Service & Maintenance Record', customs_document:'Customs Document', warranty_book:'Warranty Book' };
        var docOrder = ['unit_certificate','import_document','service_maintenance_record','customs_document','warranty_book'];
        if (docs && typeof docs === 'object') {
            docOrder.forEach(function(dk) {
                var available = docs[dk];
                docsHtml += '<div class="gti-drawer-doc-item' + (available ? '' : ' missing') + '"><i class="fas ' + (available ? 'fa-check-circle' : 'fa-times-circle') + '"></i><span>' + (docLabels[dk] || dk.replace(/_/g,' ')) + '</span></div>';
            });
        } else {
            docOrder.forEach(function(dk) {
                docsHtml += '<div class="gti-drawer-doc-item missing"><i class="fas fa-times-circle"></i><span>' + (docLabels[dk] || dk.replace(/_/g,' ')) + '</span></div>';
            });
        }
        document.getElementById('drawer-documents-list').innerHTML = docsHtml;
        // Location details
        setText('drawer-location-country', eq.location_country);
        setText('drawer-location-province', eq.location_province);
        setText('drawer-location-city', eq.location_city);
        setText('drawer-address', eq.detailed_address);
        if (eq.map_location) {
            document.getElementById('drawer-map-location').innerHTML = '<a href="' + eq.map_location + '" target="_blank" class="gti-drawer-value is-link" style="text-decoration:none;">Open in Maps →</a>';
        } else {
            setText('drawer-map-location', null);
        }
        setText('drawer-location-notes', eq.location_notes);
        // System
        setText('drawer-created', formatDate(eq.created_at));
        setText('drawer-updated', formatDate(eq.updated_at));
        setText('drawer-notes', eq.notes);
        // Highlight active row
        document.querySelectorAll('.gti-ue-table tbody tr').forEach(function(r) { r.classList.remove('active-row'); });
        var activeRow = document.querySelector('.gti-ue-table tbody tr[data-eq-id="' + eq.id + '"]');
        if (activeRow) activeRow.classList.add('active-row');
        // Show/hide Publish button based on status
        var publishBtn = document.getElementById('drawer-btn-publish');
        if (publishBtn) {
            publishBtn.style.display = (eq.status === 'draft') ? '' : 'none';
        }
    }
    function setText(id, val) {
        var el = document.getElementById(id);
        if (el) el.textContent = val || '-';
    }
    function formatDateShort(dateStr) {
        if (!dateStr) return '-';
        var d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        return d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
    }
    function showEquipmentDetail(eq) {
        updateDrawerContent(eq);
        if (window.innerWidth < 1600) {
            document.getElementById('eqDetailDrawer').classList.add('open');
            document.getElementById('drawerBackdrop').classList.add('show');
            document.body.style.overflow = 'hidden';
        }
        document.querySelectorAll('.gti-ue-action-dropdown.show').forEach(function(d) { d.classList.remove('show'); });
    }
    function switchDrawerStep(step) {
        var section = step.getAttribute('data-section');
        var steps = document.querySelectorAll('#eqDetailDrawer .gti-drawer-step');
        var lines = document.querySelectorAll('#eqDetailDrawer .gti-drawer-step-line');
        var clickedIdx = Array.prototype.indexOf.call(steps, step);
        steps.forEach(function(s, i) {
            s.classList.remove('active','completed');
            if (i < clickedIdx) s.classList.add('completed');
            else if (i === clickedIdx) s.classList.add('active');
        });
        lines.forEach(function(l, i) {
            l.classList.toggle('active', i < clickedIdx);
        });
        document.querySelectorAll('#eqDetailDrawer .gti-drawer-section[data-section]').forEach(function(s) { s.classList.remove('active'); });
        var target = document.querySelector('#eqDetailDrawer .gti-drawer-section[data-section="' + section + '"]');
        if (target) target.classList.add('active');
    }
    function formatRupiah(val) {
        if (!val) return '-';
        return 'Rp ' + Number(val).toLocaleString('id-ID');
    }
    function number_format(val) {
        return Number(val).toLocaleString('id-ID');
    }
    function formatDate(dateStr) {
        if (!dateStr) return '-';
        var d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        var months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        var day = d.getDate();
        var hours = d.getHours(); var minutes = String(d.getMinutes()).padStart(2, '0');
        var ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12 || 12;
        return day + ' ' + months[d.getMonth()] + ' ' + d.getFullYear() + ', ' + hours + ':' + minutes + ' ' + ampm;
    }

    // ================================================================
    //  EDIT MODAL — Fullscreen popup with multi-step form
    // ================================================================
    var _editCurrentStep = 1;
    var _editTotalSteps = 5;
    var _editCurrentEq = null;

    function openEditModal(eq) {
        _editCurrentEq = eq;
        originalEditCategory = eq.category || '';
        _editCurrentStep = 1;
        var overlay = document.getElementById('gtiEditOverlay');
        overlay.classList.add('show');
        document.body.style.overflow = 'hidden';

        // Set hidden id
        document.getElementById('edit-field-id').value = eq.id;

        // Reset stepper
        var steps = overlay.querySelectorAll('.gti-ae-step');
        var lines = overlay.querySelectorAll('.gti-ae-step-line');
        var contents = overlay.querySelectorAll('.gti-ae-step-content');
        steps.forEach(function(s) { s.classList.remove('active', 'completed'); });
        lines.forEach(function(l) { l.classList.remove('active'); });
        contents.forEach(function(c) { c.classList.remove('active'); });
        steps[0].classList.add('active');
        contents[0].classList.add('active');

        // Populate form fields
        var form = document.getElementById('gti-edit-form');
        populateField(form, 'name', eq.name);
        populateField(form, 'equipment_code', eq.equipment_code);
        populateField(form, 'category', eq.category);
        populateField(form, 'brand', eq.brand);
        populateField(form, 'model', eq.model);
        populateField(form, 'year', eq.year);
        populateField(form, 'hours', eq.hours);
        populateField(form, 'serial_number', eq.serial_number);
        populateField(form, 'condition_status', eq.condition_status);
        populateField(form, 'engine', eq.engine);
        populateField(form, 'engine_power', eq.engine_power);
        populateField(form, 'origin_country', eq.origin_country);
        populateField(form, 'type', eq.type);
        populateField(form, 'operating_weight', eq.operating_weight);
        populateField(form, 'bucket_capacity', eq.bucket_capacity);
        populateField(form, 'location', eq.location);
        populateField(form, 'status', eq.status);
        populateField(form, 'stock_number', eq.stock_number);
        populateField(form, 'description', eq.description);
        // Step 2 — Specs
        var specs = parseJson(eq.specifications);
        if (specs) {
            for (var k in specs) { if (specs.hasOwnProperty(k)) populateField(form, k, specs[k]); }
        }
        // Step 2 — Features
        var features = parseJson(eq.features);
        form.querySelectorAll('input[name^="features"]').forEach(function(cb) {
            var key = cb.name.replace('features[', '').replace(']', '');
            cb.checked = !!(features && features[key]);
        });
        // Step 3 — Pricing
        populateField(form, 'selling_price', eq.selling_price ? Number(eq.selling_price).toLocaleString('id-ID') : '');
        populateField(form, 'rental_price', eq.rental_price ? Number(eq.rental_price).toLocaleString('id-ID') : '');
        populateField(form, 'price_type', eq.price_type);
        populateField(form, 'vat_included', eq.vat_included);
        populateField(form, 'currency', eq.currency || 'IDR');
        populateField(form, 'price_valid_until', eq.price_valid_until);
        populateField(form, 'negotiable', eq.negotiable);
        // Step 3 — Availability
        populateField(form, 'availability_status', eq.availability_status);
        populateField(form, 'ready_to_use', eq.ready_to_use);
        populateField(form, 'service_history', eq.service_history);
        populateField(form, 'warranty_available', eq.warranty_available);
        populateField(form, 'warranty_period', eq.warranty_period);
        populateField(form, 'buyer_notes', eq.buyer_notes);
        // Step 4 — Images (show current main image preview)
        var previewEl = document.getElementById('gti-edit-upload-preview');
        var placeholderEl = document.getElementById('gti-edit-upload-placeholder');
        var previewImg = document.getElementById('gti-edit-preview-img');
        if (eq.main_image) {
            previewImg.src = eq.main_image;
            placeholderEl.style.display = 'none';
            previewEl.style.display = '';
        } else {
            previewImg.src = '';
            placeholderEl.style.display = '';
            previewEl.style.display = 'none';
        }
        // Gallery preview
        var galleryPreviewEl = document.getElementById('gti-edit-gallery-preview');
        galleryPreviewEl.innerHTML = '';
        var images = parseJson(eq.images);
        if (images && Array.isArray(images)) {
            images.forEach(function(src) {
                var item = document.createElement('div');
                item.className = 'gti-ae-gallery-item';
                item.innerHTML = '<img src="' + src + '" alt="Gallery"><button type="button" class="gti-ae-remove-img" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>';
                galleryPreviewEl.appendChild(item);
            });
        }
        // Step 5 — Additional info
        populateField(form, 'detailed_description', eq.detailed_description);
        populateField(form, 'equipment_history', eq.equipment_history);
        populateField(form, 'previous_usage', eq.previous_usage);
        populateField(form, 'working_condition', eq.working_condition);
        populateField(form, 'maintenance_record', eq.maintenance_record);
        populateField(form, 'ownership', eq.ownership);
        populateField(form, 'operator_hours', eq.operator_hours);
        populateField(form, 'last_service_date', eq.last_service_date);
        // Documents
        var docs = parseJson(eq.documents);
        form.querySelectorAll('input[name^="documents"]').forEach(function(cb) {
            var key = cb.name.replace('documents[', '').replace(']', '');
            cb.checked = !!(docs && docs[key]);
        });
        // Location details
        populateField(form, 'location_country', eq.location_country);
        populateField(form, 'location_province', eq.location_province);
        populateField(form, 'location_city', eq.location_city);
        populateField(form, 'detailed_address', eq.detailed_address);
        populateField(form, 'map_location', eq.map_location);
        populateField(form, 'location_notes', eq.location_notes);
    }

    function closeEditModal() {
        document.getElementById('gtiEditOverlay').classList.remove('show');
        document.body.style.overflow = '';
        _editCurrentEq = null;
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
        _editCurrentStep = step;
    }

    function handleEditSubmit(e) {
        if (e) e.preventDefault();
        var form = document.getElementById('gti-edit-form');
        var submitBtn = document.getElementById('gti-edit-submit');
        if (!form || !submitBtn) return;

        var formData = new FormData(form);
        // Ensure action and nonce are present
        if (!formData.has('action')) formData.append('action', 'gti_save_equipment');
        if (!formData.has('nonce')) formData.append('nonce', gtiAjax.nonce);

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

        fetch(gtiAjax.ajaxurl, { method: 'POST', body: formData })
            .then(function(r) {
                return r.text().then(function(text) {
                    try { return JSON.parse(text); }
                    catch(e) {
                        console.error('Save: non-JSON response', text);
                        throw new Error('Server returned non-JSON response');
                    }
                });
            })
            .then(function(data) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save"></i> Save Changes';
                if (data.success) {
                    showUeToast(data.data.message || 'Equipment updated successfully!', 'success');
                    closeEditModal();
                    setTimeout(function() { window.location.reload(); }, 1200);
                } else {
                    showUeToast(data.data.message || 'Failed to save changes', 'error');
                }
            })
            .catch(function(err) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save"></i> Save Changes';
                showUeToast('An error occurred while saving: ' + (err.message || 'Unknown error'), 'error');
            });
    }

    // ================================================================
    //  DELETE MODAL — Confirmation popup
    // ================================================================
    var _deleteCurrentEq = null;

    function openDeleteModal(eq) {
        _deleteCurrentEq = eq;
        document.getElementById('delete-eq-name').textContent = eq.name || '—';
        document.getElementById('delete-eq-code').textContent = eq.equipment_code || '—';
        var thumb = document.getElementById('delete-eq-thumb');
        if (eq.main_image) {
            thumb.innerHTML = '<img src="' + eq.main_image + '" alt="">';
        } else {
            thumb.innerHTML = '<i class="fas fa-truck"></i>';
        }
        var confirmBtn = document.getElementById('gti-delete-confirm-btn');
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = '<i class="fas fa-trash"></i> Delete';
        document.getElementById('gtiDeleteOverlay').classList.add('show');
    }

    function handleDeleteConfirm(e) {
        e.preventDefault();
        if (!_deleteCurrentEq) return;
        var confirmBtn = document.getElementById('gti-delete-confirm-btn');
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';

        var formData = new FormData();
        formData.append('action', 'gti_delete_equipment');
        formData.append('nonce', gtiAjax.nonce);
        formData.append('id', _deleteCurrentEq.id);

        fetch(gtiAjax.ajaxurl, { method: 'POST', body: formData })
            .then(function(r) {
                return r.text().then(function(text) {
                    try { return JSON.parse(text); }
                    catch(e) {
                        // If JSON parsing fails but HTTP was OK, treat as success
                        // (server likely deleted but response had extra output)
                        if (r.ok) return { success: true, data: { message: 'Equipment deleted' } };
                        throw e;
                    }
                });
            })
            .then(function(data) {
                if (data.success) {
                    showUeToast(data.data.message || 'Equipment deleted', 'success');
                    // Capture id BEFORE closing modal (closeDeleteModal sets _deleteCurrentEq = null)
                    var deletedId = _deleteCurrentEq ? _deleteCurrentEq.id : null;
                    closeDeleteModal();
                    // Remove the row from table
                    if (deletedId) {
                        var row = document.querySelector('tr[data-eq-id="' + deletedId + '"]');
                        if (row) {
                            row.style.transition = 'opacity 0.3s, transform 0.3s';
                            row.style.opacity = '0';
                            row.style.transform = 'translateX(20px)';
                            setTimeout(function() { row.remove(); }, 350);
                        }
                    }
                } else {
                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = '<i class="fas fa-trash"></i> Delete';
                    showUeToast(data.data.message || 'Failed to delete', 'error');
                }
            })
            .catch(function(err) {
                console.error('Delete error:', err);
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = '<i class="fas fa-trash"></i> Delete';
                showUeToast('An error occurred while deleting', 'error');
            });
    }

    // ================================================================
    //  PUBLISH EQUIPMENT — Change draft to available
    // ================================================================
    function handlePublishEquipment(eq) {
        var btn = document.getElementById('drawer-btn-publish');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Publishing...';

        var formData = new FormData();
        formData.append('action', 'gti_publish_equipment');
        formData.append('nonce', gtiAjax.nonce);
        formData.append('id', eq.id);

        fetch(gtiAjax.ajaxurl, { method: 'POST', body: formData })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check-circle"></i> Publish';
                if (data.success) {
                    showUeToast(data.data.message || 'Equipment published', 'success');
                    setTimeout(function() { window.location.reload(); }, 1200);
                } else {
                    showUeToast(data.data.message || 'Failed to publish', 'error');
                }
            })
            .catch(function() {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check-circle"></i> Publish';
                showUeToast('An error occurred while publishing', 'error');
            });
    }

    // ================================================================
    //  EDIT MODAL — Image upload handlers
    // ================================================================
    function initEditImageUploads() {
        // Main image
        var mainUpload = document.getElementById('gti-edit-main-upload');
        var mainInput = document.getElementById('gti-edit-main-image');
        var mainPlaceholder = document.getElementById('gti-edit-upload-placeholder');
        var mainPreview = document.getElementById('gti-edit-upload-preview');
        var previewImg = document.getElementById('gti-edit-preview-img');
        var removeImg = document.getElementById('gti-edit-remove-img');
        if (mainUpload && mainInput) {
            mainUpload.addEventListener('click', function(e) {
                if (e.target.closest('.gti-ae-remove-img')) return;
                mainInput.click();
            });
            mainInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    var reader = new FileReader();
                    reader.onload = function(ev) { previewImg.src = ev.target.result; mainPlaceholder.style.display = 'none'; mainPreview.style.display = ''; };
                    reader.readAsDataURL(this.files[0]);
                }
            });
        }
        if (removeImg) {
            removeImg.addEventListener('click', function(e) {
                e.stopPropagation();
                mainInput.value = '';
                previewImg.src = '';
                mainPlaceholder.style.display = '';
                mainPreview.style.display = 'none';
            });
        }
        // Gallery
        var galleryUpload = document.getElementById('gti-edit-gallery-upload');
        var galleryInput = document.getElementById('gti-edit-gallery-images');
        var galleryPreview = document.getElementById('gti-edit-gallery-preview');
        if (galleryUpload && galleryInput) {
            galleryUpload.addEventListener('click', function() { galleryInput.click(); });
            galleryInput.addEventListener('change', function() {
                if (this.files && this.files.length) {
                    Array.from(this.files).slice(0, 10).forEach(function(file) {
                        if (!file.type.startsWith('image/')) return;
                        var reader = new FileReader();
                        reader.onload = function(ev) {
                            var item = document.createElement('div');
                            item.className = 'gti-ae-gallery-item';
                            item.innerHTML = '<img src="' + ev.target.result + '" alt="Gallery"><button type="button" class="gti-ae-remove-img" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>';
                            galleryPreview.appendChild(item);
                        };
                        reader.readAsDataURL(file);
                    });
                }
            });
        }
        // Video
        var videoUpload = document.getElementById('gti-edit-video-upload');
        var videoInput = document.getElementById('gti-edit-video-file');
        var videoPlaceholder = document.getElementById('gti-edit-video-placeholder');
        var videoPreview = document.getElementById('gti-edit-video-preview');
        var videoName = document.getElementById('gti-edit-video-name');
        var removeVideo = document.getElementById('gti-edit-remove-video');
        if (videoUpload && videoInput) {
            videoUpload.addEventListener('click', function(e) {
                if (e.target.closest('.gti-ae-remove-img')) return;
                videoInput.click();
            });
            videoInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    videoName.textContent = this.files[0].name;
                    videoPlaceholder.style.display = 'none';
                    videoPreview.style.display = '';
                }
            });
        }
        if (removeVideo) {
            removeVideo.addEventListener('click', function(e) {
                e.stopPropagation();
                videoInput.value = '';
                videoPlaceholder.style.display = '';
                videoPreview.style.display = 'none';
            });
        }
    }

    // ================================================================
    //  HELPERS
    // ================================================================
    function populateField(form, name, value) {
        var field = form.querySelector('[name="' + name + '"]');
        if (!field) return;
        if (value === null || value === undefined) value = '';
        field.value = value;
    }
    function parseJson(val) {
        if (!val) return null;
        if (typeof val === 'object') return val;
        try { return JSON.parse(val); } catch(e) { return null; }
    }
