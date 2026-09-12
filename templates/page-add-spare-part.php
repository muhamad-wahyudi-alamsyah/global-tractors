<?php
/**
 * Template: Add Spare Part (/dashboard/spare-parts/add)
 * @package global-tractors
 */
if (!defined('ABSPATH')) exit;

// Auto-generate spare part code
global $wpdb;
$sp_table = $wpdb->prefix . 'gti_spare_parts';
$sp_year = date('Y');
$sp_cat_abbrev_map = [];
foreach (gti_spare_part_categories() as $sp_cat_name => $sp_cat_meta) {
    $sp_cat_abbrev_map[$sp_cat_name] = $sp_cat_meta['abbr'];
}
$gti_next_sp_code = 'SP-GEN-' . $sp_year . '-001';
// Handed to the page script as gtiPageData.categoryMap, rather than
// interpolated into an inline <script> (PRD §13.8).
gti_page_data( array( 'categoryMap' => $sp_cat_abbrev_map ) );

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

gti_dashboard_open( array(
    'page'     => 'add-spare-part',
    'title'    => 'Add Spare Part',
    'cap'      => 'gti_manage_spare_parts',
) );
?>


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

<?php
gti_dashboard_close(  );
