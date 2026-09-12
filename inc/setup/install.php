<?php
/**
 * Install & upgrade.
 *
 * The theme previously only ran setup on `after_switch_theme`, which never
 * fires for a theme that is already active — so schema changes shipped in an
 * update were never applied to a live site. Upgrades now run on every load but
 * short-circuit on an option compare, which is a single cached get_option().
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'after_switch_theme', 'gti_install' );

/**
 * First-time setup when the theme is activated.
 */
function gti_install() {
    GTI_Activator::activate();
    gti_flush_rewrite_rules();
}

add_action( 'init', 'gti_maybe_upgrade', 5 );

/**
 * Apply pending schema/role migrations. No-op once the stored version matches.
 */
function gti_maybe_upgrade() {
    if ( version_compare( get_option( GTI_DB_VERSION_KEY, '0.0.0' ), GTI_DB_VERSION, '>=' ) ) {
        return;
    }

    // Guard against two requests migrating at once.
    if ( ! gti_upgrade_lock_acquire() ) {
        return;
    }

    GTI_Activator::activate();
    gti_run_incremental_migrations();
    gti_flush_rewrite_rules();

    gti_upgrade_lock_release();
}

/**
 * Cheap advisory lock over a transient so a burst of concurrent requests does
 * not run ALTER TABLE in parallel.
 */
function gti_upgrade_lock_acquire() {
    if ( get_transient( 'gti_upgrade_lock' ) ) {
        return false;
    }
    set_transient( 'gti_upgrade_lock', 1, 5 * MINUTE_IN_SECONDS );
    return true;
}

function gti_upgrade_lock_release() {
    delete_transient( 'gti_upgrade_lock' );
}

/**
 * Data-retention cron (PRD §10.6).
 */
add_action( 'init', 'gti_schedule_maintenance' );
function gti_schedule_maintenance() {
    if ( ! wp_next_scheduled( 'gti_daily_maintenance' ) ) {
        wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'gti_daily_maintenance' );
    }
}

add_action( 'gti_daily_maintenance', 'gti_run_retention_purge' );
function gti_run_retention_purge() {
    global $wpdb;

    // Activity log: default 12 months.
    $months = max( 1, (int) get_option( 'gti_activity_retention_months', 12 ) );
    $wpdb->query( $wpdb->prepare(
        "DELETE FROM {$wpdb->prefix}gti_activity_log WHERE created_at < DATE_SUB(NOW(), INTERVAL %d MONTH)",
        $months
    ) );

    // Email log: body archived 6 months, row kept 24 months.
    $wpdb->query( "UPDATE {$wpdb->prefix}gti_email_logs SET body_html = NULL
                   WHERE body_html IS NOT NULL AND sent_at < DATE_SUB(NOW(), INTERVAL 6 MONTH)" );
    $wpdb->query( "DELETE FROM {$wpdb->prefix}gti_email_logs WHERE sent_at < DATE_SUB(NOW(), INTERVAL 24 MONTH)" );
}
