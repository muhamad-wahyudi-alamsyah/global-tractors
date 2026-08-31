<?php
/**
 * GTI Database Helper
 *
 * @package Global_Tractors
 */

defined('ABSPATH') || exit;

class GTI_Database {
    
    private static $instance = null;
    private $wpdb;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }
    
    /**
     * Get all equipment with filters
     */
    public function get_equipment($filters = array(), $limit = 20, $offset = 0) {
        $table = $this->wpdb->prefix . 'gti_equipment';
        $where = "WHERE deleted_at IS NULL";
        $params = array();
        
        if (!empty($filters['type'])) {
            $where .= " AND type = %s";
            $params[] = $filters['type'];
        }
        
        if (!empty($filters['status'])) {
            $where .= " AND status = %s";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['category'])) {
            $where .= " AND category = %s";
            $params[] = $filters['category'];
        }
        
        if (!empty($filters['brand'])) {
            $where .= " AND brand = %s";
            $params[] = $filters['brand'];
        }
        
        if (!empty($filters['condition_status'])) {
            $where .= " AND condition_status = %s";
            $params[] = $filters['condition_status'];
        }
        
        if (!empty($filters['search'])) {
            $where .= " AND (name LIKE %s OR equipment_code LIKE %s OR brand LIKE %s)";
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }
        
        $sql = "SELECT * FROM {$table} {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $params[] = $limit;
        $params[] = $offset;
        
        if (!empty($params)) {
            return $this->wpdb->get_results($this->wpdb->prepare($sql, $params));
        }
        
        return $this->wpdb->get_results("SELECT * FROM {$table} {$where} ORDER BY created_at DESC LIMIT {$limit} OFFSET {$offset}");
    }
    
    /**
     * Get equipment count
     */
    public function get_equipment_count($filters = array()) {
        $table = $this->wpdb->prefix . 'gti_equipment';
        $where = "WHERE deleted_at IS NULL";
        $params = array();
        
        if (!empty($filters['type'])) {
            $where .= " AND type = %s";
            $params[] = $filters['type'];
        }
        
        if (!empty($filters['status'])) {
            $where .= " AND status = %s";
            $params[] = $filters['status'];
        }
        
        $sql = "SELECT COUNT(*) FROM {$table} {$where}";
        
        if (!empty($params)) {
            return $this->wpdb->get_var($this->wpdb->prepare($sql, $params));
        }
        
        return $this->wpdb->get_var($sql);
    }
    
    /**
     * Get single equipment by ID
     */
    public function get_equipment_by_id($id) {
        $table = $this->wpdb->prefix . 'gti_equipment';
        return $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$table} WHERE id = %d AND deleted_at IS NULL", $id)
        );
    }
    
    /**
     * Get all spare parts with filters
     */
    public function get_spare_parts($filters = array(), $limit = 20, $offset = 0) {
        $table = $this->wpdb->prefix . 'gti_spare_parts';
        $where = "WHERE 1=1";
        $params = array();
        
        if (!empty($filters['status'])) {
            $where .= " AND status = %s";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['category'])) {
            $where .= " AND category = %s";
            $params[] = $filters['category'];
        }
        
        if (!empty($filters['brand'])) {
            $where .= " AND brand = %s";
            $params[] = $filters['brand'];
        }
        
        if (!empty($filters['search'])) {
            $where .= " AND (name LIKE %s OR part_number LIKE %s)";
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
        }
        
        $sql = "SELECT * FROM {$table} {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $params[] = $limit;
        $params[] = $offset;
        
        if (!empty($params)) {
            return $this->wpdb->get_results($this->wpdb->prepare($sql, $params));
        }
        
        return $this->wpdb->get_results("SELECT * FROM {$table} {$where} ORDER BY created_at DESC LIMIT {$limit} OFFSET {$offset}");
    }
    
    /**
     * Get spare parts count
     */
    public function get_spare_parts_count($filters = array()) {
        $table = $this->wpdb->prefix . 'gti_spare_parts';
        $where = "WHERE 1=1";
        $params = array();
        
        if (!empty($filters['status'])) {
            $where .= " AND status = %s";
            $params[] = $filters['status'];
        }
        
        $sql = "SELECT COUNT(*) FROM {$table} {$where}";
        
        if (!empty($params)) {
            return $this->wpdb->get_var($this->wpdb->prepare($sql, $params));
        }
        
        return $this->wpdb->get_var($sql);
    }
    
    /**
     * Get all requests with filters
     */
    public function get_requests($filters = array(), $limit = 20, $offset = 0) {
        $table = $this->wpdb->prefix . 'gti_requests';
        $where = "WHERE 1=1";
        $params = array();
        
        if (!empty($filters['status'])) {
            $where .= " AND status = %s";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['search'])) {
            $where .= " AND (request_id LIKE %s OR customer_name LIKE %s OR customer_company LIKE %s)";
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }
        
        $sql = "SELECT * FROM {$table} {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $params[] = $limit;
        $params[] = $offset;
        
        if (!empty($params)) {
            return $this->wpdb->get_results($this->wpdb->prepare($sql, $params));
        }
        
        return $this->wpdb->get_results("SELECT * FROM {$table} {$where} ORDER BY created_at DESC LIMIT {$limit} OFFSET {$offset}");
    }
    
    /**
     * Get requests count
     */
    public function get_requests_count($filters = array()) {
        $table = $this->wpdb->prefix . 'gti_requests';
        $where = "WHERE 1=1";
        $params = array();
        
        if (!empty($filters['status'])) {
            $where .= " AND status = %s";
            $params[] = $filters['status'];
        }
        
        $sql = "SELECT COUNT(*) FROM {$table} {$where}";
        
        if (!empty($params)) {
            return $this->wpdb->get_var($this->wpdb->prepare($sql, $params));
        }
        
        return $this->wpdb->get_var($sql);
    }
    
    /**
     * Get all quotations with filters
     */
    public function get_quotations($filters = array(), $limit = 20, $offset = 0) {
        $table = $this->wpdb->prefix . 'gti_quotations';
        $where = "WHERE 1=1";
        $params = array();
        
        if (!empty($filters['status'])) {
            $where .= " AND status = %s";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['search'])) {
            $where .= " AND (quotation_id LIKE %s OR customer_name LIKE %s OR customer_company LIKE %s)";
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }
        
        $sql = "SELECT * FROM {$table} {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $params[] = $limit;
        $params[] = $offset;
        
        if (!empty($params)) {
            return $this->wpdb->get_results($this->wpdb->prepare($sql, $params));
        }
        
        return $this->wpdb->get_results("SELECT * FROM {$table} {$where} ORDER BY created_at DESC LIMIT {$limit} OFFSET {$offset}");
    }
    
    /**
     * Get quotations count
     */
    public function get_quotations_count($filters = array()) {
        $table = $this->wpdb->prefix . 'gti_quotations';
        $where = "WHERE 1=1";
        $params = array();
        
        if (!empty($filters['status'])) {
            $where .= " AND status = %s";
            $params[] = $filters['status'];
        }
        
        $sql = "SELECT COUNT(*) FROM {$table} {$where}";
        
        if (!empty($params)) {
            return $this->wpdb->get_var($this->wpdb->prepare($sql, $params));
        }
        
        return $this->wpdb->get_var($sql);
    }
    
    /**
     * Get all customers with filters
     */
    public function get_customers($filters = array(), $limit = 20, $offset = 0) {
        $table = $this->wpdb->prefix . 'gti_customers';
        $where = "WHERE 1=1";
        $params = array();
        
        if (!empty($filters['status'])) {
            $where .= " AND status = %s";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['search'])) {
            $where .= " AND (name LIKE %s OR company LIKE %s OR email LIKE %s)";
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }
        
        $sql = "SELECT * FROM {$table} {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $params[] = $limit;
        $params[] = $offset;
        
        if (!empty($params)) {
            return $this->wpdb->get_results($this->wpdb->prepare($sql, $params));
        }
        
        return $this->wpdb->get_results("SELECT * FROM {$table} {$where} ORDER BY created_at DESC LIMIT {$limit} OFFSET {$offset}");
    }
    
    /**
     * Get customers count
     */
    public function get_customers_count($filters = array()) {
        $table = $this->wpdb->prefix . 'gti_customers';
        $where = "WHERE 1=1";
        $params = array();
        
        if (!empty($filters['status'])) {
            $where .= " AND status = %s";
            $params[] = $filters['status'];
        }
        
        $sql = "SELECT COUNT(*) FROM {$table} {$where}";
        
        if (!empty($params)) {
            return $this->wpdb->get_var($this->wpdb->prepare($sql, $params));
        }
        
        return $this->wpdb->get_var($sql);
    }
    
    /**
     * Get all sell requests with filters
     */
    public function get_sell_requests($filters = array(), $limit = 20, $offset = 0) {
        $table = $this->wpdb->prefix . 'gti_sell_requests';
        $where = "WHERE 1=1";
        $params = array();
        
        if (!empty($filters['status'])) {
            $where .= " AND status = %s";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['search'])) {
            $where .= " AND (customer_name LIKE %s OR customer_company LIKE %s OR equipment_name LIKE %s OR equipment_brand LIKE %s)";
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }
        
        $sql = "SELECT * FROM {$table} {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $params[] = $limit;
        $params[] = $offset;
        
        if (!empty($params)) {
            return $this->wpdb->get_results($this->wpdb->prepare($sql, $params));
        }
        
        return $this->wpdb->get_results("SELECT * FROM {$table} {$where} ORDER BY created_at DESC LIMIT {$limit} OFFSET {$offset}");
    }
    
    /**
     * Get sell requests count
     */
    public function get_sell_requests_count($filters = array()) {
        $table = $this->wpdb->prefix . 'gti_sell_requests';
        $where = "WHERE 1=1";
        $params = array();
        
        if (!empty($filters['status'])) {
            $where .= " AND status = %s";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['search'])) {
            $where .= " AND (customer_name LIKE %s OR customer_company LIKE %s OR equipment_name LIKE %s OR equipment_brand LIKE %s)";
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }
        
        $sql = "SELECT COUNT(*) FROM {$table} {$where}";
        
        if (!empty($params)) {
            return $this->wpdb->get_var($this->wpdb->prepare($sql, $params));
        }
        
        return $this->wpdb->get_var($sql);
    }
    
    /**
     * Get single sell request by ID
     */
    public function get_sell_request_by_id($id) {
        $table = $this->wpdb->prefix . 'gti_sell_requests';
        return $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id)
        );
    }
    
    /**
     * Get dashboard statistics
     */
    public function get_dashboard_stats() {
        return array(
            'equipment_used'    => $this->get_equipment_count(array('type' => 'used')),
            'equipment_rental'  => $this->get_equipment_count(array('type' => 'rental')),
            'spare_parts'       => $this->get_spare_parts_count(),
            'requests'          => $this->get_requests_count(),
            'quotations'        => $this->get_quotations_count(),
            'customers'         => $this->get_customers_count(),
            'requests_new'      => $this->get_requests_count(array('status' => 'new')),
            'quotations_new'    => $this->get_quotations_count(array('status' => 'new')),
        );
    }
    
    /**
     * Get equipment by type count
     */
    public function get_equipment_by_status($type = 'used') {
        $table = $this->wpdb->prefix . 'gti_equipment';
        
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT status, COUNT(*) as count FROM {$table} WHERE type = %s AND deleted_at IS NULL GROUP BY status",
                $type
            )
        );
    }
    
    /**
     * Get spare parts by status count
     */
    public function get_spare_parts_by_status() {
        $table = $this->wpdb->prefix . 'gti_spare_parts';
        
        return $this->wpdb->get_results(
            "SELECT status, COUNT(*) as count FROM {$table} GROUP BY status"
        );
    }
}
