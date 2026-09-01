/**
 * Add Equipment - Multi-Step Form
 * @package global-tractors
 */
(function() {
    'use strict';

    var currentStep = 1;
    var totalSteps = 5;

    // DOM Elements
    var form = document.getElementById('gti-ae-form');
    var prevBtn = document.getElementById('gti-ae-prev');
    var nextBtn = document.getElementById('gti-ae-next');
    var submitBtn = document.getElementById('gti-ae-submit');
    var draftBtn = document.getElementById('gti-ae-draft');
    var steps = document.querySelectorAll('.gti-ae-step');
    var stepContents = document.querySelectorAll('.gti-ae-step-content');
    var stepLines = document.querySelectorAll('.gti-ae-step-line');

    // Initialize
    function init() {
        bindEvents();
        showStep(1);
    }

    function bindEvents() {
        // Navigation
        if (nextBtn) nextBtn.addEventListener('click', nextStep);
        if (prevBtn) prevBtn.addEventListener('click', prevStep);
        if (draftBtn) draftBtn.addEventListener('click', saveDraft);

        // Step click (only completed steps)
        steps.forEach(function(step) {
            step.addEventListener('click', function() {
                var stepNum = parseInt(this.dataset.step);
                if (stepNum < currentStep) {
                    showStep(stepNum);
                }
            });
        });

        // Main image upload
        var mainUpload = document.getElementById('gti-ae-main-upload');
        var mainInput = document.getElementById('gti-ae-main-image');
        var mainPlaceholder = document.getElementById('gti-ae-upload-placeholder');
        var mainPreview = document.getElementById('gti-ae-upload-preview');
        var previewImg = document.getElementById('gti-ae-preview-img');
        var removeImg = document.getElementById('gti-ae-remove-img');

        if (mainUpload && mainInput) {
            mainUpload.addEventListener('click', function(e) {
                if (e.target === removeImg || e.target.parentElement === removeImg) return;
                mainInput.click();
            });

            // Drag and drop
            mainUpload.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.style.borderColor = 'var(--gti-primary)';
                this.style.background = '#fffbeb';
            });

            mainUpload.addEventListener('dragleave', function(e) {
                e.preventDefault();
                this.style.borderColor = '#e5e7eb';
                this.style.background = '#fafafa';
            });

            mainUpload.addEventListener('drop', function(e) {
                e.preventDefault();
                this.style.borderColor = '#e5e7eb';
                this.style.background = '#fafafa';
                if (e.dataTransfer.files.length) {
                    mainInput.files = e.dataTransfer.files;
                    showMainPreview(e.dataTransfer.files[0]);
                }
            });

            mainInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    showMainPreview(this.files[0]);
                }
            });
        }

        if (removeImg) {
            removeImg.addEventListener('click', function(e) {
                e.stopPropagation();
                mainInput.value = '';
                mainPlaceholder.style.display = '';
                mainPreview.style.display = 'none';
            });
        }

        // Gallery upload
        var galleryUpload = document.getElementById('gti-ae-gallery-upload');
        var galleryInput = document.getElementById('gti-ae-gallery-images');
        var galleryPreview = document.getElementById('gti-ae-gallery-preview');

        if (galleryUpload && galleryInput) {
            galleryUpload.addEventListener('click', function() {
                galleryInput.click();
            });

            galleryUpload.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.style.borderColor = 'var(--gti-primary)';
                this.style.background = '#fffbeb';
            });

            galleryUpload.addEventListener('dragleave', function(e) {
                e.preventDefault();
                this.style.borderColor = '#e5e7eb';
                this.style.background = '#fafafa';
            });

            galleryUpload.addEventListener('drop', function(e) {
                e.preventDefault();
                this.style.borderColor = '#e5e7eb';
                this.style.background = '#fafafa';
                if (e.dataTransfer.files.length) {
                    handleGalleryFiles(e.dataTransfer.files);
                }
            });

            galleryInput.addEventListener('change', function() {
                if (this.files && this.files.length) {
                    handleGalleryFiles(this.files);
                }
            });
        }

        // Video upload
        var videoUpload = document.getElementById('gti-ae-video-upload');
        var videoInput = document.getElementById('gti-ae-video-file');
        var videoPlaceholder = document.getElementById('gti-ae-video-placeholder');
        var videoPreview = document.getElementById('gti-ae-video-preview');
        var videoName = document.getElementById('gti-ae-video-name');
        var removeVideo = document.getElementById('gti-ae-remove-video');

        if (videoUpload && videoInput) {
            videoUpload.addEventListener('click', function(e) {
                if (e.target === removeVideo || e.target.parentElement === removeVideo) return;
                videoInput.click();
            });

            videoUpload.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.style.borderColor = 'var(--gti-primary)';
                this.style.background = '#fffbeb';
            });

            videoUpload.addEventListener('dragleave', function(e) {
                e.preventDefault();
                this.style.borderColor = '#e5e7eb';
                this.style.background = '#fafafa';
            });

            videoUpload.addEventListener('drop', function(e) {
                e.preventDefault();
                this.style.borderColor = '#e5e7eb';
                this.style.background = '#fafafa';
                if (e.dataTransfer.files.length) {
                    videoInput.files = e.dataTransfer.files;
                    showVideoPreview(e.dataTransfer.files[0]);
                }
            });

            videoInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    showVideoPreview(this.files[0]);
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

        function showVideoPreview(file) {
            videoName.textContent = file.name;
            videoPlaceholder.style.display = 'none';
            videoPreview.style.display = '';
        }

        // Warranty toggle
        var warrantyToggle = document.getElementById('gti-ae-warranty-toggle');
        var warrantyPeriod = document.getElementById('gti-ae-warranty-period');

        if (warrantyToggle && warrantyPeriod) {
            function toggleWarranty() {
                var isEnabled = warrantyToggle.value === 'yes';
                warrantyPeriod.disabled = !isEnabled;
                if (!isEnabled) {
                    warrantyPeriod.value = '';
                    warrantyPeriod.style.opacity = '0.5';
                } else {
                    warrantyPeriod.style.opacity = '';
                }
            }
            warrantyToggle.addEventListener('change', toggleWarranty);
            toggleWarranty();
        }

        // Form submit — bind to button click (not form submit, since button is type="button")
        if (submitBtn) {
            submitBtn.addEventListener('click', handleSubmit);
        }
    }

    function showMainPreview(file) {
        var mainPlaceholder = document.getElementById('gti-ae-upload-placeholder');
        var mainPreview = document.getElementById('gti-ae-upload-preview');
        var previewImg = document.getElementById('gti-ae-preview-img');

        var reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            mainPlaceholder.style.display = 'none';
            mainPreview.style.display = '';
        };
        reader.readAsDataURL(file);
    }

    function handleGalleryFiles(files) {
        var galleryPreview = document.getElementById('gti-ae-gallery-preview');
        var maxFiles = 10;

        Array.from(files).slice(0, maxFiles).forEach(function(file) {
            if (!file.type.startsWith('image/')) return;

            var reader = new FileReader();
            reader.onload = function(e) {
                var item = document.createElement('div');
                item.className = 'gti-ae-gallery-item';
                item.innerHTML = '<img src="' + e.target.result + '" alt="Gallery">' +
                    '<button type="button" class="gti-ae-remove-img"><i class="fas fa-times"></i></button>';

                item.querySelector('.gti-ae-remove-img').addEventListener('click', function() {
                    item.remove();
                });

                galleryPreview.appendChild(item);
            };
            reader.readAsDataURL(file);
        });
    }

    function showStep(step) {
        // Update step content
        stepContents.forEach(function(content) {
            content.classList.remove('active');
        });
        var targetContent = document.querySelector('.gti-ae-step-content[data-step="' + step + '"]');
        if (targetContent) targetContent.classList.add('active');

        // Update stepper
        steps.forEach(function(s, index) {
            var stepNum = parseInt(s.dataset.step);
            s.classList.remove('active', 'completed');

            if (stepNum < step) {
                s.classList.add('completed');
            } else if (stepNum === step) {
                s.classList.add('active');
            }
        });

        // Update step lines
        stepLines.forEach(function(line, index) {
            line.classList.remove('active');
            if (index < step - 1) {
                line.classList.add('active');
            }
        });

        // Update buttons
        if (prevBtn) prevBtn.style.display = step > 1 ? '' : 'none';
        if (nextBtn) nextBtn.style.display = step < totalSteps ? '' : 'none';
        if (submitBtn) submitBtn.style.display = step === totalSteps ? '' : 'none';

        currentStep = step;

        // Update preview summary when entering step 5
        if (step === 5) {
            updatePreviewSummary();
        }

        // Scroll to top of form
        var stepperCard = document.querySelector('.gti-ae-stepper-card');
        if (stepperCard) {
            stepperCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    function updatePreviewSummary() {
        // Title: brand + model
        var brand = getFieldValue('brand');
        var model = getFieldValue('model');
        var name = getFieldValue('name');
        var titleEl = document.getElementById('gti-ae-preview-title');
        if (titleEl) {
            var titleText = name || ((brand ? brand + ' ' : '') + (model || ''));
            titleEl.textContent = titleText || 'Equipment Name';
        }

        // Subtitle: category + brand
        var category = getFieldValue('category');
        var subtitleEl = document.getElementById('gti-ae-preview-subtitle');
        if (subtitleEl) {
            var parts = [];
            if (category) parts.push(category);
            if (brand) parts.push(brand);
            subtitleEl.textContent = parts.length ? parts.join(' \u2022 ') : 'Category \u2022 Brand';
        }

        // Year tag
        var year = getFieldValue('year');
        var yearEl = document.getElementById('gti-ae-preview-year');
        if (yearEl) {
            yearEl.textContent = year ? 'Year ' + year : 'Year —';
        }

        // Status tag
        var status = getFieldValue('status') || getFieldValue('availability_status');
        var statusEl = document.getElementById('gti-ae-preview-status');
        if (statusEl) {
            var statusMap = {
                'available': 'Ready to Work',
                'reserved': 'Reserved',
                'sold': 'Sold',
                'maintenance': 'Under Maintenance',
                'in_transit': 'In Transit',
                'under_maintenance': 'Under Maintenance'
            };
            statusEl.textContent = statusMap[status] || 'Ready to Work';
        }

        // Location
        var city = getFieldValue('location_city') || getFieldValue('location');
        var province = getFieldValue('location_province');
        var locationEl = document.getElementById('gti-ae-preview-location');
        if (locationEl) {
            var locParts = [];
            if (city) locParts.push(city);
            if (province && province !== city) locParts.push(province);
            locationEl.textContent = locParts.length ? locParts.join(', ') : 'Location';
        }

        // Key specs
        setSpecValue('gti-ae-spec-weight', getFieldValue('operating_weight'));
        setSpecValue('gti-ae-spec-bucket', getFieldValue('bucket_capacity'));
        setSpecValue('gti-ae-spec-engine', getFieldValue('engine_power'));
        setSpecValue('gti-ae-spec-hours', getFieldValue('hours') || getFieldValue('operator_hours'));
        setSpecValue('gti-ae-spec-price', getFieldValue('selling_price'));
        setSpecValue('gti-ae-spec-rental', getFieldValue('rental_price'));

        // Main image preview
        var previewImageEl = document.getElementById('gti-ae-step5-preview-image');
        var mainPreviewImg = document.getElementById('gti-ae-preview-img');
        if (previewImageEl && mainPreviewImg && mainPreviewImg.src && mainPreviewImg.src !== window.location.href) {
            previewImageEl.innerHTML = '<img src="' + mainPreviewImg.src + '" alt="Preview">';
        }
    }

    function getFieldValue(name) {
        var el = form.querySelector('[name="' + name + '"]');
        if (!el) return '';
        if (el.tagName === 'SELECT') {
            var opt = el.options[el.selectedIndex];
            return opt && opt.value ? opt.text : '';
        }
        return el.value || '';
    }

    function setSpecValue(id, value) {
        var el = document.getElementById(id);
        if (el) el.textContent = value || '—';
    }

    function nextStep(e) {
        e.preventDefault();

        if (!validateStep(currentStep)) return;

        if (currentStep < totalSteps) {
            showStep(currentStep + 1);
        }
    }

    function prevStep(e) {
        e.preventDefault();

        if (currentStep > 1) {
            showStep(currentStep - 1);
        }
    }

    function validateStep(step) {
        var content = document.querySelector('.gti-ae-step-content[data-step="' + step + '"]');
        if (!content) return true;

        var isValid = true;
        var firstInvalid = null;

        // Check required fields
        content.querySelectorAll('[required]').forEach(function(field) {
            // Remove previous error
            field.style.borderColor = '';

            if (!field.value || field.value.trim() === '') {
                isValid = false;
                field.style.borderColor = '#ef4444';
                if (!firstInvalid) firstInvalid = field;
            }
        });

        if (!isValid) {
            if (firstInvalid) firstInvalid.focus();
            showToast('Please fill in all required fields', 'error');
        }

        return isValid;
    }

    function saveDraft(e) {
        e.preventDefault();

        var formData = new FormData(form);
        formData.append('action', 'gti_save_equipment');
        formData.append('nonce', gtiAjax.nonce);
        formData.append('draft', '1');

        draftBtn.disabled = true;
        draftBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

        fetch(gtiAjax.ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                showToast(data.data.message || 'Draft saved!', 'success');
            } else {
                showToast(data.data.message || 'Failed to save draft', 'error');
            }
            draftBtn.disabled = false;
            draftBtn.innerHTML = '<i class="fas fa-save"></i> Save as Draft';
        })
        .catch(function() {
            showToast('An error occurred while saving draft', 'error');
            draftBtn.disabled = false;
            draftBtn.innerHTML = '<i class="fas fa-save"></i> Save as Draft';
        });
    }

    function handleSubmit(e) {
        e.preventDefault();

        if (!validateStep(currentStep)) return;

        var formData = new FormData(form);
        formData.append('action', 'gti_save_equipment');
        formData.append('nonce', gtiAjax.nonce);

        var submitBtnEl = document.getElementById('gti-ae-submit');

        // Disable button
        submitBtnEl.disabled = true;
        submitBtnEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

        fetch(gtiAjax.ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                showToast(data.data.message || 'Equipment added successfully!', 'success');
                setTimeout(function() {
                    window.location.href = data.data.redirect || form.querySelector('.gti-ae-btn-cancel').href;
                }, 1500);
            } else {
                showToast(data.data.message || 'Failed to save equipment', 'error');
                submitBtnEl.disabled = false;
                submitBtnEl.innerHTML = '<i class="fas fa-check"></i> Add Equipment';
            }
        })
        .catch(function() {
            showToast('An error occurred while saving', 'error');
            submitBtnEl.disabled = false;
            submitBtnEl.innerHTML = '<i class="fas fa-check"></i> Add Equipment';
        });
    }

    function showToast(message, type) {
        type = type || 'success';
        var toast = document.createElement('div');
        toast.style.cssText = 'position:fixed;bottom:20px;right:20px;padding:14px 24px;border-radius:10px;color:#fff;font-size:14px;font-weight:500;z-index:10000;animation:gtiSlideIn 0.3s ease;font-family:Inter,sans-serif;box-shadow:0 4px 12px rgba(0,0,0,0.15);';
        toast.style.background = type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#f59e0b';
        toast.textContent = message;
        document.body.appendChild(toast);

        setTimeout(function() {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.3s';
            setTimeout(function() { toast.remove(); }, 300);
        }, 3000);
    }

    // Add animation keyframes
    var style = document.createElement('style');
    style.textContent = '@keyframes gtiSlideIn{from{transform:translateX(100%);opacity:0}to{transform:translateX(0);opacity:1}}';
    document.head.appendChild(style);

    // Init on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
