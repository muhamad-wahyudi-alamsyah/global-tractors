<?php
/**
 * Customers — schema, auto-sync from inbound requests, and stats.
 *
 * A customer record is never entered by hand: whenever someone submits a
 * Request Equipment or a Request Quotation, that person is upserted into
 * wp_gti_customers so they show up on /dashboard/customers.
 *
 * @package global-tractors
 */

if (!defined('ABSPATH')) exit;

define('GTI_CUSTOMERS_SCHEMA', '1.1.0');

function gti_customers_table() {
    global $wpdb;
    return $wpdb->prefix . 'gti_customers';
}

/**
 * Create the customers table, or patch one created by a legacy schema.
 *
 * inc/db/schema.php and includes/class-gti-activator.php disagreed about the
 * columns; this creates the superset so either shape keeps working.
 */
function gti_ensure_customers_table($force = false) {
    static $checked = false;
    if ($checked && !$force) return;
    if (!$force && get_option('gti_customers_schema') === GTI_CUSTOMERS_SCHEMA) {
        $checked = true;
        return;
    }

    global $wpdb;
    $table = gti_customers_table();
    $charset_collate = $wpdb->get_charset_collate();

    $wpdb->query(
        "CREATE TABLE IF NOT EXISTS {$table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            customer_id VARCHAR(30) NOT NULL,
            name VARCHAR(150) NOT NULL,
            email VARCHAR(150) NOT NULL DEFAULT '',
            phone VARCHAR(50) NULL,
            company VARCHAR(200) NULL,
            industry VARCHAR(100) NULL,
            city VARCHAR(100) NULL,
            province VARCHAR(100) NULL,
            country VARCHAR(100) DEFAULT 'Indonesia',
            location VARCHAR(255) NULL,
            address TEXT NULL,
            website VARCHAR(200) NULL,
            npwp VARCHAR(50) NULL,
            contact_person VARCHAR(150) NULL,
            contact_position VARCHAR(100) NULL,
            contact_phone VARCHAR(50) NULL,
            contact_email VARCHAR(150) NULL,
            contact_whatsapp VARCHAR(50) NULL,
            avatar_url VARCHAR(500) NULL,
            source VARCHAR(50) NULL,
            status VARCHAR(20) DEFAULT 'active',
            rating DECIMAL(3,1) DEFAULT 0,
            total_transactions INT DEFAULT 0,
            total_spent BIGINT DEFAULT 0,
            registered_date DATETIME NULL,
            last_contact DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL,
            PRIMARY KEY (id),
            UNIQUE KEY customer_id (customer_id),
            KEY status (status),
            KEY email (email),
            KEY company (company),
            KEY registered_date (registered_date)
        ) {$charset_collate}"
    );

    $existing = $wpdb->get_col("SHOW COLUMNS FROM {$table}");
    if ($existing) {
        $wanted = array(
            'industry'         => "VARCHAR(100) NULL",
            'city'             => "VARCHAR(100) NULL",
            'province'         => "VARCHAR(100) NULL",
            'country'          => "VARCHAR(100) DEFAULT 'Indonesia'",
            'location'         => "VARCHAR(255) NULL",
            'address'          => "TEXT NULL",
            'website'          => "VARCHAR(200) NULL",
            'npwp'             => "VARCHAR(50) NULL",
            'contact_person'   => "VARCHAR(150) NULL",
            'contact_position' => "VARCHAR(100) NULL",
            'contact_phone'    => "VARCHAR(50) NULL",
            'contact_email'    => "VARCHAR(150) NULL",
            'contact_whatsapp' => "VARCHAR(50) NULL",
            'avatar_url'       => "VARCHAR(500) NULL",
            'source'           => "VARCHAR(50) NULL",
            'rating'           => "DECIMAL(3,1) DEFAULT 0",
            'total_transactions' => "INT DEFAULT 0",
            'total_spent'      => "BIGINT DEFAULT 0",
            'registered_date'  => "DATETIME NULL",
            'last_contact'     => "DATETIME NULL",
            'updated_at'       => "DATETIME NULL",
        );
        foreach ($wanted as $column => $definition) {
            if (!in_array($column, $existing, true)) {
                $wpdb->query("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
            }
        }
    }

    update_option('gti_customers_schema', GTI_CUSTOMERS_SCHEMA, false);
    $checked = true;
}

/**
 * Next customer id, e.g. CUST-25090701 — matches the ART-/REQ- style used elsewhere.
 */
function gti_generate_customer_id() {
    global $wpdb;
    $table  = gti_customers_table();
    $prefix = 'CUST-' . date('ymd');
    $used   = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table} WHERE customer_id LIKE %s",
        $wpdb->esc_like($prefix) . '%'
    ));

    // Skip over ids already taken (a row may have been deleted and re-added).
    for ($i = $used + 1; $i < $used + 100; $i++) {
        $candidate = $prefix . str_pad($i, 2, '0', STR_PAD_LEFT);
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE customer_id = %s", $candidate));
        if (!$exists) return $candidate;
    }
    return $prefix . wp_rand(100, 999);
}

