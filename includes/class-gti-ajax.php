<?php
/**
 * GTI AJAX Handlers
 *
 * @package Global_Tractors
 */

defined('ABSPATH') || exit;

class GTI_Ajax {
    
    /**
     * Initialize AJAX hooks
     */
    public static function init() {
        // Equipment AJAX
        add_action('wp_ajax_gti_save_equipment', array(__CLASS__, 'save_equipment'));
        add_action('wp_ajax_gti_delete_equipment', array(__CLASS__, 'delete_equipment'));
        add_action('wp_ajax_gti_publish_equipment', array(__CLASS__, 'publish_equipment'));
        add_action('wp_ajax_gti_get_equipment', array(__CLASS__, 'get_equipment'));
        add_action('wp_ajax_gti_get_next_code', array(__CLASS__, 'get_next_code'));
        
        // Spare Parts AJAX
        add_action('wp_ajax_gti_save_spare_part', array(__CLASS__, 'save_spare_part'));
        add_action('wp_ajax_gti_delete_spare_part', array(__CLASS__, 'delete_spare_part'));
        add_action('wp_ajax_gti_publish_spare_part', array(__CLASS__, 'publish_spare_part'));
        add_action('wp_ajax_gti_get_next_spare_part_code', array(__CLASS__, 'get_next_spare_part_code'));
        
        // Requests AJAX
        add_action('wp_ajax_gti_update_request_status', array(__CLASS__, 'update_request_status'));
        
        // Quotations AJAX
        add_action('wp_ajax_gti_save_quotation', array(__CLASS__, 'save_quotation'));
        add_action('wp_ajax_gti_update_quotation_status', array(__CLASS__, 'update_quotation_status'));
        
        // Sell Equipment AJAX
        add_action('wp_ajax_gti_get_sell_request_detail', array(__CLASS__, 'get_sell_request_detail'));
        add_action('wp_ajax_gti_update_sell_request_status', array(__CLASS__, 'update_sell_request_status'));
        
        // Customers AJAX
        add_action('wp_ajax_gti_save_customer', array(__CLASS__, 'save_customer'));
        
        // Users AJAX
        add_action('wp_ajax_gti_save_user', array(__CLASS__, 'save_user'));
        add_action('wp_ajax_gti_delete_user', array(__CLASS__, 'delete_user'));
        
        // Dashboard AJAX
        add_action('wp_ajax_gti_get_dashboard_stats', array(__CLASS__, 'get_dashboard_stats'));
    }
    
