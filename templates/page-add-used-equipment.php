<?php
/**
 * Template: Add New Equipment (/dashboard/used-equipment/add)
 * @package global-tractors
 */
if (!defined('ABSPATH')) exit;

// Auto-generate equipment code
global $wpdb;
$table = $wpdb->prefix . 'gti_equipment';
$year = date('Y');
$cat_abbrev_map = [
    'Excavator'     => 'EXC', 'Bulldozer'     => 'BLD', 'Wheel Loader'  => 'WLD',
    'Dump Truck'    => 'DMP', 'Motor Grader'  => 'MGR', 'Crane'         => 'CRN',
    'Compactor'     => 'CMP',
];
$gti_next_code = '';
// Default first code
$gti_next_code = 'GTI-GEN-' . $year . '-001';

// Category abbrev map for JS
// Handed to the page script as gtiPageData.categoryMap, rather than
// interpolated into an inline <script> (PRD §13.8).
gti_page_data( array( 'categoryMap' => $cat_abbrev_map ) );

// AJAX endpoint to get next code
$gti_ajax_url = admin_url('admin-ajax.php');

gti_dashboard_open( array(
    'page'     => 'add-used-equipment',
    'title'    => 'Add Used Equipment',
    'cap'      => 'gti_manage_equipment',
) );
?>


            <!-- Content -->
            <div class="gti-content">
                <!-- Back Button -->
                <div class="gti-ae-back-row">
                    <a href="<?php echo esc_url(gti_dashboard_url('used-equipment')); ?>" class="gti-ae-back-btn">
                        <i class="fas fa-arrow-left"></i> Back to Equipment List
                    </a>
                </div>

                <!-- Multi-Step Form -->
                <form class="gti-ae-form" id="gti-ae-form" method="post" enctype="multipart/form-data">
                    <!-- Stepper -->
                    <div class="gti-ae-stepper-card">
                        <div class="gti-ae-stepper">
                            <div class="gti-ae-step active" data-step="1">
                                <div class="gti-ae-step-circle">1</div>
                                <div class="gti-ae-step-label">General Info</div>
                            </div>
                            <div class="gti-ae-step-line"></div>
                            <div class="gti-ae-step" data-step="2">
                                <div class="gti-ae-step-circle">2</div>
                                <div class="gti-ae-step-label">Specifications</div>
                            </div>
                            <div class="gti-ae-step-line"></div>
                            <div class="gti-ae-step" data-step="3">
                                <div class="gti-ae-step-circle">3</div>
                                <div class="gti-ae-step-label">Pricing</div>
                            </div>
                            <div class="gti-ae-step-line"></div>
                            <div class="gti-ae-step" data-step="4">
                                <div class="gti-ae-step-circle">4</div>
                                <div class="gti-ae-step-label">Images & Media</div>
                            </div>
                            <div class="gti-ae-step-line"></div>
                            <div class="gti-ae-step" data-step="5">
                                <div class="gti-ae-step-circle">5</div>
                                <div class="gti-ae-step-label">Additional Info</div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 1: General Information -->
                    <div class="gti-ae-step-content active" data-step="1">
                        <!-- General Information -->
                        <div class="gti-ae-form-card">
                            <div class="gti-ae-card-header">
                                <h3><i class="fas fa-info-circle"></i> General Information</h3>
                            </div>
                            <div class="gti-ae-card-body">
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field">
                                        <label>Equipment Name <span class="required">*</span></label>
                                        <input type="text" name="name" placeholder="e.g., Komatsu PC200-8" required>
                                    </div>
                                    <div class="gti-ae-field">
                                        <label>Equipment Code <span class="required">*</span></label>
                                        <input type="text" name="equipment_code" value="<?php echo esc_attr($gti_next_code); ?>" readonly required style="background:#f9fafb;cursor:not-allowed;">
                                        <small style="color:#6b7280;font-size:11px;margin-top:4px;display:block;">Auto-generated • will change when category is selected</small>
                                    </div>
                                    <div class="gti-ae-field">
                                        <label>Category <span class="required">*</span></label>
                                        <select name="category" required>
                                            <option value="">Select Category</option>
                                            <option value="Excavator">Excavator</option>
                                            <option value="Bulldozer">Bulldozer</option>
                                            <option value="Wheel Loader">Wheel Loader</option>
                                            <option value="Dump Truck">Dump Truck</option>
                                            <option value="Motor Grader">Motor Grader</option>
                                            <option value="Crane">Crane</option>
                                            <option value="Compactor">Compactor</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field">
                                        <label>Brand <span class="required">*</span></label>
                                        <select name="brand" required>
                                            <option value="">Select Brand</option>
                                            <option value="KOMATSU">KOMATSU</option>
                                            <option value="CATERPILLAR">CATERPILLAR</option>
                                            <option value="HITACHI">HITACHI</option>
                                            <option value="VOLVO">VOLVO</option>
                                            <option value="KOBELCO">KOBELCO</option>
                                            <option value="DOOSAN">DOOSAN</option>
                                            <option value="HYUNDAI">HYUNDAI</option>
                                        </select>
                                    </div>
                                    <div class="gti-ae-field">
                                        <label>Model <span class="required">*</span></label>
                                        <input type="text" name="model" placeholder="e.g., PC200-8" required>
                                    </div>
                                    <div class="gti-ae-field">
                                        <label>Year <span class="required">*</span></label>
                                        <input type="number" name="year" placeholder="e.g., 2020" min="1900" max="<?php echo date('Y') + 1; ?>" required>
                                    </div>
                                </div>
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field">
                                        <label>Operating Hours</label>
                                        <input type="number" name="hours" placeholder="e.g., 5200">
                                    </div>
                                    <div class="gti-ae-field">
                                        <label>Serial Number</label>
                                        <input type="text" name="serial_number" placeholder="e.g., SN-202001234">
                                    </div>
                                    <div class="gti-ae-field">
                                        <label>Condition <span class="required">*</span></label>
                                        <select name="condition_status" required>
                                            <option value="">Select Condition</option>
                                            <option value="excellent">Excellent</option>
                                            <option value="good">Good</option>
                                            <option value="fair">Fair</option>
                                            <option value="poor">Poor</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field">
                                        <label>Engine</label>
                                        <input type="text" name="engine" placeholder="e.g., Cummins QSB6.7">
                                    </div>
                                    <div class="gti-ae-field">
                                        <label>Engine Power</label>
                                        <input type="text" name="engine_power" placeholder="e.g., 155 HP">
                                    </div>
                                    <div class="gti-ae-field">
                                        <label>Country of Origin</label>
                                        <select name="origin_country">
                                            <option value="">Select Origin</option>
                                            <option value="Japan">Japan</option>
                                            <option value="USA">USA</option>
                                            <option value="South Korea">South Korea</option>
                                            <option value="China">China</option>
                                            <option value="Germany">Germany</option>
                                            <option value="Sweden">Sweden</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Basic Information -->
                        <div class="gti-ae-form-card">
                            <div class="gti-ae-card-header">
                                <h3><i class="fas fa-clipboard-list"></i> Basic Information</h3>
                            </div>
                            <div class="gti-ae-card-body">
                                <!-- operating_weight & bucket_capacity live in Step 2 (Specifications).
                                     Never duplicate a name= within this form: FormData sends both and PHP keeps the last one. -->
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field">
                                        <label>Equipment Type</label>
                                        <select name="type">
                                            <option value="">Select Type</option>
                                            <option value="used">Used Equipment</option>
                                            <option value="rental">Rental Equipment</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field">
                                        <label>Location</label>
                                        <select name="location">
                                            <option value="">Select Location</option>
                                            <option value="Jakarta">Jakarta</option>
                                            <option value="Balikpapan">Balikpapan</option>
                                            <option value="Surabaya">Surabaya</option>
                                            <option value="Bandung">Bandung</option>
                                            <option value="Medan">Medan</option>
                                            <option value="Makassar">Makassar</option>
                                        </select>
                                    </div>
                                    <div class="gti-ae-field">
                                        <label>Availability Status <span class="required">*</span></label>
                                        <select name="status" required>
                                            <option value="">Select Status</option>
                                            <option value="available">Available</option>
                                            <option value="sold">Sold</option>
                                            <option value="reserved">Reserved</option>
                                            <option value="maintenance">Maintenance</option>
                                        </select>
                                    </div>
                                    <div class="gti-ae-field">
                                        <label>Stock Number (Internal)</label>
                                        <input type="text" name="stock_number" placeholder="e.g., STK-001">
                                    </div>
                                </div>
                                <div class="gti-ae-field gti-ae-field-full">
                                    <label>Description <span class="required">*</span></label>
                                    <textarea name="description" rows="4" placeholder="Enter equipment description..." required></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Specifications -->
                    <div class="gti-ae-step-content" data-step="2">
                        <!-- Technical Specifications -->
                        <div class="gti-ae-form-card">
                            <div class="gti-ae-card-header">
                                <h3><i class="fas fa-cogs"></i> Technical Specifications</h3>
                            </div>
                            <div class="gti-ae-card-body">
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field">
                                        <label>Operating Weight <span class="required">*</span></label>
                                        <input type="text" name="operating_weight" placeholder="e.g., 20 ton" required>
                                    </div>
                                    <div class="gti-ae-field">
                                        <label>Bucket Capacity <span class="required">*</span></label>
                                        <input type="text" name="bucket_capacity" placeholder="e.g., 1.0 m³" required>
                                    </div>
                                    <div class="gti-ae-field">
                                        <label>Engine Model</label>
                                        <input type="text" name="engine_model" placeholder="e.g., QSB6.7-C150">
                                    </div>
                                </div>
                                <!-- engine_power lives in Step 1 (General Information) — do not duplicate it here. -->
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field">
                                        <label>Displacement</label>
                                        <input type="text" name="displacement" placeholder="e.g., 6.7 L">
                                    </div>
                                    <div class="gti-ae-field">
                                        <label>No. of Cylinders</label>
                                        <input type="number" name="cylinders" placeholder="e.g., 6">
                                    </div>
                                </div>
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field">
                                        <label>Hydraulic System</label>
                                        <input type="text" name="hydraulic_system" placeholder="e.g., Variable displacement">
                                    </div>
                                    <div class="gti-ae-field">
                                        <label>Hydraulic Pump Flow</label>
                                        <input type="text" name="hydraulic_pump_flow" placeholder="e.g., 2 x 200 L/min">
                                    </div>
                                    <div class="gti-ae-field">
                                        <label>Max. Digging Depth</label>
                                        <input type="text" name="max_digging_depth" placeholder="e.g., 6.5m">
                                    </div>
                                </div>
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field">
                                        <label>Max. Digging Height</label>
                                        <input type="text" name="max_digging_height" placeholder="e.g., 9.8m">
                                    </div>
                                    <div class="gti-ae-field">
                                        <label>Max. Reach at Ground Level</label>
                                        <input type="text" name="max_reach_ground" placeholder="e.g., 9.5m">
                                    </div>
                                    <div class="gti-ae-field">
                                        <label>Max. Dumping Height</label>
                                        <input type="text" name="max_dumping_height" placeholder="e.g., 6.8m">
                                    </div>
                                </div>
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field">
                                        <label>Travel Speed (High/Low)</label>
                                        <input type="text" name="travel_speed" placeholder="e.g., 5.5 / 3.0 km/h">
                                    </div>
                                    <div class="gti-ae-field">
                                        <label>Swing Speed</label>
                                        <input type="text" name="swing_speed" placeholder="e.g., 12.4 rpm">
                                    </div>
                                    <div class="gti-ae-field">
                                        <label>Fuel Tank Capacity</label>
                                        <input type="text" name="fuel_tank_capacity" placeholder="e.g., 400 L">
                                    </div>
                                </div>
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field">
                                        <label>Hydraulic Tank Capacity</label>
                                        <input type="text" name="hydraulic_tank_capacity" placeholder="e.g., 180 L">
                                    </div>
                                    <div class="gti-ae-field">
                                        <label>Track Width</label>
                                        <input type="text" name="track_width" placeholder="e.g., 600 mm">
                                    </div>
                                    <div class="gti-ae-field">
                                        <label>Ground Pressure</label>
                                        <input type="text" name="ground_pressure" placeholder="e.g., 0.45 kg/cm²">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Features & Configurations -->
                        <div class="gti-ae-form-card">
                            <div class="gti-ae-card-header">
                                <h3><i class="fas fa-sliders-h"></i> Features & Configurations</h3>
                            </div>
                            <div class="gti-ae-card-body">
                                <div class="gti-ae-features-grid">
                                    <label class="gti-ae-feature-item">
                                        <input type="checkbox" name="features[air_conditioner]" value="1">
                                        <span class="gti-ae-feature-check"></span>
                                        <span>Air Conditioner</span>
                                    </label>
                                    <label class="gti-ae-feature-item">
                                        <input type="checkbox" name="features[backup_alarm]" value="1">
                                        <span class="gti-ae-feature-check"></span>
                                        <span>Backup Alarm</span>
                                    </label>
                                    <label class="gti-ae-feature-item">
                                        <input type="checkbox" name="features[led_work_light]" value="1">
                                        <span class="gti-ae-feature-check"></span>
                                        <span>LED Work Light</span>
                                    </label>
                                    <label class="gti-ae-feature-item">
                                        <input type="checkbox" name="features[camera]" value="1">
                                        <span class="gti-ae-feature-check"></span>
                                        <span>Camera</span>
                                    </label>
                                    <label class="gti-ae-feature-item">
                                        <input type="checkbox" name="features[auto_idle]" value="1">
                                        <span class="gti-ae-feature-check"></span>
                                        <span>Auto Idle</span>
                                    </label>
                                    <label class="gti-ae-feature-item">
                                        <input type="checkbox" name="features[hammer_line]" value="1">
                                        <span class="gti-ae-feature-check"></span>
                                        <span>Hammer Line</span>
                                    </label>
                                    <label class="gti-ae-feature-item">
                                        <input type="checkbox" name="features[quick_coupler]" value="1">
                                        <span class="gti-ae-feature-check"></span>
                                        <span>Quick Coupler</span>
                                    </label>
                                    <label class="gti-ae-feature-item">
                                        <input type="checkbox" name="features[gps_system]" value="1">
                                        <span class="gti-ae-feature-check"></span>
                                        <span>GPS System</span>
                                    </label>
                                    <label class="gti-ae-feature-item">
                                        <input type="checkbox" name="features[centralized_greasing]" value="1">
                                        <span class="gti-ae-feature-check"></span>
                                        <span>Centralized Greasing</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Pricing & Status -->
                    <div class="gti-ae-step-content" data-step="3">
                        <!-- Pricing Information -->
                        <div class="gti-ae-form-card">
                            <div class="gti-ae-card-header">
                                <h3><i class="fas fa-tag"></i> Pricing Information</h3>
                            </div>
                            <div class="gti-ae-card-body">
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field">
                                        <label>Selling Price (IDR)</label>
                                        <input type="text" name="selling_price" placeholder="e.g., 850.000.000" inputmode="numeric">
                                    </div>
                                    <div class="gti-ae-field">
                                        <label>Rental Price (IDR/Month)</label>
                                        <input type="text" name="rental_price" placeholder="e.g., 25.000.000" inputmode="numeric">
                                    </div>
                                    <div class="gti-ae-field">
                                        <label>Price Type <span class="required">*</span></label>
                                        <select name="price_type" required>
                                            <option value="">-- Select Price Type --</option>
                                            <option value="sale">For Sale</option>
                                            <option value="rental">For Rental</option>
                                            <option value="sale_and_rental">Sale & Rental</option>
                                            <option value="price_on_ask">Price on Ask</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field">
                                        <label>VAT Included?</label>
                                        <select name="vat_included">
                                            <option value="">-- Select --</option>
                                            <option value="yes">Yes</option>
                                            <option value="no">No</option>
                                        </select>
                                    </div>
                                    <div class="gti-ae-field">
                                        <label>Currency</label>
                                        <select name="currency">
                                            <option value="IDR">IDR (Rp)</option>
                                            <option value="USD">USD ($)</option>
                                            <option value="EUR">EUR (€)</option>
                                        </select>
                                    </div>
                                    <div class="gti-ae-field">
                                        <label>Price Valid Until</label>
                                        <input type="date" name="price_valid_until">
                                    </div>
                                    <div class="gti-ae-field">
                                        <label>Negotiable</label>
                                        <select name="negotiable">
                                            <option value="">-- Select --</option>
                                            <option value="yes">Yes</option>
                                            <option value="no">No</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="gti-ae-price-guidelines">
                                    <i class="fas fa-info-circle"></i>
                                    <div>
                                        <strong>Price Guidelines</strong>
                                        <ul>
                                            <li>Pastikan harga sesuai dengan kondisi unit dan pasar saat ini.</li>
                                            <li>Harga akan ditampilkan di website sesuai status publikasi.</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Status & Availability -->
                        <div class="gti-ae-form-card">
                            <div class="gti-ae-card-header">
                                <h3><i class="fas fa-clipboard-check"></i> Status & Availability</h3>
                            </div>
                            <div class="gti-ae-card-body">
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field">
                                        <label>Availability Status</label>
                                        <select name="availability_status">
                                            <option value="">-- Select Status --</option>
                                            <option value="available">Available</option>
                                            <option value="reserved">Reserved</option>
                                            <option value="sold">Sold</option>
                                            <option value="in_transit">In Transit</option>
                                            <option value="under_maintenance">Under Maintenance</option>
                                        </select>
                                    </div>
                                </div>
                                <!-- stock_number, condition_status and location are captured in Step 1 — duplicating them here
                                     silently overwrote the Step 1 values on submit. -->
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field">
                                        <label>Ready to Use</label>
                                        <select name="ready_to_use">
                                            <option value="">-- Select --</option>
                                            <option value="yes">Yes</option>
                                            <option value="no">No</option>
                                            <option value="after_service">After Service</option>
                                        </select>
                                    </div>
                                    <div class="gti-ae-field">
                                        <label>Service History</label>
                                        <select name="service_history">
                                            <option value="">-- Select --</option>
                                            <option value="full">Full Service History</option>
                                            <option value="partial">Partial Service History</option>
                                            <option value="none">No Service History</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field">
                                        <label>Warranty Available?</label>
                                        <select name="warranty_available" id="gti-ae-warranty-toggle">
                                            <option value="">-- Select --</option>
                                            <option value="yes">Yes</option>
                                            <option value="no">No</option>
                                        </select>
                                    </div>
                                    <div class="gti-ae-field">
                                        <label>Warranty Period</label>
                                        <input type="text" name="warranty_period" placeholder="e.g., 6 months / 1000 hours" id="gti-ae-warranty-period">
                                    </div>
                                </div>
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field gti-ae-field-full">
                                        <label>Notes for Buyers <span style="font-weight:400;color:#9ca3af;">(Optional)</span></label>
                                        <textarea name="buyer_notes" rows="3" placeholder="Additional information for potential buyers, e.g., recent repairs, included attachments, special conditions..."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 4: Images & Media -->
                    <div class="gti-ae-step-content" data-step="4">
                        <div class="gti-ae-form-card">
                            <div class="gti-ae-card-header">
                                <h3><i class="fas fa-images"></i> Images & Media</h3>
                            </div>
                            <div class="gti-ae-card-body">
                                <div class="gti-ae-field gti-ae-field-full">
                                    <label>Main Image <span class="required">*</span></label>
                                    <div class="gti-ae-upload-area" id="gti-ae-main-upload">
                                        <input type="file" name="main_image" id="gti-ae-main-image" accept="image/*" style="display:none;">
                                        <div class="gti-ae-upload-placeholder" id="gti-ae-upload-placeholder">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                            <p>Click or drag image here to upload</p>
                                            <span>Recommended: 800x600px, JPG/PNG, max 5MB</span>
                                        </div>
                                        <div class="gti-ae-upload-preview" id="gti-ae-upload-preview" style="display:none;">
                                            <img id="gti-ae-preview-img" src="" alt="Preview">
                                            <button type="button" class="gti-ae-remove-img" id="gti-ae-remove-img">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="gti-ae-field gti-ae-field-full">
                                    <label>Additional Images</label>
                                    <div class="gti-ae-upload-area" id="gti-ae-gallery-upload">
                                        <input type="file" name="gallery_images[]" id="gti-ae-gallery-images" accept="image/*" multiple style="display:none;">
                                        <div class="gti-ae-upload-placeholder">
                                            <i class="fas fa-images"></i>
                                            <p>Click or drag multiple images here</p>
                                            <span>You can select up to 10 images</span>
                                        </div>
                                    </div>
                                    <div class="gti-ae-gallery-preview" id="gti-ae-gallery-preview"></div>
                                </div>

                                <div class="gti-ae-field gti-ae-field-full">
                                    <label>Video <span style="font-weight:400;color:#9ca3af;">(Optional)</span></label>
                                    <div class="gti-ae-upload-area" id="gti-ae-video-upload">
                                        <input type="file" name="video_file" id="gti-ae-video-file" accept="video/*" style="display:none;">
                                        <div class="gti-ae-upload-placeholder" id="gti-ae-video-placeholder">
                                            <i class="fas fa-video"></i>
                                            <p>Click or drag video here to upload</p>
                                            <span>MP4, MOV, AVI — max 50MB</span>
                                        </div>
                                        <div class="gti-ae-upload-preview" id="gti-ae-video-preview" style="display:none;">
                                            <div class="gti-ae-video-thumb">
                                                <i class="fas fa-play-circle"></i>
                                                <span id="gti-ae-video-name"></span>
                                            </div>
                                            <button type="button" class="gti-ae-remove-img" id="gti-ae-remove-video">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 5: Additional Information -->
                    <div class="gti-ae-step-content" data-step="5">
                        <div class="gti-ae-step5-layout">

                            <!-- LEFT COLUMN -->
                            <div class="gti-ae-step5-left">

                                <!-- Additional Information -->
                                <div class="gti-ae-form-card">
                                    <div class="gti-ae-card-header">
                                        <h3><i class="fas fa-clipboard-list"></i> Additional Information</h3>
                                    </div>
                                    <div class="gti-ae-card-body">
                                        <!-- Description -->
                                        <div class="gti-ae-field gti-ae-field-full">
                                            <label>Description (Detailed)</label>
                                            <textarea name="detailed_description" rows="4" placeholder="Describe the equipment in detail — condition, history, included attachments, etc."></textarea>
                                        </div>

                                        <!-- Equipment History -->
                                        <div class="gti-ae-field gti-ae-field-full">
                                            <label>Equipment History</label>
                                            <textarea name="equipment_history" rows="4" placeholder="Describe the equipment history — previous usage, working condition, maintenance record, etc."></textarea>
                                        </div>

                                        <div class="gti-ae-form-grid">
                                            <div class="gti-ae-field">
                                                <label>Previous Usage</label>
                                                <input type="text" name="previous_usage" placeholder="e.g., Mining, Construction">
                                            </div>
                                            <div class="gti-ae-field">
                                                <label>Working Condition</label>
                                                <select name="working_condition">
                                                    <option value="">Select Condition</option>
                                                    <option value="excellent">Excellent</option>
                                                    <option value="good">Good</option>
                                                    <option value="fair">Fair</option>
                                                    <option value="poor">Poor</option>
                                                </select>
                                            </div>
                                            <div class="gti-ae-field">
                                                <label>Maintenance Record</label>
                                                <select name="maintenance_record">
                                                    <option value="">Select</option>
                                                    <option value="full">Full Record</option>
                                                    <option value="partial">Partial Record</option>
                                                    <option value="none">No Record</option>
                                                </select>
                                            </div>
                                            <div class="gti-ae-field">
                                                <label>Ownership</label>
                                                <select name="ownership">
                                                    <option value="">Select</option>
                                                    <option value="first_owner">First Owner</option>
                                                    <option value="second_owner">Second Owner</option>
                                                    <option value="third_plus">Third Owner or More</option>
                                                    <option value="company">Company Fleet</option>
                                                </select>
                                            </div>
                                            <div class="gti-ae-field">
                                                <label>Operator Hours</label>
                                                <input type="number" name="operator_hours" placeholder="e.g., 5200">
                                            </div>
                                            <div class="gti-ae-field">
                                                <label>Last Service Date</label>
                                                <input type="date" name="last_service_date">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Document Availability -->
                                <div class="gti-ae-form-card">
                                    <div class="gti-ae-card-header">
                                        <h3><i class="fas fa-file-alt"></i> Document Availability</h3>
                                    </div>
                                    <div class="gti-ae-card-body">
                                        <div class="gti-ae-doc-grid">
                                            <label class="gti-ae-doc-item">
                                                <input type="checkbox" name="documents[unit_certificate]" value="1">
                                                <span class="gti-ae-doc-check"></span>
                                                <span class="gti-ae-doc-label">Unit Certificate (STNK/BPKB)</span>
                                            </label>
                                            <label class="gti-ae-doc-item">
                                                <input type="checkbox" name="documents[import_document]" value="1">
                                                <span class="gti-ae-doc-check"></span>
                                                <span class="gti-ae-doc-label">Import Document</span>
                                            </label>
                                            <label class="gti-ae-doc-item">
                                                <input type="checkbox" name="documents[service_maintenance_record]" value="1">
                                                <span class="gti-ae-doc-check"></span>
                                                <span class="gti-ae-doc-label">Service & Maintenance Record</span>
                                            </label>
                                            <label class="gti-ae-doc-item">
                                                <input type="checkbox" name="documents[customs_document]" value="1">
                                                <span class="gti-ae-doc-check"></span>
                                                <span class="gti-ae-doc-label">Customs Document</span>
                                            </label>
                                            <label class="gti-ae-doc-item">
                                                <input type="checkbox" name="documents[warranty_book]" value="1">
                                                <span class="gti-ae-doc-check"></span>
                                                <span class="gti-ae-doc-label">Warranty Book</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Location Details -->
                                <div class="gti-ae-form-card">
                                    <div class="gti-ae-card-header">
                                        <h3><i class="fas fa-map-marker-alt"></i> Location Details</h3>
                                    </div>
                                    <div class="gti-ae-card-body">
                                        <div class="gti-ae-form-grid">
                                            <div class="gti-ae-field">
                                                <label>Country</label>
                                                <select name="location_country">
                                                    <option value="">Select Country</option>
                                                    <option value="Indonesia">Indonesia</option>
                                                    <option value="Japan">Japan</option>
                                                    <option value="South Korea">South Korea</option>
                                                    <option value="China">China</option>
                                                    <option value="USA">USA</option>
                                                    <option value="Germany">Germany</option>
                                                </select>
                                            </div>
                                            <div class="gti-ae-field">
                                                <label>Province</label>
                                                <select name="location_province">
                                                    <option value="">Select Province</option>
                                                    <option value="DKI Jakarta">DKI Jakarta</option>
                                                    <option value="Jawa Barat">Jawa Barat</option>
                                                    <option value="Jawa Timur">Jawa Timur</option>
                                                    <option value="Kalimantan Timur">Kalimantan Timur</option>
                                                    <option value="Kalimantan Selatan">Kalimantan Selatan</option>
                                                    <option value="Sulawesi Selatan">Sulawesi Selatan</option>
                                                    <option value="Sumatera Utara">Sumatera Utara</option>
                                                </select>
                                            </div>
                                            <div class="gti-ae-field">
                                                <label>City / Regency</label>
                                                <select name="location_city">
                                                    <option value="">Select City</option>
                                                    <option value="Jakarta">Jakarta</option>
                                                    <option value="Bandung">Bandung</option>
                                                    <option value="Surabaya">Surabaya</option>
                                                    <option value="Balikpapan">Balikpapan</option>
                                                    <option value="Samarinda">Samarinda</option>
                                                    <option value="Makassar">Makassar</option>
                                                    <option value="Medan">Medan</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="gti-ae-field gti-ae-field-full">
                                            <label>Detailed Address</label>
                                            <textarea name="detailed_address" rows="2" placeholder="Enter full address..."></textarea>
                                        </div>
                                        <div class="gti-ae-form-grid">
                                            <div class="gti-ae-field">
                                                <label>Map Location <span style="font-weight:400;color:#9ca3af;">(Optional)</span></label>
                                                <input type="text" name="map_location" placeholder="Paste Google Maps link">
                                            </div>
                                            <div class="gti-ae-field gti-ae-field-span-2">
                                                <label>Additional Notes <span style="font-weight:400;color:#9ca3af;">(Optional)</span></label>
                                                <input type="text" name="location_notes" placeholder="Any notes about the location">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- RIGHT COLUMN -->
                            <div class="gti-ae-step5-right">

                                <!-- Preview Summary -->
                                <div class="gti-ae-form-card gti-ae-preview-card">
                                    <div class="gti-ae-card-header">
                                        <h3><i class="fas fa-eye"></i> Preview Summary</h3>
                                    </div>
                                    <div class="gti-ae-card-body">
                                        <div class="gti-ae-preview-image" id="gti-ae-step5-preview-image">
                                            <i class="fas fa-image"></i>
                                            <span>Main Image</span>
                                        </div>
                                        <div class="gti-ae-preview-tags">
                                            <span class="gti-ae-preview-tag gti-ae-preview-tag-ready" id="gti-ae-preview-status">Ready to Work</span>
                                            <span class="gti-ae-preview-tag" id="gti-ae-preview-year">Year 2019</span>
                                        </div>
                                        <h4 class="gti-ae-preview-title" id="gti-ae-preview-title">KOMATSU PC200-8</h4>
                                        <p class="gti-ae-preview-subtitle" id="gti-ae-preview-subtitle">Excavator • Komatsu</p>
                                        <p class="gti-ae-preview-location">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <span id="gti-ae-preview-location">Balikpapan, Kalimantan Timur</span>
                                        </p>

                                        <div class="gti-ae-preview-divider"></div>

                                        <div class="gti-ae-preview-section-title">Key Information</div>
                                        <div class="gti-ae-preview-specs">
                                            <div class="gti-ae-preview-spec">
                                                <span class="gti-ae-preview-spec-label">Operating Weight</span>
                                                <span class="gti-ae-preview-spec-value" id="gti-ae-spec-weight">—</span>
                                            </div>
                                            <div class="gti-ae-preview-spec">
                                                <span class="gti-ae-preview-spec-label">Bucket Capacity</span>
                                                <span class="gti-ae-preview-spec-value" id="gti-ae-spec-bucket">—</span>
                                            </div>
                                            <div class="gti-ae-preview-spec">
                                                <span class="gti-ae-preview-spec-label">Engine Power</span>
                                                <span class="gti-ae-preview-spec-value" id="gti-ae-spec-engine">—</span>
                                            </div>
                                            <div class="gti-ae-preview-spec">
                                                <span class="gti-ae-preview-spec-label">Operating Hours</span>
                                                <span class="gti-ae-preview-spec-value" id="gti-ae-spec-hours">—</span>
                                            </div>
                                            <div class="gti-ae-preview-spec">
                                                <span class="gti-ae-preview-spec-label">Selling Price</span>
                                                <span class="gti-ae-preview-spec-value" id="gti-ae-spec-price">—</span>
                                            </div>
                                            <div class="gti-ae-preview-spec">
                                                <span class="gti-ae-preview-spec-label">Rental Price / Month</span>
                                                <span class="gti-ae-preview-spec-value" id="gti-ae-spec-rental">—</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tips -->
                                <div class="gti-ae-tips-card">
                                    <div class="gti-ae-tips-icon">
                                        <i class="fas fa-lightbulb"></i>
                                    </div>
                                    <div class="gti-ae-tips-content">
                                        <strong>Tips</strong>
                                        <p>Informasi tambahan yang lengkap dapat meningkatkan kepercayaan buyer. Pastikan data dokumen dan riwayat unit sudah valid.</p>
                                    </div>
                                </div>

                                <!-- Checklist -->
                                <div class="gti-ae-form-card gti-ae-checklist-card">
                                    <div class="gti-ae-card-header">
                                        <h3><i class="fas fa-check-double"></i> Checklist</h3>
                                    </div>
                                    <div class="gti-ae-card-body">
                                        <label class="gti-ae-checklist-item">
                                            <input type="checkbox" name="checklist[info_complete]" value="1">
                                            <span class="gti-ae-checklist-check"></span>
                                            <span>Semua informasi sudah terisi</span>
                                        </label>
                                        <label class="gti-ae-checklist-item">
                                            <input type="checkbox" name="checklist[specs_complete]" value="1">
                                            <span class="gti-ae-checklist-check"></span>
                                            <span>Spesifikasi unit sudah lengkap</span>
                                        </label>
                                        <label class="gti-ae-checklist-item">
                                            <input type="checkbox" name="checklist[photos_uploaded]" value="1">
                                            <span class="gti-ae-checklist-check"></span>
                                            <span>Foto unit sudah diupload</span>
                                        </label>
                                        <label class="gti-ae-checklist-item">
                                            <input type="checkbox" name="checklist[price_status]" value="1">
                                            <span class="gti-ae-checklist-check"></span>
                                            <span>Harga dan status sudah diisi</span>
                                        </label>
                                        <label class="gti-ae-checklist-item">
                                            <input type="checkbox" name="checklist[location_docs]" value="1">
                                            <span class="gti-ae-checklist-check"></span>
                                            <span>Lokasi dan dokumen sudah diisi</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Navigation -->
                    <div class="gti-ae-form-nav">
                        <button type="button" class="gti-ae-btn gti-ae-btn-back" id="gti-ae-prev" style="display:none;">
                            <i class="fas fa-arrow-left"></i> Previous
                        </button>
                        <div class="gti-ae-form-nav-right">
                            <a href="<?php echo esc_url(gti_dashboard_url('used-equipment')); ?>" class="gti-ae-btn gti-ae-btn-cancel">Cancel</a>
                            <button type="button" class="gti-ae-btn gti-ae-btn-draft" id="gti-ae-draft">
                                <i class="fas fa-save"></i> Save as Draft
                            </button>
                            <button type="button" class="gti-ae-btn gti-ae-btn-next" id="gti-ae-next">
                                Next <i class="fas fa-arrow-right"></i>
                            </button>
                            <button type="button" class="gti-ae-btn gti-ae-btn-submit" id="gti-ae-submit" style="display:none;">
                                <i class="fas fa-check"></i> Add Equipment
                            </button>
                        </div>
                    </div>
                </form>
            </div>

<?php
gti_dashboard_close(  );
