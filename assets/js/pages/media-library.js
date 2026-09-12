/**
 * Media Library page script.
 *
 * Lifted out of the template's inline <script> during the R1/R3 refactor.
 * Shared behaviour — toast, drawer, modal, action dropdown, sidebar collapse,
 * date/currency formatting and the fetch wrapper — comes from
 * assets/js/dashboard-ui.js; gtiAjax comes from wp_localize_script().
 */

(function() {
        // Sidebar collapse
        var collapseBtn = document.getElementById('gti-collapse-btn');
        var sidebar = document.getElementById('gti-sidebar');
        var mainEl = document.getElementById('gti-main');

        // Backdrop close
        document.getElementById('drawerBackdrop').addEventListener('click', closeMediaDrawer);
        document.addEventListener('keydown', function(e) { if (e.key === 'Escape') { closeMediaDrawer(); closeUploadModal(); } });

        // View toggle — actually switches layout, and the choice sticks.
        var mediaGrid = document.getElementById('mediaGrid');
        function applyMediaView(view) {
            if (!mediaGrid) return;
            mediaGrid.classList.toggle('is-list', view === 'list');
            document.querySelectorAll('.gti-ml-view-btn').forEach(function(b) {
                b.classList.toggle('active', b.getAttribute('data-view') === view);
            });
        }
        applyMediaView(localStorage.getItem('gti-media-view') || 'grid');

        document.querySelectorAll('.gti-ml-view-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var view = this.getAttribute('data-view');
                localStorage.setItem('gti-media-view', view);
                applyMediaView(view);
            });
        });

        // Upload button (hidden for users without the upload_files capability)
        var uploadBtn = document.getElementById('uploadMediaBtn');
        if (uploadBtn) {
            uploadBtn.addEventListener('click', function() {
                document.getElementById('uploadModal').style.display = 'flex';
            });
        }

        // Drop zone
        var dz = document.getElementById('uploadDropZone');
        var fi = document.getElementById('fileInput');
        dz.addEventListener('click', function() { fi.click(); });
        dz.addEventListener('dragover', function(e) { e.preventDefault(); dz.style.borderColor = '#F5A623'; dz.style.background = '#FFF8EC'; });
        dz.addEventListener('dragleave', function() { dz.style.borderColor = '#d1d5db'; dz.style.background = ''; });
        dz.addEventListener('drop', function(e) { e.preventDefault(); dz.style.borderColor = '#d1d5db'; dz.style.background = ''; handleFiles(e.dataTransfer.files); });
        fi.addEventListener('change', function() { handleFiles(this.files); });

        var _pendingFiles = [];
        function handleFiles(files) {
            _pendingFiles = Array.from(files);
            if (_pendingFiles.length > 0) {
                document.getElementById('startUploadBtn').disabled = false;
                document.getElementById('uploadStatus').textContent = _pendingFiles.length + ' file(s) selected';
            }
        }

        document.getElementById('startUploadBtn').addEventListener('click', function() {
            if (_pendingFiles.length === 0) return;
            var formData = new FormData();
            _pendingFiles.forEach(function(f) { formData.append('files[]', f); });
            formData.append('action', 'gti_upload_media');
            formData.append('nonce', (typeof gtiAjax !== 'undefined' ? gtiAjax.nonce : ''));

            document.getElementById('uploadProgress').style.display = 'block';
            document.getElementById('uploadStatus').textContent = 'Uploading...';

            fetch((typeof gtiAjax !== 'undefined' ? gtiAjax.ajaxurl : '/wp-admin/admin-ajax.php'), {
                method: 'POST',
                body: formData
            }).then(function(r) { return r.json(); }).then(function(res) {
                document.getElementById('uploadProgressBar').style.width = '100%';
                var msg = res.data && res.data.message ? res.data.message : (res.success ? 'Upload complete!' : 'Upload failed. Please try again.');
                document.getElementById('uploadStatus').textContent = res.success ? msg + ' Reloading...' : msg;
                if (res.success) { setTimeout(function() { location.reload(); }, 1200); }
            }).catch(function() {
                document.getElementById('uploadStatus').textContent = 'Upload failed. Please try again.';
            });
        });
    })();

    var _currentMedia = null;

    // Open media detail drawer
    function openMediaDetail(el) {
        var data;
        try { data = JSON.parse(el.getAttribute('data-media')); } catch(e) { return; }
        _currentMedia = data;

        // Mark active card
        document.querySelectorAll('.gti-ml-card').forEach(function(c) { c.classList.remove('active'); });
        el.classList.add('active');

        // Populate drawer
        document.getElementById('drawer-filename').textContent = data.filename;
        var badge = document.getElementById('drawer-type-badge');
        badge.textContent = data.type.toUpperCase();
        badge.className = 'gti-drawer-status ' + (data.type === 'image' ? 'status-new' : 'status-processing');

        // Preview
        var preview = document.getElementById('drawer-preview');
        if (data.type === 'image') {
            preview.innerHTML = '<img src="' + data.url + '" alt="' + data.filename + '">';
        } else {
            preview.innerHTML = '<i class="fas fa-file-alt gti-ml-doc-icon-lg"></i>';
        }

        document.getElementById('drawer-filename-val').textContent = data.filename;
        document.getElementById('drawer-filetype').textContent = data.ext + ' (' + data.type + ')';
        document.getElementById('drawer-filesize').textContent = data.size_human;
        document.getElementById('drawer-uploaded').textContent = data.date_human;
        document.getElementById('drawer-dims').textContent = data.dims ? data.dims[0] + ' × ' + data.dims[1] + ' px' : '-';
        document.getElementById('drawer-title-val').textContent = data.title || '-';
        document.getElementById('drawer-alt').textContent = data.alt || '-';
        document.getElementById('drawer-uploader').textContent = data.uploader || '-';
        document.getElementById('drawer-attached').textContent = data.attached_to || 'Unattached';
        document.getElementById('drawer-url').textContent = data.url;
        document.getElementById('drawer-url').setAttribute('data-url', data.url);
        document.getElementById('drawer-download').href = data.url;

        // Open drawer on small screens
        if (window.innerWidth < 1600) {
            document.getElementById('mediaDetailDrawer').classList.add('open');
            document.getElementById('drawerBackdrop').classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeMediaDrawer() {
        document.getElementById('mediaDetailDrawer').classList.remove('open');
        document.getElementById('drawerBackdrop').classList.remove('show');
        document.body.style.overflow = '';
        document.querySelectorAll('.gti-ml-card').forEach(function(c) { c.classList.remove('active'); });
    }

    function closeUploadModal() {
        document.getElementById('uploadModal').style.display = 'none';
    }

    // Permanently remove the attachment, its resized files included.
    document.getElementById('drawer-delete').addEventListener('click', function() {
        if (!_currentMedia) return;
        if (!confirm('Permanently delete "' + _currentMedia.filename + '"? This cannot be undone.')) return;

        var btn = this;
        btn.disabled = true;

        var formData = new FormData();
        formData.append('action', 'gti_delete_media');
        formData.append('id', _currentMedia.id);
        formData.append('nonce', (typeof gtiAjax !== 'undefined' ? gtiAjax.nonce : ''));

        fetch((typeof gtiAjax !== 'undefined' ? gtiAjax.ajaxurl : '/wp-admin/admin-ajax.php'), {
            method: 'POST',
            body: formData
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            btn.disabled = false;
            if (res.success) {
                location.reload();
            } else {
                alert(res.data && res.data.message ? res.data.message : 'Failed to delete the file.');
            }
        })
        .catch(function() {
            btn.disabled = false;
            alert('An error occurred. Please try again.');
        });
    });

    function copyUrl(el) {
        var url = el.getAttribute('data-url') || el.textContent;
        if (navigator.clipboard) {
            navigator.clipboard.writeText(url).then(function() {
                var orig = el.textContent;
                el.textContent = 'URL copied!';
                setTimeout(function() { el.textContent = orig; }, 1500);
            });
        }
    }
