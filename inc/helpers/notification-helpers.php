<?php
/**
 * Email transport (DOKUMENTASI_EMAIL_SYSTEM.md).
 *
 * SMTP is configured from wp-admin (Settings → GTI Email) and stored in
 * gti_smtp_* options. Empty host = leave PHPMailer alone, so the server's
 * mail() or the WP Mail SMTP plugin keeps handling delivery.
 *
 * gti_send_mail() returns true or a human-readable failure reason, so callers
 * can show/log why a message did not go out.
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'phpmailer_init', function ( $phpmailer ) {
    $host = trim( (string) get_option( 'gti_smtp_host', '' ) );
    if ( ! $host ) {
        return;
    }

    $phpmailer->isSMTP();
    $phpmailer->Host       = $host;
    $phpmailer->Port       = (int) get_option( 'gti_smtp_port', 587 ) ?: 587;
    $phpmailer->SMTPSecure = strtolower( trim( (string) get_option( 'gti_smtp_secure', 'tls' ) ) );
    $phpmailer->Username   = (string) get_option( 'gti_smtp_username', '' );
    $phpmailer->Password   = (string) get_option( 'gti_smtp_password', '' );
    $phpmailer->SMTPAuth   = '' !== $phpmailer->Username;
    $phpmailer->Timeout    = 20;

    $from = gti_mail_from();
    $phpmailer->setFrom( $from['email'], $from['name'], false );
}, 20 );

add_action( 'wp_mail_failed', function ( $error ) {
    $GLOBALS['gti_last_mail_error'] = $error->get_error_message();
} );

/**
 * Sender: configured From email → SMTP username → admin_email.
 */
function gti_mail_from() {
    $email = trim( (string) get_option( 'gti_smtp_from_email', '' ) );
    if ( ! is_email( $email ) ) {
        $user  = (string) get_option( 'gti_smtp_username', '' );
        $email = is_email( $user ) ? $user : (string) get_option( 'admin_email' );
    }

    $name = trim( (string) get_option( 'gti_smtp_from_name', '' ) );

    return array(
        'email' => $email,
        'name'  => $name ? $name : wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
    );
}

function gti_mail_from_header() {
    $from = gti_mail_from();
    return 'From: ' . $from['name'] . ' <' . $from['email'] . '>';
}

/**
 * @param string|array $to
 * @param string       $subject
 * @param string       $message
 * @param bool         $html
 * @param array        $attachments Absolute file paths.
 * @param array        $headers     Extra headers (Reply-To, Cc, …).
 * @return true|string True on success, failure reason otherwise.
 */
function gti_send_mail( $to, $subject, $message, $html = false, $attachments = array(), $headers = array() ) {
    $GLOBALS['gti_last_mail_error'] = '';

    $headers = array_merge( array(
        'Content-Type: text/' . ( $html ? 'html' : 'plain' ) . '; charset=UTF-8',
        gti_mail_from_header(),
    ), (array) $headers );

    $subject = wp_specialchars_decode( $subject, ENT_QUOTES );

    if ( wp_mail( $to, $subject, $message, $headers, $attachments ) ) {
        return true;
    }

    $detail = $GLOBALS['gti_last_mail_error'] ? $GLOBALS['gti_last_mail_error'] : 'cek konfigurasi SMTP';
    return 'wp_mail gagal mengirim ke ' . ( is_array( $to ) ? implode( ', ', $to ) : $to ) . ': ' . $detail;
}

// ── Settings → GTI Email ─────────────────────────────────────────────────────
add_action( 'admin_menu', function () {
    add_options_page( 'GTI Email', 'GTI Email', 'manage_options', 'gti-email', 'gti_email_settings_page' );
} );

