<?php
/**
 * Inline edit modal for used and rental equipment.
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
            <div class="gti-ue-edit-header">
                <div class="gti-ue-edit-header-left">
                    <h2><i class="fas fa-edit"></i> Edit Equipment</h2>
                </div>
                <div class="gti-ae-stepper-card">
                    <div class="gti-ae-stepper">
                        <div class="gti-ae-step active" data-step="1" onclick="editGoStep(1)">
                            <div class="gti-ae-step-circle">1</div>
                            <div class="gti-ae-step-label">General Info</div>
                        </div>
                        <div class="gti-ae-step-line"></div>
                        <div class="gti-ae-step" data-step="2" onclick="editGoStep(2)">
                            <div class="gti-ae-step-circle">2</div>
                            <div class="gti-ae-step-label">Specifications</div>
                        </div>
                        <div class="gti-ae-step-line"></div>
                        <div class="gti-ae-step" data-step="3" onclick="editGoStep(3)">
                            <div class="gti-ae-step-circle">3</div>
                            <div class="gti-ae-step-label">Pricing</div>
                        </div>
                        <div class="gti-ae-step-line"></div>
                        <div class="gti-ae-step" data-step="4" onclick="editGoStep(4)">
                            <div class="gti-ae-step-circle">4</div>
                            <div class="gti-ae-step-label">Images & Media</div>
                        </div>
                        <div class="gti-ae-step-line"></div>
                        <div class="gti-ae-step" data-step="5" onclick="editGoStep(5)">
                            <div class="gti-ae-step-circle">5</div>
                            <div class="gti-ae-step-label">Additional Info</div>
                        </div>
                    </div>
                </div>
                <button class="gti-ue-edit-close" onclick="closeEditModal()" title="Close"><i class="fas fa-times"></i></button>
            </div>
            <div class="gti-ue-edit-body">
                <form id="gti-edit-form" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="id" id="edit-field-id" value="">
                    <input type="hidden" name="action" value="gti_save_equipment">
                    <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('gti_nonce')); ?>">

                    <!-- Step 1: General Information -->
                    <div class="gti-ae-step-content active" data-step="1">
                        <div class="gti-ae-form-card">
                            <div class="gti-ae-card-header"><h3><i class="fas fa-info-circle"></i> General Information</h3></div>
                            <div class="gti-ae-card-body">
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field"><label>Equipment Name <span class="required">*</span></label><input type="text" name="name" required></div>
                                    <div class="gti-ae-field"><label>Equipment Code <span class="required">*</span></label><input type="text" name="equipment_code" value="<?php echo esc_attr($gti_next_code); ?>" readonly required style="background:#f9fafb;cursor:not-allowed;"></div>
                                    <div class="gti-ae-field"><label>Category <span class="required">*</span></label><select name="category" required><option value="">Select Category</option><option value="Excavator">Excavator</option><option value="Bulldozer">Bulldozer</option><option value="Wheel Loader">Wheel Loader</option><option value="Dump Truck">Dump Truck</option><option value="Motor Grader">Motor Grader</option><option value="Crane">Crane</option><option value="Compactor">Compactor</option></select></div>
                                </div>
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field"><label>Brand <span class="required">*</span></label><select name="brand" required><option value="">Select Brand</option><option value="KOMATSU">KOMATSU</option><option value="CATERPILLAR">CATERPILLAR</option><option value="HITACHI">HITACHI</option><option value="VOLVO">VOLVO</option><option value="KOBELCO">KOBELCO</option><option value="DOOSAN">DOOSAN</option><option value="HYUNDAI">HYUNDAI</option></select></div>
                                    <div class="gti-ae-field"><label>Model <span class="required">*</span></label><input type="text" name="model" required></div>
                                    <div class="gti-ae-field"><label>Year <span class="required">*</span></label><input type="number" name="year" min="1900" max="<?php echo date('Y') + 1; ?>" required></div>
                                </div>
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field"><label>Operating Hours</label><input type="number" name="hours"></div>
                                    <div class="gti-ae-field"><label>Serial Number</label><input type="text" name="serial_number"></div>
                                    <div class="gti-ae-field"><label>Condition <span class="required">*</span></label><select name="condition_status" required><option value="">Select Condition</option><option value="excellent">Excellent</option><option value="good">Good</option><option value="fair">Fair</option><option value="poor">Poor</option></select></div>
                                </div>
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field"><label>Engine</label><input type="text" name="engine"></div>
                                    <div class="gti-ae-field"><label>Engine Power</label><input type="text" name="engine_power"></div>
                                    <div class="gti-ae-field"><label>Country of Origin</label><select name="origin_country"><option value="">Select Origin</option><option value="Japan">Japan</option><option value="USA">USA</option><option value="South Korea">South Korea</option><option value="China">China</option><option value="Germany">Germany</option><option value="Sweden">Sweden</option></select></div>
                                </div>
                            </div>
                        </div>
                        <div class="gti-ae-form-card">
                            <div class="gti-ae-card-header"><h3><i class="fas fa-clipboard-list"></i> Basic Information</h3></div>
                            <div class="gti-ae-card-body">
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field"><label>Equipment Type</label><select name="type"><option value="">Select Type</option><option value="used">Used Equipment</option><option value="rental">Rental Equipment</option></select></div>
                                </div>
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field"><label>Location</label><select name="location"><option value="">Select Location</option><option value="Jakarta">Jakarta</option><option value="Balikpapan">Balikpapan</option><option value="Surabaya">Surabaya</option><option value="Bandung">Bandung</option><option value="Medan">Medan</option><option value="Makassar">Makassar</option></select></div>
                                    <div class="gti-ae-field"><label>Availability Status <span class="required">*</span></label><select name="status" required><option value="">Select Status</option><option value="available">Available</option><option value="sold">Sold</option><option value="reserved">Reserved</option><option value="maintenance">Maintenance</option></select></div>
                                    <div class="gti-ae-field"><label>Stock Number (Internal)</label><input type="text" name="stock_number"></div>
                                </div>
                                <div class="gti-ae-field gti-ae-field-full"><label>Description <span class="required">*</span></label><textarea name="description" rows="4" required></textarea></div>
                            </div>
                        </div>
                        <div class="gti-ae-step-nav"><span></span><button type="button" class="gti-ae-next" onclick="editNextStep()">Next <i class="fas fa-arrow-right"></i></button></div>
                    </div>

                    <!-- Step 2: Specifications -->
                    <div class="gti-ae-step-content" data-step="2">
                        <div class="gti-ae-form-card">
                            <div class="gti-ae-card-header"><h3><i class="fas fa-cogs"></i> Technical Specifications</h3></div>
                            <div class="gti-ae-card-body">
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field"><label>Operating Weight</label><input type="text" name="operating_weight"></div>
                                    <div class="gti-ae-field"><label>Bucket Capacity</label><input type="text" name="bucket_capacity"></div>
                                    <div class="gti-ae-field"><label>Engine Model</label><input type="text" name="engine_model"></div>
                                </div>
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field"><label>Displacement</label><input type="text" name="displacement"></div>
                                    <div class="gti-ae-field"><label>No. of Cylinders</label><input type="number" name="cylinders"></div>
                                </div>
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field"><label>Hydraulic System</label><input type="text" name="hydraulic_system"></div>
                                    <div class="gti-ae-field"><label>Hydraulic Pump Flow</label><input type="text" name="hydraulic_pump_flow"></div>
                                    <div class="gti-ae-field"><label>Max. Digging Depth</label><input type="text" name="max_digging_depth"></div>
                                </div>
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field"><label>Max. Digging Height</label><input type="text" name="max_digging_height"></div>
                                    <div class="gti-ae-field"><label>Max. Reach at Ground Level</label><input type="text" name="max_reach_ground"></div>
                                    <div class="gti-ae-field"><label>Max. Dumping Height</label><input type="text" name="max_dumping_height"></div>
                                </div>
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field"><label>Travel Speed (High/Low)</label><input type="text" name="travel_speed"></div>
                                    <div class="gti-ae-field"><label>Swing Speed</label><input type="text" name="swing_speed"></div>
                                    <div class="gti-ae-field"><label>Fuel Tank Capacity</label><input type="text" name="fuel_tank_capacity"></div>
                                </div>
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field"><label>Hydraulic Tank Capacity</label><input type="text" name="hydraulic_tank_capacity"></div>
                                    <div class="gti-ae-field"><label>Track Width</label><input type="text" name="track_width"></div>
                                    <div class="gti-ae-field"><label>Ground Pressure</label><input type="text" name="ground_pressure"></div>
                                </div>
                            </div>
                        </div>
                        <div class="gti-ae-form-card">
                            <div class="gti-ae-card-header"><h3><i class="fas fa-sliders-h"></i> Features & Configurations</h3></div>
                            <div class="gti-ae-card-body">
                                <div class="gti-ae-features-grid">
                                    <label class="gti-ae-feature-item"><input type="checkbox" name="features[air_conditioner]" value="1"><span class="gti-ae-feature-check"></span><span>Air Conditioner</span></label>
                                    <label class="gti-ae-feature-item"><input type="checkbox" name="features[backup_alarm]" value="1"><span class="gti-ae-feature-check"></span><span>Backup Alarm</span></label>
                                    <label class="gti-ae-feature-item"><input type="checkbox" name="features[led_work_light]" value="1"><span class="gti-ae-feature-check"></span><span>LED Work Light</span></label>
                                    <label class="gti-ae-feature-item"><input type="checkbox" name="features[camera]" value="1"><span class="gti-ae-feature-check"></span><span>Camera</span></label>
                                    <label class="gti-ae-feature-item"><input type="checkbox" name="features[auto_idle]" value="1"><span class="gti-ae-feature-check"></span><span>Auto Idle</span></label>
                                    <label class="gti-ae-feature-item"><input type="checkbox" name="features[hammer_line]" value="1"><span class="gti-ae-feature-check"></span><span>Hammer Line</span></label>
                                    <label class="gti-ae-feature-item"><input type="checkbox" name="features[quick_coupler]" value="1"><span class="gti-ae-feature-check"></span><span>Quick Coupler</span></label>
                                    <label class="gti-ae-feature-item"><input type="checkbox" name="features[gps_system]" value="1"><span class="gti-ae-feature-check"></span><span>GPS System</span></label>
                                    <label class="gti-ae-feature-item"><input type="checkbox" name="features[centralized_greasing]" value="1"><span class="gti-ae-feature-check"></span><span>Centralized Greasing</span></label>
                                </div>
                            </div>
                        </div>
                        <div class="gti-ae-step-nav"><button type="button" class="gti-ae-prev" onclick="editPrevStep()"><i class="fas fa-arrow-left"></i> Previous</button><button type="button" class="gti-ae-next" onclick="editNextStep()">Next <i class="fas fa-arrow-right"></i></button></div>
                    </div>

                    <!-- Step 3: Pricing & Status -->
                    <div class="gti-ae-step-content" data-step="3">
                        <div class="gti-ae-form-card">
                            <div class="gti-ae-card-header"><h3><i class="fas fa-tag"></i> Pricing Information</h3></div>
                            <div class="gti-ae-card-body">
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field"><label>Selling Price (IDR)</label><input type="text" name="selling_price" inputmode="numeric"></div>
                                    <div class="gti-ae-field"><label>Rental Price (IDR/Month)</label><input type="text" name="rental_price" inputmode="numeric"></div>
                                    <div class="gti-ae-field"><label>Price Type</label><select name="price_type"><option value="">-- Select Price Type --</option><option value="sale">For Sale</option><option value="rental">For Rental</option><option value="sale_and_rental">Sale & Rental</option><option value="price_on_ask">Price on Ask</option></select></div>
                                </div>
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field"><label>VAT Included?</label><select name="vat_included"><option value="">-- Select --</option><option value="yes">Yes</option><option value="no">No</option></select></div>
                                    <div class="gti-ae-field"><label>Currency</label><select name="currency"><option value="IDR">IDR (Rp)</option><option value="USD">USD ($)</option><option value="EUR">EUR (€)</option></select></div>
                                    <div class="gti-ae-field"><label>Price Valid Until</label><input type="date" name="price_valid_until"></div>
                                    <div class="gti-ae-field"><label>Negotiable</label><select name="negotiable"><option value="">-- Select --</option><option value="yes">Yes</option><option value="no">No</option></select></div>
                                </div>
                            </div>
                        </div>
                        <div class="gti-ae-form-card">
                            <div class="gti-ae-card-header"><h3><i class="fas fa-clipboard-check"></i> Status & Availability</h3></div>
                            <div class="gti-ae-card-body">
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field"><label>Availability Status</label><select name="availability_status"><option value="">-- Select Status --</option><option value="available">Available</option><option value="reserved">Reserved</option><option value="sold">Sold</option><option value="in_transit">In Transit</option><option value="under_maintenance">Under Maintenance</option></select></div>
                                </div>
                                <!-- stock_number, condition_status, location, operating_weight, bucket_capacity and engine_power
                                     are each defined exactly once in this form. A repeated name= is sent twice by FormData and
                                     PHP keeps only the last value, which silently wiped the earlier step's input. -->
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field"><label>Ready to Use</label><select name="ready_to_use"><option value="">-- Select --</option><option value="yes">Yes</option><option value="no">No</option><option value="after_service">After Service</option></select></div>
                                    <div class="gti-ae-field"><label>Service History</label><select name="service_history"><option value="">-- Select --</option><option value="full">Full Service History</option><option value="partial">Partial Service History</option><option value="none">No Service History</option></select></div>
                                </div>
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field"><label>Warranty Available?</label><select name="warranty_available"><option value="">-- Select --</option><option value="yes">Yes</option><option value="no">No</option></select></div>
                                    <div class="gti-ae-field"><label>Warranty Period</label><input type="text" name="warranty_period"></div>
                                </div>
                                <div class="gti-ae-form-grid"><div class="gti-ae-field gti-ae-field-full"><label>Notes for Buyers</label><textarea name="buyer_notes" rows="3"></textarea></div></div>
                            </div>
                        </div>
                        <div class="gti-ae-step-nav"><button type="button" class="gti-ae-prev" onclick="editPrevStep()"><i class="fas fa-arrow-left"></i> Previous</button><button type="button" class="gti-ae-next" onclick="editNextStep()">Next <i class="fas fa-arrow-right"></i></button></div>
                    </div>

                    <!-- Step 4: Images & Media -->
                    <div class="gti-ae-step-content" data-step="4">
                        <div class="gti-ae-form-card">
                            <div class="gti-ae-card-header"><h3><i class="fas fa-images"></i> Images & Media</h3></div>
                            <div class="gti-ae-card-body">
                                <div class="gti-ae-field gti-ae-field-full">
                                    <label>Main Image</label>
                                    <div class="gti-ae-upload-area" id="gti-edit-main-upload">
                                        <input type="file" name="main_image" id="gti-edit-main-image" accept="image/*" style="display:none;">
                                        <div class="gti-ae-upload-placeholder" id="gti-edit-upload-placeholder">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                            <p>Click or drag image here to upload</p>
                                        </div>
                                        <div class="gti-ae-upload-preview" id="gti-edit-upload-preview" style="display:none;">
                                            <img id="gti-edit-preview-img" src="" alt="Preview">
                                            <button type="button" class="gti-ae-remove-img" id="gti-edit-remove-img"><i class="fas fa-times"></i></button>
                                        </div>
                                    </div>
                                </div>
                                <div class="gti-ae-field gti-ae-field-full">
                                    <label>Additional Images</label>
                                    <div class="gti-ae-upload-area" id="gti-edit-gallery-upload">
                                        <input type="file" name="gallery_images[]" id="gti-edit-gallery-images" accept="image/*" multiple style="display:none;">
                                        <div class="gti-ae-upload-placeholder"><i class="fas fa-images"></i><p>Click or drag multiple images here</p></div>
                                    </div>
                                    <div class="gti-ae-gallery-preview" id="gti-edit-gallery-preview"></div>
                                </div>
                                <div class="gti-ae-field gti-ae-field-full">
                                    <label>Video (Optional)</label>
                                    <div class="gti-ae-upload-area" id="gti-edit-video-upload">
                                        <input type="file" name="video_file" id="gti-edit-video-file" accept="video/*" style="display:none;">
                                        <div class="gti-ae-upload-placeholder" id="gti-edit-video-placeholder"><i class="fas fa-video"></i><p>Click or drag video here to upload</p></div>
                                        <div class="gti-ae-upload-preview" id="gti-edit-video-preview" style="display:none;">
                                            <div class="gti-ae-video-thumb"><i class="fas fa-play-circle"></i><span id="gti-edit-video-name"></span></div>
                                            <button type="button" class="gti-ae-remove-img" id="gti-edit-remove-video"><i class="fas fa-times"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="gti-ae-step-nav"><button type="button" class="gti-ae-prev" onclick="editPrevStep()"><i class="fas fa-arrow-left"></i> Previous</button><button type="button" class="gti-ae-next" onclick="editNextStep()">Next <i class="fas fa-arrow-right"></i></button></div>
                    </div>

                    <!-- Step 5: Additional Information -->
                    <div class="gti-ae-step-content" data-step="5">
                        <div class="gti-ae-form-card">
                            <div class="gti-ae-card-header"><h3><i class="fas fa-clipboard-list"></i> Additional Information</h3></div>
                            <div class="gti-ae-card-body">
                                <div class="gti-ae-field gti-ae-field-full"><label>Description (Detailed)</label><textarea name="detailed_description" rows="4"></textarea></div>
                                <div class="gti-ae-field gti-ae-field-full"><label>Equipment History</label><textarea name="equipment_history" rows="4"></textarea></div>
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field"><label>Previous Usage</label><input type="text" name="previous_usage"></div>
                                    <div class="gti-ae-field"><label>Working Condition</label><select name="working_condition"><option value="">Select</option><option value="excellent">Excellent</option><option value="good">Good</option><option value="fair">Fair</option><option value="poor">Poor</option></select></div>
                                    <div class="gti-ae-field"><label>Maintenance Record</label><select name="maintenance_record"><option value="">Select</option><option value="full">Full Record</option><option value="partial">Partial Record</option><option value="none">No Record</option></select></div>
                                    <div class="gti-ae-field"><label>Ownership</label><select name="ownership"><option value="">Select</option><option value="first_owner">First Owner</option><option value="second_owner">Second Owner</option><option value="third_plus">Third Owner or More</option><option value="company">Company Fleet</option></select></div>
                                    <div class="gti-ae-field"><label>Operator Hours</label><input type="number" name="operator_hours"></div>
                                    <div class="gti-ae-field"><label>Last Service Date</label><input type="date" name="last_service_date"></div>
                                </div>
                            </div>
                        </div>
                        <div class="gti-ae-form-card">
                            <div class="gti-ae-card-header"><h3><i class="fas fa-file-alt"></i> Document Availability</h3></div>
                            <div class="gti-ae-card-body">
                                <div class="gti-ae-doc-grid">
                                    <label class="gti-ae-doc-item"><input type="checkbox" name="documents[unit_certificate]" value="1"><span class="gti-ae-doc-check"></span><span class="gti-ae-doc-label">Unit Certificate (STNK/BPKB)</span></label>
                                    <label class="gti-ae-doc-item"><input type="checkbox" name="documents[import_document]" value="1"><span class="gti-ae-doc-check"></span><span class="gti-ae-doc-label">Import Document</span></label>
                                    <label class="gti-ae-doc-item"><input type="checkbox" name="documents[service_maintenance_record]" value="1"><span class="gti-ae-doc-check"></span><span class="gti-ae-doc-label">Service & Maintenance Record</span></label>
                                    <label class="gti-ae-doc-item"><input type="checkbox" name="documents[customs_document]" value="1"><span class="gti-ae-doc-check"></span><span class="gti-ae-doc-label">Customs Document</span></label>
                                    <label class="gti-ae-doc-item"><input type="checkbox" name="documents[warranty_book]" value="1"><span class="gti-ae-doc-check"></span><span class="gti-ae-doc-label">Warranty Book</span></label>
                                </div>
                            </div>
                        </div>
                        <div class="gti-ae-form-card">
                            <div class="gti-ae-card-header"><h3><i class="fas fa-map-marker-alt"></i> Location Details</h3></div>
                            <div class="gti-ae-card-body">
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field"><label>Country</label><select name="location_country"><option value="">Select Country</option><option value="Indonesia">Indonesia</option><option value="Japan">Japan</option><option value="South Korea">South Korea</option><option value="China">China</option><option value="USA">USA</option><option value="Germany">Germany</option></select></div>
                                    <div class="gti-ae-field"><label>Province</label><select name="location_province"><option value="">Select Province</option><option value="DKI Jakarta">DKI Jakarta</option><option value="Jawa Barat">Jawa Barat</option><option value="Jawa Timur">Jawa Timur</option><option value="Kalimantan Timur">Kalimantan Timur</option><option value="Kalimantan Selatan">Kalimantan Selatan</option><option value="Sulawesi Selatan">Sulawesi Selatan</option><option value="Sumatera Utara">Sumatera Utara</option></select></div>
                                    <div class="gti-ae-field"><label>City / Regency</label><select name="location_city"><option value="">Select City</option><option value="Jakarta">Jakarta</option><option value="Bandung">Bandung</option><option value="Surabaya">Surabaya</option><option value="Balikpapan">Balikpapan</option><option value="Samarinda">Samarinda</option><option value="Makassar">Makassar</option><option value="Medan">Medan</option></select></div>
                                </div>
                                <div class="gti-ae-field gti-ae-field-full"><label>Detailed Address</label><textarea name="detailed_address" rows="2"></textarea></div>
                                <div class="gti-ae-form-grid">
                                    <div class="gti-ae-field"><label>Map Location</label><input type="text" name="map_location" placeholder="Paste Google Maps link"></div>
                                    <div class="gti-ae-field"><label>Additional Notes</label><input type="text" name="location_notes"></div>
                                </div>
                            </div>
                        </div>
                        <div class="gti-ae-step-nav"><button type="button" class="gti-ae-prev" onclick="editPrevStep()"><i class="fas fa-arrow-left"></i> Previous</button><span></span></div>
                    </div>
                </form>
            </div>
            <div class="gti-ue-edit-footer">
                <button type="button" class="gti-ae-btn-cancel" onclick="closeEditModal()">Cancel</button>
                <button type="button" class="gti-ae-btn-submit" id="gti-edit-submit"><i class="fas fa-save"></i> Save Changes</button>
            </div>
        </div>
    </div>
