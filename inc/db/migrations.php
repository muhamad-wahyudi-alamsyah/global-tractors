<?php
/**
 * Incremental migrations that sit outside the versioned schema.
 *
 * The versioned path lives in includes/class-gti-activator.php and is driven by
 * the gti_db_version option. This file only holds column top-ups that must run
 * regardless of that version, and is invoked from gti_maybe_upgrade().
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Run migrations that add columns without a version bump.
 */
function gti_run_incremental_migrations() {
    require_once __DIR__ . '/migration-add-equipment-columns.php';

    if ( function_exists( 'gti_migration_add_equipment_columns' ) ) {
        gti_migration_add_equipment_columns();
    }
}