    /**
     * Verify nonce for AJAX
     */
    private static function verify_nonce() {
        if (!wp_verify_nonce($_POST['nonce'], 'gti_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            exit;
        }
    }
    
    /**
     * Save Equipment — handles multi-step form with file uploads.
     *
     * Accepts multipart/form-data via FormData.
     * Stores complex nested fields (specs, features, docs, location) as JSON.
     */
    public static function save_equipment() {
        while (ob_get_level()) { ob_end_clean(); }
        self::verify_nonce();

        global $wpdb;
        $table = $wpdb->prefix . 'gti_equipment';

        $id         = intval( $_POST['id'] ?? 0 );
        $is_draft   = ! empty( $_POST['draft'] );
        $user_id    = get_current_user_id();
        $upload_dir = wp_upload_dir();

        // ── Handle file uploads ────────────────────────────────────────────
        $main_image_url = '';
        if ( ! empty( $_FILES['main_image']['name'] ) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK ) {
            $main_image_url = self::upload_file( $_FILES['main_image'], 'equipment/images' );
        }

        $gallery_urls = [];
        if ( ! empty( $_FILES['gallery_images']['name'][0] ) ) {
            $count = count( $_FILES['gallery_images']['name'] );
            for ( $i = 0; $i < $count; $i++ ) {
                if ( $_FILES['gallery_images']['error'][ $i ] === UPLOAD_ERR_OK ) {
                    $file = [
                        'name'     => $_FILES['gallery_images']['name'][ $i ],
                        'type'     => $_FILES['gallery_images']['type'][ $i ],
                        'tmp_name' => $_FILES['gallery_images']['tmp_name'][ $i ],
                        'error'    => $_FILES['gallery_images']['error'][ $i ],
                        'size'     => $_FILES['gallery_images']['size'][ $i ],
                    ];
                    $url = self::upload_file( $file, 'equipment/gallery' );
                    if ( $url ) $gallery_urls[] = $url;
                }
            }
        }

        $video_url = '';
        if ( ! empty( $_FILES['video_file']['name'] ) && $_FILES['video_file']['error'] === UPLOAD_ERR_OK ) {
            $video_url = self::upload_file( $_FILES['video_file'], 'equipment/videos' );
        }

        // ── If updating, merge with existing image data ────────────────────
        $existing_images = [];
        if ( $id > 0 ) {
            $existing = $wpdb->get_row( $wpdb->prepare( "SELECT main_image, images, video_url FROM {$table} WHERE id = %d", $id ) );
            if ( $existing ) {
                if ( ! $main_image_url && $existing->main_image ) {
                    $main_image_url = $existing->main_image;
                }
                $existing_images = json_decode( $existing->images, true ) ?: [];
                if ( empty( $gallery_urls ) && ! empty( $existing->images ) ) {
                    $gallery_urls = $existing_images;
                }
                if ( ! $video_url && $existing->video_url ) {
                    $video_url = $existing->video_url;
                }
            }
        }

        // ── Features (checkbox array) ──────────────────────────────────────
        $features = [];
        if ( ! empty( $_POST['features'] ) && is_array( $_POST['features'] ) ) {
            foreach ( $_POST['features'] as $key => $val ) {
                $features[ sanitize_key( $key ) ] = 1;
            }
        }

        // ── Documents (checkbox array) ─────────────────────────────────────
        $documents = [];
        if ( ! empty( $_POST['documents'] ) && is_array( $_POST['documents'] ) ) {
            foreach ( $_POST['documents'] as $key => $val ) {
                $documents[ sanitize_key( $key ) ] = 1;
            }
        }

        // ── Technical specifications (JSON) ────────────────────────────────
        $spec_fields = [
            'engine_model', 'displacement', 'cylinders', 'hydraulic_system',
            'hydraulic_pump_flow', 'max_digging_depth', 'max_digging_height',
            'max_reach_ground', 'max_dumping_height', 'travel_speed',
            'swing_speed', 'fuel_tank_capacity', 'hydraulic_tank_capacity',
            'track_width', 'ground_pressure',
        ];
        $specifications = [];
        foreach ( $spec_fields as $f ) {
            $val = sanitize_text_field( $_POST[ $f ] ?? '' );
            if ( $val !== '' ) {
                $specifications[ $f ] = $val;
            }
        }

        // ── Location details ───────────────────────────────────────────────
        $location_data = [
            'country'   => sanitize_text_field( $_POST['location_country'] ?? '' ),
            'province'  => sanitize_text_field( $_POST['location_province'] ?? '' ),
            'city'      => sanitize_text_field( $_POST['location_city'] ?? '' ),
            'address'   => sanitize_textarea_field( $_POST['detailed_address'] ?? '' ),
            'map_link'  => esc_url_raw( $_POST['map_location'] ?? '' ),
            'notes'     => sanitize_text_field( $_POST['location_notes'] ?? '' ),
        ];

        // ── Determine price from selling_price or rental_price ─────────────
        $selling_price = floatval( $_POST['selling_price'] ?? 0 );
        $rental_price  = floatval( $_POST['rental_price'] ?? 0 );
        $price         = $selling_price > 0 ? $selling_price : $rental_price;

        // ── Operating hours ────────────────────────────────────────────────
        // Both inputs arrive as '' when left blank, so `??` never falls through —
        // compare against '' instead, otherwise operator_hours is silently dropped.
        $hours_input    = trim( (string) ( $_POST['hours'] ?? '' ) );
        $operator_hours = trim( (string) ( $_POST['operator_hours'] ?? '' ) );

        // ── Determine type from equipment_type or type ──────────────────────
        $type = sanitize_text_field( $_POST['type'] ?? '' );
        if ( $type === '' ) {
            $type = 'used';
        }

        // ── Build data array ───────────────────────────────────────────────
        $data = [
            // General Info
            'equipment_code'   => sanitize_text_field( $_POST['equipment_code'] ?? '' ),
            'name'             => sanitize_text_field( $_POST['name'] ?? '' ),
            'type'             => $type,
            'category'         => sanitize_text_field( $_POST['category'] ?? '' ),
            'brand'            => sanitize_text_field( $_POST['brand'] ?? '' ),
            'model'            => sanitize_text_field( $_POST['model'] ?? '' ),
            'year'             => intval( $_POST['year'] ?? 0 ),
            'condition_status' => sanitize_text_field( $_POST['condition_status'] ?? '' ),
            'hours'            => intval( $hours_input !== '' ? $hours_input : $operator_hours ),
            'operator_hours'   => $operator_hours !== '' ? intval( $operator_hours ) : '',
            'engine'           => sanitize_text_field( $_POST['engine'] ?? '' ),
            'engine_power'     => sanitize_text_field( $_POST['engine_power'] ?? '' ),
            'origin_country'   => sanitize_text_field( $_POST['origin_country'] ?? '' ),
            'serial_number'    => sanitize_text_field( $_POST['serial_number'] ?? '' ),

            // Specs
            'operating_weight' => sanitize_text_field( $_POST['operating_weight'] ?? '' ),
            'bucket_capacity'  => sanitize_text_field( $_POST['bucket_capacity'] ?? '' ),

            // Pricing
            'price'             => $price,
            'selling_price'     => $selling_price,
            'rental_price'      => $rental_price,
            'price_type'        => sanitize_text_field( $_POST['price_type'] ?? '' ),
            'currency'          => sanitize_text_field( $_POST['currency'] ?? 'IDR' ),
            'vat_included'      => sanitize_text_field( $_POST['vat_included'] ?? '' ),
            'price_valid_until' => sanitize_text_field( $_POST['price_valid_until'] ?? '' ) ?: null,

            // Status & Availability
            'status'              => $is_draft ? 'draft' : sanitize_text_field( $_POST['status'] ?? $_POST['availability_status'] ?? 'available' ),
            'availability_status' => sanitize_text_field( $_POST['availability_status'] ?? '' ),
            'location'            => sanitize_text_field( $_POST['location'] ?? $_POST['location_city'] ?? '' ),
            'stock_number'        => sanitize_text_field( $_POST['stock_number'] ?? '' ),
            'ready_to_use'        => sanitize_text_field( $_POST['ready_to_use'] ?? '' ),
            'service_history'     => sanitize_text_field( $_POST['service_history'] ?? '' ),
            'buyer_notes'         => sanitize_textarea_field( $_POST['buyer_notes'] ?? '' ),

            // Warranty
            'warranty_available' => sanitize_text_field( $_POST['warranty_available'] ?? '' ),
            'warranty_period'    => sanitize_text_field( $_POST['warranty_period'] ?? '' ),

            // Description
            'description'           => wp_kses_post( $_POST['description'] ?? '' ),
            'detailed_description'  => wp_kses_post( $_POST['detailed_description'] ?? '' ),

            // Equipment history
            'equipment_history'  => wp_kses_post( $_POST['equipment_history'] ?? '' ),
            'previous_usage'     => sanitize_text_field( $_POST['previous_usage'] ?? '' ),
            'working_condition'  => sanitize_text_field( $_POST['working_condition'] ?? '' ),
            'maintenance_record' => sanitize_text_field( $_POST['maintenance_record'] ?? '' ),
            'ownership'          => sanitize_text_field( $_POST['ownership'] ?? '' ),
            'last_service_date'  => sanitize_text_field( $_POST['last_service_date'] ?? '' ) ?: null,

            // Images & Media
            'main_image' => $main_image_url,
            'images'     => wp_json_encode( $gallery_urls ),
            'video_url'  => $video_url,

            // JSON columns
            'specifications' => wp_json_encode( $specifications ),
            'features'       => wp_json_encode( $features ),
            'documents'      => wp_json_encode( $documents ),

            // Location details
            'location_country'  => sanitize_text_field( $_POST['location_country'] ?? '' ),
            'location_province' => sanitize_text_field( $_POST['location_province'] ?? '' ),
            'location_city'     => sanitize_text_field( $_POST['location_city'] ?? '' ),
            'detailed_address'  => sanitize_textarea_field( $_POST['detailed_address'] ?? '' ),
            'map_location'      => esc_url_raw( $_POST['map_location'] ?? '' ),
            'location_notes'    => sanitize_text_field( $_POST['location_notes'] ?? '' ),

            // Meta
            'created_by' => $user_id,
        ];

        // Remove empty optional fields to avoid overwriting on update
        $optional = [
            'serial_number', 'engine', 'engine_power', 'origin_country',
            'operating_weight', 'bucket_capacity', 'buyer_notes',
            'availability_status', 'stock_number', 'ready_to_use',
            'service_history', 'previous_usage', 'working_condition',
            'maintenance_record', 'ownership', 'operator_hours',
            'detailed_description', 'equipment_history',
            'location_country', 'location_province', 'location_city',
            'detailed_address', 'map_location', 'location_notes',
            'warranty_available', 'warranty_period',
        ];
        foreach ( $optional as $key ) {
            if ( isset( $data[ $key ] ) && $data[ $key ] === '' ) {
                unset( $data[ $key ] );
            }
        }

        if ( $id > 0 ) {
            $result = $wpdb->update( $table, $data, [ 'id' => $id ] );
        } else {
            $result = $wpdb->insert( $table, $data );
            $id = $wpdb->insert_id;
        }

        if ( $result !== false ) {
            try { self::log_activity( $id > 0 ? 'update' : 'create', 'equipment', $id ); } catch (\Throwable $e) { /* log silently */ }

            wp_send_json_success( [
                'message'  => $is_draft ? 'Draft saved successfully' : 'Equipment saved successfully',
                'id'       => $id,
                'redirect' => gti_dashboard_url( 'used-equipment' ),
            ] );
        } else {
            $err = $wpdb->last_error;
            $msg = 'Failed to save equipment.';
            if ( stripos( $err, 'duplicate' ) !== false || stripos( $err, 'unique' ) !== false ) {
                $msg = 'Equipment code already exists. Please use a unique code.';
            } else {
                $msg .= ' ' . $err;
            }
            wp_send_json_error( [ 'message' => $msg ] );
        }

        exit;
    }

    /**
     * Upload a single file to the WordPress uploads directory.
     *
     * @param array  $file  $_FILES entry.
     * @param string $subdir Subdirectory inside uploads.
     * @return string URL on success, empty string on failure.
     */
    private static function upload_file( $file, $subdir = '' ) {
        $upload_dir = wp_upload_dir();
        $target_dir = $upload_dir['path'] . ( $subdir ? '/' . $subdir : '' );

        // Create subdirectory if needed
        if ( $subdir && ! file_exists( $target_dir ) ) {
            wp_mkdir_p( $target_dir );
        }

        // Generate unique filename
        $ext        = pathinfo( $file['name'], PATHINFO_EXTENSION );
        $filename   = wp_unique_filename( $target_dir, sanitize_file_name( pathinfo( $file['name'], PATHINFO_FILENAME ) ) . '.' . $ext );
        $target_file = $target_dir . '/' . $filename;

        // Check MIME type
        $allowed_image_types = [ 'image/jpeg', 'image/png', 'image/gif', 'image/webp' ];
        $allowed_video_types = [ 'video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/webm' ];
        $allowed = array_merge( $allowed_image_types, $allowed_video_types );

        $finfo    = finfo_open( FILEINFO_MIME_TYPE );
        $mime     = finfo_file( $finfo, $file['tmp_name'] );
        finfo_close( $finfo );

        if ( ! in_array( $mime, $allowed, true ) ) {
            return '';
        }

        if ( ! move_uploaded_file( $file['tmp_name'], $target_file ) ) {
            return '';
        }

        // Return relative URL from uploads dir
        $relative = str_replace( $upload_dir['basedir'] . '/', '', $target_file );
        return $upload_dir['baseurl'] . '/' . $relative;
    }
    
    /**
     * Delete Equipment
     */
    public static function delete_equipment() {
        // Clean any output buffer before sending JSON
        while (ob_get_level()) { ob_end_clean(); }

        self::verify_nonce();

        global $wpdb;
        $table = $wpdb->prefix . 'gti_equipment';
        $id = intval($_POST['id']);

        // Soft delete
        $result = $wpdb->update(
            $table,
            array('deleted_at' => current_time('mysql')),
            array('id' => $id)
        );

        if ($result !== false) {
            try { self::log_activity('delete', 'equipment', $id); } catch (\Throwable $e) { /* log silently */ }
            wp_send_json_success(array('message' => 'Equipment deleted'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete equipment'));
        }

        exit;
    }
    
    /**
     * Publish Equipment — change status from draft to available
     */
    public static function publish_equipment() {
        self::verify_nonce();

        global $wpdb;
        $table = $wpdb->prefix . 'gti_equipment';
        $id = intval($_POST['id']);

        $result = $wpdb->update(
            $table,
            array(
                'status'     => 'available',
                'updated_at' => current_time('mysql'),
            ),
            array('id' => $id)
        );

        if ($result !== false) {
            self::log_activity('publish', 'equipment', $id);
            wp_send_json_success(array('message' => 'Equipment published successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to publish equipment'));
        }

        exit;
    }

    /**
     * Get Equipment by ID
     */
    public static function get_equipment() {
        self::verify_nonce();
        
        global $wpdb;
        $table = $wpdb->prefix . 'gti_equipment';
        $id = intval($_POST['id']);
        
        $equipment = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d AND deleted_at IS NULL", $id)
        );
        
        if ($equipment) {
            wp_send_json_success($equipment);
        } else {
            wp_send_json_error(array('message' => 'Equipment not found'));
        }
        
        exit;
    }

    /**
     * Get Next Equipment Code — auto-generate based on category abbreviation + sequence number.
     *
     * Returns a code like GTI-EXC-2026-003 for the given category.
     */
    public static function get_next_code() {
        while (ob_get_level()) { ob_end_clean(); }
        self::verify_nonce();

        global $wpdb;
        $table = $wpdb->prefix . 'gti_equipment';

        $category = sanitize_text_field($_POST['category'] ?? '');
        $year     = date('Y');

        $cat_abbrev = [
            'Excavator'    => 'EXC', 'Bulldozer'    => 'BLD', 'Wheel Loader' => 'WLD',
            'Dump Truck'   => 'DMP', 'Motor Grader' => 'MGR', 'Crane'        => 'CRN',
            'Compactor'    => 'CMP',
        ];
        $abbr = $cat_abbrev[$category] ?? 'GEN';

        $prefix = "GTI-{$abbr}-{$year}-";

        // Find the highest existing sequence number for this prefix
        $like = $wpdb->esc_like($prefix) . '%';
        $max_code = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT equipment_code FROM {$table} WHERE equipment_code LIKE %s AND deleted_at IS NULL ORDER BY id DESC LIMIT 1",
                $like
            )
        );

        $seq = 1;
        if ($max_code) {
            $last_num = (int) substr($max_code, strrpos($max_code, '-') + 1);
            $seq = $last_num + 1;
        }

        $code = $prefix . str_pad($seq, 3, '0', STR_PAD_LEFT);

        wp_send_json_success(['code' => $code]);
        exit;
    }

