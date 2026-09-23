# Sistem Email di Pengacaradwpind

Dokumentasi lengkap arsitektur, implementasi, dan cara adapt sistem email ke project lain.

---

## 📋 Daftar Isi

1. [Overview](#overview)
2. [Arsitektur Sistem](#arsitektur-sistem)
3. [Komponen Utama](#komponen-utama)
4. [Alur Kerja](#alur-kerja)
5. [Implementasi di Project Baru](#implementasi-di-project-baru)
6. [Konfigurasi SMTP](#konfigurasi-smtp)
7. [Testing & Debugging](#testing--debugging)
8. [Troubleshooting](#troubleshooting)

---

## Overview

Sistem email di pengacaradwpind menangani:
- **Kirim invoice otomatis** ke klien saat invoice dibuat
- **Notifikasi progress** ke admin ketika klien menambah catatan
- **Fleksibilitas SMTP** → bisa pakai Gmail, Mailgun, custom SMTP server
- **Logging & error tracking** → setiap kirim dicatat untuk debugging

**Pattern kunci**: Fungsi kirim email mengembalikan **`true` atau string alasan gagal**, bukan boolean. Ini memudahkan menampilkan error message langsung ke UI.

---

## Arsitektur Sistem

```
┌─────────────────────────────────────────────────────────┐
│                  WordPress Admin                         │
│  (Pengaturan → DWP Dashboard)                            │
│  Input: SMTP Host, Port, Username, Password, From Email │
│  Simpan ke wp_options database                           │
└────────────────────┬────────────────────────────────────┘
                     │
                     ↓
         ┌───────────────────────┐
         │  notification-helpers │
         │  - phpmailer_init()   │
         │  - dwp_send_mail()    │
         └───────┬───────────────┘
                 │
         ┌───────┴────────────────────────────┐
         ↓                                    ↓
    ┌─────────────┐               ┌──────────────────────┐
    │  wp_mail()  │               │  Fungsi Khusus:      │
    │  (WordPress)│               │  - Invoice emails    │
    │             │               │  - Progress notes    │
    │  ↓ SMTP     │               │  - Custom messages   │
    │  ↓ Send     │               └──────────────────────┘
    └─────────────┘
         │
         ↓
    ┌─────────────────────────────┐
    │  Email Provider             │
    │  - Gmail SMTP               │
    │  - Custom SMTP Server       │
    │  - mail() bawaan server     │
    └─────────────────────────────┘
```

---

## Komponen Utama

### 1. SMTP Configuration Hook

**File**: `inc/helpers/notification-helpers.php` (baris 45-62)

```php
add_action( 'phpmailer_init', function ( $phpmailer ) {
    $host = trim( (string) get_option( 'dwp_smtp_host', '' ) );
    
    // Jika SMTP tidak dikonfigurasi, gunakan mail() bawaan server
    if ( ! $host ) {
        return;
    }
    
    $phpmailer->isSMTP();
    $phpmailer->Host       = $host;
    $phpmailer->Port       = (int) get_option( 'dwp_smtp_port', 587 ) ?: 587;
    $phpmailer->SMTPSecure = strtolower( trim( (string) get_option( 'dwp_smtp_secure', 'tls' ) ) );
    $phpmailer->Username   = (string) get_option( 'dwp_smtp_username', '' );
    $phpmailer->Password   = (string) get_option( 'dwp_smtp_password', '' );
    $phpmailer->SMTPAuth   = '' !== $phpmailer->Username;
    $phpmailer->Timeout    = 20;
    
    $from = dwp_mail_from();
    $phpmailer->setFrom( $from['email'], $from['name'], false );
} );
```

**Cara kerja**:
- Hook `phpmailer_init` dipanggil setiap kali `wp_mail()` dijalankan
- Override default email handling dengan SMTP custom
- Jika host kosong → WordPress gunakan `mail()` bawaan server
- Timeout 20 detik untuk koneksi SMTP (bisa disesuaikan)

**Setting yang dibaca dari wp_options**:
```
dwp_smtp_host          → mail.domain.com (contoh: smtp.gmail.com)
dwp_smtp_port          → 587 (TLS) atau 465 (SSL)
dwp_smtp_secure        → tls atau ssl
dwp_smtp_username      → email@domain.com
dwp_smtp_password      → password (disimpan plain, jangan di git)
dwp_smtp_from_email    → email pengirim (optional)
dwp_smtp_from_name     → nama pengirim (optional)
```

---

### 2. Email Wrapper Function

**File**: `inc/helpers/notification-helpers.php` (baris 92-101)

```php
function dwp_send_mail( $to, $subject, $message, $html = false, $attachments = array() ) {
    // Kosongkan error sebelumnya
    $GLOBALS['dwp_last_mail_error'] = '';
    
    // Setup header
    $headers = array(
        'Content-Type: text/' . ( $html ? 'html' : 'plain' ) . '; charset=UTF-8',
        dwp_mail_from_header()  // From: Nama <email@domain.com>
    );
    
    $subject = wp_specialchars_decode( $subject, ENT_QUOTES );
    
    // Kirim email
    if ( wp_mail( $to, $subject, $message, $headers, $attachments ) ) {
        return true;  // ✅ Berhasil
    }
    
    // ❌ Gagal → kembalikan pesan error (bukan FALSE)
    $detail = $GLOBALS['dwp_last_mail_error'] 
        ? $GLOBALS['dwp_last_mail_error'] 
        : 'cek konfigurasi SMTP';
    return 'wp_mail gagal mengirim ke ' . $to . ': ' . $detail;
}
```

**Keunggulan pattern ini**:
- **Return `true` atau string** → bisa langsung ditampilkan di UI/log
- **Tangkap error detail** → Hook `wp_mail_failed` (baris 65-67) simpan error ke `$GLOBALS`
- **Support HTML/plain text** → Parameter `$html = true` untuk HTML
- **Support attachment** → Kirim file PDF atau dokumen lain

**Error handling**:
```php
add_action( 'wp_mail_failed', function ( $error ) {
    $GLOBALS['dwp_last_mail_error'] = $error->get_error_message();
} );
```

---

### 3. Helper Functions

#### `dwp_mail_from()` (baris 70-78)
```php
function dwp_mail_from() {
    $email = trim( (string) get_option( 'dwp_smtp_from_email', '' ) );
    
    // Jika dari_email kosong, gunakan username SMTP atau admin email
    if ( ! is_email( $email ) ) {
        $user  = (string) get_option( 'dwp_smtp_username', '' );
        $email = is_email( $user ) ? $user : (string) get_option( 'admin_email' );
    }
    
    $name = trim( (string) get_option( 'dwp_smtp_from_name', '' ) );
    return array(
        'email' => $email,
        'name'  => $name ? $name : dwp_site_name()
    );
}
```

**Return**: `['email' => 'sender@domain.com', 'name' => 'DWP & Partner\'s']`

#### `dwp_mail_from_header()` (baris 86-89)
```php
function dwp_mail_from_header() {
    $from = dwp_mail_from();
    return 'From: ' . $from['name'] . ' <' . $from['email'] . '>';
}
```

**Return**: `'From: DWP & Partner's <noreply@domain.com>'`

#### `dwp_normalize_wa_phone()` (baris 104-110)
```php
function dwp_normalize_wa_phone( $raw ) {
    // Hapus karakter non-angka
    $phone = preg_replace( '/[^0-9]/', '', (string) $raw );
    
    // Ubah 08xxx → 628xxx (Indonesia)
    if ( '0' === substr( $phone, 0, 1 ) ) {
        $phone = '62' . substr( $phone, 1 );
    }
    
    return $phone;  // Return: '628xxxxxxxxxx'
}
```

---

### 4. Fungsi Kirim Invoice Email

**File**: `inc/helpers/notification-helpers.php` (baris 174-198)

```php
function dwp_send_email_invoice( $invoice, $case, $pdf ) {
    // 1. Tulis PDF ke file sementara
    $pdf_path = trailingslashit( get_temp_dir() ) 
              . sanitize_file_name( $invoice->invoice_number ) . '.pdf';
    
    if ( false === file_put_contents( $pdf_path, $pdf ) ) {
        return 'tidak bisa menulis PDF sementara ke ' . $pdf_path;
    }
    
    // 2. Buat pesan HTML
    $e       = 'esc_html';
    $subject = sprintf( 'Invoice %s - %s', 
        $invoice->invoice_number, 
        dwp_site_name() 
    );
    
    $message = '<div style="font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#111827;line-height:1.6">'
        . '<p>Halo <strong>' . $e( $case->client_name ) . '</strong>,</p>'
        . '<p>Terlampir invoice <strong>' . $e( $invoice->invoice_number ) . '</strong> '
        . 'untuk perkara <strong>' . $e( $case->title ) . '</strong> '
        . 'sebesar <strong>' . $e( dwp_rupiah( $invoice->total ) ) . '</strong>'
        . ', jatuh tempo ' . $e( $invoice->due_date 
            ? dwp_tanggal_id( $invoice->due_date ) 
            : 'belum ditentukan' ) . '.</p>'
        . '<p>Setelah melakukan pembayaran, silakan kirim bukti transfer '
        . 'melalui portal klien:<br>'
        . '<a href="' . esc_url( dwp_portal_url() ) . '">'
        . $e( dwp_portal_url() ) . '</a></p>'
        . '<p>Terima kasih,<br>' . $e( dwp_site_name() ) . '</p>'
        . '</div>';
    
    // 3. Kirim email dengan attachment PDF
    $reason = dwp_send_mail( 
        $case->client_email, 
        $subject, 
        $message, 
        true,  // HTML
        array( $pdf_path )  // Lampiran
    );
    
    // 4. Bersihkan file sementara
    wp_delete_file( $pdf_path );
    
    // 5. Log hasil
    if ( true === $reason ) {
        dwp_log( 'notification', 0, 'invoice_email_sent', 
            $invoice->invoice_number . ' → ' . $case->client_email );
        return true;
    }
    
    dwp_log( 'notification', 0, 'invoice_email_failed', 
        $invoice->invoice_number . ' → ' . $reason );
    return $reason;  // Return error message
}
```

**Parameter**:
- `$invoice` → Object invoice dengan properties: `invoice_number`, `total`, `due_date`
- `$case` → Object case dengan properties: `client_name`, `client_email`, `title`
- `$pdf` → Binary PDF content (dari html2pdf atau library lain)

**Return**: 
- `true` jika berhasil
- String alasan gagal jika error

---

### 5. Fungsi Notify Admin Client Note

**File**: `inc/helpers/notification-helpers.php` (baris 201-245)

```php
function dwp_notify_admin_client_note( $case, $progress, $note ) {
    $admin_email = get_option( 'admin_email' );
    if ( ! $admin_email ) {
        dwp_log( 'notification', 0, 'client_note_email_failed', 
            'Admin email tidak terkonfigurasi' );
        return false;
    }
    
    $subject = sprintf(
        '[Catatan Klien] %s — Progres: %s',
        $case->client_code,
        $progress->title
    );
    
    $message = sprintf(
        "Klien %s telah menambahkan catatan untuk progres perkara Anda.\n\n" .
        "=== Detail Perkara ===\n" .
        "Kode: %s\n" .
        "Nomor Perkara: %s\n" .
        "Klien: %s\n\n" .
        "=== Progres ===\n" .
        "Judul: %s\n" .
        "Tanggal: %s\n\n" .
        "=== Catatan Klien ===\n" .
        "%s\n\n" .
        "Lihat di dashboard: %s\n\n" .
        "Terima kasih,\nSistem DWP Dashboard",
        $case->client_code,
        $case->case_code,
        $case->title,
        $case->client_name,
        $progress->title,
        dwp_tanggal_id( $progress->progress_date ),
        $note,
        admin_url( 'admin.php?page=dwp-cases&case_id=' . $case->id )
    );
    
    $reason = dwp_send_mail( $admin_email, $subject, $message );
    
    if ( true === $reason ) {
        dwp_log( 'notification', 0, 'client_note_email_sent', 
            $case->client_code . ' → ' . $admin_email );
        return true;
    }
    
    dwp_log( 'notification', 0, 'client_note_email_failed', 
        $case->client_code . ' → ' . $reason );
    return false;
}
```

---

### 6. Fungsi Notify Invoice Created

**File**: `inc/helpers/notification-helpers.php` (baris 13-39)

```php
function dwp_notify_invoice_created( $invoice_id ) {
    $invoice = dwp_get_invoice( $invoice_id );
    if ( ! $invoice ) {
        $reason = 'Invoice ID ' . $invoice_id . ' tidak ditemukan';
        dwp_log( 'notification', 0, 'invoice_not_found', $reason );
        return array( 'whatsapp' => $reason, 'email' => $reason );
    }
    
    $case = dwp_get_case( (int) $invoice->case_id );
    if ( ! $case ) {
        $reason = 'Perkara ID ' . $invoice->case_id . ' tidak ditemukan';
        dwp_log( 'notification', 0, 'invoice_notification_failed', $reason );
        return array( 'whatsapp' => $reason, 'email' => $reason );
    }
    
    $result = array();
    
    // Kirim WhatsApp jika ada nomor
    if ( $case->client_phone ) {
        $result['whatsapp'] = dwp_send_whatsapp_invoice( $invoice, $case );
    } else {
        $result['whatsapp'] = 'Nomor WhatsApp klien belum diisi';
        dwp_log( 'notification', 0, 'invoice_whatsapp_skipped', 
            $invoice->invoice_number . ' → ' . $result['whatsapp'] );
    }
    
    // Email invoice dikirim dari halaman detail (AJAX send_invoice_email)
    // Karena PDF dibuat di browser dulu
    
    return $result;  // Return: ['whatsapp' => true|string, 'email' => ...]
}
```

**Return**: `array( 'whatsapp' => true|string, 'email' => true|string )`

---

## Alur Kerja

### Alur 1: Kirim Invoice Email

```
1. ADMIN BUAT INVOICE (AJAX save_invoice)
   └─→ inc/ajax/ajax-invoices.php:68
       └─→ dwp_save_invoice( $data )
           └─→ Insert ke database
               └─→ Trigger notifikasi (WhatsApp saat invoice dibuat)
                   └─→ dwp_notify_invoice_created()
                       └─→ Kirim WhatsApp (instant)
                       └─→ Email belum (akan dikirim dari halaman detail)

2. ADMIN AKSES HALAMAN DETAIL INVOICE
   └─→ /dashboard/invoices/detail/?id=123
   
3. ADMIN TEKAN TOMBOL "KIRIM EMAIL"
   └─→ Browser:
       ├─→ Render invoice ke canvas HTML2PDF
       ├─→ Generate PDF blob dari canvas
       └─→ AJAX send_invoice_email
   
4. AJAX HANDLER (send_invoice_email)
   └─→ Terima PDF base64 dari browser
   └─→ Decode base64 → binary PDF
   └─→ dwp_send_email_invoice( $invoice, $case, $pdf )
       ├─→ Tulis PDF ke file temp
       ├─→ Kirim email dengan attachment
       ├─→ Hapus file temp
       └─→ Log hasil
   
5. RESPONSE KE BROWSER
   └─→ Success: "Invoice sent to client@email.com"
   └─→ Error: "Failed to send: smtp.gmail.com connection timeout"
```

### Alur 2: Notifikasi Progress Note

```
1. KLIEN TAMBAH CATATAN DI PORTAL
   └─→ AJAX handler progress note
   
2. SERVER TERIMA DATA
   └─→ Insert catatan ke database
   └─→ Trigger: dwp_notify_admin_client_note( $case, $progress, $note )
   
3. KIRIM EMAIL KE ADMIN
   └─→ dwp_send_mail()
       ├─→ Hook phpmailer_init
       ├─→ Gunakan SMTP dari konfigurasi
       └─→ Kirim email
   
4. LOG HASIL
   └─→ Berhasil: dwp_activity_log → 'client_note_email_sent'
   └─→ Gagal: dwp_activity_log → 'client_note_email_failed'
```

---

## Implementasi di Project Baru

### Struktur Folder
```
project/
├── inc/
│   └── helpers/
│       └── notification-helpers.php  ← Copy file ini
├── inc/
│   └── bootstrap.php  ← Pastikan load notification-helpers.php
└── admin-settings/
    └── email-settings-page.php  ← Buat form untuk SMTP config
```

### Langkah 1: Setup Halaman Settings

Buat form di wp-admin untuk input SMTP configuration:

```php
// admin-settings/email-settings-page.php

function my_email_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    
    if ( $_POST ) {
        check_admin_referer( 'my_email_settings' );
        
        update_option( 'my_smtp_host', sanitize_text_field( $_POST['smtp_host'] ) );
        update_option( 'my_smtp_port', (int) $_POST['smtp_port'] );
        update_option( 'my_smtp_secure', sanitize_text_field( $_POST['smtp_secure'] ) );
        update_option( 'my_smtp_username', sanitize_text_field( $_POST['smtp_username'] ) );
        update_option( 'my_smtp_password', sanitize_text_field( $_POST['smtp_password'] ) );
        update_option( 'my_smtp_from_email', sanitize_email( $_POST['smtp_from_email'] ) );
        update_option( 'my_smtp_from_name', sanitize_text_field( $_POST['smtp_from_name'] ) );
        
        echo '<div class="notice notice-success"><p>Pengaturan email tersimpan.</p></div>';
    }
    
    $smtp_host = get_option( 'my_smtp_host', '' );
    $smtp_port = get_option( 'my_smtp_port', 587 );
    $smtp_secure = get_option( 'my_smtp_secure', 'tls' );
    $smtp_username = get_option( 'my_smtp_username', '' );
    $smtp_from_email = get_option( 'my_smtp_from_email', '' );
    $smtp_from_name = get_option( 'my_smtp_from_name', '' );
    ?>
    
    <div class="wrap">
        <h1>Email Settings</h1>
        <form method="POST">
            <?php wp_nonce_field( 'my_email_settings' ); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="smtp_host">SMTP Host</label></th>
                    <td>
                        <input type="text" id="smtp_host" name="smtp_host" 
                            value="<?php echo esc_attr( $smtp_host ); ?>" 
                            placeholder="smtp.gmail.com" class="regular-text">
                        <p class="description">Contoh: smtp.gmail.com, mail.yourdomain.com</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="smtp_port">SMTP Port</label></th>
                    <td>
                        <input type="number" id="smtp_port" name="smtp_port" 
                            value="<?php echo (int) $smtp_port; ?>" class="small-text">
                        <p class="description">587 (TLS) atau 465 (SSL)</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="smtp_secure">Enkripsi</label></th>
                    <td>
                        <select id="smtp_secure" name="smtp_secure">
                            <option value="tls" <?php selected( $smtp_secure, 'tls' ); ?>>TLS (Port 587)</option>
                            <option value="ssl" <?php selected( $smtp_secure, 'ssl' ); ?>>SSL (Port 465)</option>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="smtp_username">Username/Email SMTP</label></th>
                    <td>
                        <input type="text" id="smtp_username" name="smtp_username" 
                            value="<?php echo esc_attr( $smtp_username ); ?>" 
                            class="regular-text">
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="smtp_password">Password SMTP</label></th>
                    <td>
                        <input type="password" id="smtp_password" name="smtp_password" 
                            class="regular-text">
                        <p class="description">Jangan kosongkan kecuali ingin mengubah</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="smtp_from_email">Email Pengirim</label></th>
                    <td>
                        <input type="email" id="smtp_from_email" name="smtp_from_email" 
                            value="<?php echo esc_attr( $smtp_from_email ); ?>" 
                            class="regular-text">
                        <p class="description">Jika kosong, gunakan username SMTP</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="smtp_from_name">Nama Pengirim</label></th>
                    <td>
                        <input type="text" id="smtp_from_name" name="smtp_from_name" 
                            value="<?php echo esc_attr( $smtp_from_name ); ?>" 
                            class="regular-text">
                        <p class="description">Contoh: "Customer Service" atau "My Company"</p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

add_action( 'admin_menu', function() {
    add_options_page( 
        'Email Settings', 
        'Email Settings', 
        'manage_options', 
        'my-email-settings', 
        'my_email_settings_page' 
    );
});
```

### Langkah 2: Copy File notification-helpers.php

Copy file `inc/helpers/notification-helpers.php` dari pengacaradwpind ke project Anda, tapi sesuaikan nama fungsi:

```php
// Ganti prefix dwp_ dengan prefix project Anda (e.g., my_)

// dwp_send_mail() → my_send_mail()
// dwp_mail_from() → my_mail_from()
// dwp_notify_invoice_created() → my_notify_invoice_created()
// dll
```

### Langkah 3: Load File di Bootstrap

```php
// functions.php atau inc/bootstrap.php

require_once get_template_directory() . '/inc/helpers/notification-helpers.php';
```

### Langkah 4: Gunakan di Project

```php
// Kirim email manual
$result = my_send_mail( 
    'user@example.com', 
    'Hello World', 
    'This is email content',
    false  // plain text
);

if ( true === $result ) {
    echo 'Email sent!';
} else {
    echo 'Error: ' . $result;  // Tampilkan alasan gagal
}

// Kirim email dengan HTML & attachment
$pdf_content = /* ... generate PDF ... */;
$pdf_path = sys_get_temp_dir() . 'document.pdf';
file_put_contents( $pdf_path, $pdf_content );

$result = my_send_mail(
    'user@example.com',
    'Invoice ABC123',
    '<h1>Invoice</h1><p>Please see attachment</p>',
    true,  // HTML
    array( $pdf_path )
);

unlink( $pdf_path );

if ( true === $result ) {
    // Log success
} else {
    // Log error: $result
}
```

---

## Konfigurasi SMTP

### Gmail SMTP

**Settings**:
- Host: `smtp.gmail.com`
- Port: `587`
- Enkripsi: `tls`
- Username: `your-email@gmail.com`
- Password: **App Password** (bukan password akun biasa!)

**Cara dapat App Password**:
1. Buka https://myaccount.google.com/security
2. Aktifkan 2-Step Verification
3. Buat "App Password" untuk "Mail"
4. Copy password 16 karakter → paste ke settings

### Mailgun

**Settings**:
- Host: `smtp.mailgun.org`
- Port: `587`
- Enkripsi: `tls`
- Username: `postmaster@your-domain.mailgun.org`
- Password: API key dari Mailgun dashboard

### Amazon SES

**Settings**:
- Host: `email-smtp.region.amazonaws.com` (e.g., `email-smtp.us-east-1.amazonaws.com`)
- Port: `587` atau `25`
- Enkripsi: `tls`
- Username: SMTP username dari AWS SES
- Password: SMTP password dari AWS SES

### Custom SMTP Server

**Settings**:
- Host: `mail.yourdomain.com`
- Port: Biasanya `25`, `587`, atau `465`
- Enkripsi: Tanya ke hosting provider
- Username: Email atau username yang disediakan
- Password: Password yang disediakan

---

## Testing & Debugging

### Halaman Diagnostik

Buat file `test-email.php` di root project:

```php
<?php
// test-email.php?key=secret123

$wp_path = dirname( __FILE__ );
require_once $wp_path . '/wp-load.php';

if ( ! isset( $_GET['key'] ) || 'secret123' !== $_GET['key'] ) {
    wp_die( 'Access denied' );
}

if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( 'Only admins' );
}

// Cek function exist
if ( ! function_exists( 'my_send_mail' ) ) {
    echo '<p style="color:red">❌ Fungsi my_send_mail() tidak ditemukan</p>';
    exit;
}

// Cek SMTP config
$smtp_host = get_option( 'my_smtp_host', '' );
$smtp_port = get_option( 'my_smtp_port', '' );
$smtp_username = get_option( 'my_smtp_username', '' );

echo '<h1>📧 Email Diagnostic</h1>';
echo '<p>SMTP Host: ' . ( $smtp_host ? esc_html( $smtp_host ) : '<span style="color:red">(kosong)</span>' ) . '</p>';
echo '<p>SMTP Port: ' . ( $smtp_port ? esc_html( $smtp_port ) : '<span style="color:red">(kosong)</span>' ) . '</p>';
echo '<p>SMTP Username: ' . ( $smtp_username ? esc_html( substr( $smtp_username, 0, 10 ) ) . '***' : '<span style="color:red">(kosong)</span>' ) . '</p>';

// Test kirim email
if ( $_POST ) {
    $to = sanitize_email( $_POST['test_email'] );
    
    $result = my_send_mail(
        $to,
        'Test Email - ' . get_bloginfo( 'name' ),
        'Ini adalah email tes.\n\nJika email ini sampai, konfigurasi SMTP sudah benar.'
    );
    
    if ( true === $result ) {
        echo '<p style="color:green"><strong>✅ Email terkirim ke ' . esc_html( $to ) . '</strong></p>';
    } else {
        echo '<p style="color:red"><strong>❌ Gagal: ' . esc_html( $result ) . '</strong></p>';
    }
}
?>

<form method="POST">
    <input type="email" name="test_email" value="<?php echo esc_attr( wp_get_current_user()->user_email ); ?>" required>
    <button type="submit">Kirim Email Tes</button>
</form>
```

**Akses**: `http://yoursite.com/test-email.php?key=secret123`

### Checklist Debug

```
☐ Konfigurasi SMTP sudah diisi (Host, Port, Username, Password)
☐ Email pengirim sesuai dengan credential SMTP
☐ Untuk Gmail: menggunakan App Password (bukan password akun)
☐ Port benar (587 untuk TLS, 465 untuk SSL)
☐ Koneksi internet hosting bisa reach SMTP server
☐ Firewall hosting tidak memblokir SMTP outgoing
☐ Fungsi my_send_mail() sudah di-load (check functions.php)
☐ Error handler wp_mail_failed hook terpasang
☐ Activity log table tersedia untuk logging
```

---

## Troubleshooting

### Error: "Could not resolve host"

**Penyebab**: Hostname SMTP salah atau DNS resolution gagal

**Solusi**:
1. Cek typo di hostname (contoh: "fontte" bukan "fonnte")
2. Test koneksi: `telnet smtp.gmail.com 587`
3. Pastikan hosting memperbolehkan outgoing SMTP

### Error: "SMTP authentication failed"

**Penyebab**: Username atau password salah

**Solusi**:
1. Double-check username dan password (jangan ada spasi)
2. Untuk Gmail: gunakan App Password, bukan password akun
3. Copy-paste langsung dari provider SMTP

### Error: "Connection timeout"

**Penyebab**: Port salah atau hosting memblokir koneksi SMTP

**Solusi**:
1. Cek port yang benar (biasanya 587 TLS atau 465 SSL)
2. Tanya ke hosting support apakah SMTP outgoing diblokir
3. Coba port lain (25, 587, 465, 2525)

### Email terkirim tapi masuk folder Spam

**Penyebab**: SPF/DKIM record belum dikonfigurasi

**Solusi**:
1. Tambahkan SPF record ke DNS: `v=spf1 include:smtp.gmail.com ~all`
2. Setup DKIM di email provider
3. Pastikan "From" email sesuai domain SPF

### Error: "Password empty" atau "SMTP Auth required"

**Penyebab**: Username kosong atau tidak ada

**Solusi**:
1. Isi username SMTP (biasanya email)
2. Isi password SMTP
3. Cek apakah SMTP server memerlukan autentikasi

### File attachment tidak terkirim

**Penyebab**: Path file salah atau file tidak readable

**Solusi**:
```php
// Cek file sebelum kirim email
$pdf_path = '/tmp/invoice.pdf';

if ( ! file_exists( $pdf_path ) ) {
    echo 'File tidak ditemukan: ' . $pdf_path;
    return false;
}

if ( ! is_readable( $pdf_path ) ) {
    echo 'File tidak bisa dibaca';
    return false;
}

// Baru kirim email
$result = my_send_mail( $to, $subject, $message, true, array( $pdf_path ) );
```

### Email belum dikirim tapi tidak ada error

**Penyebab**: `wp_mail` mengembalikan false tapi error handler tidak terpasang

**Solusi**: Pastikan error handler terpasang:
```php
add_action( 'wp_mail_failed', function ( $error ) {
    $GLOBALS['dwp_last_mail_error'] = $error->get_error_message();
} );
```

---

## Best Practices

1. **Jangan hardcode password** → Gunakan wp_options atau environment variable
2. **Log setiap kirim** → Buat tabel activity_log untuk audit trail
3. **Timeout 20-30 detik** → SMTP bisa lambat
4. **Bersihkan file temp** → Jangan biarkan PDF sementara menumpuk
5. **Escaping HTML** → Gunakan `esc_html()` di email content
6. **Test sebelum production** → Kirim email tes ke diri sendiri
7. **Monitor log** → Setup dashboard untuk melihat email sent/failed
8. **Error message jelas** → Return string error, bukan boolean

---

## Referensi

- File utama: `inc/helpers/notification-helpers.php`
- Halaman diagnostik: `diagnostic-notifications.php`
- Penggunaan: `inc/ajax/ajax-invoices.php` (invoice created → notifikasi)
- Halaman test: `/wp-content/themes/pengacaradwpind/diagnostic-notifications.php?key=secret123`

---

**Dokumentasi terakhir diperbarui**: September 17, 2026
**Versi sistem**: 1.2.0+
