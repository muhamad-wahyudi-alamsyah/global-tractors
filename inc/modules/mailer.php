<?php
/**
 * Mailer (PRD §5.3) — replaces inc/ajax/ajax-email-notifications.php.
 *
 * The old implementation hooked the same AJAX actions at priority 5, so mail
 * went out *before* the nonce and capability checks ran and before the UPDATE
 * was known to have succeeded (PRD §3.1 A-04). This one listens on
 * gti_status_changed, which is only fired after a committed write.
 *
 * Every send is logged to wp_gti_email_logs, body included, whether it
 * succeeded or not.
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class GTI_Mailer {

    /**
     * Subject line + body template key per entity/status (PRD §5.3 matrix).
     */
    public static function matrix() {
        return array(
            'request' => array(
                'new'           => 'Pesanan Anda Telah Diterima',
                'processing'    => 'Pesanan Anda Sedang Diproses',
                'proposal_sent' => 'Proposal Telah Dikirim',
                'on_hold'       => 'Pesanan Anda Ditunda Sementara',
                'closed'        => 'Pesanan Selesai',
            ),
            'quotation' => array(
                'new'              => 'Quotation Request Diterima',
                'processing'       => 'Quotation Sedang Disusun',
                'waiting_customer' => 'Quotation Anda Sudah Siap',
                'approved'         => 'Quotation Disetujui',
                'rejected'         => 'Quotation Belum Dapat Diproses',
                'completed'        => 'Transaksi Selesai',
            ),
            'sell' => array(
                'new'               => 'Tawaran Equipment Diterima',
                'processing'        => 'Tawaran Sedang Direview',
                'approved'          => 'Tawaran Diterima',
                'invoice_requested' => 'Mohon Kirimkan Invoice Anda',
                'rejected'          => 'Tawaran Belum Dapat Diterima',
                'completed'         => 'Transaksi Selesai',
            ),
        );
    }

    /**
     * Wire the hook. Runs only after a committed status change.
     */
    public static function init() {
        add_action( 'gti_status_changed', array( __CLASS__, 'on_status_changed' ), 10, 5 );
    }

    /**
     * @param string $entity_type request|quotation|sell
     * @param int    $id
     * @param string $from
     * @param string $to
     * @param array  $context attachment_ids[], note, email
     */
    public static function on_status_changed( $entity_type, $id, $from, $to, $context = array() ) {
        $context = is_array( $context ) ? $context : array();

        if ( isset( $context['email'] ) && ! $context['email'] ) {
            return;
        }

        self::send_status_email(
            $entity_type,
            $id,
            $to,
            isset( $context['attachment_ids'] ) ? (array) $context['attachment_ids'] : array(),
            isset( $context['note'] ) ? $context['note'] : ''
        );
    }

    /**
     * Send the templated email for a status.
     *
     * @return array{sent: bool, message: string, log_id: int}
     */
    public static function send_status_email( $entity_type, $entity_id, $status, array $attachment_ids = array(), $note = '' ) {
        $row = gti_entity_row( $entity_type, $entity_id );
        if ( ! $row ) {
            return array( 'sent' => false, 'message' => 'Data tidak ditemukan.', 'log_id' => 0 );
        }

        $matrix = self::matrix();
        if ( empty( $matrix[ $entity_type ][ $status ] ) ) {
            // No template for this status: nothing to send, and that is fine.
            return array( 'sent' => false, 'message' => '', 'log_id' => 0 );
        }

        $ref       = gti_entity_ref( $entity_type, $row );
        $subject   = $matrix[ $entity_type ][ $status ] . ' — ' . $ref;
        $recipient = self::recipient( $row );

        $body = self::render( $entity_type . '-' . str_replace( '_', '-', $status ), array(
            'row'            => $row,
            'entity_type'    => $entity_type,
            'status'         => $status,
            'ref'            => $ref,
            'note'           => $note,
            'attachment_ids' => $attachment_ids,
            'title'          => $matrix[ $entity_type ][ $status ],
        ) );

        return self::dispatch( array(
            'type'           => $entity_type,
            'related_id'     => $entity_id,
            'status'         => $status,
            'to'             => $recipient,
            'subject'        => $subject,
            'body'           => $body,
            'attachment_ids' => $attachment_ids,
        ) );
    }

    /**
     * Send a free-form email composed by an admin (PRD §6.6.4).
     */
    public static function send_custom_email( $entity_type, $entity_id, $subject, $body_text, array $attachment_ids = array(), $cc = '', $signature = true ) {
        $row = gti_entity_row( $entity_type, $entity_id );

        // Customers are addressable too, and they live in a different table.
        if ( ! $row && $entity_type === 'customer' ) {
            global $wpdb;
            $row = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}gti_customers WHERE id = %d", (int) $entity_id
            ), ARRAY_A );
        }

        if ( ! $row ) {
            return array( 'sent' => false, 'message' => 'Data tidak ditemukan.', 'log_id' => 0 );
        }

        $ref  = $entity_type === 'customer'
            ? ( $row['customer_id'] ?? ( 'CUS-' . $row['id'] ) )
            : gti_entity_ref( $entity_type, $row );

        $body = self::render_layout( array(
            'title'          => $subject,
            'body_html'      => wpautop( wp_kses_post( $body_text ) ),
            'ref'            => $ref,
            'customer_name'  => self::customer_name( $row ),
            'signature_html' => $signature ? self::signature_html() : '',
        ) );

        return self::dispatch( array(
            'type'           => 'manual',
            'related_id'     => $entity_id,
            'status'         => 'manual_reply',
            'to'             => self::recipient( $row ),
            'cc'             => $cc,
            'subject'        => $subject,
            'body'           => $body,
            'attachment_ids' => $attachment_ids,
            'entity_type'    => $entity_type,
        ) );
    }

    /**
     * Render a status body template, falling back to a generic one.
     */
    public static function render( $template_key, array $vars ) {
        /**
         * Extra variables for a body template. Used by handlers that collect
         * input the template needs but the row does not carry — e.g. the
         * invoice document checklist and deadline (PRD §6.7.2).
         */
        $vars = apply_filters( 'gti_mail_template_vars', $vars, $template_key );

        $file = __DIR__ . '/email-templates/' . sanitize_file_name( $template_key ) . '.php';

        if ( file_exists( $file ) ) {
            extract( $vars, EXTR_SKIP ); // phpcs:ignore — controlled, local keys only
            ob_start();
            include $file;
            $inner = ob_get_clean();
        } else {
            $inner = '<p style="color: #374151; line-height: 1.6; margin: 0 0 15px 0;">Status pesanan Anda telah diperbarui menjadi <strong>'
                   . esc_html( gti_status_label( $vars['entity_type'], $vars['status'] ) )
                   . '</strong>.</p>';
        }

        if ( ! empty( $vars['note'] ) ) {
            $inner .= '<div style="background:#f9fafb;border-left:3px solid #F5A623;padding:12px 16px;margin:20px 0;color:#374151;line-height:1.6;">'
                    . wpautop( wp_kses_post( $vars['note'] ) ) . '</div>';
        }

        if ( ! empty( $vars['attachment_ids'] ) ) {
            $names = array();
            foreach ( $vars['attachment_ids'] as $aid ) {
                $att = gti_get_attachment( $aid );
                if ( $att ) {
                    $names[] = esc_html( $att['original_name'] ) . ' <span style="color:#9ca3af;">(' . esc_html( $att['size_text'] ) . ')</span>';
                }
            }
            if ( $names ) {
                $inner .= '<p style="color:#374151;line-height:1.6;margin:0 0 8px 0;"><strong>Dokumen terlampir:</strong></p>'
                        . '<ul style="color:#374151;line-height:1.6;margin:0 0 15px 0;padding-left:20px;"><li>'
                        . implode( '</li><li>', $names ) . '</li></ul>';
            }
        }

        return self::render_layout( array(
            'title'         => $vars['title'],
            'body_html'     => $inner,
            'ref'           => $vars['ref'],
            'customer_name' => self::customer_name( $vars['row'] ),
        ) );
    }

    /**
     * Wrap body HTML in the shared layout.
     */
    public static function render_layout( array $vars ) {
        $vars = array_merge( array(
            'title'          => '',
            'body_html'      => '',
            'ref'            => '',
            'customer_name'  => '',
            'signature_html' => '',
            'cta'            => array(),
            'sent_date'      => date_i18n( 'd F Y, H:i' ),
            'site_url'       => home_url(),
        ), $vars );

        extract( $vars, EXTR_SKIP ); // phpcs:ignore — controlled, local keys only

        ob_start();
        include __DIR__ . '/email-templates/layout.php';
        return ob_get_clean();
    }

    /**
     * Send, then log — always log, including failures and missing recipients.
     *
     * @return array{sent: bool, message: string, log_id: int}
     */
    protected static function dispatch( array $args ) {
        global $wpdb;

        $args = array_merge( array(
            'type' => 'manual', 'related_id' => 0, 'status' => '', 'to' => '',
            'cc' => '', 'subject' => '', 'body' => '', 'attachment_ids' => array(),
        ), $args );

        $log = array(
            'type'            => $args['type'],
            'related_id'      => (int) $args['related_id'],
            'status'          => $args['status'],
            'recipient_email' => $args['to'],
            'cc'              => $args['cc'] ?: null,
            'subject'         => $args['subject'],
            'body_html'       => $args['body'],
            'attachment_ids'  => $args['attachment_ids'] ? implode( ',', array_map( 'intval', $args['attachment_ids'] ) ) : null,
            'send_result'     => 0,
            'error_message'   => null,
            'sent_by'         => get_current_user_id() ?: null,
            'sent_at'         => current_time( 'mysql' ),
        );

        // No recipient is a warning, not an error: the status change itself stood.
        if ( ! $args['to'] || ! is_email( $args['to'] ) ) {
            $log['error_message'] = 'no recipient';
            $wpdb->insert( $wpdb->prefix . 'gti_email_logs', $log );

            return array(
                'sent'    => false,
                'message' => 'Email tidak terkirim: alamat email pelanggan kosong.',
                'log_id'  => (int) $wpdb->insert_id,
            );
        }

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'Reply-To: ' . self::reply_to(),
        );
        if ( $args['cc'] && is_email( $args['cc'] ) ) {
            $headers[] = 'Cc: ' . $args['cc'];
        }

        $paths = gti_attachment_paths( (array) $args['attachment_ids'] );

        $error = '';
        $catch = function ( $wp_error ) use ( &$error ) {
            $error = $wp_error->get_error_message();
        };
        add_action( 'wp_mail_failed', $catch );

        $sent = wp_mail( $args['to'], $args['subject'], $args['body'], $headers, $paths );

        remove_action( 'wp_mail_failed', $catch );

        $log['send_result']   = $sent ? 1 : 0;
        $log['error_message'] = $sent ? null : ( $error ?: 'wp_mail() returned false' );

        $wpdb->insert( $wpdb->prefix . 'gti_email_logs', $log );
        $log_id = (int) $wpdb->insert_id;

        if ( $sent && $args['attachment_ids'] ) {
            gti_mark_attachments_emailed( (array) $args['attachment_ids'] );
        }

        gti_log_current_activity(
            'email_sent',
            sprintf( '%s → %s', $args['subject'], $args['to'] ),
            isset( $args['entity_type'] ) ? $args['entity_type'] : $args['type'],
            (int) $args['related_id'],
            array( 'to' => $args['to'], 'result' => $sent ? 'ok' : 'failed', 'log_id' => $log_id )
        );

        return array(
            'sent'    => (bool) $sent,
            'message' => $sent
                ? sprintf( 'Email terkirim ke %s', $args['to'] )
                : 'Email gagal terkirim. Periksa konfigurasi SMTP.',
            'log_id'  => $log_id,
        );
    }

    /**
     * Reply-To is the logged-in admin, so a customer reply lands with the person
     * who wrote it rather than the generic site address (PRD §10.1).
     */
    protected static function reply_to() {
        $user = wp_get_current_user();

        if ( $user && $user->exists() && is_email( $user->user_email ) ) {
            return sprintf( '%s <%s>', $user->display_name, $user->user_email );
        }

        return get_option( 'admin_email' );
    }

    protected static function recipient( array $row ) {
        foreach ( array( 'customer_email', 'email' ) as $key ) {
            if ( ! empty( $row[ $key ] ) ) {
                return $row[ $key ];
            }
        }
        return '';
    }

    protected static function customer_name( array $row ) {
        foreach ( array( 'customer_name', 'name' ) as $key ) {
            if ( ! empty( $row[ $key ] ) ) {
                return $row[ $key ];
            }
        }
        return '';
    }

    /**
     * Signature block for the current admin.
     */
    public static function signature_html() {
        $user = wp_get_current_user();
        if ( ! $user || ! $user->exists() ) {
            return '';
        }

        $lines = array( '<strong>' . esc_html( $user->display_name ) . '</strong>' );

        $role = gti_role_label_for_user( $user->ID );
        if ( $role ) {
            $lines[] = esc_html( $role );
        }
        $lines[] = 'PT Global Tractors Indonesia';
        if ( is_email( $user->user_email ) ) {
            $lines[] = esc_html( $user->user_email );
        }

        return '<p style="margin:0;color:#374151;line-height:1.6;">' . implode( '<br>', $lines ) . '</p>';
    }

    /**
     * Recent email log rows for an entity (drawer "Email History").
     */
    public static function history( $entity_type, $entity_id, $limit = 5 ) {
        global $wpdb;

        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT id, type, status, recipient_email, subject, send_result, sent_at
               FROM {$wpdb->prefix}gti_email_logs
              WHERE related_id = %d AND (type = %s OR type = 'manual')
           ORDER BY sent_at DESC, id DESC
              LIMIT %d",
            (int) $entity_id, $entity_type, (int) $limit
        ), ARRAY_A );

        return $rows ?: array();
    }
}

GTI_Mailer::init();