    /**
     * Save Spare Part
     */
    public static function save_spare_part() {
        while (ob_get_level()) { ob_end_clean(); }
        self::verify_nonce();
        
        global $wpdb;
        $table = $wpdb->prefix . 'gti_spare_parts';
        
        $id = intval($_POST['id'] ?? 0);
        $is_draft = !empty($_POST['draft']);
        $stock = intval($_POST['stock'] ?? 0);
        $minimum_stock = intval($_POST['minimum_stock'] ?? 10);
        
        // Determine status: draft takes priority, then form status, then stock-based
        if ($is_draft) {
            $status = 'draft';
        } else {
            $form_status = sanitize_text_field($_POST['status'] ?? '');
            if ($form_status) {
                $status = $form_status;
            } elseif ($stock == 0) {
                $status = 'out_of_stock';
            } elseif ($stock <= $minimum_stock) {
                $status = 'low_stock';
            } else {
                $status = 'in_stock';
            }
        }

        // Handle image upload
        $image_url = '';
        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload = wp_upload_bits($_FILES['image']['name'], null, file_get_contents($_FILES['image']['tmp_name']));
            if (!empty($upload['url'])) {
                $image_url = $upload['url'];
            }
        }
        
        $data = array(
            'part_number'   => sanitize_text_field($_POST['part_number'] ?? ''),
            'name'          => sanitize_text_field($_POST['name'] ?? ''),
            'category'      => sanitize_text_field($_POST['category'] ?? ''),
            'brand'         => sanitize_text_field($_POST['brand'] ?? ''),
            'description'   => wp_kses_post($_POST['description'] ?? ''),
            'stock'         => $stock,
            'minimum_stock' => $minimum_stock,
            'unit_price'    => floatval($_POST['unit_price'] ?? 0),
            'supplier'      => sanitize_text_field($_POST['supplier'] ?? ''),
            'location'      => sanitize_text_field($_POST['location'] ?? ''),
            'status'        => $status,
        );

