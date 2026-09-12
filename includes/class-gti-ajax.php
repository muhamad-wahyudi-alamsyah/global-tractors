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
        add_action('wp_ajax_gti_delete_request', array(__CLASS__, 'delete_request'));
        
        // Quotations AJAX
        add_action('wp_ajax_gti_save_quotation', array(__CLASS__, 'save_quotation'));
        add_action('wp_ajax_gti_update_quotation_status', array(__CLASS__, 'update_quotation_status'));
        add_action('wp_ajax_gti_delete_quotation', array(__CLASS__, 'delete_quotation'));

        // Sell Equipment AJAX
        add_action('wp_ajax_gti_get_sell_request_detail', array(__CLASS__, 'get_sell_request_detail'));
        add_action('wp_ajax_gti_update_sell_request_status', array(__CLASS__, 'update_sell_request_status'));
        add_action('wp_ajax_gti_delete_sell_request', array(__CLASS__, 'delete_sell_request'));
        
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
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'gti_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed', 'code' => 'bad_nonce'), 403);
            exit;
        }
    }

    /**
     * Capability gate — PRD §9.4 layer 3.
     *
     * Most handlers here only verified the nonce, which any logged-in user can
     * obtain from any dashboard page. That let an inventory user delete a
     * quotation, for example.
     */
    private static function require_cap($capability) {
        if (!current_user_can($capability)) {
            wp_send_json_error(
                array('message' => 'You do not have permission to do that.', 'code' => 'forbidden'),
                403
            );
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
        self::require_cap('gti_manage_equipment');

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
            'negotiable'        => sanitize_text_field( $_POST['negotiable'] ?? '' ),

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
            'warranty_available', 'warranty_period', 'negotiable',
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
                'redirect' => gti_dashboard_url(
                $type === 'rental' ? 'rental-equipment' : 'used-equipment'
            ),
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
        self::require_cap('gti_manage_equipment');

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
        self::require_cap('gti_manage_equipment');

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
        self::require_cap('gti_manage_equipment');
        
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
        self::require_cap('gti_manage_equipment');

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
                "SELECT equipment_code
                FROM {$table}
                WHERE equipment_code LIKE %s
                ORDER BY id DESC
                LIMIT 1",
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
        self::require_cap('gti_manage_spare_parts');
        
        global $wpdb;
        $table = $wpdb->prefix . 'gti_spare_parts';
        
        $id = intval($_POST['id'] ?? 0);
        $is_draft = !empty($_POST['draft']);
        $stock = intval($_POST['stock'] ?? 0);
        $minimum_stock = intval($_POST['minimum_stock'] ?? 10);
        
        // A form may only choose the publication state. The stock level is always
        // derived, so the dashboard badge, the catalog card and the catalog
        // filters can never disagree with the quantity actually on hand.
        $form_status = sanitize_text_field($_POST['status'] ?? '');
        if ($is_draft || 'draft' === $form_status) {
            $status = 'draft';
        } else {
            $status = gti_spare_stock_status($stock, $minimum_stock);
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
        self::require_cap('gti_manage_spare_parts');
        
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
        self::require_cap('gti_manage_spare_parts');
        global $wpdb;
        $table = $wpdb->prefix . 'gti_spare_parts';
        $id = intval($_POST['id'] ?? 0);
        // Derive from the stored quantities so publishing cannot mislabel stock.
        $row = $wpdb->get_row($wpdb->prepare("SELECT stock, minimum_stock FROM {$table} WHERE id = %d", $id));
        if (!$row) {
            wp_send_json_error(array('message' => 'Spare part not found'));
        }
        $status = gti_spare_stock_status($row->stock, $row->minimum_stock);
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
        self::require_cap('gti_manage_spare_parts');

        global $wpdb;
        $table = $wpdb->prefix . 'gti_spare_parts';

        $category = sanitize_text_field($_POST['category'] ?? '');
        $year     = date('Y');

        $abbr = gti_spare_part_category_abbr( $category );

        $prefix = "SP-{$abbr}-{$year}-";

        // Find the highest existing sequence number for this prefix
        $like = $wpdb->esc_like($prefix) . '%';
        $max_code = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT part_number
                FROM {$table}
                WHERE part_number LIKE %s
                ORDER BY id DESC
                LIMIT 1",
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
        // Delegated to the shared inbox flow: the transition is validated
        // against gti_status_map() and the email is sent by GTI_Mailer *after*
        // a committed write, rather than by a priority-5 hook before one (A-04).
        $_POST['entity_type'] = 'request';
        gti_ajax_change_status();
    }
    
    /**
     * Delete Quotation — hard delete (gti_quotations has no deleted_at column)
     */
    public static function delete_quotation() {
        self::verify_nonce();
        self::require_cap('gti_manage_quotations');

        global $wpdb;
        $table = $wpdb->prefix . 'gti_quotations';
        $id = intval($_POST['id']);

        $result = $wpdb->delete($table, array('id' => $id), array('%d'));

        if ($result) {
            self::log_activity('delete', 'quotation', $id);
            wp_send_json_success(array('message' => 'Quotation deleted'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete quotation'));
        }

        exit;
    }

    /**
     * Delete Sell Request — hard delete (gti_sell_requests has no deleted_at column)
     */
    public static function delete_sell_request() {
        self::verify_nonce();
        self::require_cap('gti_manage_requests');

        global $wpdb;
        $table = $wpdb->prefix . 'gti_sell_requests';
        $id = intval($_POST['id']);

        $result = $wpdb->delete($table, array('id' => $id), array('%d'));

        if ($result) {
            self::log_activity('delete', 'sell_request', $id);
            wp_send_json_success(array('message' => 'Sell request deleted'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete sell request'));
        }

        exit;
    }

    /**
     * Delete Request — hard delete (gti_requests has no deleted_at column)
     */
    public static function delete_request() {
        self::verify_nonce();
        self::require_cap('gti_manage_requests');

        global $wpdb;
        $table = $wpdb->prefix . 'gti_requests';
        $id = intval($_POST['id']);

        $result = $wpdb->delete($table, array('id' => $id), array('%d'));

        if ($result) {
            self::log_activity('delete', 'request', $id);
            wp_send_json_success(array('message' => 'Request deleted'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete request'));
        }

        exit;
    }

    /**
     * Save Quotation
     */
    public static function save_quotation() {
        self::verify_nonce();
        self::require_cap('gti_manage_quotations');
        
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
        // Delegated to the shared inbox flow: the transition is validated
        // against gti_status_map() and the email is sent by GTI_Mailer *after*
        // a committed write, rather than by a priority-5 hook before one (A-04).
        $_POST['entity_type'] = 'quotation';
        gti_ajax_change_status();
    }
    
    /**
     * Get Sell Request Detail (for right drawer)
     */
    public static function get_sell_request_detail() {
        self::verify_nonce();
        self::require_cap('gti_manage_requests');
        
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
        // Delegated to the shared inbox flow: the transition is validated
        // against gti_status_map() and the email is sent by GTI_Mailer *after*
        // a committed write, rather than by a priority-5 hook before one (A-04).
        $_POST['entity_type'] = 'sell';
        gti_ajax_change_status();
    }
    
    /**
     * Save Customer
     */
    public static function save_customer() {
        self::verify_nonce();
        self::require_cap('gti_manage_customers');
        
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
            self::log_activity($id > 0 ? 'update' : 'create', 'customer', $id, array('name' => $data['name']));
            if (function_exists('gti_refresh_customer_stats')) {
                gti_refresh_customer_stats($data['customer_id']);
            }
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

        $required_cap = $user_id > 0 ? 'edit_users' : 'create_users';
        if (!current_user_can($required_cap)) {
            wp_send_json_error(array('message' => 'You do not have permission to manage users.'));
            exit;
        }

        // Only roles this user is allowed to hand out.
        $role = sanitize_text_field($_POST['role'] ?? '');
        if ($role && !array_key_exists($role, get_editable_roles())) {
            wp_send_json_error(array('message' => 'That role is not available.'));
            exit;
        }

        $userdata = array(
            'user_login'   => sanitize_user($_POST['user_login'] ?? ''),
            'user_email'   => sanitize_email($_POST['user_email'] ?? ''),
            'first_name'   => sanitize_text_field($_POST['first_name'] ?? ''),
            'last_name'    => sanitize_text_field($_POST['last_name'] ?? ''),
            'display_name' => sanitize_text_field($_POST['display_name'] ?? ''),
            'role'         => $role,
        );
        if ($userdata['display_name'] === '') {
            unset($userdata['display_name']);
        }
        
        // Password only on create or if provided
        if (!empty($_POST['user_pass'])) {
            $userdata['user_pass'] = $_POST['user_pass'];
        }
        
        if ($user_id > 0) {
            $userdata['ID'] = $user_id;
            unset($userdata['user_login']); // user_login is immutable in WordPress
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
            update_user_meta($result, 'gti_phone', sanitize_text_field($_POST['phone'] ?? ''));
            update_user_meta($result, 'department', sanitize_text_field($_POST['department'] ?? ''));
            if (isset($_POST['profile_photo'])) {
                update_user_meta($result, 'profile_photo', intval($_POST['profile_photo']));
            }

            $saved_user = get_userdata($result);
            self::log_activity($user_id > 0 ? 'update' : 'create', 'user', $result, array(
                'name' => $saved_user ? $saved_user->display_name : '',
            ));
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

        if (!current_user_can('delete_users')) {
            wp_send_json_error(array('message' => 'You do not have permission to delete users.'));
            exit;
        }

        $user_id = intval($_POST['id']);
        
        // Don't allow deleting yourself
        if ($user_id == get_current_user_id()) {
            wp_send_json_error(array('message' => 'Cannot delete your own account'));
            exit;
        }
        
        $doomed = get_userdata($user_id);
        $result = wp_delete_user($user_id);

        if ($result) {
            self::log_activity('delete', 'user', $user_id, array(
                'name' => $doomed ? $doomed->display_name : '',
            ));
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
        self::require_cap('gti_access');
        
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
        // Routed through the shared logger so these rows also carry a readable
        // description and an IP address, which /dashboard/activity-log renders.
        if (function_exists('gti_log_activity')) {
            gti_log_activity(
                get_current_user_id(),
                $action,
                gti_activity_build_description($action, $entity_type, $entity_id, $details),
                $entity_type,
                $entity_id,
                $details
            );
            return;
        }

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
}

/**
 * Surface wp_mail() failures.
 *
 * This used to append to wp-content/uploads/gti-mail-debug.log on every failure
 * — an unbounded file in a publicly served directory, the same problem as A-05
 * and A-07. Failures are now recorded per message in wp_gti_email_logs by
 * GTI_Mailer, and this only adds a line to the normal debug log.
 */
if (!function_exists('gti_capture_mail_error')) {
    function gti_capture_mail_error($result) {
        if (!$result || ($result instanceof \WP_Error)) {
            gti_log('wp_mail failed', is_wp_error($result) ? $result->get_error_message() : 'wp_mail() returned false');
        }
        return $result;
    }
    add_filter('wp_mail_failed', 'gti_capture_mail_error', 10, 1);
}

// Handlers are registered from inc/bootstrap.php on `init`.
