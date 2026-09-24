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
        // Must mirror modal-customer.php field for field: array_intersect_key()
        // below filters from $data, so any column missing here is dropped
        // silently and the admin sees a green "Customer saved" either way.
        $data = array(
            'customer_id'      => sanitize_text_field($_POST['customer_id'] ?? ''),
            'name'             => sanitize_text_field($_POST['name'] ?? ''),
            'company'          => sanitize_text_field($_POST['company'] ?? ''),
            'email'            => sanitize_email($_POST['email'] ?? ''),
            'phone'            => sanitize_text_field($_POST['phone'] ?? ''),
            'address'          => wp_kses_post($_POST['address'] ?? ''),
            'city'             => sanitize_text_field($_POST['city'] ?? ''),
            'province'         => sanitize_text_field($_POST['province'] ?? ''),
            'country'          => sanitize_text_field($_POST['country'] ?? ''),
            'contact_person'   => sanitize_text_field($_POST['contact_person'] ?? ''),
            'contact_phone'    => sanitize_text_field($_POST['contact_phone'] ?? ''),
            'contact_email'    => sanitize_email($_POST['contact_email'] ?? ''),
            'contact_whatsapp' => sanitize_text_field($_POST['contact_whatsapp'] ?? ''),
            'industry'         => sanitize_text_field($_POST['industry'] ?? ''),
            'location'         => sanitize_text_field($_POST['location'] ?? ''),
            'status'           => sanitize_text_field($_POST['status'] ?? ''),
            'registered_date'  => sanitize_text_field($_POST['registered_date'] ?? ''),
        );
        // Only touch columns the form actually sent; the rest keep their value.
        $data = array_intersect_key($data, $_POST);

        if (empty($data['name'])) {
            wp_send_json_error(array('message' => 'Nama customer wajib diisi'));
        }

        if ($id > 0) {
            $data['updated_at'] = current_time('mysql');
            $result = $wpdb->update($table, $data, array('id' => $id));
        } else {
            // customer_id is UNIQUE and has no default; a blank one collides
            // with the previous blank insert.
            if (empty($data['customer_id'])) {
                $data['customer_id'] = gti_generate_customer_id();
            }
            if (empty($data['registered_date'])) {
                $data['registered_date'] = current_time('mysql');
            }
            $result = $wpdb->insert($table, $data);
            $id = $wpdb->insert_id;
        }

        if ($result !== false) {
            self::log_activity($id > 0 ? 'update' : 'create', 'customer', $id, array('name' => $data['name']));
            // Guarded: an edit form that never sends customer_id used to raise
            // "Undefined array key", which corrupts the JSON response on any
            // install with display_errors on.
            if (!empty($data['customer_id']) && function_exists('gti_refresh_customer_stats')) {
                gti_refresh_customer_stats($data['customer_id']);
            }
            wp_send_json_success(array('message' => 'Customer saved', 'id' => $id));
        } else {
            wp_send_json_error(array('message' => 'Failed to save customer'));
        }

        exit;
    }

}
