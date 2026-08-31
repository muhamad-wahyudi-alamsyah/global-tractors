<?php
/**
 * Equipment Form View (Multi-Step)
 *
 * @package Global_Tractors
 */

defined('ABSPATH') || exit;

// Get equipment ID if editing
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : 'used';
$equipment = null;

if ($id > 0) {
    $db = GTI_Database::get_instance();
    $equipment = $db->get_equipment_by_id($id);
    if ($equipment) {
        $type = $equipment->type;
    }
}

$is_edit = $equipment !== null;
$page_title = $is_edit ? 'Edit Equipment' : 'Add New Equipment';
$form_action = $is_edit ? 'gti_edit_equipment' : 'gti_add_equipment';

// Get categories and brands
global $wpdb;
$categories = $wpdb->get_col("SELECT DISTINCT category FROM {$wpdb->prefix}gti_equipment WHERE category != '' ORDER BY category");
$brands = $wpdb->get_col("SELECT DISTINCT brand FROM {$wpdb->prefix}gti_equipment WHERE brand != '' ORDER BY brand");
$locations = array('Jakarta', 'Balikpapan', 'Surabaya', 'Bandung', 'Medan', 'Makassar');
?>

<div class="gti-admin-wrap">
    <!-- Page Header -->
    <div class="gti-page-header">
        <div>
            <h1 class="gti-page-title"><?php echo esc_html($page_title); ?></h1>
            <p class="gti-page-subtitle">
                <?php echo $is_edit ? 'Edit equipment details' : 'Fill in the details below to add new equipment'; ?>
            </p>
        </div>
        <a href="<?php echo admin_url('admin.php?page=gti-equipment&type=' . $type); ?>" class="gti-btn gti-btn-secondary">
            ← Back to List
        </a>
    </div>
    
    <!-- Multi-Step Form -->
    <form class="gti-multi-step-form gti-form" method="post" action="<?php echo admin_url('admin-ajax.php'); ?>" data-steps="5">
        <input type="hidden" name="action" value="<?php echo esc_attr($form_action); ?>">
        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('gti_nonce'); ?>">
        <input type="hidden" name="id" value="<?php echo esc_attr($id); ?>">
        <input type="hidden" id="gti-form-data" name="form_data" value="">
        
        <!-- Stepper -->
        <div class="gti-card">
            <div class="gti-card-body">
                <div class="gti-stepper">
                    <div class="gti-step active" data-step="1">
                        <div class="gti-step-number">1</div>
                        <div class="gti-step-label">General Info</div>
                    </div>
                    <div class="gti-step" data-step="2">
                        <div class="gti-step-number">2</div>
                        <div class="gti-step-label">Specifications</div>
                    </div>
                    <div class="gti-step" data-step="3">
                        <div class="gti-step-number">3</div>
                        <div class="gti-step-label">Pricing & Status</div>
                    </div>
                    <div class="gti-step" data-step="4">
                        <div class="gti-step-number">4</div>
                        <div class="gti-step-label">Images & Media</div>
                    </div>
                    <div class="gti-step" data-step="5">
                        <div class="gti-step-number">5</div>
                        <div class="gti-step-label">Additional Info</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Step 1: General Information -->
        <div class="gti-step-content" data-step="1">
            <div class="gti-card">
                <div class="gti-card-header">
                    <h3 class="gti-card-title">General Information</h3>
                </div>
                <div class="gti-card-body">
                    <div class="gti-form-row">
                        <div class="gti-form-group">
                            <label class="gti-form-label">Equipment Name <span class="required">*</span></label>
                            <input type="text" name="name" class="gti-form-control" required
                                   value="<?php echo esc_attr($equipment->name ?? ''); ?>"
                                   placeholder="e.g., Komatsu PC200-8">
                        </div>
                        <div class="gti-form-group">
                            <label class="gti-form-label">Equipment Code <span class="required">*</span></label>
                            <input type="text" name="equipment_code" class="gti-form-control" required
                                   value="<?php echo esc_attr($equipment->equipment_code ?? ''); ?>"
                                   placeholder="e.g., EQ-001">
                        </div>
                    </div>
                    
                    <div class="gti-form-row">
                        <div class="gti-form-group">
                            <label class="gti-form-label">Type <span class="required">*</span></label>
                            <select name="type" class="gti-form-control" required>
                                <option value="used" <?php selected($type, 'used'); ?>>Used Equipment</option>
                                <option value="rental" <?php selected($type, 'rental'); ?>>Rental Equipment</option>
                            </select>
                        </div>
                        <div class="gti-form-group">
                            <label class="gti-form-label">Category <span class="required">*</span></label>
                            <select name="category" class="gti-form-control" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo esc_attr($cat); ?>" <?php selected($equipment->category ?? '', $cat); ?>>
                                        <?php echo esc_html($cat); ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="Excavator" <?php selected($equipment->category ?? '', 'Excavator'); ?>>Excavator</option>
                                <option value="Bulldozer" <?php selected($equipment->category ?? '', 'Bulldozer'); ?>>Bulldozer</option>
                                <option value="Wheel Loader" <?php selected($equipment->category ?? '', 'Wheel Loader'); ?>>Wheel Loader</option>
                                <option value="Dump Truck" <?php selected($equipment->category ?? '', 'Dump Truck'); ?>>Dump Truck</option>
                                <option value="Motor Grader" <?php selected($equipment->category ?? '', 'Motor Grader'); ?>>Motor Grader</option>
                                <option value="Compactor" <?php selected($equipment->category ?? '', 'Compactor'); ?>>Compactor</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="gti-form-row">
                        <div class="gti-form-group">
                            <label class="gti-form-label">Brand <span class="required">*</span></label>
                            <select name="brand" class="gti-form-control" required>
                                <option value="">Select Brand</option>
                                <?php foreach ($brands as $b): ?>
                                    <option value="<?php echo esc_attr($b); ?>" <?php selected($equipment->brand ?? '', $b); ?>>
                                        <?php echo esc_html($b); ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="Komatsu" <?php selected($equipment->brand ?? '', 'Komatsu'); ?>>Komatsu</option>
                                <option value="CAT" <?php selected($equipment->brand ?? '', 'CAT'); ?>>CAT</option>
                                <option value="Hitachi" <?php selected($equipment->brand ?? '', 'Hitachi'); ?>>Hitachi</option>
                                <option value="Volvo" <?php selected($equipment->brand ?? '', 'Volvo'); ?>>Volvo</option>
                                <option value="Doosan" <?php selected($equipment->brand ?? '', 'Doosan'); ?>>Doosan</option>
                                <option value="Hyundai" <?php selected($equipment->brand ?? '', 'Hyundai'); ?>>Hyundai</option>
                            </select>
                        </div>
                        <div class="gti-form-group">
                            <label class="gti-form-label">Model <span class="required">*</span></label>
                            <input type="text" name="model" class="gti-form-control" required
                                   value="<?php echo esc_attr($equipment->model ?? ''); ?>"
                                   placeholder="e.g., PC200-8">
                        </div>
                    </div>
                    
                    <div class="gti-form-row">
                        <div class="gti-form-group">
                            <label class="gti-form-label">Year <span class="required">*</span></label>
                            <input type="number" name="year" class="gti-form-control" required
                                   value="<?php echo esc_attr($equipment->year ?? date('Y')); ?>"
                                   min="1900" max="<?php echo date('Y') + 1; ?>">
                        </div>
                        <div class="gti-form-group">
                            <label class="gti-form-label">Location <span class="required">*</span></label>
                            <select name="location" class="gti-form-control" required>
                                <option value="">Select Location</option>
                                <?php foreach ($locations as $loc): ?>
                                    <option value="<?php echo esc_attr($loc); ?>" <?php selected($equipment->location ?? '', $loc); ?>>
                                        <?php echo esc_html($loc); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="gti-form-group">
                        <label class="gti-form-label">Description</label>
                        <textarea name="description" class="gti-form-control" rows="4"
                                  placeholder="Equipment description..."><?php echo esc_textarea($equipment->description ?? ''); ?></textarea>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Step 2: Specifications -->
        <div class="gti-step-content" data-step="2" style="display: none;">
            <div class="gti-card">
                <div class="gti-card-header">
                    <h3 class="gti-card-title">Specifications</h3>
                </div>
                <div class="gti-card-body">
                    <?php
                    $specs = json_decode($equipment->specifications ?? '{}', true) ?: array();
                    ?>
                    <div class="gti-form-row">
                        <div class="gti-form-group">
                            <label class="gti-form-label">Engine Type</label>
                            <input type="text" name="specifications[engine_type]" class="gti-form-control"
                                   value="<?php echo esc_attr($specs['engine_type'] ?? ''); ?>"
                                   placeholder="e.g., Cummins QSB6.7">
                        </div>
                        <div class="gti-form-group">
                            <label class="gti-form-label">Operating Weight (ton)</label>
                            <input type="number" name="specifications[weight]" class="gti-form-control"
                                   value="<?php echo esc_attr($specs['weight'] ?? ''); ?>"
                                   placeholder="e.g., 20">
                        </div>
                    </div>
                    
                    <div class="gti-form-row">
                        <div class="gti-form-group">
                            <label class="gti-form-label">Power (HP)</label>
                            <input type="number" name="specifications[power]" class="gti-form-control"
                                   value="<?php echo esc_attr($specs['power'] ?? ''); ?>"
                                   placeholder="e.g., 155">
                        </div>
                        <div class="gti-form-group">
                            <label class="gti-form-label">Hours</label>
                            <input type="number" name="hours" class="gti-form-control"
                                   value="<?php echo esc_attr($equipment->hours ?? ''); ?>"
                                   placeholder="Operating hours">
                        </div>
                    </div>
                    
                    <div class="gti-form-row">
                        <div class="gti-form-group">
                            <label class="gti-form-label">Fuel Type</label>
                            <select name="specifications[fuel_type]" class="gti-form-control">
                                <option value="">Select Fuel Type</option>
                                <option value="Diesel" <?php selected($specs['fuel_type'] ?? '', 'Diesel'); ?>>Diesel</option>
                                <option value="Gasoline" <?php selected($specs['fuel_type'] ?? '', 'Gasoline'); ?>>Gasoline</option>
                                <option value="Electric" <?php selected($specs['fuel_type'] ?? '', 'Electric'); ?>>Electric</option>
                            </select>
                        </div>
                        <div class="gti-form-group">
                            <label class="gti-form-label">Dimensions</label>
                            <input type="text" name="specifications[dimensions]" class="gti-form-control"
                                   value="<?php echo esc_attr($specs['dimensions'] ?? ''); ?>"
                                   placeholder="L x W x H">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Step 3: Pricing & Status -->
        <div class="gti-step-content" data-step="3" style="display: none;">
            <div class="gti-card">
                <div class="gti-card-header">
                    <h3 class="gti-card-title">Pricing & Status</h3>
                </div>
                <div class="gti-card-body">
                    <div class="gti-form-row">
                        <div class="gti-form-group">
                            <label class="gti-form-label">Price (Rp) <span class="required">*</span></label>
                            <input type="number" name="price" class="gti-form-control" required
                                   value="<?php echo esc_attr($equipment->price ?? ''); ?>"
                                   placeholder="Enter price">
                        </div>
                        <div class="gti-form-group">
                            <label class="gti-form-label">Price Type <span class="required">*</span></label>
                            <select name="price_type" class="gti-form-control" required>
                                <option value="sale" <?php selected($equipment->price_type ?? $type, 'sale'); ?>>Sale Price</option>
                                <option value="monthly_rental" <?php selected($equipment->price_type ?? '', 'monthly_rental'); ?>>Monthly Rental</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="gti-form-row">
                        <div class="gti-form-group">
                            <label class="gti-form-label">Condition <span class="required">*</span></label>
                            <select name="condition_status" class="gti-form-control" required>
                                <option value="">Select Condition</option>
                                <option value="excellent" <?php selected($equipment->condition_status ?? '', 'excellent'); ?>>Excellent</option>
                                <option value="good" <?php selected($equipment->condition_status ?? '', 'good'); ?>>Good</option>
                                <option value="fair" <?php selected($equipment->condition_status ?? '', 'fair'); ?>>Fair</option>
                                <option value="poor" <?php selected($equipment->condition_status ?? '', 'poor'); ?>>Poor</option>
                            </select>
                        </div>
                        <div class="gti-form-group">
                            <label class="gti-form-label">Status <span class="required">*</span></label>
                            <select name="status" class="gti-form-control" required>
                                <option value="available" <?php selected($equipment->status ?? '', 'available'); ?>>Available</option>
                                <?php if ($type === 'used'): ?>
                                    <option value="sold" <?php selected($equipment->status ?? '', 'sold'); ?>>Sold</option>
                                <?php else: ?>
                                    <option value="rented" <?php selected($equipment->status ?? '', 'rented'); ?>>Rented</option>
                                <?php endif; ?>
                                <option value="reserved" <?php selected($equipment->status ?? '', 'reserved'); ?>>Reserved</option>
                                <option value="maintenance" <?php selected($equipment->status ?? '', 'maintenance'); ?>>Maintenance</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="gti-form-group">
                        <div class="gti-form-check">
                            <input type="checkbox" name="negotiable" value="1" 
                                   <?php checked($equipment->negotiable ?? 0, 1); ?>>
                            <label>Price is negotiable</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Step 4: Images & Media -->
        <div class="gti-step-content" data-step="4" style="display: none;">
            <div class="gti-card">
                <div class="gti-card-header">
                    <h3 class="gti-card-title">Images & Media</h3>
                </div>
                <div class="gti-card-body">
                    <div class="gti-form-group">
                        <label class="gti-form-label">Main Image <span class="required">*</span></label>
                        <div class="gti-image-upload">
                            <input type="file" name="main_image" accept="image/*" style="display: none;">
                            <input type="hidden" name="main_image_id" value="<?php echo esc_attr($equipment->main_image ?? ''); ?>">
                            
                            <?php if (!empty($equipment->main_image)): ?>
                                <div class="gti-image-preview">
                                    <img src="<?php echo esc_url(GTI_Helpers::get_image_url($equipment->main_image)); ?>" 
                                         style="max-width: 200px; border-radius: 8px;">
                                    <br>
                                    <button type="button" class="gti-btn gti-btn-sm gti-btn-secondary gti-remove-image">Remove</button>
                                </div>
                            <?php else: ?>
                                <button type="button" class="gti-btn gti-btn-secondary gti-upload-btn">
                                    📷 Choose Image
                                </button>
                                <p class="gti-form-hint">Recommended: 800x600px, JPG/PNG, max 5MB</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Step 5: Additional Information -->
        <div class="gti-step-content" data-step="5" style="display: none;">
            <div class="gti-card">
                <div class="gti-card-header">
                    <h3 class="gti-card-title">Additional Information</h3>
                </div>
                <div class="gti-card-body">
                    <div class="gti-form-row">
                        <div class="gti-form-group">
                            <label class="gti-form-label">Registration Number</label>
                            <input type="text" name="registration_number" class="gti-form-control"
                                   value="<?php echo esc_attr($equipment->registration_number ?? ''); ?>">
                        </div>
                        <div class="gti-form-group">
                            <label class="gti-form-label">Insurance Status</label>
                            <select name="insurance_status" class="gti-form-control">
                                <option value="">Select Status</option>
                                <option value="active" <?php selected($equipment->insurance_status ?? '', 'active'); ?>>Active</option>
                                <option value="expired" <?php selected($equipment->insurance_status ?? '', 'expired'); ?>>Expired</option>
                                <option value="none" <?php selected($equipment->insurance_status ?? '', 'none'); ?>>None</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="gti-form-row">
                        <div class="gti-form-group">
                            <label class="gti-form-label">Last Service Date</label>
                            <input type="date" name="last_service_date" class="gti-form-control"
                                   value="<?php echo esc_attr($equipment->last_service_date ?? ''); ?>">
                        </div>
                        <div class="gti-form-group">
                            <label class="gti-form-label">Next Service Due</label>
                            <input type="date" name="next_service_due" class="gti-form-control"
                                   value="<?php echo esc_attr($equipment->next_service_due ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="gti-form-group">
                        <label class="gti-form-label">Warranty Information</label>
                        <textarea name="warranty_info" class="gti-form-control" rows="3"
                                  placeholder="Warranty details..."><?php echo esc_textarea($equipment->warranty_info ?? ''); ?></textarea>
                    </div>
                    
                    <div class="gti-form-group">
                        <label class="gti-form-label">Notes</label>
                        <textarea name="notes" class="gti-form-control" rows="3"
                                  placeholder="Additional notes..."><?php echo esc_textarea($equipment->notes ?? ''); ?></textarea>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Form Actions -->
        <div class="gti-card">
            <div class="gti-card-body" style="display: flex; justify-content: space-between;">
                <button type="button" class="gti-btn gti-btn-secondary gti-btn-prev" style="display: none;">
                    ← Previous
                </button>
                <div style="margin-left: auto;">
                    <button type="button" class="gti-btn gti-btn-primary gti-btn-next">
                        Next →
                    </button>
                    <button type="submit" class="gti-btn gti-btn-success gti-btn-submit" style="display: none;">
                        ✓ <?php echo $is_edit ? 'Update Equipment' : 'Add Equipment'; ?>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>