/**
 * Find an existing customer by email, then by phone.
 *
 * @return object|null
 */
function gti_find_customer($email = '', $phone = '') {
    global $wpdb;
    $table = gti_customers_table();

    if ($email) {
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE email = %s LIMIT 1", $email));
        if ($row) return $row;
    }
    if ($phone) {
        $digits = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($digits) >= 8) {
            $row = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$table} WHERE phone <> '' AND REPLACE(REPLACE(REPLACE(REPLACE(phone,' ',''),'-',''),'(',''),')','') LIKE %s LIMIT 1",
                '%' . $wpdb->esc_like(substr($digits, -9))
            ));
            if ($row) return $row;
        }
    }
    return null;
}

/**
 * Insert or update a customer from an inbound request/quotation.
 *
 * Existing non-empty values are never overwritten with blanks — a later, thinner
 * submission must not wipe details captured earlier.
 *
 * @param array $args           name/email/phone/company/location/source/date
 * @param bool  $refresh_stats  Recompute the counters now. The bulk backfill passes
 *                              false and refreshes each customer once at the end,
 *                              which keeps a page load from issuing thousands of queries.
 * @return string|false The customer_id, or false when there is nothing to key on.
 */
function gti_sync_customer($args, $refresh_stats = true) {
    global $wpdb;

    gti_ensure_customers_table();
    $table = gti_customers_table();

    $name  = sanitize_text_field($args['name'] ?? '');
    $email = sanitize_email($args['email'] ?? '');
    $phone = sanitize_text_field($args['phone'] ?? '');

    // Without at least an email or a phone there is no way to dedupe.
    if (!$email && !$phone) return false;
    if (!$name) $name = $email ? strstr($email, '@', true) : $phone;

    $company  = sanitize_text_field($args['company'] ?? '');
    $location = sanitize_text_field($args['location'] ?? '');
    $source   = sanitize_text_field($args['source'] ?? '');
    $when     = $args['date'] ?? current_time('mysql');
    if (!$when || $when === '0000-00-00 00:00:00') $when = current_time('mysql');

    $existing = gti_find_customer($email, $phone);

    if ($existing) {
        $update = array(
            'last_contact' => $when,
            'updated_at'   => current_time('mysql'),
        );
        // Backfill only the fields that are still blank.
        $fillable = array(
            'name' => $name, 'email' => $email, 'phone' => $phone,
            'company' => $company, 'city' => $location, 'location' => $location,
        );
        foreach ($fillable as $column => $value) {
            if ($value !== '' && empty($existing->$column)) {
                $update[$column] = $value;
            }
        }
        $wpdb->update($table, $update, array('id' => $existing->id));
        if ($refresh_stats) gti_refresh_customer_stats($existing->customer_id);
        return $existing->customer_id;
    }

    $customer_id = gti_generate_customer_id();
    $inserted = $wpdb->insert($table, array(
        'customer_id'     => $customer_id,
        'name'            => $name,
        'email'           => $email,
        'phone'           => $phone,
        'company'         => $company,
        'city'            => $location,
        'location'        => $location,
        'country'         => 'Indonesia',
        'contact_person'  => $name,
        'contact_email'   => $email,
        'contact_phone'   => $phone,
        'contact_whatsapp'=> $phone,
        'source'          => $source,
        'status'          => 'active',
        'registered_date' => $when,
        'last_contact'    => $when,
        'created_at'      => current_time('mysql'),
        'updated_at'      => current_time('mysql'),
    ));

    if (!$inserted) return false;

    if ($refresh_stats) gti_refresh_customer_stats($customer_id);

    if (function_exists('gti_log_activity')) {
        gti_log_activity(
            get_current_user_id(),
            'create',
            'New customer "' . $name . '" created from ' . ($source ?: 'an inbound request'),
            'customer',
            (int) $wpdb->insert_id,
            array('name' => $name, 'source' => $source)
        );
    }

    return $customer_id;
}

/**
 * Requests + quotations belonging to one customer, keyed on email then phone.
 *
 * @return array{requests:int, quotations:int, total_spent:float, last_contact:?string}
 */
function gti_customer_stats($customer) {
    global $wpdb;

    $email = is_object($customer) ? $customer->email : ($customer['email'] ?? '');
    $phone = is_object($customer) ? $customer->phone : ($customer['phone'] ?? '');

    $stats = array('requests' => 0, 'quotations' => 0, 'total_spent' => 0.0, 'last_contact' => null);
    if (!$email && !$phone) return $stats;

    $clauses = array();
    $params  = array();
    if ($email) { $clauses[] = 'customer_email = %s'; $params[] = $email; }
    if ($phone) { $clauses[] = 'customer_phone = %s'; $params[] = $phone; }
    $where = '(' . implode(' OR ', $clauses) . ')';

    $requests_table   = $wpdb->prefix . 'gti_requests';
    $quotations_table = $wpdb->prefix . 'gti_quotations';

    if (gti_table_exists($requests_table)) {
        $stats['requests'] = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$requests_table} WHERE {$where}", $params
        ));
        $last = $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(created_at) FROM {$requests_table} WHERE {$where}", $params
        ));
        if ($last) $stats['last_contact'] = $last;
    }

    if (gti_table_exists($quotations_table)) {
        $stats['quotations'] = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$quotations_table} WHERE {$where}", $params
        ));
        // Only quotations the customer actually accepted count as money spent.
        $stats['total_spent'] = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(total), 0) FROM {$quotations_table}
             WHERE {$where} AND status IN ('approved', 'completed')", $params
        ));
        $last = $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(created_at) FROM {$quotations_table} WHERE {$where}", $params
        ));
        if ($last && (!$stats['last_contact'] || $last > $stats['last_contact'])) {
            $stats['last_contact'] = $last;
        }
    }

    return $stats;
}

