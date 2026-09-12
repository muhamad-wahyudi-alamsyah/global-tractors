<?php
/**
 * Inbox row handlers (legacy action names)
 *
 * The save/delete handlers behind the original gti_update_*_status and
 * gti_delete_* actions. The status ones now delegate to gti_ajax_change_status()
 * in inbox.php so every transition goes through the state machine.
 *
 * Split out of includes/class-gti-ajax.php, which had grown to 1,279 lines
 * covering eight unrelated domains (PRD §13.3 R-04). These are traits rather
 * than new classes so the handlers keep their GTI_Ajax::method() identity and
 * every existing add_action() registration keeps working unchanged.
 *
 * @package global-tractors
 */

defined('ABSPATH') || exit;

trait GTI_Ajax_Inbox {

/**
     * Update Request Status
     */
public static function update_request_status() {
        // Delegated to the shared inbox flow: the transition is validated
        // against gti_status_map() and the email is sent by GTI_Mailer *after*
        // a committed write, rather than by a priority-5 hook before one (A-04).
        $_POST['entity_type'] = 'request';
        gti_ajax_change_status();
    }

/**
     * Delete Request — hard delete (gti_requests has no deleted_at column)
     */
    public static function delete_request() {
        self::verify_nonce();
        self::require_cap('gti_manage_requests');

        global $wpdb;
        $table = $wpdb->prefix . 'gti_requests';
        $id = intval($_POST['id']);

        $result = $wpdb->delete($table, array('id' => $id), array('%d'));

        if ($result) {
            self::log_activity('delete', 'request', $id);
            wp_send_json_success(array('message' => 'Request deleted'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete request'));
        }

        exit;
    }

/**
     * Save Quotation
     */
    public static function save_quotation() {
        self::verify_nonce();
        self::require_cap('gti_manage_quotations');
        
        global $wpdb;
        $table = $wpdb->prefix . 'gti_quotations';
        
        $id = intval($_POST['id'] ?? 0);
        
        // Calculate totals
        $subtotal = floatval($_POST['subtotal']);
        $discount = floatval($_POST['discount']);
        $discount_type = sanitize_text_field($_POST['discount_type'] ?? 'amount');
        $tax_rate = floatval($_POST['tax_rate'] ?? 11);
        
        if ($discount_type === 'percentage') {
            $discount_amount = $subtotal * ($discount / 100);
        } else {
            $discount_amount = $discount;
        }
        
        $tax_amount = ($subtotal - $discount_amount) * ($tax_rate / 100);
        $total = $subtotal - $discount_amount + $tax_amount;
        
        $data = array(
            'quotation_id'      => sanitize_text_field($_POST['quotation_id']),
            'customer_name'     => sanitize_text_field($_POST['customer_name']),
            'customer_company'  => sanitize_text_field($_POST['customer_company']),
            'customer_email'    => sanitize_email($_POST['customer_email']),
            'customer_phone'    => sanitize_text_field($_POST['customer_phone']),
            'customer_address'  => wp_kses_post($_POST['customer_address']),
            'items'             => wp_json_encode($_POST['items']),
            'subtotal'          => $subtotal,
            'discount'          => $discount,
            'discount_type'     => $discount_type,
            'tax_rate'          => $tax_rate,
            'tax_amount'        => $tax_amount,
            'total'             => $total,
            'sales_pic'         => sanitize_text_field($_POST['sales_pic']),
            'valid_until'       => sanitize_text_field($_POST['valid_until']),
            'delivery_location' => sanitize_text_field($_POST['delivery_location']),
            'additional_notes'  => wp_kses_post($_POST['additional_notes']),
            'status'            => sanitize_text_field($_POST['status']),
            'request_date'      => sanitize_text_field($_POST['request_date']),
        );
        
        if ($id > 0) {
            $result = $wpdb->update($table, $data, array('id' => $id));
        } else {
            $result = $wpdb->insert($table, $data);
            $id = $wpdb->insert_id;
        }
        
        if ($result !== false) {
            self::log_activity($id > 0 ? 'update' : 'create', 'quotation', $id);
            wp_send_json_success(array('message' => 'Quotation saved', 'id' => $id));
        } else {
            wp_send_json_error(array('message' => 'Failed to save quotation'));
        }
        
        exit;
    }

/**
     * Update Quotation Status
     */
public static function update_quotation_status() {
        // Delegated to the shared inbox flow: the transition is validated
        // against gti_status_map() and the email is sent by GTI_Mailer *after*
        // a committed write, rather than by a priority-5 hook before one (A-04).
        $_POST['entity_type'] = 'quotation';
        gti_ajax_change_status();
    }

/**
     * Delete Quotation — hard delete (gti_quotations has no deleted_at column)
     */
    public static function delete_quotation() {
        self::verify_nonce();
        self::require_cap('gti_manage_quotations');

        global $wpdb;
        $table = $wpdb->prefix . 'gti_quotations';
        $id = intval($_POST['id']);

        $result = $wpdb->delete($table, array('id' => $id), array('%d'));

        if ($result) {
            self::log_activity('delete', 'quotation', $id);
            wp_send_json_success(array('message' => 'Quotation deleted'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete quotation'));
        }

        exit;
    }

/**
     * Get Sell Request Detail (for right drawer)
     */
    public static function get_sell_request_detail() {
        self::verify_nonce();
        self::require_cap('gti_manage_requests');
        
        global $wpdb;
        $table = $wpdb->prefix . 'gti_sell_requests';
        $id = intval($_POST['id']);
        
        $request = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id)
        );
        
        if ($request) {
            // Parse images JSON
            $request->images_array = json_decode($request->images, true) ?: array();
            
            wp_send_json_success($request);
        } else {
            wp_send_json_error(array('message' => 'Sell request not found'));
        }
        
        exit;
    }

/**
     * Update Sell Request Status
     */
public static function update_sell_request_status() {
        // Delegated to the shared inbox flow: the transition is validated
        // against gti_status_map() and the email is sent by GTI_Mailer *after*
        // a committed write, rather than by a priority-5 hook before one (A-04).
        $_POST['entity_type'] = 'sell';
        gti_ajax_change_status();
    }

/**
     * Delete Sell Request — hard delete (gti_sell_requests has no deleted_at column)
     */
    public static function delete_sell_request() {
        self::verify_nonce();
        self::require_cap('gti_manage_requests');

        global $wpdb;
        $table = $wpdb->prefix . 'gti_sell_requests';
        $id = intval($_POST['id']);

        $result = $wpdb->delete($table, array('id' => $id), array('%d'));

        if ($result) {
            self::log_activity('delete', 'sell_request', $id);
            wp_send_json_success(array('message' => 'Sell request deleted'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete sell request'));
        }

        exit;
    }

}
