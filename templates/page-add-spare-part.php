<?php
/**
 * Template: Add Spare Part (/dashboard/spare-parts/add)
 * @package global-tractors
 */
if (!defined('ABSPATH')) exit;
gti_require_login();

$current_user = wp_get_current_user();
$user_name = $current_user->display_name ?: $current_user->user_login;
$user_avatar = get_avatar_url($current_user->ID, ['size' => 80]);

// Auto-generate spare part code
global $wpdb;
$sp_table = $wpdb->prefix . 'gti_spare_parts';
$sp_year = date('Y');
$sp_cat_abbrev_map = [];
foreach (gti_spare_part_categories() as $sp_cat_name => $sp_cat_meta) {
    $sp_cat_abbrev_map[$sp_cat_name] = $sp_cat_meta['abbr'];
}
$gti_next_sp_code = 'SP-GEN-' . $sp_year . '-001';
$gti_sp_cat_map_json = wp_json_encode($sp_cat_abbrev_map);

// Handle form submission (kept as fallback — primary path is AJAX via gti_save_spare_part)
global $wpdb;
$table = $wpdb->prefix . 'gti_spare_parts';
$success = false;
$error = '';

// Note: Form submission is now handled via AJAX (gti_save_spare_part).
// This POST handler is kept only as a fallback for non-JS environments.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['gti_sp_nonce']) && wp_verify_nonce($_POST['gti_sp_nonce'], 'gti_save_spare_part') && empty($_POST['action'])) {
    $part_number = sanitize_text_field($_POST['part_number'] ?? '');
    $name = sanitize_text_field($_POST['name'] ?? '');
    $category = sanitize_text_field($_POST['category'] ?? '');
    $brand = sanitize_text_field($_POST['brand'] ?? '');
    $description = sanitize_textarea_field($_POST['description'] ?? '');
    $stock = intval($_POST['stock'] ?? 0);
    $minimum_stock = intval($_POST['minimum_stock'] ?? 10);
    $unit_price = floatval($_POST['unit_price'] ?? 0);
    $supplier = sanitize_text_field($_POST['supplier'] ?? '');
    $location = sanitize_text_field($_POST['location'] ?? '');
    $status = gti_spare_stock_status($stock, $minimum_stock);

    if (empty($part_number) || empty($name) || empty($category) || empty($brand)) {
        $error = 'Please fill in all required fields.';
    } else {
        $result = $wpdb->insert($table, [
            'part_number' => $part_number,
            'name' => $name,
            'category' => $category,
            'brand' => $brand,
            'description' => $description,
            'stock' => $stock,
            'minimum_stock' => $minimum_stock,
            'unit_price' => $unit_price,
            'supplier' => $supplier,
            'location' => $location,
            'status' => $status,
        ]);

        if ($result !== false) {
            $success = true;
        } else {
            $error = 'Failed to save spare part. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Spare Part - <?php bloginfo('name'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo GTI_CHILD_URL; ?>/assets/css/dashboard.css">
    <link rel="stylesheet" href="<?php echo GTI_CHILD_URL; ?>/assets/css/add-equipment.css?v=<?php echo GTI_VERSION; ?>">
</head>
<body class="gti-body">
    <div class="gti-wrapper">
        <!-- Sidebar -->
        <aside class="gti-sidebar" id="gti-sidebar">
            <div class="gti-sidebar-header">
                <div class="gti-logo">
                    <img src="http://global-tractors.test/wp-content/uploads/2026/07/logo-header-footer-pt-global-tractors-indonesia.png" alt="PT Global Tractors Indonesia" class="gti-logo-img">
                    <img src="http://global-tractors.test/wp-content/uploads/2026/07/cropped-favicon-pt-global-tractors-indonesia.png" alt="GTI" class="gti-logo-favicon">
                </div>
            </div>

            <div class="gti-sidebar-divider"></div>

            <nav class="gti-nav">
                <div class="gti-nav-group">
                    <a href="<?php echo esc_url(gti_dashboard_url()); ?>" class="gti-nav-item">
                        <i class="fas fa-th-large"></i>
                        <span>Dashboard</span>
                    </a>
                </div>

                <div class="gti-nav-group gti-has-children open">
                    <div class="gti-nav-section">EQUIPMENT</div>
                    <a href="#" class="gti-nav-parent" data-toggle="dropdown"><i class="fas fa-truck"></i><span>Equipment</span><i class="fas fa-chevron-down gti-nav-arrow"></i></a>
                    <div class="gti-nav-children">
                        <a href="<?php echo esc_url(gti_dashboard_url('used-equipment')); ?>" class="gti-nav-child"><i></i><span>Used Equipment</span></a>
                        <a href="<?php echo esc_url(gti_dashboard_url('rental-equipment')); ?>" class="gti-nav-child"><i></i><span>Rental Equipment</span></a>
                    </div>
                    <a href="<?php echo esc_url(gti_dashboard_url('spare-parts')); ?>" class="gti-nav-item active"><i class="fas fa-cog"></i><span>Spare Parts</span></a>
                </div>

                <div class="gti-nav-group">
                    <div class="gti-nav-section">REQUEST &amp; INQUIRY</div>
                    <a href="<?php echo esc_url(gti_dashboard_url('request-equipment')); ?>" class="gti-nav-item"><i class="fas fa-file-alt"></i><span>Request Equipment</span></a>
                    <a href="<?php echo esc_url(gti_dashboard_url('request-quotation')); ?>" class="gti-nav-item"><i class="fas fa-clipboard-list"></i><span>Request Quotation</span></a>
                    <a href="<?php echo esc_url(gti_dashboard_url('sell-equipment')); ?>" class="gti-nav-item"><i class="fas fa-handshake"></i><span>Sell Equipment</span></a>
                </div>

                <div class="gti-nav-group">
                    <div class="gti-nav-section">MANAGEMENT</div>
                    <a href="<?php echo esc_url(gti_dashboard_url('customers')); ?>" class="gti-nav-item"><i class="fas fa-users"></i><span>Customers</span></a>
                    <a href="<?php echo esc_url(gti_dashboard_url('news-articles')); ?>" class="gti-nav-item"><i class="fas fa-newspaper"></i><span>News &amp; Articles</span></a>
                    <a href="<?php echo esc_url(gti_dashboard_url('media-library')); ?>" class="gti-nav-item"><i class="fas fa-photo-video"></i><span>Media Library</span></a>
                </div>

                <div class="gti-nav-group">
                    <div class="gti-nav-section">SYSTEM</div>
                    <a href="<?php echo esc_url(gti_dashboard_url('users')); ?>" class="gti-nav-item"><i class="fas fa-user-shield"></i><span>Users</span></a>
                    <a href="<?php echo esc_url(gti_dashboard_url('activity-log')); ?>" class="gti-nav-item"><i class="fas fa-history"></i><span>Activity Log</span></a>
                </div>
            </nav>

            <div class="gti-sidebar-footer">
                <a href="#" class="gti-nav-item" id="gti-collapse-btn">
                    <i class="fas fa-chevron-left"></i>
                    <span>Collapse Menu</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="gti-main" id="gti-main">
            <!-- Header -->
            <header class="gti-header">
                <div class="gti-header-left">
                    <button class="gti-menu-toggle" id="gti-menu-toggle"><i class="fas fa-bars"></i></button>
                    <div>
                        <h1 class="gti-page-title">Add New Spare Part</h1>
                        <p class="gti-welcome">Fill in the details below to add a new spare part</p>
                    </div>
                </div>
                <div class="gti-header-right">
                    <div class="gti-date-filter">
                        <i class="fas fa-calendar"></i>
                        <span><?php echo date('M j, Y'); ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="gti-notifications">
                        <i class="fas fa-bell"></i>
                        <span class="gti-badge">3</span>
                    </div>
                    <div class="gti-user-menu">
                        <img src="<?php echo esc_url($user_avatar); ?>" alt="Avatar" class="gti-avatar">
                        <div class="gti-user-info">
                            <strong><?php echo esc_html($user_name); ?></strong>
                            <small>Super Admin</small>
                        </div>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="gti-content">
                <!-- Back Button -->
                <div class="gti-ae-back-row">
                    <a href="<?php echo esc_url(gti_dashboard_url('spare-parts')); ?>" class="gti-ae-back-btn">
                        <i class="fas fa-arrow-left"></i> Back to Spare Parts List
                    </a>
                </div>

                <?php if ($success): ?>
                    <div class="gti-ae-form-card" style="border-left: 4px solid #10b981; margin-bottom: 20px;">
                        <div class="gti-ae-card-body" style="display: flex; align-items: center; gap: 12px; padding: 16px 20px;">
                            <i class="fas fa-check-circle" style="color: #10b981; font-size: 20px;"></i>
                            <span style="color: #10b981; font-weight: 500;">Spare part saved successfully!</span>
                            <a href="<?php echo esc_url(gti_dashboard_url('spare-parts')); ?>" style="margin-left: auto; color: var(--gti-primary); font-weight: 500; text-decoration: none;">View List &rarr;</a>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="gti-ae-form-card" style="border-left: 4px solid #ef4444; margin-bottom: 20px;">
                        <div class="gti-ae-card-body" style="display: flex; align-items: center; gap: 12px; padding: 16px 20px;">
                            <i class="fas fa-exclamation-circle" style="color: #ef4444; font-size: 20px;"></i>
                            <span style="color: #ef4444; font-weight: 500;"><?php echo esc_html($error); ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Form -->
                <form class="gti-ae-form" id="gti-sp-form" method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('gti_save_spare_part', 'gti_sp_nonce'); ?>

                    <!-- Part Information -->
                    <div class="gti-ae-form-card">
                        <div class="gti-ae-card-header">
                            <h3><i class="fas fa-info-circle"></i> Part Information</h3>
                        </div>
                        <div class="gti-ae-card-body">
                            <div class="gti-ae-form-grid">
                                <div class="gti-ae-field">
                                    <label>Part Number <span class="required">*</span></label>
                                    <input type="text" name="part_number" value="<?php echo esc_attr($gti_next_sp_code); ?>" readonly required style="background:#f9fafb;cursor:not-allowed;">
                                    <small style="color:#6b7280;font-size:11px;margin-top:4px;display:block;">Auto-generated • will change when category is selected</small>
                                </div>
                                <div class="gti-ae-field">
                                    <label>Part Name <span class="required">*</span></label>
                                    <input type="text" name="name" placeholder="e.g., Filter Udara PC200" required value="<?php echo esc_attr($_POST['name'] ?? ''); ?>">
                                </div>
                                <div class="gti-ae-field">
                                    <label>Category <span class="required">*</span></label>
                                    <select name="category" required>
                                        <option value="">Select Category</option>
                                        <?php
                                        $categories = gti_spare_part_category_names();
                                        $sel_cat = $_POST['category'] ?? '';
                                        foreach ($categories as $cat):
                                        ?>
                                            <option value="<?php echo esc_attr($cat); ?>" <?php selected($sel_cat, $cat); ?>><?php echo esc_html($cat); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="gti-ae-form-grid">
                                <div class="gti-ae-field">
                                    <label>Brand <span class="required">*</span></label>
                                    <select name="brand" required>
                                        <option value="">Select Brand</option>
                                        <?php
                                        $brands = gti_spare_part_brands();
                                        $sel_brand = $_POST['brand'] ?? '';
                                        foreach ($brands as $b):
                                        ?>
                                            <option value="<?php echo esc_attr($b); ?>" <?php selected($sel_brand, $b); ?>><?php echo esc_html($b); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="gti-ae-field">
                                    <label>Supplier</label>
                                    <input type="text" name="supplier" placeholder="e.g., PT Komatsu Indonesia" value="<?php echo esc_attr($_POST['supplier'] ?? ''); ?>">
                                </div>
                                <div class="gti-ae-field">
                                    <label>Location</label>
                                    <input type="text" name="location" placeholder="e.g., Rak A1" value="<?php echo esc_attr($_POST['location'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="gti-ae-field gti-ae-field-full">
                                <label>Description</label>
                                <textarea name="description" rows="4" placeholder="Enter part description..."><?php echo esc_textarea($_POST['description'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Stock & Pricing -->
                    <div class="gti-ae-form-card">
                        <div class="gti-ae-card-header">
                            <h3><i class="fas fa-boxes"></i> Stock &amp; Pricing</h3>
                        </div>
                        <div class="gti-ae-card-body">
                            <div class="gti-ae-form-grid">
                                <div class="gti-ae-field">
                                    <label>Stock Quantity <span class="required">*</span></label>
                                    <input type="number" name="stock" placeholder="e.g., 50" min="0" required value="<?php echo esc_attr($_POST['stock'] ?? '0'); ?>">
                                </div>
                                <div class="gti-ae-field">
                                    <label>Minimum Stock</label>
                                    <input type="number" name="minimum_stock" placeholder="e.g., 10" min="0" value="<?php echo esc_attr($_POST['minimum_stock'] ?? '10'); ?>">
                                </div>
                                <div class="gti-ae-field">
                                    <label>Unit Price (Rp) <span class="required">*</span></label>
                                    <input type="number" name="unit_price" placeholder="e.g., 350000" min="0" step="1000" required value="<?php echo esc_attr($_POST['unit_price'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="gti-ae-form-grid">
                                <div class="gti-ae-field">
                                    <label>Stock Status</label>
                                    <input type="text" id="gti-sp-status-display" value="In Stock" readonly style="background:#f9fafb;cursor:not-allowed;">
                                    <small style="color:#6b7280;font-size:11px;margin-top:4px;display:block;">Derived from stock vs minimum stock</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Image Upload -->
                    <div class="gti-ae-form-card">
                        <div class="gti-ae-card-header">
                            <h3><i class="fas fa-image"></i> Part Image</h3>
                        </div>
                        <div class="gti-ae-card-body">
                            <div class="gti-ae-field gti-ae-field-full">
                                <label>Upload Image</label>
                                <div class="gti-ae-upload-area" id="gti-ae-upload-area">
                                    <i class="fas fa-cloud-upload-alt" style="font-size: 32px; color: #9ca3af; margin-bottom: 8px;"></i>
                                    <p style="color: #6b7280; font-size: 13px; margin: 0;">Drag & drop image here or click to browse</p>
                                    <input type="file" name="image" id="gti-ae-file-input" accept="image/*" style="display: none;">
                                </div>
                                <div id="gti-ae-image-preview" style="margin-top: 10px; display: none;">
                                    <img id="gti-ae-preview-img" src="" alt="Preview" style="max-width: 150px; border-radius: 8px;">
                                    <button type="button" id="gti-ae-remove-img" style="margin-left: 10px; color: #ef4444; background: none; border: none; cursor: pointer;"><i class="fas fa-trash"></i> Remove</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Navigation -->
                    <div class="gti-ae-form-nav">
                        <div></div>
                        <div class="gti-ae-form-nav-right">
                            <a href="<?php echo esc_url(gti_dashboard_url('spare-parts')); ?>" class="gti-ae-btn gti-ae-btn-cancel">Cancel</a>
                            <button type="button" class="gti-ae-btn gti-ae-btn-draft" id="gti-sp-draft">
                                <i class="fas fa-save"></i> Save as Draft
                            </button>
                            <button type="button" class="gti-ae-btn gti-ae-btn-submit" id="gti-sp-submit">
                                <i class="fas fa-check"></i> Add Spare Part
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
    var gtiAjax = gtiAjax || {
        ajaxurl: '<?php echo esc_js(admin_url('admin-ajax.php')); ?>',
        nonce: '<?php echo esc_js(wp_create_nonce('gti_nonce')); ?>',
        version: '<?php echo esc_js(GTI_VERSION); ?>'
    };
    document.addEventListener('DOMContentLoaded', function() {
        // Collapse Menu
        var collapseBtn = document.getElementById('gti-collapse-btn');
        var sidebar = document.getElementById('gti-sidebar');
        var mainEl = document.querySelector('.gti-main');

        if (collapseBtn && sidebar) {
            if (localStorage.getItem('gti-sidebar-collapsed') === 'true') {
                sidebar.classList.add('collapsed');
                if (mainEl) mainEl.classList.add('collapsed');
            }

            collapseBtn.addEventListener('click', function(e) {
                e.preventDefault();
                sidebar.classList.toggle('collapsed');
                if (mainEl) mainEl.classList.toggle('collapsed');
                localStorage.setItem('gti-sidebar-collapsed', sidebar.classList.contains('collapsed'));
            });
        }

        // Sidebar Dropdown Toggle
        document.querySelectorAll('[data-toggle="dropdown"]').forEach(function(toggle) {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                var group = this.closest('.gti-has-children');
                if (group) {
                    group.classList.toggle('open');
                }
            });
        });

        // Auto-generate spare part code on category change
        var spCatMap = <?php echo $gti_sp_cat_map_json; ?>;
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
                            window.location.href = data.data.redirect || '<?php echo esc_js(gti_dashboard_url('spare-parts')); ?>';
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
    </script>
</body>
</html>
