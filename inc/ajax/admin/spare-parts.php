<?php
/**
 * Spare Parts AJAX handlers
 *
 * Spare parts CRUD.
 *
 * Split out of includes/class-gti-ajax.php, which had grown to 1,279 lines
 * covering eight unrelated domains (PRD §13.3 R-04). These are traits rather
 * than new classes so the handlers keep their GTI_Ajax::method() identity and
 * every existing add_action() registration keeps working unchanged.
 *
 * @package global-tractors
 */

defined('ABSPATH') || exit;

trait GTI_Ajax_Spare_Parts {

/**
     * Save Spare Part
     */
    public static function save_spare_part() {
        while (ob_get_level()) { ob_end_clean(); }
        self::verify_nonce();
        self::require_cap('gti_manage_spare_parts');
        
        global $wpdb;
        $table = $wpdb->prefix . 'gti_spare_parts';
        
        $id = intval($_POST['id'] ?? 0);
        $is_draft = !empty($_POST['draft']);
        $stock = intval($_POST['stock'] ?? 0);
        $minimum_stock = intval($_POST['minimum_stock'] ?? 10);
        
        // A form may only choose the publication state. The stock level is always
        // derived, so the dashboard badge, the catalog card and the catalog
        // filters can never disagree with the quantity actually on hand.
        $form_status = sanitize_text_field($_POST['status'] ?? '');
        if ($is_draft || 'draft' === $form_status) {
            $status = 'draft';
        } else {
            $status = gti_spare_stock_status($stock, $minimum_stock);
        }

        // Handle image upload
        $image_url = '';
        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload = wp_upload_bits($_FILES['image']['name'], null, file_get_contents($_FILES['image']['tmp_name']));
            if (!empty($upload['url'])) {
                $image_url = $upload['url'];
            }
        }
        
        $data = array(
            'part_number'   => sanitize_text_field($_POST['part_number'] ?? ''),
            'name'          => sanitize_text_field($_POST['name'] ?? ''),
            'category'      => sanitize_text_field($_POST['category'] ?? ''),
            'brand'         => sanitize_text_field($_POST['brand'] ?? ''),
            'description'   => wp_kses_post($_POST['description'] ?? ''),
            'stock'         => $stock,
            'minimum_stock' => $minimum_stock,
            'unit_price'    => floatval($_POST['unit_price'] ?? 0),
            'supplier'      => sanitize_text_field($_POST['supplier'] ?? ''),
            'location'      => sanitize_text_field($_POST['location'] ?? ''),
            'status'        => $status,
        );

        if ($image_url) {
            $data['image'] = $image_url;
        }
        
        if ($id > 0) {
            $result = $wpdb->update($table, $data, array('id' => $id));
        } else {
            $result = $wpdb->insert($table, $data);
            $id = $wpdb->insert_id;
        }
        
        if ($result !== false) {
            self::log_activity($id > 0 ? 'update' : 'create', 'spare_part', $id);
            wp_send_json_success(array(
                'message'  => $is_draft ? 'Draft saved successfully' : 'Spare part saved successfully',
                'id'       => $id,
                'redirect' => gti_dashboard_url('spare-parts'),
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to save spare part. Please try again.'));
        }
        
        exit;
    }

/**
     * Delete Spare Part
     */
    public static function delete_spare_part() {
        self::verify_nonce();
        self::require_cap('gti_manage_spare_parts');
        
        global $wpdb;
        $table = $wpdb->prefix . 'gti_spare_parts';
        $id = intval($_POST['id']);
        
        $result = $wpdb->delete($table, array('id' => $id));
        
        if ($result !== false) {
            self::log_activity('delete', 'spare_part', $id);
            wp_send_json_success(array('message' => 'Spare part deleted'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete'));
        }
        
        exit;
    }

/**
     * Publish Spare Part (change status from draft to in_stock)
     */
    public static function publish_spare_part() {
        while (ob_get_level()) { ob_end_clean(); }
        self::verify_nonce();
        self::require_cap('gti_manage_spare_parts');
        global $wpdb;
        $table = $wpdb->prefix . 'gti_spare_parts';
        $id = intval($_POST['id'] ?? 0);
        // Derive from the stored quantities so publishing cannot mislabel stock.
        $row = $wpdb->get_row($wpdb->prepare("SELECT stock, minimum_stock FROM {$table} WHERE id = %d", $id));
        if (!$row) {
            wp_send_json_error(array('message' => 'Spare part not found'));
        }
        $status = gti_spare_stock_status($row->stock, $row->minimum_stock);
        $result = $wpdb->update($table, array('status' => $status), array('id' => $id));
        if ($result !== false) {
            self::log_activity('publish', 'spare_part', $id, array('status' => $status));
            wp_send_json_success(array('message' => 'Spare part published successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to publish spare part'));
        }
        exit;
    }

/**
     * Get Next Spare Part Code — auto-generate based on category abbreviation + sequence number.
     *
     * Returns a code like SP-FLT-2026-003 for the given category.
     */
    public static function get_next_spare_part_code() {
        while (ob_get_level()) { ob_end_clean(); }
        self::verify_nonce();
        self::require_cap('gti_manage_spare_parts');

        global $wpdb;
        $table = $wpdb->prefix . 'gti_spare_parts';

        $category = sanitize_text_field($_POST['category'] ?? '');
        $year     = date('Y');

        $abbr = gti_spare_part_category_abbr( $category );

        $prefix = "SP-{$abbr}-{$year}-";

        // Find the highest existing sequence number for this prefix
        $like = $wpdb->esc_like($prefix) . '%';
        $max_code = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT part_number
                FROM {$table}
                WHERE part_number LIKE %s
                ORDER BY id DESC
                LIMIT 1",
                $like
            )
        );

        $seq = 1;
        if ($max_code) {
            $last_num = (int) substr($max_code, strrpos($max_code, '-') + 1);
            $seq = $last_num + 1;
        }

        $code = $prefix . str_pad($seq, 3, '0', STR_PAD_LEFT);

        wp_send_json_success(['code' => $code]);
        exit;
    }

}
