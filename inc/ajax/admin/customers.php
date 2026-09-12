<?php
/**
 * Customers AJAX handlers
 *
 * Customer records.
 *
 * Split out of includes/class-gti-ajax.php, which had grown to 1,279 lines
 * covering eight unrelated domains (PRD §13.3 R-04). These are traits rather
 * than new classes so the handlers keep their GTI_Ajax::method() identity and
 * every existing add_action() registration keeps working unchanged.
 *
 * @package global-tractors
 */

defined('ABSPATH') || exit;

trait GTI_Ajax_Customers {

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

}