function gti_email_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( isset( $_POST['gti_email_save'] ) ) {
        check_admin_referer( 'gti_email_settings' );
        $p = wp_unslash( $_POST );

        update_option( 'gti_smtp_host', sanitize_text_field( $p['smtp_host'] ?? '' ) );
        update_option( 'gti_smtp_port', (int) ( $p['smtp_port'] ?? 587 ) );
        update_option( 'gti_smtp_secure', in_array( $p['smtp_secure'] ?? '', array( 'tls', 'ssl' ), true ) ? $p['smtp_secure'] : 'tls' );
        update_option( 'gti_smtp_username', sanitize_text_field( $p['smtp_username'] ?? '' ) );
        // Blank = keep the stored password; it is never echoed back into the form.
        if ( '' !== ( $p['smtp_password'] ?? '' ) ) {
            update_option( 'gti_smtp_password', (string) $p['smtp_password'], false );
        }
        update_option( 'gti_smtp_from_email', sanitize_email( $p['smtp_from_email'] ?? '' ) );
        update_option( 'gti_smtp_from_name', sanitize_text_field( $p['smtp_from_name'] ?? '' ) );

        echo '<div class="notice notice-success"><p>Pengaturan email tersimpan.</p></div>';
    }

    if ( isset( $_POST['gti_email_test'] ) ) {
        check_admin_referer( 'gti_email_settings' );
        $to     = sanitize_email( wp_unslash( $_POST['test_to'] ?? '' ) );
        $result = is_email( $to )
            ? gti_send_mail( $to, 'Tes Email - ' . get_bloginfo( 'name' ), 'Email tes dari halaman GTI Email. Jika Anda menerima ini, konfigurasi berhasil.' )
            : 'alamat email tujuan tidak valid';

        printf(
            '<div class="notice notice-%s"><p>%s</p></div>',
            true === $result ? 'success' : 'error',
            esc_html( true === $result ? 'Email tes terkirim ke ' . $to : $result )
        );
    }

    $v = function ( $key, $default = '' ) {
        return esc_attr( get_option( $key, $default ) );
    };
    $secure = get_option( 'gti_smtp_secure', 'tls' );
    ?>
    <div class="wrap">
        <h1>GTI Email</h1>
        <p>Kosongkan SMTP Host untuk memakai mail() bawaan server / plugin WP Mail SMTP.</p>
        <form method="post">
            <?php wp_nonce_field( 'gti_email_settings' ); ?>
            <table class="form-table">
                <tr><th><label for="smtp_host">SMTP Host</label></th>
                    <td><input name="smtp_host" id="smtp_host" class="regular-text" value="<?php echo $v( 'gti_smtp_host' ); ?>" placeholder="smtp.gmail.com"></td></tr>
                <tr><th><label for="smtp_port">Port</label></th>
                    <td><input type="number" name="smtp_port" id="smtp_port" value="<?php echo $v( 'gti_smtp_port', 587 ); ?>"> <span class="description">587 (TLS) / 465 (SSL)</span></td></tr>
                <tr><th><label for="smtp_secure">Enkripsi</label></th>
                    <td><select name="smtp_secure" id="smtp_secure">
                        <option value="tls" <?php selected( $secure, 'tls' ); ?>>TLS</option>
                        <option value="ssl" <?php selected( $secure, 'ssl' ); ?>>SSL</option>
                    </select></td></tr>
                <tr><th><label for="smtp_username">Username</label></th>
                    <td><input name="smtp_username" id="smtp_username" class="regular-text" value="<?php echo $v( 'gti_smtp_username' ); ?>" autocomplete="off"></td></tr>
                <tr><th><label for="smtp_password">Password</label></th>
                    <td><input type="password" name="smtp_password" id="smtp_password" class="regular-text" autocomplete="new-password"
                        placeholder="<?php echo get_option( 'gti_smtp_password' ) ? '•••••••• (tersimpan)' : ''; ?>">
                        <p class="description">Kosongkan untuk mempertahankan password lama. Gmail: pakai App Password.</p></td></tr>
                <tr><th><label for="smtp_from_email">From Email</label></th>
                    <td><input type="email" name="smtp_from_email" id="smtp_from_email" class="regular-text" value="<?php echo $v( 'gti_smtp_from_email' ); ?>">
                        <p class="description">Kosong = username SMTP, lalu admin email.</p></td></tr>
                <tr><th><label for="smtp_from_name">From Name</label></th>
                    <td><input name="smtp_from_name" id="smtp_from_name" class="regular-text" value="<?php echo $v( 'gti_smtp_from_name' ); ?>" placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"></td></tr>
            </table>
            <?php submit_button( 'Simpan', 'primary', 'gti_email_save' ); ?>

            <h2>Kirim Email Tes</h2>
            <input type="email" name="test_to" class="regular-text" value="<?php echo esc_attr( wp_get_current_user()->user_email ); ?>">
            <?php submit_button( 'Kirim Tes', 'secondary', 'gti_email_test', false ); ?>
        </form>
    </div>
    <?php
}
