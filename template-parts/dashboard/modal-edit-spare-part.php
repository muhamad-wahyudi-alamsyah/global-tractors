<?php
/**
 * Inline edit modal for a spare part.
 *
 * Recovered during the R1 layout refactor: this markup sat after </main> in
 * the page template and was rendered inline there. It is a part now, requested
 * via gti_dashboard_close(['modals' => [...]]).
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>
    <div class="gti-ue-edit-overlay" id="gtiEditOverlay">
        <div class="gti-edit-backdrop" onclick="closeEditModal()"></div>

        <div class="gti-ue-edit-container">

            <!-- Header -->
            <div class="gti-ue-edit-header">
                <div class="gti-ue-edit-header-left">
                    <h2>
                        <i class="fas fa-edit"></i> Edit Spare Part
                    </h2>
                </div>

                <button
                    class="gti-ue-edit-close"
                    onclick="closeEditModal()"
                    title="Close"
                    type="button">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Body -->
            <div class="gti-ue-edit-body">

                <form
                    id="gti-edit-form"
                    method="post"
                    enctype="multipart/form-data"
                >

                    <input type="hidden" name="id" id="edit-field-id" value="">

                    <input
                        type="hidden"
                        name="action"
                        value="gti_save_spare_part"
                    >

                    <input
                        type="hidden"
                        name="nonce"
                        value="<?php echo esc_attr(wp_create_nonce('gti_nonce')); ?>"
                    >

                    <!-- ========================================= -->
                    <!-- PART INFORMATION -->
                    <!-- ========================================= -->
                    <div class="gti-ae-form-card">

                        <div class="gti-ae-card-header">
                            <h3>
                                <i class="fas fa-info-circle"></i>
                                Part Information
                            </h3>
                        </div>

                        <div class="gti-ae-card-body">

                            <!-- Row 1 -->
                            <div class="gti-ae-form-grid">

                                <div class="gti-ae-field">
                                    <label>
                                        Part Number
                                        <span class="required">*</span>
                                    </label>

                                    <input
                                        type="text"
                                        name="part_number"
                                        id="edit-field-part-number"
                                        value="<?php echo esc_attr($gti_next_sp_code); ?>"
                                        readonly required style="background:#f9fafb;cursor:not-allowed;"
                                    >
                                </div>

                                <div class="gti-ae-field">
                                    <label>
                                        Part Name
                                        <span class="required">*</span>
                                    </label>

                                    <input
                                        type="text"
                                        name="name"
                                        id="edit-field-name"
                                        required
                                    >
                                </div>

                                <div class="gti-ae-field">
                                    <label>
                                        Category
                                        <span class="required">*</span>
                                    </label>

                                    <select
                                        name="category"
                                        id="edit-field-category"
                                        required
                                    >
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

                            <!-- Row 2 -->
                            <div class="gti-ae-form-grid">

                                <div class="gti-ae-field">
                                    <label>
                                        Brand
                                        <span class="required">*</span>
                                    </label>

                                    <select
                                        name="brand"
                                        id="edit-field-brand"
                                        required
                                    >
                                        <option value="">Select Brand</option>

                                        <?php foreach (gti_spare_part_brands() as $brand_opt): ?>
                                            <option value="<?php echo esc_attr($brand_opt); ?>">
                                                <?php echo esc_html($brand_opt); ?>
                                            </option>
                                        <?php endforeach; ?>

                                    </select>
                                </div>

                                <div class="gti-ae-field">
                                    <label>Supplier</label>

                                    <input
                                        type="text"
                                        name="supplier"
                                        id="edit-field-supplier"
                                    >
                                </div>

                                <div class="gti-ae-field">
                                    <label>Location</label>

                                    <input
                                        type="text"
                                        name="location"
                                        id="edit-field-location"
                                    >
                                </div>

                            </div>

                            <!-- Description -->
                            <div class="gti-ae-field gti-ae-field-full">

                                <label>Description</label>

                                <textarea
                                    name="description"
                                    id="edit-field-description"
                                    rows="4"
                                ></textarea>

                            </div>

                        </div>
                    </div>


                    <!-- ========================================= -->
                    <!-- STOCK & PRICING -->
                    <!-- ========================================= -->
                    <div class="gti-ae-form-card">

                        <div class="gti-ae-card-header">
                            <h3>
                                <i class="fas fa-boxes"></i>
                                Stock &amp; Pricing
                            </h3>
                        </div>

                        <div class="gti-ae-card-body">

                            <!-- Stock / Minimum / Price -->
                            <div class="gti-ae-form-grid">

                                <div class="gti-ae-field">

                                    <label>
                                        Stock Quantity
                                        <span class="required">*</span>
                                    </label>

                                    <input
                                        type="number"
                                        name="stock"
                                        id="edit-field-stock"
                                        min="0"
                                        required
                                    >

                                </div>

                                <div class="gti-ae-field">

                                    <label>Minimum Stock</label>

                                    <input
                                        type="number"
                                        name="minimum_stock"
                                        id="edit-field-minimum-stock"
                                        min="0"
                                        value="10"
                                    >

                                </div>

                                <div class="gti-ae-field">

                                    <label>
                                        Unit Price (Rp)
                                        <span class="required">*</span>
                                    </label>

                                    <input
                                        type="number"
                                        name="unit_price"
                                        id="edit-field-unit-price"
                                        min="0"
                                        step="1000"
                                        required
                                    >

                                </div>

                            </div>

                            <!-- Stock Status -->
                            <div class="gti-ae-form-grid">

                                <div class="gti-ae-field">

                                    <label>Stock Status</label>

                                    <input
                                        type="text"
                                        id="gti-edit-status-display"
                                        value="In Stock"
                                        readonly
                                        style="background:#f9fafb;cursor:not-allowed;"
                                    >

                                    <small
                                        style="
                                            color:#6b7280;
                                            font-size:11px;
                                            margin-top:4px;
                                            display:block;
                                        ">
                                        Derived from stock vs minimum stock
                                    </small>

                                </div>

                            </div>

                        </div>
                    </div>


                    <!-- ========================================= -->
                    <!-- PART IMAGE -->
                    <!-- ========================================= -->
                    <div class="gti-ae-form-card">

                        <div class="gti-ae-card-header">

                            <h3>
                                <i class="fas fa-image"></i>
                                Part Image
                            </h3>

                        </div>

                        <div class="gti-ae-card-body">

                            <div class="gti-ae-field gti-ae-field-full">

                                <label>Upload Image</label>

                                <div
                                    class="gti-ae-upload-area"
                                    id="gti-edit-main-upload"
                                >

                                    <input
                                        type="file"
                                        name="image"
                                        id="gti-edit-main-image"
                                        accept="image/*"
                                        style="display:none;"
                                    >

                                    <!-- Upload Placeholder -->
                                    <div
                                        class="gti-ae-upload-placeholder"
                                        id="gti-edit-upload-placeholder"
                                    >

                                        <i class="fas fa-cloud-upload-alt"></i>

                                        <p>
                                            Drag &amp; drop image here or click to browse
                                        </p>

                                    </div>

                                    <!-- Preview -->
                                    <div
                                        class="gti-ae-upload-preview"
                                        id="gti-edit-upload-preview"
                                        style="display:none;"
                                    >

                                        <img
                                            id="gti-edit-preview-img"
                                            src=""
                                            alt="Preview"
                                        >

                                        <button
                                            type="button"
                                            class="gti-ae-remove-img"
                                            id="gti-edit-remove-img"
                                        >
                                            <i class="fas fa-times"></i>
                                        </button>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </form>

            </div>


            <!-- Footer -->
            <div class="gti-ue-edit-footer">

                <button
                    type="button"
                    class="gti-ae-btn-cancel"
                    onclick="closeEditModal()"
                >
                    Cancel
                </button>

                <button
                    type="button"
                    class="gti-ae-btn-submit"
                    id="gti-edit-submit"
                >
                    <i class="fas fa-save"></i>
                    Save Changes
                </button>

            </div>

        </div>
    </div>
