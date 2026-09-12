<?php
/**
 * GTI Activator
 *
 * @package Global_Tractors
 */

defined('ABSPATH') || exit;

class GTI_Activator {
    
    /**
     * Run on plugin/theme activation
     */
    public static function activate() {
        // Create database tables
        self::create_tables();
        
        // Create custom roles
        GTI_Roles::create_roles();
        
        // Create admin page
        self::create_admin_page();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Store activation time
        update_option('gti_activation_time', current_time('mysql'));
    }
    
    /**
     * Run on deactivation
     */
    public static function deactivate() {
        flush_rewrite_rules();
    }
    
    /**
     * Create custom database tables
     */
    private static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Equipment table
        $table_equipment = $wpdb->prefix . 'gti_equipment';
        $sql_equipment = "CREATE TABLE {$table_equipment} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            equipment_code VARCHAR(50) UNIQUE NOT NULL,
            name VARCHAR(255) NOT NULL,
            type ENUM('used', 'rental') NOT NULL,
            category VARCHAR(100),
            brand VARCHAR(100),
            model VARCHAR(100),
            year INT,
            condition_status VARCHAR(50),
            status VARCHAR(50) DEFAULT 'available',
            price DECIMAL(15,2),
            price_type ENUM('sale', 'monthly_rental'),
            location VARCHAR(255),
            description TEXT,
            specifications JSON,
            images JSON,
            main_image VARCHAR(500),
            hours INT,
            negotiable TINYINT(1) DEFAULT 0,
            warranty_info TEXT,
            registration_number VARCHAR(100),
            insurance_status VARCHAR(50),
            last_service_date DATE,
            next_service_due DATE,
            notes TEXT,
            created_by BIGINT UNSIGNED,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at DATETIME NULL
        ) {$charset_collate};";
        
        // Spare Parts table
        $table_spare_parts = $wpdb->prefix . 'gti_spare_parts';
        $sql_spare_parts = "CREATE TABLE {$table_spare_parts} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            part_number VARCHAR(100) UNIQUE NOT NULL,
            name VARCHAR(255) NOT NULL,
            category VARCHAR(100),
            brand VARCHAR(100),
            description TEXT,
            stock INT DEFAULT 0,
            minimum_stock INT DEFAULT 10,
            unit_price DECIMAL(15,2),
            supplier VARCHAR(255),
            location VARCHAR(255),
            image VARCHAR(500),
            status VARCHAR(50) DEFAULT 'in_stock',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) {$charset_collate};";
        
        // Requests table
        $table_requests = $wpdb->prefix . 'gti_requests';
        $sql_requests = "CREATE TABLE {$table_requests} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            request_id VARCHAR(20) UNIQUE NOT NULL,
            customer_name VARCHAR(255),
            customer_company VARCHAR(255),
            customer_email VARCHAR(255),
            customer_phone VARCHAR(50),
            customer_location VARCHAR(255),
            equipment VARCHAR(255),
            category VARCHAR(100),
            brand VARCHAR(100),
            quantity INT DEFAULT 1,
            location VARCHAR(255),
            budget DECIMAL(15,2),
            required_date DATE,
            notes TEXT,
            status VARCHAR(50) DEFAULT 'new',
            request_date DATE,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) {$charset_collate};";
        
        // Quotations table
        $table_quotations = $wpdb->prefix . 'gti_quotations';
        $sql_quotations = "CREATE TABLE {$table_quotations} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            quotation_id VARCHAR(20) UNIQUE NOT NULL,
            customer_name VARCHAR(255),
            customer_company VARCHAR(255),
            customer_email VARCHAR(255),
            customer_phone VARCHAR(50),
            customer_address TEXT,
            items JSON,
            subtotal DECIMAL(15,2),
            discount DECIMAL(15,2) DEFAULT 0,
            discount_type ENUM('amount', 'percentage') DEFAULT 'amount',
            tax_rate DECIMAL(5,2) DEFAULT 11.00,
            tax_amount DECIMAL(15,2),
            total DECIMAL(15,2),
            sales_pic VARCHAR(255),
            valid_until DATE,
            delivery_location VARCHAR(255),
            additional_notes TEXT,
            status VARCHAR(50) DEFAULT 'new',
            request_date DATE,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) {$charset_collate};";
        
        // Customers table
        $table_customers = $wpdb->prefix . 'gti_customers';
        $sql_customers = "CREATE TABLE {$table_customers} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            customer_id VARCHAR(20) UNIQUE NOT NULL,
            name VARCHAR(255) NOT NULL,
            company VARCHAR(255),
            email VARCHAR(255),
            phone VARCHAR(50),
            address TEXT,
            industry VARCHAR(100),
            location VARCHAR(255),
            status VARCHAR(50) DEFAULT 'active',
            registered_date DATE,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) {$charset_collate};";
        
        // Sell Equipment Requests table
        $table_sell_requests = $wpdb->prefix . 'gti_sell_requests';
        $sql_sell_requests = "CREATE TABLE {$table_sell_requests} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            customer_name VARCHAR(255),
            customer_company VARCHAR(255),
            customer_email VARCHAR(255),
            customer_phone VARCHAR(50),
            equipment_name VARCHAR(255),
            equipment_brand VARCHAR(100),
            equipment_model VARCHAR(100),
            equipment_year INT,
            equipment_condition VARCHAR(50),
            equipment_hours INT,
            offered_price DECIMAL(15,2),
            images JSON,
            status VARCHAR(50) DEFAULT 'new',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) {$charset_collate};";
        
        // Contact Messages table
        $table_messages = $wpdb->prefix . 'gti_messages';
        $sql_messages = "CREATE TABLE {$table_messages} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            sender_name VARCHAR(255),
            sender_email VARCHAR(255),
            subject VARCHAR(255),
            message TEXT,
            status VARCHAR(50) DEFAULT 'unread',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) {$charset_collate};";
        
        // Activity Log table
        $table_activity = $wpdb->prefix . 'gti_activity_log';
        $sql_activity = "CREATE TABLE {$table_activity} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED,
            action VARCHAR(100),
            entity_type VARCHAR(50),
            entity_id BIGINT UNSIGNED,
            details JSON,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) {$charset_collate};";
        
        // Require dbDelta
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Create tables
        dbDelta($sql_equipment);
        dbDelta($sql_spare_parts);
        dbDelta($sql_requests);
        dbDelta($sql_quotations);
        dbDelta($sql_customers);
        dbDelta($sql_sell_requests);
        dbDelta($sql_messages);
        dbDelta($sql_activity);
        
        // Migrations: add missing columns
        $db_version = get_option('gti_db_version', '1.0.0');
        if (version_compare($db_version, '1.1.0', '<')) {
            // Add category column to requests table if missing
            $col = $wpdb->get_var("SHOW COLUMNS FROM {$table_requests} LIKE 'category'");
            if (!$col) {
                $wpdb->query("ALTER TABLE {$table_requests} ADD COLUMN category VARCHAR(100) AFTER equipment");
            }
        }

        // Equipment: columns added after the original 1.0.0 schema above.
        // dbDelta() will not add them on its own, and the live database was migrated by hand,
        // so a fresh install would silently drop every field from form steps 2-5.
        // Keep this map in sync whenever the add/edit form gains a stored field.
        if (version_compare($db_version, '1.2.0', '<')) {
            $equipment_columns = array(
                'availability_status'  => "VARCHAR(50)",
                'stock_number'         => "VARCHAR(100)",
                'ready_to_use'         => "VARCHAR(20)",
                'service_history'      => "VARCHAR(50)",
                'buyer_notes'          => "TEXT",
                'selling_price'        => "DECIMAL(15,2)",
                'rental_price'         => "DECIMAL(15,2)",
                'vat_included'         => "VARCHAR(10)",
                'currency'             => "VARCHAR(10)",
                'price_valid_until'    => "DATE",
                'location_country'     => "VARCHAR(100)",
                'location_province'    => "VARCHAR(100)",
                'location_city'        => "VARCHAR(100)",
                'detailed_address'     => "TEXT",
                'map_location'         => "VARCHAR(500)",
                'location_notes'       => "TEXT",
                'detailed_description' => "TEXT",
                'equipment_history'    => "TEXT",
                'previous_usage'       => "VARCHAR(255)",
                'working_condition'    => "VARCHAR(50)",
                'maintenance_record'   => "VARCHAR(50)",
                'ownership'            => "VARCHAR(50)",
                'operator_hours'       => "INT",
                'features'             => "LONGTEXT",
                'documents'            => "LONGTEXT",
                'video_url'            => "VARCHAR(500)",
                'serial_number'        => "VARCHAR(100)",
                'engine'               => "VARCHAR(255)",
                'engine_power'         => "VARCHAR(50)",
                'origin_country'       => "VARCHAR(100)",
                'operating_weight'     => "VARCHAR(50)",
                'bucket_capacity'      => "VARCHAR(50)",
                'warranty_available'   => "VARCHAR(20)",
                'warranty_period'      => "VARCHAR(255)",
            );

            $existing = $wpdb->get_col("SHOW COLUMNS FROM {$table_equipment}");
            foreach ($equipment_columns as $column => $definition) {
                if (!in_array($column, $existing, true)) {
                    $wpdb->query("ALTER TABLE {$table_equipment} ADD COLUMN `{$column}` {$definition}");
                }
            }

            // price_type was an ENUM('sale','monthly_rental'); the form also submits
            // 'rental', 'sale_and_rental' and 'price_on_ask', which that ENUM rejects.
            $wpdb->query("ALTER TABLE {$table_equipment} MODIFY COLUMN price_type VARCHAR(50)");
        }

        // Seed demo request rows. Off by default: a production inbox must never
        // show orders that no customer placed (same rule as PRD §7.5 for the catalogue).
        $req_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_requests}");
        if ($req_count === 0 && get_option('gti_show_demo_data', 'no') === 'yes') {
            $dummy_requests = array(
                array('REQ-2026-001', 'Budi Santoso', 'PT Maju Jaya', 'budi@majujaya.co.id', '081234567890', 'Jakarta', 'Excavator PC200', 'Heavy Equipment', 'Komatsu', 2, 'Jakarta', 1500000000.00, '2026-09-15', 'Need for mining project', 'processing', '2026-08-15'),
                array('REQ-2026-002', 'Ahmad Hidayat', 'CV Berkah Konstruksi', 'ahmad@berkah.co.id', '082345678901', 'Surabaya', 'Wheel Loader WA320', 'Heavy Equipment', 'Komatsu', 1, 'Surabaya', 850000000.00, '2026-09-20', '', 'new', '2026-08-18'),
                array('REQ-2026-003', 'Siti Rahayu', 'PT Abadi Sejahtera', 'siti@abadi.co.id', '083456789012', 'Bandung', 'Crane RT550', 'Lifting', 'Tadano', 1, 'Bandung', 2100000000.00, '2026-10-01', 'Urgent requirement for bridge project', 'proposal_sent', '2026-08-10'),
                array('REQ-2026-004', 'Dewi Lestari', 'PT Nusantara Mining', 'dewi@nusantara.co.id', '084567890123', 'Balikpapan', 'Bulldozer D65', 'Heavy Equipment', 'Komatsu', 3, 'Kalimantan', 3200000000.00, '2026-09-30', 'For coal mining operations', 'new', '2026-08-20'),
                array('REQ-2026-005', 'Rudi Hartono', 'CV Gemilang', 'rudi@gemilang.co.id', '085678901234', 'Medan', 'Forklift FD30', 'Material Handling', 'Toyota', 2, 'Medan', 450000000.00, '2026-09-10', '', 'processing', '2026-08-12'),
                array('REQ-2026-006', 'Indra Wijaya', 'PT Sinar Terang', 'indra@sinar.co.id', '086789012345', 'Semarang', 'Concrete Pump', 'Concrete', 'Putzmeister', 1, 'Semarang', 1800000000.00, '2026-10-15', 'High-rise building project', 'new', '2026-08-22'),
                array('REQ-2026-007', 'Maya Putri', 'PT Bumi Kencana', 'maya@bumi.co.id', '087890123456', 'Makassar', 'Excavator ZX210', 'Heavy Equipment', 'Hitachi', 1, 'Makassar', 1350000000.00, '2026-09-25', '', 'proposal_sent', '2026-08-08'),
                array('REQ-2026-008', 'Hendra Kusuma', 'CV Prima Jaya', 'hendra@prima.co.id', '088901234567', 'Palembang', 'Dump Truck HD785', 'Trucking', 'Komatsu', 5, 'Palembang', 8500000000.00, '2026-10-30', 'Large scale mining project', 'closed', '2026-07-25'),
                array('REQ-2026-009', 'Rina Susanti', 'PT Mandiri Sejahtera', 'rina@mandiri.co.id', '089012345678', 'Yogyakarta', 'Mini Excavator PC55', 'Heavy Equipment', 'Komatsu', 2, 'Yogyakarta', 650000000.00, '2026-09-18', 'Small construction project', 'new', '2026-08-25'),
                array('REQ-2026-010', 'Agus Setiawan', 'PT Cahaya Baru', 'agus@cahaya.co.id', '081123456789', 'Batam', 'Generator 500kVA', 'Power', 'Caterpillar', 3, 'Batam', 900000000.00, '2026-10-05', 'Backup power for factory', 'processing', '2026-08-14'),
                array('REQ-2026-011', 'Lina Marlina', 'CV Berkah Abadi', 'lina@berkahabadi.co.id', '082234567890', 'Balikpapan', 'Crane LTM1100', 'Lifting', 'Liebherr', 1, 'Balikpapan', 4500000000.00, '2026-11-01', 'Heavy lift project', 'new', '2026-08-26'),
                array('REQ-2026-012', 'Joko Prasetyo', 'PT Teknologi Nusantara', 'joko@teknusa.co.id', '083345678901', 'Surabaya', 'Wheel Loader WA470', 'Heavy Equipment', 'Komatsu', 1, 'Surabaya', 1200000000.00, '2026-09-28', '', 'closed', '2026-07-30'),
            );

            foreach ($dummy_requests as $req) {
                $wpdb->insert($table_requests, array(
                    'request_id'        => $req[0],
                    'customer_name'     => $req[1],
                    'customer_company'  => $req[2],
                    'customer_email'    => $req[3],
                    'customer_phone'    => $req[4],
                    'customer_location' => $req[5],
                    'equipment'         => $req[6],
                    'category'          => $req[7],
                    'brand'             => $req[8],
                    'quantity'          => $req[9],
                    'location'          => $req[10],
                    'budget'            => $req[11],
                    'required_date'     => $req[12],
                    'notes'             => $req[13],
                    'status'            => $req[14],
                    'request_date'      => $req[15],
                ), array('%s','%s','%s','%s','%s','%s','%s','%s','%s','%d','%s','%f','%s','%s','%s','%s'));
            }
        }

        // ── v1.3.0 (PRD §4) ──────────────────────────────────────────────
        // New columns are added with SHOW COLUMNS + ALTER TABLE rather than
        // dbDelta(), because the CREATE TABLE statements above were never
        // updated to match and dbDelta() only reconciles against those.
        if (version_compare($db_version, '1.3.0', '<')) {
            self::add_columns($table_requests, array(
                'usage_purpose'    => 'VARCHAR(255) NULL',
                'message'          => 'TEXT NULL',
                'customer_address' => 'TEXT NULL',
                'assigned_to'      => 'BIGINT UNSIGNED NULL',
                'assigned_at'      => 'DATETIME NULL',
                'sales_pic'        => 'VARCHAR(255) NULL',
                'updated_by'       => 'BIGINT UNSIGNED NULL',
                'source'           => 'VARCHAR(50) NULL',
            ));

            self::add_columns($table_quotations, array(
                'equipment_id'      => 'BIGINT UNSIGNED NULL',
                'equipment_type'    => 'VARCHAR(20) NULL',
                'quantity'          => 'INT DEFAULT 1',
                'needed_date'       => 'DATE NULL',
                'rental_start_date' => 'DATE NULL',
                'rental_end_date'   => 'DATE NULL',
                'rental_duration'   => 'VARCHAR(50) NULL',
                'payment_terms'     => 'VARCHAR(50) NULL',
                'budget'            => 'DECIMAL(15,2) NULL',
                'assigned_to'       => 'BIGINT UNSIGNED NULL',
                'assigned_at'       => 'DATETIME NULL',
                'updated_by'        => 'BIGINT UNSIGNED NULL',
                'source_url'        => 'VARCHAR(500) NULL',
            ));

            self::add_columns($table_sell_requests, array(
                'customer_whatsapp'    => 'VARCHAR(50) NULL',
                'equipment_location'   => 'VARCHAR(255) NULL',
                'message'              => 'TEXT NULL',
                'invoice_requested_at' => 'DATETIME NULL',
                'invoice_received_at'  => 'DATETIME NULL',
                'assigned_to'          => 'BIGINT UNSIGNED NULL',
                'assigned_at'          => 'DATETIME NULL',
                'sales_pic'            => 'VARCHAR(255) NULL',
                'updated_by'           => 'BIGINT UNSIGNED NULL',
            ));

            self::create_v130_tables($charset_collate);
            self::add_v130_indexes();

            // Capabilities gained three new entries in §9.2; re-run role setup so
            // existing installs pick them up rather than only fresh ones.
            if (class_exists('GTI_Roles')) {
                GTI_Roles::create_roles();
            }
        }

        // Store table version
        update_option('gti_db_version', '1.3.0');
    }

    /**
     * Add columns that are missing from a table. Existing columns are left alone,
     * so this is safe to re-run.
     *
     * @param string $table   Fully-qualified table name.
     * @param array  $columns column name => SQL type definition.
     */
    private static function add_columns($table, array $columns) {
        global $wpdb;

        $existing = $wpdb->get_col("SHOW COLUMNS FROM {$table}");
        if (!is_array($existing)) {
            return;
        }

        foreach ($columns as $column => $definition) {
            if (!in_array($column, $existing, true)) {
                $wpdb->query("ALTER TABLE {$table} ADD COLUMN `{$column}` {$definition}");
            }
        }
    }

    /**
     * Tables introduced in v1.3.0 (PRD §4.5 – §4.7).
     *
     * gti_email_logs already existed, but was created lazily inside the mail
     * routine — every outgoing message ran dbDelta(). It is declared here now.
     */
    private static function create_v130_tables($charset_collate) {
        global $wpdb;

        $attachments    = $wpdb->prefix . 'gti_attachments';
        $status_history = $wpdb->prefix . 'gti_status_history';
        $email_logs     = $wpdb->prefix . 'gti_email_logs';

        $sql_attachments = "CREATE TABLE {$attachments} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            entity_type VARCHAR(20) NOT NULL,
            entity_id BIGINT UNSIGNED NOT NULL,
            kind VARCHAR(20) NOT NULL,
            attachment_id BIGINT UNSIGNED NOT NULL,
            original_name VARCHAR(255) NULL,
            file_size BIGINT UNSIGNED NULL,
            mime_type VARCHAR(100) NULL,
            note TEXT NULL,
            emailed_at DATETIME NULL,
            uploaded_by BIGINT UNSIGNED NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_entity (entity_type, entity_id),
            KEY idx_kind (kind)
        ) {$charset_collate};";

        $sql_status_history = "CREATE TABLE {$status_history} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            entity_type VARCHAR(20) NOT NULL,
            entity_id BIGINT UNSIGNED NOT NULL,
            from_status VARCHAR(50) NULL,
            to_status VARCHAR(50) NOT NULL,
            note TEXT NULL,
            user_id BIGINT UNSIGNED NULL,
            email_sent TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_entity (entity_type, entity_id, created_at)
        ) {$charset_collate};";

        $sql_email_logs = "CREATE TABLE {$email_logs} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            type VARCHAR(50) NOT NULL,
            related_id BIGINT UNSIGNED NOT NULL,
            status VARCHAR(50) NOT NULL,
            recipient_email VARCHAR(190) NOT NULL,
            cc VARCHAR(500) NULL,
            subject VARCHAR(255) NOT NULL,
            body_html LONGTEXT NULL,
            attachment_ids VARCHAR(255) NULL,
            send_result TINYINT(1) DEFAULT 0,
            error_message TEXT NULL,
            sent_by BIGINT UNSIGNED NULL,
            sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_type_id (type, related_id)
        ) {$charset_collate};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_attachments);
        dbDelta($sql_status_history);
        dbDelta($sql_email_logs);

        // The lazily-created email log predates body_html/cc/attachment_ids.
        self::add_columns($email_logs, array(
            'cc'             => 'VARCHAR(500) NULL',
            'body_html'      => 'LONGTEXT NULL',
            'attachment_ids' => 'VARCHAR(255) NULL',
            'send_result'    => 'TINYINT(1) DEFAULT 0',
            'error_message'  => 'TEXT NULL',
            'sent_by'        => 'BIGINT UNSIGNED NULL',
        ));
    }

    /**
     * PRD §4.8 — indexes that keep the list queries cheap past a few thousand rows.
     */
    private static function add_v130_indexes() {
        global $wpdb;

        $indexes = array(
            array($wpdb->prefix . 'gti_requests',      'idx_status_created', '(status, created_at)'),
            array($wpdb->prefix . 'gti_requests',      'idx_assigned',       '(assigned_to)'),
            array($wpdb->prefix . 'gti_quotations',    'idx_status_created', '(status, created_at)'),
            array($wpdb->prefix . 'gti_quotations',    'idx_assigned',       '(assigned_to)'),
            array($wpdb->prefix . 'gti_sell_requests', 'idx_status_created', '(status, created_at)'),
            array($wpdb->prefix . 'gti_sell_requests', 'idx_assigned',       '(assigned_to)'),
            array($wpdb->prefix . 'gti_equipment',     'idx_type_status',    '(type, status, deleted_at)'),
            array($wpdb->prefix . 'gti_customers',     'idx_email',          '(email)'),
            array($wpdb->prefix . 'gti_activity_log',  'idx_created',        '(created_at)'),
        );

        foreach ($indexes as $index) {
            list($table, $name, $columns) = $index;
            $exists = $wpdb->get_var($wpdb->prepare(
                "SHOW INDEX FROM {$table} WHERE Key_name = %s",
                $name
            ));
            if (!$exists) {
                $wpdb->query("ALTER TABLE {$table} ADD INDEX {$name} {$columns}");
            }
        }
    }
    
    /**
     * Create admin page
     */
    private static function create_admin_page() {
        // This will be handled by GTI_Admin_Menu
    }
}
