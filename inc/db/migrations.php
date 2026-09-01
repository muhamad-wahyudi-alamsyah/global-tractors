<?php
/**
 * Database migrations
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Run database migrations.
 */
function gti_run_migrations() {
    global $wpdb;
    
    $current_version = get_option( GTI_DB_VERSION_KEY, '0.0.0' );
    
    if ( version_compare( $current_version, GTI_DB_VERSION, '>=' ) ) {
        // Still run incremental migrations even if version is up to date
        gti_run_incremental_migrations();
        return;
    }
    
    // Create tables
    gti_create_tables();
    
    // Add version tracking
    if ( ! $wpdb->get_var( "SHOW TABLES LIKE '" . gti_table( 'version' ) . "'" ) ) {
        $table_name = gti_table( 'version' );
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            version VARCHAR(50) NOT NULL,
            applied_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) {$charset_collate};";
        
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }
    
    // Record version
    $wpdb->insert(
        gti_table( 'version' ),
        [ 'version' => GTI_DB_VERSION ]
    );

    // Run incremental migrations
    gti_run_incremental_migrations();
}

/**
 * Run incremental migrations that add columns/tables without version bumps.
 */
function gti_run_incremental_migrations() {
    // Migration: Add equipment columns for multi-step form
    require_once __DIR__ . '/migration-add-equipment-columns.php';
    if ( function_exists( 'gti_migration_add_equipment_columns' ) ) {
        gti_migration_add_equipment_columns();
    }
}
