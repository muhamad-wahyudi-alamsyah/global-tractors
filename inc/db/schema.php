<?php
/**
 * Database schema
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Get table name dengan prefix.
 *
 * @param string $table Nama tabel (tanpa prefix).
 * @return string
 */
if ( ! function_exists( 'gti_table' ) ) {
    function gti_table( $table ) {
        global $wpdb;
        return $wpdb->prefix . 'gti_' . $table;
    }
}

/**
 * Create custom tables.
 */
if ( ! function_exists( 'gti_create_tables' ) ) {
    function gti_create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
    
        $tables = [];
    
        // Customers table
        $table_name = gti_table( 'customers' );
        $tables[] = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            customer_id VARCHAR(30) NOT NULL,
            name VARCHAR(150) NOT NULL,
            email VARCHAR(150) NOT NULL,
            phone VARCHAR(50),
            company VARCHAR(200),
            industry VARCHAR(100),
            city VARCHAR(100),
            province VARCHAR(100),
            country VARCHAR(100) DEFAULT 'Indonesia',
            address TEXT,
            website VARCHAR(200),
            npwp VARCHAR(50),
            contact_person VARCHAR(150),
            contact_position VARCHAR(100),
            contact_phone VARCHAR(50),
            contact_email VARCHAR(150),
            contact_whatsapp VARCHAR(50),
            avatar_url VARCHAR(500),
            status VARCHAR(20) DEFAULT 'active',
            rating DECIMAL(3,1) DEFAULT 0,
            total_transactions INT DEFAULT 0,
            total_spent BIGINT DEFAULT 0,
            registered_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_contact DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY customer_id (customer_id),
            KEY status (status),
            KEY email (email),
            KEY company (company),
            KEY registered_date (registered_date)
        ) {$charset_collate};";

        // Activity log table
        $table_name = gti_table( 'activity_log' );
        $tables[] = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            action VARCHAR(100) NOT NULL,
            description TEXT,
            ip_address VARCHAR(45),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY action (action),
            KEY created_at (created_at)
        ) {$charset_collate};";
    
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        foreach ( $tables as $sql ) {
            dbDelta( $sql );
        }
    
        update_option( GTI_DB_VERSION_KEY, GTI_DB_VERSION );
    }
}
