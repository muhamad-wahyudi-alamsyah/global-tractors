<?php
/**
 * Migration: Add missing columns to gti_equipment table
 * for the multi-step Add Equipment form.
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function gti_migration_add_equipment_columns() {
    global $wpdb;

    $table = $wpdb->prefix . 'gti_equipment';

    // Check table exists
    $exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
    if ( ! $exists ) return;

    // Get existing columns
    $columns = $wpdb->get_col( "DESCRIBE {$table}", 0 );

    $additions = [
        // Pricing
        'selling_price'      => "ALTER TABLE {$table} ADD COLUMN selling_price DECIMAL(15,2) NULL AFTER price",
        'rental_price'       => "ALTER TABLE {$table} ADD COLUMN rental_price DECIMAL(15,2) NULL AFTER selling_price",
        'vat_included'       => "ALTER TABLE {$table} ADD COLUMN vat_included VARCHAR(10) NULL AFTER rental_price",
        'currency'           => "ALTER TABLE {$table} ADD COLUMN currency VARCHAR(10) DEFAULT 'IDR' AFTER vat_included",
        'price_valid_until'  => "ALTER TABLE {$table} ADD COLUMN price_valid_until DATE NULL AFTER currency",
        'negotiable'         => "ALTER TABLE {$table} ADD COLUMN negotiable VARCHAR(10) NULL AFTER price_valid_until",

        // Additional status
        'availability_status' => "ALTER TABLE {$table} ADD COLUMN availability_status VARCHAR(50) NULL AFTER status",
        'stock_number'       => "ALTER TABLE {$table} ADD COLUMN stock_number VARCHAR(100) NULL AFTER availability_status",
        'ready_to_use'       => "ALTER TABLE {$table} ADD COLUMN ready_to_use VARCHAR(20) NULL AFTER stock_number",
        'service_history'    => "ALTER TABLE {$table} ADD COLUMN service_history VARCHAR(50) NULL AFTER ready_to_use",
        'warranty_available' => "ALTER TABLE {$table} ADD COLUMN warranty_available VARCHAR(20) NULL AFTER service_history",
        'warranty_period'    => "ALTER TABLE {$table} ADD COLUMN warranty_period VARCHAR(255) NULL AFTER warranty_available",
        'buyer_notes'        => "ALTER TABLE {$table} ADD COLUMN buyer_notes TEXT NULL AFTER warranty_period",

        // Engine & Serial
        'serial_number'      => "ALTER TABLE {$table} ADD COLUMN serial_number VARCHAR(100) NULL AFTER hours",
        'engine'             => "ALTER TABLE {$table} ADD COLUMN engine VARCHAR(255) NULL AFTER serial_number",
        'engine_power'       => "ALTER TABLE {$table} ADD COLUMN engine_power VARCHAR(50) NULL AFTER engine",
        'origin_country'     => "ALTER TABLE {$table} ADD COLUMN origin_country VARCHAR(100) NULL AFTER engine_power",

        // Basic specs (direct columns for filtering)
        'operating_weight'   => "ALTER TABLE {$table} ADD COLUMN operating_weight VARCHAR(50) NULL AFTER origin_country",
        'bucket_capacity'    => "ALTER TABLE {$table} ADD COLUMN bucket_capacity VARCHAR(50) NULL AFTER operating_weight",

        // Media
        'video_url'          => "ALTER TABLE {$table} ADD COLUMN video_url VARCHAR(500) NULL AFTER main_image",

        // Additional info
        'detailed_description' => "ALTER TABLE {$table} ADD COLUMN detailed_description TEXT NULL AFTER description",
        'equipment_history'  => "ALTER TABLE {$table} ADD COLUMN detailed_description TEXT NULL AFTER description",
        'previous_usage'     => "ALTER TABLE {$table} ADD COLUMN previous_usage VARCHAR(255) NULL AFTER equipment_history",
        'working_condition'  => "ALTER TABLE {$table} ADD COLUMN working_condition VARCHAR(50) NULL AFTER previous_usage",
        'maintenance_record' => "ALTER TABLE {$table} ADD COLUMN maintenance_record VARCHAR(50) NULL AFTER working_condition",
        'ownership'          => "ALTER TABLE {$table} ADD COLUMN ownership VARCHAR(50) NULL AFTER maintenance_record",
        'operator_hours'     => "ALTER TABLE {$table} ADD COLUMN operator_hours INT NULL AFTER ownership",

        // Location details
        'location_country'   => "ALTER TABLE {$table} ADD COLUMN location_country VARCHAR(100) NULL AFTER location",
        'location_province'  => "ALTER TABLE {$table} ADD COLUMN location_province VARCHAR(100) NULL AFTER location_country",
        'location_city'      => "ALTER TABLE {$table} ADD COLUMN location_city VARCHAR(100) NULL AFTER location_province",
        'detailed_address'   => "ALTER TABLE {$table} ADD COLUMN detailed_address TEXT NULL AFTER location_city",
        'map_location'       => "ALTER TABLE {$table} ADD COLUMN map_location VARCHAR(500) NULL AFTER detailed_address",
        'location_notes'     => "ALTER TABLE {$table} ADD COLUMN location_notes TEXT NULL AFTER map_location",

        // JSON storage for complex nested data
        'features'           => "ALTER TABLE {$table} ADD COLUMN features JSON NULL AFTER specifications",
        'documents'          => "ALTER TABLE {$table} ADD COLUMN documents JSON NULL AFTER features",
    ];

    foreach ( $additions as $col => $sql ) {
        if ( ! in_array( $col, $columns, true ) ) {
            // Skip equipment_history since detailed_description already covers it
            if ( $col === 'equipment_history' ) continue;
            $wpdb->query( $sql );
        }
    }
}
