/**
 * Add Article page script.
 *
 * Lifted out of the template's inline <script> during the R1/R3 refactor.
 * Shared behaviour — toast, drawer, modal, action dropdown, sidebar collapse,
 * date/currency formatting and the fetch wrapper — comes from
 * assets/js/dashboard-ui.js; gtiAjax comes from wp_localize_script().
 */

document.addEventListener('DOMContentLoaded', function() {
        // ── Sidebar collapse ──
        var collapseBtn = document.getElementById('gti-collapse-btn');
        var sidebar = document.getElementById('gti-sidebar');
        var mainEl = document.querySelector('.gti-main');


        // ── Excerpt character counter ──
        var excerptField = document.querySelector('textarea[name="excerpt"]');
        var excerptCount = document.getElementById('excerpt-count');
        if (excerptField && excerptCount) {
            function updateExcerptCount() {
                excerptCount.textContent = excerptField.value.length;
                excerptCount.style.color = excerptField.value.length > 300 ? '#ef4444' : '';
            }
            excerptField.addEventListener('input', updateExcerptCount);
            updateExcerptCount();
        }

        // ── Tags Manager ──
        var tagInput = document.getElementById('tag-input');
        var tagAddBtn = document.getElementById('tag-add-btn');
        var tagsHidden = document.getElementById('tags-hidden');
        var tagsContainer = document.getElementById('tags-container');
        var tags = [];

        // Load existing tags from hidden field
        var existingTags = (tagsHidden.value || '').split(',').map(function(t) { return t.trim(); }).filter(Boolean);
        existingTags.forEach(function(tag) { addTag(tag); });

        function addTag(text) {
            text = text.trim();
            if (!text || tags.indexOf(text) !== -1) return;
            tags.push(text);
            renderTags();
            tagsHidden.value = tags.join(',');
        }

        function removeTag(index) {
            tags.splice(index, 1);
            renderTags();
            tagsHidden.value = tags.join(',');
        }

        function renderTags() {
            tagsContainer.innerHTML = tags.map(function(tag, i) {
                return '<span class="gti-na-tag">' + tag + '<button type="button" class="gti-na-tag-remove" data-index="' + i + '">&times;</button></span>';
            }).join('');
        }

        tagInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ',') {
                e.preventDefault();
                var val = this.value.replace(/,/g, '').trim();
                if (val) { addTag(val); this.value = ''; }
            }
        });

        tagAddBtn.addEventListener('click', function() {
            var val = tagInput.value.replace(/,/g, '').trim();
            if (val) { addTag(val); tagInput.value = ''; }
        });

        tagsContainer.addEventListener('click', function(e) {
            var btn = e.target.closest('.gti-na-tag-remove');
            if (btn) removeTag(parseInt(btn.getAttribute('data-index'), 10));
        });

        // ── Featured Image Upload ──
        var uploadArea = document.getElementById('na-upload-area');
        var fileInput = document.getElementById('na-file-input');
        var preview = document.getElementById('na-preview');
        var previewImg = document.getElementById('na-preview-img');
        var removeBtn = document.getElementById('na-remove-img');

        if (uploadArea && fileInput) {
            uploadArea.addEventListener('click', function() { fileInput.click(); });

            uploadArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                uploadArea.style.borderColor = '#F5A623';
                uploadArea.style.background = '#fffbeb';
            });

            uploadArea.addEventListener('dragleave', function() {
                uploadArea.style.borderColor = '#d1d5db';
                uploadArea.style.background = '#fafafa';
            });

            uploadArea.addEventListener('drop', function(e) {
                e.preventDefault();
                uploadArea.style.borderColor = '#d1d5db';
                uploadArea.style.background = '#fafafa';
                if (e.dataTransfer.files.length) {
                    fileInput.files = e.dataTransfer.files;
                    showPreview(e.dataTransfer.files[0]);
                }
            });

            fileInput.addEventListener('change', function() {
                if (this.files.length) showPreview(this.files[0]);
            });
        }

        function showPreview(file) {
            if (file && file.type.startsWith('image/')) {
                if (file.size > 5 * 1024 * 1024) {
                    alert('File too large. Maximum size is 5MB.');
                    return;
                }
                var reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    preview.style.display = 'block';
                    uploadArea.style.display = 'none';
                };
                reader.readAsDataURL(file);
            }
        }

        if (removeBtn) {
            removeBtn.addEventListener('click', function() {
                fileInput.value = '';
                previewImg.src = '';
                preview.style.display = 'none';
                uploadArea.style.display = '';
                // Tells the server to detach the stored featured image.
                var removeFlag = document.getElementById('na-remove-flag');
                if (removeFlag) removeFlag.value = '1';
            });
        }

        // Choosing a new file cancels a pending removal.
        if (fileInput) {
            fileInput.addEventListener('change', function() {
                var removeFlag = document.getElementById('na-remove-flag');
                if (removeFlag) removeFlag.value = '';
            });
        }
    });