        if ($image_url) {
            $data['image'] = $image_url;
        }
        
        if ($id > 0) {
            $result = $wpdb->update($table, $data, array('id' => $id));
        } else {
            $result = $wpdb->insert($table, $data);
            $id = $wpdb->insert_id;
        }
        
        if ($result !== false) {
            self::log_activity($id > 0 ? 'update' : 'create', 'spare_part', $id);
            wp_send_json_success(array(
                'message'  => $is_draft ? 'Draft saved successfully' : 'Spare part saved successfully',
                'id'       => $id,
                'redirect' => gti_dashboard_url('spare-parts'),
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to save spare part. Please try again.'));
        }
        
        exit;
    }
    
    /**
     * Delete Spare Part
     */
    public static function delete_spare_part() {
        self::verify_nonce();
        
        global $wpdb;
        $table = $wpdb->prefix . 'gti_spare_parts';
        $id = intval($_POST['id']);
        
        $result = $wpdb->delete($table, array('id' => $id));
        
        if ($result !== false) {
            self::log_activity('delete', 'spare_part', $id);
            wp_send_json_success(array('message' => 'Spare part deleted'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete'));
        }
        
        exit;
    }

    /**
     * Publish Spare Part (change status from draft to in_stock)
     */
    public static function publish_spare_part() {
        while (ob_get_level()) { ob_end_clean(); }
        self::verify_nonce();
        global $wpdb;
        $table = $wpdb->prefix . 'gti_spare_parts';
        $id = intval($_POST['id'] ?? 0);
        $status = sanitize_text_field($_POST['status'] ?? 'in_stock');
        $result = $wpdb->update($table, array('status' => $status), array('id' => $id));
        if ($result !== false) {
            wp_send_json_success(array('message' => 'Spare part published successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to publish spare part'));
        }
        exit;
    }

    /**
     * Get Next Spare Part Code — auto-generate based on category abbreviation + sequence number.
     *
     * Returns a code like SP-FLT-2026-003 for the given category.
     */
    public static function get_next_spare_part_code() {
        while (ob_get_level()) { ob_end_clean(); }
        self::verify_nonce();

        global $wpdb;
        $table = $wpdb->prefix . 'gti_spare_parts';

        $category = sanitize_text_field($_POST['category'] ?? '');
        $year     = date('Y');

        $cat_abbrev = [
            'Filter' => 'FLT', 'Belt' => 'BLT', 'Brake' => 'BRK',
            'Engine' => 'ENG', 'Hydraulic' => 'HYD', 'Seal' => 'SEL',
            'Undercarriage' => 'UND', 'Cooling' => 'CLG', 'Electrical' => 'ELT',
            'Other' => 'OTH',
        ];
        $abbr = $cat_abbrev[$category] ?? 'GEN';

        $prefix = "SP-{$abbr}-{$year}-";

        // Find the highest existing sequence number for this prefix
        $like = $wpdb->esc_like($prefix) . '%';
        $max_code = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT part_number FROM {$table} WHERE part_number LIKE %s ORDER BY id DESC LIMIT 1",
                $like
            )
        );

        $seq = 1;
        if ($max_code) {
            $last_num = (int) substr($max_code, strrpos($max_code, '-') + 1);
            $seq = $last_num + 1;
        }

        $code = $prefix . str_pad($seq, 3, '0', STR_PAD_LEFT);

        wp_send_json_success(['code' => $code]);
        exit;
    }

    /**
     * Update Request Status
     */
    public static function update_request_status() {
        self::verify_nonce();
        
        global $wpdb;
        $table = $wpdb->prefix . 'gti_requests';
        
        $id = intval($_POST['id']);
        $status = sanitize_text_field($_POST['status']);
        
        // Get current record BEFORE updating (for email)
        $current = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id),
            ARRAY_A
        );
        