/**
 * Recompute the denormalised counters stored on a customer row.
 */
function gti_refresh_customer_stats($customer_id) {
    global $wpdb;
    $table = gti_customers_table();

    $customer = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE customer_id = %s", $customer_id));
    if (!$customer) return;

    $stats = gti_customer_stats($customer);

    $wpdb->update($table, array(
        'total_transactions' => $stats['requests'] + $stats['quotations'],
        'total_spent'        => (int) round($stats['total_spent']),
        'last_contact'       => $stats['last_contact'] ?: $customer->last_contact,
        'updated_at'         => current_time('mysql'),
    ), array('id' => $customer->id));
}

/**
 * Cheap table-exists check, memoised per request.
 */
function gti_table_exists($table) {
    static $cache = array();
    if (isset($cache[$table])) return $cache[$table];
    global $wpdb;
    $cache[$table] = ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table);
    return $cache[$table];
}

/**
 * Backfill customers from every request and quotation already in the database.
 *
 * Runs when /dashboard/customers is opened so historic submissions appear even
 * though they predate the sync hooks. Throttled to once every 5 minutes.
 */
function gti_sync_customers_from_sources($force = false) {
    global $wpdb;

    if (!$force && get_transient('gti_customers_synced')) return 0;
    set_transient('gti_customers_synced', 1, 5 * MINUTE_IN_SECONDS);

    gti_ensure_customers_table();
    $touched = array();

    $requests_table = $wpdb->prefix . 'gti_requests';
    if (gti_table_exists($requests_table)) {
        $rows = $wpdb->get_results(
            "SELECT customer_name, customer_company, customer_email, customer_phone,
                    customer_location, MIN(created_at) AS first_seen
             FROM {$requests_table}
             WHERE customer_email <> '' OR customer_phone <> ''
             GROUP BY customer_email, customer_phone,
                      customer_name, customer_company, customer_location"
        );
        foreach ($rows as $row) {
            $customer_id = gti_sync_customer(array(
                'name'     => $row->customer_name,
                'email'    => $row->customer_email,
                'phone'    => $row->customer_phone,
                'company'  => $row->customer_company,
                'location' => $row->customer_location,
                'source'   => 'request-equipment',
                'date'     => $row->first_seen,
            ), false);
            if ($customer_id) $touched[$customer_id] = true;
        }
    }

    $quotations_table = $wpdb->prefix . 'gti_quotations';
    if (gti_table_exists($quotations_table)) {
        $rows = $wpdb->get_results(
            "SELECT customer_name, customer_company, customer_email, customer_phone,
                    customer_address, MIN(created_at) AS first_seen
             FROM {$quotations_table}
             WHERE customer_email <> '' OR customer_phone <> ''
             GROUP BY customer_email, customer_phone,
                      customer_name, customer_company, customer_address"
        );
        foreach ($rows as $row) {
            $customer_id = gti_sync_customer(array(
                'name'     => $row->customer_name,
                'email'    => $row->customer_email,
                'phone'    => $row->customer_phone,
                'company'  => $row->customer_company,
                'location' => $row->customer_address,
                'source'   => 'request-quotation',
                'date'     => $row->first_seen,
            ), false);
            if ($customer_id) $touched[$customer_id] = true;
        }
    }

    foreach (array_keys($touched) as $customer_id) {
        gti_refresh_customer_stats($customer_id);
    }

    return count($touched);
}

/**
 * Real-time hook — call after inserting a request or quotation.
 *
 * @param string $source 'request-equipment' | 'request-quotation'
 * @param array  $data   The row that was just written.
 */
function gti_sync_customer_from_submission($source, $data) {
    return gti_sync_customer(array(
        'name'     => $data['customer_name'] ?? '',
        'email'    => $data['customer_email'] ?? '',
        'phone'    => $data['customer_phone'] ?? '',
        'company'  => $data['customer_company'] ?? '',
        'location' => $data['customer_location'] ?? ($data['customer_address'] ?? ''),
        'source'   => $source,
        'date'     => $data['created_at'] ?? current_time('mysql'),
    ));
}
add_action('gti_submission_received', 'gti_sync_customer_from_submission', 10, 2);
