<?php
/**
 * Equipment AJAX handlers
 *
 * Used and rental equipment CRUD.
 *
 * Split out of includes/class-gti-ajax.php, which had grown to 1,279 lines
 * covering eight unrelated domains (PRD §13.3 R-04). These are traits rather
 * than new classes so the handlers keep their GTI_Ajax::method() identity and
 * every existing add_action() registration keeps working unchanged.
 *
 * @package global-tractors
 */

defined('ABSPATH') || exit;

trait GTI_Ajax_Equipment {

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

}