        $result = $wpdb->update(
            $table,
            array('status' => $status),
            array('id' => $id)
        );
        
        if ($result !== false) {
            self::log_activity('status_change', 'request', $id, array('new_status' => $status));
            
            // Send email notification to customer
            if ($current && ($current['status'] ?? '') !== $status && !empty($current['customer_email'])) {
                self::send_status_email('request', $status, $current);
            }
            
            wp_send_json_success(array('message' => 'Status updated'));
        } else {
            wp_send_json_error(array('message' => 'Failed to update status'));
        }
        
        exit;
    }
    
    /**
     * Save Quotation
     */
    public static function save_quotation() {
        self::verify_nonce();
        
        global $wpdb;
        $table = $wpdb->prefix . 'gti_quotations';
        
        $id = intval($_POST['id'] ?? 0);
        
        // Calculate totals
        $subtotal = floatval($_POST['subtotal']);
        $discount = floatval($_POST['discount']);
        $discount_type = sanitize_text_field($_POST['discount_type'] ?? 'amount');
        $tax_rate = floatval($_POST['tax_rate'] ?? 11);
        
        if ($discount_type === 'percentage') {
            $discount_amount = $subtotal * ($discount / 100);
        } else {
            $discount_amount = $discount;
        }
        
        $tax_amount = ($subtotal - $discount_amount) * ($tax_rate / 100);
        $total = $subtotal - $discount_amount + $tax_amount;
        
        $data = array(
            'quotation_id'      => sanitize_text_field($_POST['quotation_id']),
            'customer_name'     => sanitize_text_field($_POST['customer_name']),
            'customer_company'  => sanitize_text_field($_POST['customer_company']),
            'customer_email'    => sanitize_email($_POST['customer_email']),
            'customer_phone'    => sanitize_text_field($_POST['customer_phone']),
            'customer_address'  => wp_kses_post($_POST['customer_address']),
            'items'             => wp_json_encode($_POST['items']),
            'subtotal'          => $subtotal,
            'discount'          => $discount,
            'discount_type'     => $discount_type,
            'tax_rate'          => $tax_rate,
            'tax_amount'        => $tax_amount,
            'total'             => $total,
            'sales_pic'         => sanitize_text_field($_POST['sales_pic']),
            'valid_until'       => sanitize_text_field($_POST['valid_until']),
            'delivery_location' => sanitize_text_field($_POST['delivery_location']),
            'additional_notes'  => wp_kses_post($_POST['additional_notes']),
            'status'            => sanitize_text_field($_POST['status']),
            'request_date'      => sanitize_text_field($_POST['request_date']),
        );
        
        if ($id > 0) {
            $result = $wpdb->update($table, $data, array('id' => $id));
        } else {
            $result = $wpdb->insert($table, $data);
            $id = $wpdb->insert_id;
        }
        
        if ($result !== false) {
            self::log_activity($id > 0 ? 'update' : 'create', 'quotation', $id);
            wp_send_json_success(array('message' => 'Quotation saved', 'id' => $id));
        } else {
            wp_send_json_error(array('message' => 'Failed to save quotation'));
        }
        
        exit;
    }
    
    /**
     * Update Quotation Status
     */
    public static function update_quotation_status() {
        self::verify_nonce();
        
        global $wpdb;
        $table = $wpdb->prefix . 'gti_quotations';
        
        $id = intval($_POST['id']);
        $status = sanitize_text_field($_POST['status']);
        
        // Get current record BEFORE updating (for email)
        $current = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id),
            ARRAY_A
        );
        
        $result = $wpdb->update(
            $table,
            array('status' => $status),
            array('id' => $id)
        );
        
        if ($result !== false) {
            self::log_activity('status_change', 'quotation', $id, array('new_status' => $status));
            
            // Send email notification to customer
            if ($current && ($current['status'] ?? '') !== $status && !empty($current['customer_email'])) {
                self::send_status_email('quotation', $status, $current);
            }
            
            wp_send_json_success(array('message' => 'Status updated'));
        } else {
            wp_send_json_error(array('message' => 'Failed to update status'));
        }
        
        exit;
    }
    
    /**
     * Get Sell Request Detail (for right drawer)
     */
    public static function get_sell_request_detail() {
        self::verify_nonce();
        
        global $wpdb;
        $table = $wpdb->prefix . 'gti_sell_requests';
        $id = intval($_POST['id']);
        
        $request = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id)
        );
        
        if ($request) {
            // Parse images JSON
            $request->images_array = json_decode($request->images, true) ?: array();
            
            wp_send_json_success($request);
        } else {
            wp_send_json_error(array('message' => 'Sell request not found'));
        }
        
        exit;
    }
    
    /**
     * Update Sell Request Status
     */
    public static function update_sell_request_status() {
        self::verify_nonce();
        
        global $wpdb;
        $table = $wpdb->prefix . 'gti_sell_requests';
        
        $id = intval($_POST['id']);
        $status = sanitize_text_field($_POST['status']);
        
        // Get current record BEFORE updating (for email)
        $current = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id),
            ARRAY_A
        );
        
        $result = $wpdb->update(
            $table,
            array('status' => $status),
            array('id' => $id)
        );
        
        if ($result !== false) {
            self::log_activity('status_change', 'sell_request', $id, array('new_status' => $status));
            
            // Send email notification to customer
            if ($current && ($current['status'] ?? '') !== $status && !empty($current['customer_email'])) {
                self::send_status_email('sell', $status, $current);
            }
            
            wp_send_json_success(array('message' => 'Status updated'));
        } else {
            wp_send_json_error(array('message' => 'Failed to update status'));
        }
        
        exit;
    }
    
    /**
     * Save Customer
     */
    public static function save_customer() {
        self::verify_nonce();
        
        global $wpdb;
        $table = $wpdb->prefix . 'gti_customers';
        
        $id = intval($_POST['id'] ?? 0);
        $data = array(
            'customer_id'   => sanitize_text_field($_POST['customer_id']),
            'name'          => sanitize_text_field($_POST['name']),
            'company'       => sanitize_text_field($_POST['company']),
            'email'         => sanitize_email($_POST['email']),
            'phone'         => sanitize_text_field($_POST['phone']),
            'address'       => wp_kses_post($_POST['address']),
            'industry'      => sanitize_text_field($_POST['industry']),
            'location'      => sanitize_text_field($_POST['location']),
            'status'        => sanitize_text_field($_POST['status']),
            'registered_date' => sanitize_text_field($_POST['registered_date']),
        );
        
        if ($id > 0) {
            $result = $wpdb->update($table, $data, array('id' => $id));
        } else {
            $result = $wpdb->insert($table, $data);
            $id = $wpdb->insert_id;
        }
        
        if ($result !== false) {
            self::log_activity($id > 0 ? 'update' : 'create', 'customer', $id);
            wp_send_json_success(array('message' => 'Customer saved', 'id' => $id));
        } else {
            wp_send_json_error(array('message' => 'Failed to save customer'));
        }
        
        exit;
    }
    
    /**
     * Save User
     */
    public static function save_user() {
        self::verify_nonce();
        
        $user_id = intval($_POST['user_id'] ?? 0);
        $userdata = array(
            'user_login'   => sanitize_user($_POST['user_login']),
            'user_email'   => sanitize_email($_POST['user_email']),
            'first_name'   => sanitize_text_field($_POST['first_name']),
            'last_name'    => sanitize_text_field($_POST['last_name']),
            'role'         => sanitize_text_field($_POST['role']),
        );
        
        // Password only on create or if provided
        if (!empty($_POST['user_pass'])) {
            $userdata['user_pass'] = $_POST['user_pass'];
        }
        
        if ($user_id > 0) {
            $result = wp_update_user($userdata);
        } else {
            if (empty($userdata['user_pass'])) {
                wp_send_json_error(array('message' => 'Password is required for new users'));
                exit;
            }
            $result = wp_insert_user($userdata);
        }
        
        if (!is_wp_error($result)) {
            // Update meta
            update_user_meta($result, 'phone', sanitize_text_field($_POST['phone']));
            update_user_meta($result, 'department', sanitize_text_field($_POST['department']));
            update_user_meta($result, 'profile_photo', intval($_POST['profile_photo'] ?? 0));
            
            self::log_activity($user_id > 0 ? 'update' : 'create', 'user', $result);
            wp_send_json_success(array('message' => 'User saved', 'id' => $result));
        } else {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        exit;
    }
    
    /**
     * Delete User
     */
    public static function delete_user() {
        self::verify_nonce();
        
        $user_id = intval($_POST['id']);
        
        // Don't allow deleting yourself
        if ($user_id == get_current_user_id()) {
            wp_send_json_error(array('message' => 'Cannot delete your own account'));
            exit;
        }
        
        $result = wp_delete_user($user_id);
        
        if ($result) {
            self::log_activity('delete', 'user', $user_id);
            wp_send_json_success(array('message' => 'User deleted'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete user'));
        }
        
        exit;
    }
    
    /**
     * Get Dashboard Statistics
     */
    public static function get_dashboard_stats() {
        self::verify_nonce();
        
        global $wpdb;
        
        $stats = array(
            'equipment_used'    => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}gti_equipment WHERE type = 'used' AND deleted_at IS NULL"),
            'equipment_rental'  => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}gti_equipment WHERE type = 'rental' AND deleted_at IS NULL"),
            'spare_parts'       => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}gti_spare_parts"),
            'requests'          => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}gti_requests"),
            'quotations'        => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}gti_quotations"),
            'customers'         => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}gti_customers"),
            'sell_requests'     => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}gti_sell_requests"),
            'messages'          => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}gti_messages WHERE status = 'unread'"),
        );
        
        // Recent activity
        $stats['recent_activity'] = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}gti_activity_log ORDER BY created_at DESC LIMIT 10"
        );
        
        wp_send_json_success($stats);
        exit;
    }
    
    /**
     * Log activity
     */
    private static function log_activity($action, $entity_type, $entity_id, $details = array()) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'gti_activity_log',
            array(
                'user_id'     => get_current_user_id(),
                'action'      => $action,
                'entity_type' => $entity_type,
                'entity_id'   => $entity_id,
                'details'     => wp_json_encode($details),
            )
        );
    }
    
    /**
     * Send status change email notification to customer
     *
     * @param string $type    'request', 'quotation', or 'sell'
     * @param string $status  New status value
     * @param array  $data    Full row data from DB
     */
    private static function send_status_email($type, $status, $data) {
        global $wpdb;
        
        $customer_name  = $data['customer_name'] ?? 'Customer';
        $customer_email = $data['customer_email'] ?? '';
        
        if (empty($customer_email)) return;
        
        // Reference ID
        $ref_id = '';
        if ($type === 'request')   $ref_id = $data['request_id'] ?? '';
        if ($type === 'quotation') $ref_id = $data['quotation_id'] ?? '';
        if ($type === 'sell')      $ref_id = $data['equipment_name'] ?? '';
        
        // Subject map
        $subjects = array(
            'request_new'            => 'Pesanan Anda Telah Diterima - ' . $ref_id,
            'request_processing'     => 'Pesanan Anda Sedang Diproses - ' . $ref_id,
            'request_proposal_sent'  => 'Proposal Telah Dikirim - ' . $ref_id,
            'request_closed'         => 'Pesanan Selesai - ' . $ref_id,
            'quotation_new'          => 'Quotation Request Diterima - ' . $ref_id,
            'quotation_processing'   => 'Quotation Sedang Disusun - ' . $ref_id,
            'quotation_waiting_customer' => 'Quotation Telah Dikirim - ' . $ref_id,
            'quotation_approved'     => 'Quotation Disetujui - ' . $ref_id,
            'quotation_rejected'     => 'Quotation Belum Dapat Diproses - ' . $ref_id,
            'quotation_completed'    => 'Transaksi Selesai - ' . $ref_id,
            'sell_new'               => 'Tawaran Equipment Diterima - ' . $ref_id,
            'sell_processing'        => 'Tawaran Sedang Direview - ' . $ref_id,
            'sell_approved'          => 'Tawaran Diterima - ' . $ref_id,
            'sell_rejected'          => 'Tawaran Belum Dapat Diterima - ' . $ref_id,
            'sell_completed'         => 'Transaksi Selesai - ' . $ref_id,
        );
        
        $key = $type . '_' . $status;
        $subject = $subjects[$key] ?? 'Status Update - ' . $ref_id;
        
        // Message map
        $messages = array(
            'request_new'           => array('Pesanan Telah Diterima', 'Pesanan Anda dengan nomor <strong>' . $ref_id . '</strong> telah kami terima dan akan segera kami proses.', 'Tim kami akan menghubungi Anda dalam 1-2 hari kerja.'),
            'request_processing'    => array('Pesanan Sedang Diproses', 'Pesanan Anda dengan nomor <strong>' . $ref_id . '</strong> sedang kami proses.', 'Kami akan menghubungi Anda segera jika ada update.'),
            'request_proposal_sent' => array('Proposal Telah Dikirim', 'Proposal untuk pesanan <strong>' . $ref_id . '</strong> telah kami kirim ke email Anda.', 'Silakan cek email Anda untuk detail penawaran.'),
            'request_closed'        => array('Pesanan Selesai', 'Pesanan <strong>' . $ref_id . '</strong> telah selesai diproses.', 'Terima kasih atas kepercayaan Anda.'),
            'quotation_new'              => array('Quotation Request Diterima', 'Quotation request Anda dengan nomor <strong>' . $ref_id . '</strong> telah kami terima.', 'Tim kami akan segera menyiapkan quotation.'),
            'quotation_processing'       => array('Quotation Sedang Disusun', 'Quotation Anda dengan nomor <strong>' . $ref_id . '</strong> sedang kami susun.', 'Tim kami sedang menyiapkan penawaran terbaik untuk Anda.'),
            'quotation_waiting_customer' => array('Quotation Telah Dikirim', 'Quotation dengan nomor <strong>' . $ref_id . '</strong> telah kami kirim ke email Anda.', 'Silakan cek email Anda untuk detail penawaran.'),
            'quotation_approved'         => array('Quotation Disetujui', 'Quotation <strong>' . $ref_id . '</strong> telah disetujui.', 'Tim kami akan segera memproses pesanan Anda.'),
            'quotation_rejected'         => array('Quotation Belum Dapat Diproses', 'Mohon maaf, untuk saat ini kami belum dapat memproses permintaan Anda.', 'Jika ada yang bisa kami bantu di masa mendatang, silakan hubungi kami.'),
            'quotation_completed'        => array('Transaksi Selesai', 'Transaksi untuk quotation <strong>' . $ref_id . '</strong> telah selesai.', 'Terima kasih atas kepercayaan Anda.'),
            'sell_new'        => array('Tawaran Diterima', 'Tawaran Anda untuk equipment <strong>' . $ref_id . '</strong> telah kami terima.', 'Tim kami akan segera mereview tawaran Anda.'),
            'sell_processing' => array('Tawaran Sedang Direview', 'Tawaran Anda untuk equipment <strong>' . $ref_id . '</strong> sedang kami review.', 'Kami akan menghubungi Anda segera.'),
            'sell_approved'   => array('Tawaran Diterima', 'Tawaran Anda untuk equipment <strong>' . $ref_id . '</strong> telah diterima.', 'Kami akan menghubungi Anda untuk langkah selanjutnya.'),
            'sell_rejected'   => array('Tawaran Belum Dapat Diterima', 'Mohon maaf, untuk saat ini kami belum dapat menerima tawaran Anda.', 'Terima kasih atas tawaran Anda.'),
            'sell_completed'  => array('Transaksi Selesai', 'Transaksi pembelian equipment <strong>' . $ref_id . '</strong> telah selesai.', 'Terima kasih atas kepercayaan Anda.'),
        );
        
        $msg = $messages[$key] ?? array('Status Update', 'Status pesanan Anda telah diperbarui.', '');
        $current_date = date('d M Y, H:i');
        $site_name = get_bloginfo('name');
        $site_url  = home_url();

        // Use plain text body to test if HTML is causing delivery issues
        $body = "==================================================\n";
        $body .= $site_name . "\n";
        $body .= "==================================================\n\n";
        $body .= $msg[0] . "\n\n";
        $body .= "Halo " . esc_html($customer_name) . ",\n\n";
        $body .= $msg[1] . "\n\n";
        $body .= $msg[2] . "\n\n";
        $body .= "--------------------------------------------------\n";
        $body .= "Tanggal: " . $current_date . "\n";
        $body .= "Ref: " . $ref_id . "\n";
        $body .= "--------------------------------------------------\n\n";
        $body .= "Email ini dikirim otomatis dari " . $site_name . "\n";
        $body .= "Kunjungi: " . $site_url . "\n";
        
        $admin_email = get_option('admin_email');

        // RFC 2047 encode subject if it contains non-ASCII characters
        $encoded_subject = $subject;
        if (preg_match('/[^\x20-\x7E]/', $subject)) {
            $encoded_subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        }

        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . $site_name . ' <' . $admin_email . '>',
            'Reply-To: ' . $admin_email,
            'Return-Path: ' . $admin_email,
            'X-Mailer: Global-Tractors/1.0',
        );

        // Debug: log email details before sending
        $debug_file = WP_CONTENT_DIR . '/uploads/gti-mail-debug.log';
        $timestamp = date('[Y-m-d H:i:s]');
        $debug_details = $timestamp . ' [' . $type . ':' . $status . '] To=' . $customer_email . ' Subject=' . substr($subject, 0, 50) . ' BodyLen=' . strlen($body) . ' HeadersCount=' . count($headers) . "\n";
        error_log($debug_details, 3, $debug_file);

        $mail_result = wp_mail($customer_email, $encoded_subject, $body, $headers);

        // Log result
        $timestamp = date('[Y-m-d H:i:s]');
        $log_msg = $timestamp . ' [' . $type . ':' . $status . '] To=' . $customer_email . ' RefId=' . $ref_id . ' Result=' . ($mail_result ? 'SENT' : 'FAILED') . "\n";
        error_log($log_msg, 3, $debug_file);

        // Log to email_logs table
        $wpdb->insert(
            $wpdb->prefix . 'gti_email_logs',
            array(
                'type'            => $type,
                'related_id'      => $data['id'] ?? 0,
                'status'          => $status,
                'recipient_email' => $customer_email,
                'subject'         => $subject,
                'sent_status'     => $mail_result ? 'sent' : 'failed',
            ),
            array('%s', '%d', '%s', '%s', '%s', '%s')
        );
    }
}

// Capture mail errors for diagnostics
if (!function_exists('gti_capture_mail_error')) {
    function gti_capture_mail_error($result) {
        if (!$result || ($result instanceof \WP_Error)) {
            $debug_file = WP_CONTENT_DIR . '/uploads/gti-mail-debug.log';
            $timestamp = date('[Y-m-d H:i:s]');
            $error_msg = is_wp_error($result) ? $result->get_error_message() : 'wp_mail() returned false';
            $log_line = $timestamp . ' [MAIL_ERROR] ' . $error_msg . "\n";
            error_log($log_line, 3, $debug_file);
        }
        return $result;
    }
    add_filter('wp_mail_failed', 'gti_capture_mail_error', 10, 1);
}

// Initialize AJAX handlers
GTI_Ajax::init();
