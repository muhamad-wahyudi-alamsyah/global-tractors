<?php
/**
 * GTI Email Notification AJAX Handlers
 *
 * Handles sending email notifications when status changes
 * for Request Equipment, Request Quotation, and Sell Equipment.
 *
 * @package Global_Tractors
 */

defined('ABSPATH') || exit;

class GTI_Email_Notifications {
    
    /**
     * Initialize AJAX hooks
     */
    public static function init() {
        // Email notification AJAX
        add_action('wp_ajax_gti_send_status_email', array(__CLASS__, 'send_status_email'));
        
        // Also hook into existing status updates to auto-send emails
        add_action('wp_ajax_gti_update_request_status', array(__CLASS__, 'on_request_status_update'), 5);
        add_action('wp_ajax_gti_update_quotation_status', array(__CLASS__, 'on_quotation_status_update'), 5);
        add_action('wp_ajax_gti_update_sell_request_status', array(__CLASS__, 'on_sell_request_status_update'), 5);
    }
    
    /**
     * Verify nonce for AJAX
     */
    private static function verify_nonce() {
        if (!wp_verify_nonce($_POST['nonce'], 'gti_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            exit;
        }
    }
    
    /**
     * Get email configuration
     */
    private static function get_email_config() {
        return array(
            'from_name'  => get_bloginfo('name'),
            'from_email' => get_option('admin_email'),
            'reply_to'   => get_option('admin_email'),
        );
    }
    
    /**
     * Get email template for status change
     */
    private static function get_email_template($type, $status, $data) {
        $config = self::get_email_config();
        $customer_name = $data['customer_name'] ?? 'Customer';
        $customer_email = $data['customer_email'] ?? '';
        
        // Generate reference ID based on type
        $ref_id = '';
        if ($type === 'request') {
            $ref_id = $data['request_id'] ?? '';
        } elseif ($type === 'quotation') {
            $ref_id = $data['quotation_id'] ?? '';
        } elseif ($type === 'sell') {
            $ref_id = $data['equipment_name'] ?? '';
        }
        
        // Status-specific content
        $subjects = array(
            // Request Equipment
            'request_new' => 'Pesanan Anda Telah Diterima - ' . $ref_id,
            'request_processing' => 'Pesanan Anda Sedang Diproses - ' . $ref_id,
            'request_proposal_sent' => 'Proposal Telah Dikirim - ' . $ref_id,
            'request_closed' => 'Pesanan Selesai - ' . $ref_id,
            
            // Request Quotation
            'quotation_new' => 'Quotation Request Diterima - ' . $ref_id,
            'quotation_processing' => 'Quotation Sedang Disusun - ' . $ref_id,
            'quotation_waiting_customer' => 'Quotation Telah Dikirim - ' . $ref_id,
            'quotation_approved' => 'Quotation Disetujui - ' . $ref_id,
            'quotation_rejected' => 'Quotation Belum Dapat Diproses - ' . $ref_id,
            'quotation_completed' => 'Transaksi Selesai - ' . $ref_id,
            
            // Sell Equipment
            'sell_new' => 'Tawaran Equipment Diterima - ' . $ref_id,
            'sell_processing' => 'Tawaran Sedang Direview - ' . $ref_id,
            'sell_approved' => 'Tawaran Diterima - ' . $ref_id,
            'sell_rejected' => 'Tawaran Belum Dapat Diterima - ' . $ref_id,
            'sell_completed' => 'Transaksi Selesai - ' . $ref_id,
        );
        
        $key = $type . '_' . $status;
        $subject = $subjects[$key] ?? 'Status Update - ' . $ref_id;
        
        // Build email body based on status
        $body = self::build_email_body($type, $status, $data, $ref_id);
        
        return array(
            'to'      => $customer_email,
            'subject' => $subject,
            'body'    => $body,
            'headers' => array(
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . $config['from_name'] . ' <' . $config['from_email'] . '>',
                'Reply-To: ' . $config['reply_to'],
            ),
        );
    }
    
    /**
     * Build email body HTML
     */
    private static function build_email_body($type, $status, $data, $ref_id) {
        $customer_name = $data['customer_name'] ?? 'Customer';
        $site_name = get_bloginfo('name');
        $site_url = home_url();
        $current_date = date('d M Y, H:i');
        
        // Status messages
        $messages = array(
            // Request Equipment
            'request_new' => array(
                'title' => 'Pesanan Telah Diterima',
                'message' => 'Terima kasih telah menghubungi kami. Pesanan Anda dengan nomor <strong>' . $ref_id . '</strong> telah kami terima dan akan segera kami proses.',
                'next_step' => 'Tim kami akan menghubungi Anda dalam 1-2 hari kerja.',
            ),
            'request_processing' => array(
                'title' => 'Pesanan Sedang Diproses',
                'message' => 'Pesanan Anda dengan nomor <strong>' . $ref_id . '</strong> sedang kami proses.',
                'next_step' => 'Tim kami sedang bekerja untuk memberikan yang terbaik untuk Anda. Kami akan menghubungi Anda segera jika ada update.',
            ),
            'request_proposal_sent' => array(
                'title' => 'Proposal Telah Dikirim',
                'message' => 'Proposal untuk pesanan <strong>' . $ref_id . '</strong> telah kami kirim ke email Anda.',
                'next_step' => 'Silakan cek email Anda untuk detail penawaran. Jika ada pertanyaan, jangan ragu untuk menghubungi kami.',
            ),
            'request_closed' => array(
                'title' => 'Pesanan Selesai',
                'message' => 'Pesanan <strong>' . $ref_id . '</strong> telah selesai diproses.',
                'next_step' => 'Terima kasih atas kepercayaan Anda. Kami berharap dapat melayani Anda kembali di masa mendatang.',
            ),
            
            // Request Quotation
            'quotation_new' => array(
                'title' => 'Quotation Request Diterima',
                'message' => 'Terima kasih telah menghubungi kami. Quotation request Anda dengan nomor <strong>' . $ref_id . '</strong> telah kami terima.',
                'next_step' => 'Tim kami akan segera menyiapkan quotation dan menghubungi Anda.',
            ),
            'quotation_processing' => array(
                'title' => 'Quotation Sedang Disusun',
                'message' => 'Quotation Anda dengan nomor <strong>' . $ref_id . '</strong> sedang kami susun.',
                'next_step' => 'Tim kami sedang menyiapkan penawaran terbaik untuk Anda.',
            ),
            'quotation_waiting_customer' => array(
                'title' => 'Quotation Telah Dikirim',
                'message' => 'Quotation dengan nomor <strong>' . $ref_id . '</strong> telah kami kirim ke email Anda.',
                'next_step' => 'Silakan cek email Anda untuk detail penawaran. Berlaku sampai tanggal yang tertera.',
            ),
            'quotation_approved' => array(
                'title' => 'Quotation Disetujui',
                'message' => 'Dengan senang hati kami informasikan bahwa quotation <strong>' . $ref_id . '</strong> telah disetujui.',
                'next_step' => 'Tim kami akan segera memproses pesanan Anda.',
            ),
            'quotation_rejected' => array(
                'title' => 'Quotation Belum Dapat Diproses',
                'message' => 'Terima kasih atas quotation request Anda dengan nomor <strong>' . $ref_id . '</strong>.',
                'next_step' => 'Mohon maaf, untuk saat ini kami belum dapat memproses permintaan Anda. Jika ada yang bisa kami bantu di masa mendatang, silakan hubungi kami.',
            ),
            'quotation_completed' => array(
                'title' => 'Transaksi Selesai',
                'message' => 'Transaksi untuk quotation <strong>' . $ref_id . '</strong> telah selesai diproses.',
                'next_step' => 'Terima kasih atas kepercayaan Anda kepada kami.',
            ),
            
            // Sell Equipment
            'sell_new' => array(
                'title' => 'Tawaran Equipment Diterima',
                'message' => 'Terima kasih telah menghubungi kami. Tawaran Anda untuk equipment <strong>' . $ref_id . '</strong> telah kami terima.',
                'next_step' => 'Tim kami akan segera mereview tawaran Anda.',
            ),
            'sell_processing' => array(
                'title' => 'Tawaran Sedang Direview',
                'message' => 'Tawaran Anda untuk equipment <strong>' . $ref_id . '</strong> sedang kami review.',
                'next_step' => 'Tim kami sedang mengevaluasi equipment Anda. Kami akan menghubungi Anda segera.',
            ),
            'sell_approved' => array(
                'title' => 'Tawaran Diterima',
                'message' => 'Tawaran Anda untuk equipment <strong>' . $ref_id . '</strong> telah diterima.',
                'next_step' => 'Kami akan menghubungi Anda untuk langkah selanjutnya.',
            ),
            'sell_rejected' => array(
                'title' => 'Tawaran Belum Dapat Diterima',
                'message' => 'Terima kasih atas tawaran Anda untuk equipment <strong>' . $ref_id . '</strong>.',
                'next_step' => 'Mohon maaf, untuk saat ini kami belum dapat menerima tawaran Anda.',
            ),
            'sell_completed' => array(
                'title' => 'Transaksi Selesai',
                'message' => 'Transaksi pembelian equipment <strong>' . $ref_id . '</strong> telah selesai.',
                'next_step' => 'Terima kasih atas kepercayaan Anda.',
            ),
        );
        
        $msg = $messages[$type . '_' . $status] ?? array(
            'title' => 'Status Update',
            'message' => 'Status pesanan Anda telah diperbarui.',
            'next_step' => '',
        );
        
        // Build HTML email
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background-color: #F5A623; padding: 30px; text-align: center;">
                            <h1 style="color: #1a1f36; margin: 0; font-size: 24px;">PT Global Tractors Indonesia</h1>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <h2 style="color: #1a1f36; margin: 0 0 20px 0; font-size: 20px;">' . $msg['title'] . '</h2>
                            
                            <p style="color: #374151; line-height: 1.6; margin: 0 0 15px 0;">
                                Halo ' . esc_html($customer_name) . ',
                            </p>
                            
                            <p style="color: #374151; line-height: 1.6; margin: 0 0 15px 0;">
                                ' . $msg['message'] . '
                            </p>
                            
                            <p style="color: #374151; line-height: 1.6; margin: 0 0 15px 0;">
                                ' . $msg['next_step'] . '
                            </p>
                            
                            <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 20px 0;">
                            
                            <p style="color: #6b7280; font-size: 12px; margin: 0;">
                                Tanggal: ' . $current_date . '<br>
                                Ref: ' . $ref_id . '
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f9fafb; padding: 20px 30px; text-align: center; border-top: 1px solid #e5e7eb;">
                            <p style="color: #6b7280; font-size: 12px; margin: 0 0 10px 0;">
                                PT Global Tractors Indonesia<br>
                                <a href="' . $site_url . '" style="color: #F5A623; text-decoration: none;">' . $site_url . '</a>
                            </p>
                            <p style="color: #9ca3af; font-size: 11px; margin: 0;">
                                Email ini dikirim otomatis, mohon jangan membalas email ini.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
        
        return $html;
    }
    
    /**
     * Send email notification
     */
    public static function send_status_email() {
        self::verify_nonce();
        
        $type = sanitize_text_field($_POST['type'] ?? '');
        $status = sanitize_text_field($_POST['status'] ?? '');
        $id = intval($_POST['id'] ?? 0);
        
        if (empty($type) || empty($status) || empty($id)) {
            wp_send_json_error(array('message' => 'Missing required parameters'));
            exit;
        }
        
        // Get data based on type
        global $wpdb;
        $data = array();
        
        if ($type === 'request') {
            $table = $wpdb->prefix . 'gti_requests';
            $data = $wpdb->get_row(
                $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id),
                ARRAY_A
            ) ?: array();
        } elseif ($type === 'quotation') {
            $table = $wpdb->prefix . 'gti_quotations';
            $data = $wpdb->get_row(
                $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id),
                ARRAY_A
            ) ?: array();
        } elseif ($type === 'sell') {
            $table = $wpdb->prefix . 'gti_sell_requests';
            $data = $wpdb->get_row(
                $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id),
                ARRAY_A
            ) ?: array();
        }
        
        if (empty($data)) {
            wp_send_json_error(array('message' => 'Data not found'));
            exit;
        }
        
        // Check if customer email exists
        $customer_email = $data['customer_email'] ?? '';
        if (empty($customer_email)) {
            wp_send_json_error(array('message' => 'Customer email not found'));
            exit;
        }
        
        // Get email template
        $email = self::get_email_template($type, $status, $data);
        
        // Send email
        $sent = wp_mail(
            $email['to'],
            $email['subject'],
            $email['body'],
            $email['headers']
        );
        
        // Always log the email attempt (even if wp_mail fails on dev server)
        self::log_email($type, $id, $status, $customer_email, $email['subject']);
        
        wp_send_json_success(array(
            'message' => $sent ? 'Email notification sent' : 'Email logged (mail not configured)',
            'to' => $customer_email,
            'sent' => $sent,
        ));
        
        exit;
    }
    
    /**
     * Log email to database
     */
    private static function log_email($type, $id, $status, $to, $subject) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'gti_email_logs';
        
        // Create table if not exists
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS {$table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            type varchar(50) NOT NULL,
            related_id bigint(20) NOT NULL,
            status varchar(50) NOT NULL,
            recipient_email varchar(100) NOT NULL,
            subject varchar(255) NOT NULL,
            sent_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_type_id (type, related_id)
        ) {$charset_collate};";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Insert log
        $wpdb->insert($table, array(
            'type' => $type,
            'related_id' => $id,
            'status' => $status,
            'recipient_email' => $to,
            'subject' => $subject,
        ));
    }
    
    /**
     * Hook: On Request Status Update - Auto send email
     */
    public static function on_request_status_update() {
        // Get the status before it's updated
        global $wpdb;
        $table = $wpdb->prefix . 'gti_requests';
        $id = intval($_POST['id'] ?? 0);
        $new_status = sanitize_text_field($_POST['status'] ?? '');
        
        if ($id && $new_status) {
            // Get current data
            $current = $wpdb->get_row(
                $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id),
                ARRAY_A
            );
            
            if ($current && $current['status'] !== $new_status) {
                // Send email notification
                $email = self::get_email_template('request', $new_status, $current);
                
                if (!empty($email['to'])) {
                    wp_mail(
                        $email['to'],
                        $email['subject'],
                        $email['body'],
                        $email['headers']
                    );
                    
                    self::log_email('request', $id, $new_status, $email['to'], $email['subject']);
                }
            }
        }
    }
    
    /**
     * Hook: On Quotation Status Update - Auto send email
     */
    public static function on_quotation_status_update() {
        global $wpdb;
        $table = $wpdb->prefix . 'gti_quotations';
        $id = intval($_POST['id'] ?? 0);
        $new_status = sanitize_text_field($_POST['status'] ?? '');
        
        // Debug logging - write to a file since error_log may not be configured
        $debug_msg = date('Y-m-d H:i:s') . ' - GTI Email: on_quotation_status_update called - ID: ' . $id . ', Status: ' . $new_status . "\n";
        file_put_contents('/tmp/gti-email-debug.log', $debug_msg, FILE_APPEND);
        
        if ($id && $new_status) {
            $current = $wpdb->get_row(
                $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id),
                ARRAY_A
            );
            
            $debug_msg2 = date('Y-m-d H:i:s') . ' - GTI Email: Current status: ' . ($current['status'] ?? 'null') . "\n";
            file_put_contents('/tmp/gti-email-debug.log', $debug_msg2, FILE_APPEND);
            
            if ($current && $current['status'] !== $new_status) {
                $debug_msg3 = date('Y-m-d H:i:s') . ' - GTI Email: Status changed, sending email...' . "\n";
                file_put_contents('/tmp/gti-email-debug.log', $debug_msg3, FILE_APPEND);
                
                $email = self::get_email_template('quotation', $new_status, $current);
                
                if (!empty($email['to'])) {
                    $debug_msg4 = date('Y-m-d H:i:s') . ' - GTI Email: Sending to: ' . $email['to'] . "\n";
                    file_put_contents('/tmp/gti-email-debug.log', $debug_msg4, FILE_APPEND);
                    
                    wp_mail(
                        $email['to'],
                        $email['subject'],
                        $email['body'],
                        $email['headers']
                    );
                    
                    self::log_email('quotation', $id, $new_status, $email['to'], $email['subject']);
                    $debug_msg5 = date('Y-m-d H:i:s') . ' - GTI Email: Email logged successfully' . "\n";
                    file_put_contents('/tmp/gti-email-debug.log', $debug_msg5, FILE_APPEND);
                }
            } else {
                $debug_msg6 = date('Y-m-d H:i:s') . ' - GTI Email: Status unchanged or current is null' . "\n";
                file_put_contents('/tmp/gti-email-debug.log', $debug_msg6, FILE_APPEND);
            }
        }
    }
    
    /**
     * Hook: On Sell Request Status Update - Auto send email
     */
    public static function on_sell_request_status_update() {
        global $wpdb;
        $table = $wpdb->prefix . 'gti_sell_requests';
        $id = intval($_POST['id'] ?? 0);
        $new_status = sanitize_text_field($_POST['status'] ?? '');
        
        if ($id && $new_status) {
            $current = $wpdb->get_row(
                $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id),
                ARRAY_A
            );
            
            if ($current && $current['status'] !== $new_status) {
                $email = self::get_email_template('sell', $new_status, $current);
                
                if (!empty($email['to'])) {
                    wp_mail(
                        $email['to'],
                        $email['subject'],
                        $email['body'],
                        $email['headers']
                    );
                    
                    self::log_email('sell', $id, $new_status, $email['to'], $email['subject']);
                }
            }
        }
    }
}

// Initialize
GTI_Email_Notifications::init();
