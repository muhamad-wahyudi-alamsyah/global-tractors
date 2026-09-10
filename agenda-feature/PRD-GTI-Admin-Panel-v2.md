# PRD v2 — GTI Admin Panel & Website Shortcodes

**Status dokumen:** implementation-ready
**Basis:** audit kode aktual pada branch `main` (commit `03a3a6f`), 2026-09-10
**Menggantikan:** `PRD-GTI-Admin-Panel.md` (v1 tetap berlaku untuk bagian Design System §7 dan Personas §2)

Dokumen ini menspesifikasikan **apa yang sudah jalan**, **apa yang belum**, dan **persis apa yang harus dibangun** agar seluruh alur Customer → Admin → Email berjalan mulus. Setiap section berisi kontrak data, endpoint, nonce, capability, dan acceptance criteria supaya bisa langsung dikerjakan tanpa tanya balik.

---

## Daftar Isi

1. [Ruang Lingkup](#1-ruang-lingkup)
2. [Arsitektur Saat Ini (fakta kode)](#2-arsitektur-saat-ini-fakta-kode)
3. [Hasil Audit — Bug & Gap](#3-hasil-audit--bug--gap)
4. [Model Data & Migrasi v1.3.0](#4-model-data--migrasi-v130)
5. [Fondasi Sistem Baru](#5-fondasi-sistem-baru)
6. [Spesifikasi Per Halaman](#6-spesifikasi-per-halaman)
7. [Spesifikasi Shortcode Website](#7-spesifikasi-shortcode-website)
8. [Kontrak AJAX API](#8-kontrak-ajax-api)
9. [Roles & Capabilities](#9-roles--capabilities)
10. [Non-Functional Requirements](#10-non-functional-requirements)
11. [Rencana Implementasi](#11-rencana-implementasi)
12. [Acceptance Test Scenarios](#12-acceptance-test-scenarios)

---

## 1. Ruang Lingkup

### 1.1 Yang masuk

| Area | Modul |
|---|---|
| **Katalog (CRUD)** | Used Equipment, Rental Equipment, Spare Parts |
| **Inbox (alur pesanan)** | Request Equipment, Request Quotation, Sell Equipment |
| **Management** | Customers, Customer Detail, News & Articles, Media Library |
| **System** | Users, Activity Log |
| **Website (public)** | 3 shortcode katalog + halaman detail + form inquiry |

### 1.2 Prinsip alur bisnis

```
┌──────────────┐   submit    ┌──────────────┐  ubah status  ┌──────────────┐
│   KLIEN      │ ──────────► │ ADMIN PANEL  │ ────────────► │  EMAIL KLIEN │
│ (website /   │             │ (dashboard)  │  + attachment │              │
│  FluentForm) │ ◄────────── │              │ ◄──────────── │              │
└──────────────┘   balasan   └──────────────┘   reply       └──────────────┘
```

**Kanal komunikasi admin ↔ klien adalah email** (ditambah WhatsApp deep-link untuk Sell Equipment). Tidak ada login klien, tidak ada portal klien. Setiap perubahan status oleh admin **wajib** menghasilkan satu email ke klien dan satu baris di email log + activity log.

### 1.3 Yang TIDAK masuk (v2)

- Modul Rental Agreement / kontrak sewa (tab "Rentals" di Customer Detail akan dihapus, bukan diisi).
- Modul Invoice internal (GTI tidak menerbitkan invoice di sistem ini — pada Sell Equipment, GTI **meminta** invoice dari penjual).
- Website Settings (route `dashboard/website-settings` sudah terdaftar tapi tanpa template — biarkan 404 atau arahkan ke coming-soon).
- Contact Messages (tabel `gti_messages` ada, template tidak ada — di luar scope v2).

---

## 2. Arsitektur Saat Ini (fakta kode)

### 2.1 Routing

`inc/setup/rewrite.php` mendaftarkan rewrite rule `^dashboard/{slug}/?$` → `index.php?gti_page={slug}`.
`inc/helpers/template-loader.php` memetakan `gti_page` ke file di `templates/`.
Helper URL: `gti_dashboard_url('slug')`.

> ⚠️ **Query var `paged` milik WordPress.** Menggunakannya di URL dashboard menyebabkan 404. Semua halaman **wajib** memakai `page_num`. (Lihat §3, temuan A-01.)

### 2.2 Lapisan PHP

| Path | Isi |
|---|---|
| `includes/class-gti-ajax.php` | 21 AJAX handler admin (save/delete/status/next-code) |
| `includes/class-gti-database.php` | Query helper read-only (`GTI_Database::get_instance()`) |
| `includes/class-gti-activator.php` | `CREATE TABLE` + migrasi `gti_db_version` (kini `1.2.0`) |
| `includes/class-gti-roles.php` | 4 role GTI + capability |
| `inc/ajax/*.php` | AJAX publik (login, register, filter katalog, quotation customer, email) |
| `inc/db/*.php` | Activity log, customers-sync, migrations |
| `inc/modules/news-articles.php` | Wrapper WP Posts ← dipakai halaman News |
| `inc/modules/media-library.php` | Wrapper WP Attachments ← dipakai halaman Media |
| `inc/shortcodes/*.php` | 3 shortcode katalog publik |
| `templates/page-*.php` | Halaman dashboard (HTML + CSS + JS inline) |

### 2.3 Tabel database (prefix `wp_`)

`gti_equipment`, `gti_spare_parts`, `gti_requests`, `gti_quotations`, `gti_customers`, `gti_sell_requests`, `gti_messages`, `gti_activity_log`, `gti_email_logs` (dibuat lazily oleh email module).

### 2.4 Sumber data inbox

| Halaman | Sumber |
|---|---|
| Request Equipment | FluentForm **ID 3** → hook `fluentform/submission_inserted` → `gti_fluentform_to_request()` (`functions.php:146`) |
| Sell Equipment | FluentForm **ID 4** → `gti_fluentform_to_sell_request()` (`functions.php:263`) |
| Request Quotation | Form inquiry di 3 shortcode → `gti_customer_submit_quotation` (`inc/ajax/ajax-customer-quotation.php`) |

Ketiganya memanggil `do_action('gti_submission_received', $source, $data)` → auto-create/update row di `gti_customers`.

---

## 3. Hasil Audit — Bug & Gap

Diurutkan berdasarkan dampak. Kolom **Aksi** menunjukkan apakah diperbaiki di v2.

### 3.1 Bug fungsional

| # | Temuan | Lokasi | Dampak | Aksi |
|---|---|---|---|---|
| **A-01** | Pagination memakai query var `paged` (milik WP) | `page-request-equipment.php:25,924+`<br>`page-request-quotation.php:24,962+`<br>`page-sell-equipment.php:24,867+` | Klik halaman 2 → **404**. Halaman lain sudah pakai `page_num`. | **FIX** — rename ke `page_num` di parsing *dan* di semua `http_build_query()` |
| **A-02** | Field `name=` duplikat dalam satu form | `page-add-rental-equipment.php`: `operating_weight` (295 & 349), `bucket_capacity` (299 & 353), `stock_number` (327 & 581), `location` (305 & 598) | `FormData` mengirim keduanya, PHP ambil yang terakhir → nilai Step 1 **hilang diam-diam**. Bug yang sama sudah diperbaiki di `page-add-used-equipment.php` (lihat komentar baris 284 & 571). | **FIX** — hapus duplikat, samakan pola dengan used-equipment |
| **A-03** | Field `name="condition"` padahal kolom DB `condition_status` | `page-add-rental-equipment.php:585` | Nilai tidak tersimpan | **FIX** — hapus (sudah ada `condition_status` di Step 1) |
| **A-04** | Hook email di-attach ke action AJAX yang sama dengan priority 5 | `inc/ajax/ajax-email-notifications.php:23-25` | Email terkirim **sebelum** nonce & capability dicek, dan **sebelum** DB update berhasil. Kalau update gagal, klien tetap dapat email. | **FIX** — ganti dengan `do_action('gti_status_changed', ...)` yang dipanggil handler setelah update sukses |
| **A-05** | Debug log ditulis ke `/tmp/gti-email-debug.log` di production path | `ajax-email-notifications.php:430,439,443,448,459,464` | I/O tiap request + kebocoran data ke lokasi world-readable | **FIX** — hapus, ganti `gti_log()` yang hanya aktif saat `WP_DEBUG` |
| **A-06** | `'valid_until' => ''` di-insert ke kolom `DATE` | `ajax-customer-quotation.php:94` | MySQL strict mode menolak → insert gagal / `0000-00-00` | **FIX** — kirim `null` |
| **A-07** | Debug log FluentForm menulis ke `wp-content/debug-fluentform-fields.log` tanpa guard | `functions.php:165,280` | File tumbuh tanpa batas, bisa diakses publik | **FIX** — guard `WP_DEBUG` |
| **A-08** | Duplikat handler `gti_update_profile` & `gti_logout` (2 pendaftaran berbeda) | `inc/ajax/ajax-profile.php` vs `ajax-dashboard.php` | Handler kedua menang / perilaku tak terduga | **FIX** — sisakan satu |

### 3.2 Field ditampilkan tapi tidak punya sumber data

| # | Halaman | Field ditampilkan | Kolom DB | Aksi |
|---|---|---|---|---|
| **B-01** | Request Equipment drawer | `Usage Purpose`, `Message` | ❌ tidak ada (fallback ke `notes`) | **TAMBAH kolom** `usage_purpose`, `message` |
| **B-02** | Request Quotation drawer | `Payment Terms`, `Attachments`, `Total Items`, `Est. Total Value` | ❌ `payment_terms` & `attachments` tidak ada | **TAMBAH kolom** + tabel `gti_attachments` |
| **B-03** | Customer Detail | `Rating` (KPI besar) | `rating` ada, tapi **tidak ada UI input** → selamanya 0.0 | **HAPUS** KPI Rating |
| **B-04** | Customer Detail | tab `Rentals`, tab `Documents` | tidak ada modul | **HAPUS** kedua tab |
| **B-05** | Customer Detail | `Edit Customer`, `More Actions` (6 item) | semua `href="#"` | **WIRE** Edit (AJAX `gti_save_customer` sudah ada) + Delete + Change Status; **HAPUS** Create Quotation / Request Equipment / Log Call / Export |
| **B-06** | Semua halaman | header `Notifications` badge hardcoded `3` | — | **HAPUS** atau isi dari count status `new` |
| **B-07** | Semua halaman | `<small>Super Admin</small>` hardcoded | — | **FIX** — pakai role label user aktif (sudah benar di `page-users.php:489`) |
| **B-08** | Add Used/Rental Equipment | header `May 1, 2024 - May 31, 2024` hardcoded | — | **FIX** — `date('M j, Y')` |
| **B-09** | Request Quotation | tombol `Create Quotation` → `alert()` TODO | `page-request-quotation.php` | **BANGUN** modal upload (§6.6) |
| **B-10** | Sell Equipment | tombol `Contact Customer` → `tel:` | `page-sell-equipment.php:1417` | **UBAH** → WhatsApp deep-link |
| **B-11** | Media Library | AJAX `gti_update_media` terdaftar, tidak ada UI | `inc/modules/media-library.php` | **BANGUN** edit Title/Alt/Caption di drawer |

### 3.3 Fitur yang belum ada sama sekali

| # | Fitur | Diminta di |
|---|---|---|
| **C-01** | Upload dokumen Proposal saat status → `proposal_sent`, dilampirkan ke email | Request Equipment |
| **C-02** | Upload dokumen Quotation saat klik `Create Quotation`, dilampirkan ke email, status → `waiting_customer` | Request Quotation |
| **C-03** | Modal "Reply to Customer" — kirim email bebas (subject + body + lampiran) dari dalam panel | Request Quotation, Sell Equipment, Request Equipment |
| **C-04** | Assign PIC ke pesanan + row-level scoping (sales hanya melihat pesanan miliknya) | Request Quotation |
| **C-05** | Aksi "Request Invoice" ke penjual | Sell Equipment |
| **C-06** | Timeline status yang nyata (saat ini ditebak dari `updated_at`) | Semua inbox |
| **C-07** | Field tambahan pada form inquiry shortcode | 3 shortcode |

---

## 4. Model Data & Migrasi v1.3.0

### 4.1 Aturan migrasi

Semua perubahan schema **wajib** lewat `includes/class-gti-activator.php` dengan guard `version_compare($db_version, '1.3.0', '<')` dan pola `SHOW COLUMNS` + `ALTER TABLE ... ADD COLUMN` (pola yang sudah dipakai untuk v1.2.0, baris 240-291). `dbDelta()` tidak dipakai untuk kolom baru karena definisi `CREATE TABLE` asli tidak ikut diperbarui.

Di akhir: `update_option('gti_db_version', '1.3.0')`.

### 4.2 `wp_gti_requests` — kolom baru

| Kolom | Tipe | Keterangan |
|---|---|---|
| `usage_purpose` | `VARCHAR(255) NULL` | B-01 — tujuan penggunaan unit |
| `message` | `TEXT NULL` | B-01 — pesan bebas dari klien (`notes` dipakai untuk catatan internal) |
| `customer_address` | `TEXT NULL` | drawer menampilkan Address |
| `assigned_to` | `BIGINT UNSIGNED NULL` | WP user ID PIC |
| `assigned_at` | `DATETIME NULL` | |
| `sales_pic` | `VARCHAR(255) NULL` | denormalisasi nama PIC untuk filter & tampilan |
| `updated_by` | `BIGINT UNSIGNED NULL` | dipakai timeline |
| `source` | `VARCHAR(50) NULL` | `fluentform-3` / `manual` |

### 4.3 `wp_gti_quotations` — kolom baru

| Kolom | Tipe | Keterangan |
|---|---|---|
| `equipment_id` | `BIGINT UNSIGNED NULL` | FK longgar ke `gti_equipment.id` / `gti_spare_parts.id` |
| `equipment_type` | `VARCHAR(20) NULL` | `used` \| `rental` \| `spare_part` |
| `quantity` | `INT DEFAULT 1` | |
| `needed_date` | `DATE NULL` | kapan unit dibutuhkan |
| `rental_start_date` | `DATE NULL` | khusus rental |
| `rental_end_date` | `DATE NULL` | khusus rental |
| `rental_duration` | `VARCHAR(50) NULL` | mis. `3 bulan` |
| `payment_terms` | `VARCHAR(50) NULL` | B-02 |
| `budget` | `DECIMAL(15,2) NULL` | |
| `assigned_to` | `BIGINT UNSIGNED NULL` | C-04 |
| `assigned_at` | `DATETIME NULL` | |
| `updated_by` | `BIGINT UNSIGNED NULL` | |
| `source_url` | `VARCHAR(500) NULL` | halaman asal submit |

> `sales_pic VARCHAR(255)` sudah ada — tetap dipakai sebagai nama tampilan; diisi otomatis dari `display_name` user saat assign.
> `valid_until DATE` sudah ada — kolom ini diisi admin saat upload dokumen quotation.

### 4.4 `wp_gti_sell_requests` — kolom baru

| Kolom | Tipe | Keterangan |
|---|---|---|
| `customer_whatsapp` | `VARCHAR(50) NULL` | fallback ke `customer_phone` |
| `equipment_location` | `VARCHAR(255) NULL` | |
| `message` | `TEXT NULL` | |
| `invoice_requested_at` | `DATETIME NULL` | C-05 |
| `invoice_received_at` | `DATETIME NULL` | |
| `assigned_to` | `BIGINT UNSIGNED NULL` | |
| `assigned_at` | `DATETIME NULL` | |
| `sales_pic` | `VARCHAR(255) NULL` | |
| `updated_by` | `BIGINT UNSIGNED NULL` | |

### 4.5 Tabel baru — `wp_gti_attachments`

Satu tempat untuk semua dokumen yang dikirim/diterima (proposal, quotation, invoice).

```sql
CREATE TABLE wp_gti_attachments (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  entity_type   VARCHAR(20)  NOT NULL,   -- request | quotation | sell
  entity_id     BIGINT UNSIGNED NOT NULL,
  kind          VARCHAR(20)  NOT NULL,   -- proposal | quotation | invoice | other
  attachment_id BIGINT UNSIGNED NOT NULL,-- WP media attachment ID
  original_name VARCHAR(255) NULL,
  file_size     BIGINT UNSIGNED NULL,
  mime_type     VARCHAR(100) NULL,
  note          TEXT NULL,               -- catatan admin saat upload
  emailed_at    DATETIME NULL,           -- kapan dilampirkan ke email
  uploaded_by   BIGINT UNSIGNED NULL,
  created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY idx_entity (entity_type, entity_id),
  KEY idx_kind (kind)
);
```

### 4.6 Tabel baru — `wp_gti_status_history`

C-06. Timeline saat ini **ditebak** dari `created_at`/`updated_at` (`page-request-equipment.php:1270-1300`) sehingga hanya bisa menampilkan 2 titik. Tabel ini membuatnya nyata.

```sql
CREATE TABLE wp_gti_status_history (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  entity_type  VARCHAR(20) NOT NULL,     -- request | quotation | sell
  entity_id    BIGINT UNSIGNED NOT NULL,
  from_status  VARCHAR(50) NULL,
  to_status    VARCHAR(50) NOT NULL,
  note         TEXT NULL,
  user_id      BIGINT UNSIGNED NULL,
  email_sent   TINYINT(1) DEFAULT 0,
  created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY idx_entity (entity_type, entity_id, created_at)
);
```

### 4.7 Tabel `wp_gti_email_logs` — diformalkan

Saat ini dibuat *lazily* di dalam `log_email()` (`ajax-email-notifications.php:352-381`) — `dbDelta()` dipanggil setiap kirim email. Pindahkan ke activator dan tambah kolom:

```sql
CREATE TABLE wp_gti_email_logs (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  type            VARCHAR(50) NOT NULL,      -- request | quotation | sell | manual
  related_id      BIGINT UNSIGNED NOT NULL,
  status          VARCHAR(50) NOT NULL,      -- status tujuan, atau 'manual_reply'
  recipient_email VARCHAR(190) NOT NULL,
  cc              VARCHAR(500) NULL,
  subject         VARCHAR(255) NOT NULL,
  body_html       LONGTEXT NULL,             -- arsip isi email
  attachment_ids  VARCHAR(255) NULL,         -- CSV wp_gti_attachments.id
  send_result     TINYINT(1) DEFAULT 0,      -- hasil wp_mail()
  error_message   TEXT NULL,
  sent_by         BIGINT UNSIGNED NULL,
  sent_at         DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY idx_type_id (type, related_id)
);
```

### 4.8 Indeks tambahan (performa)

```sql
ALTER TABLE wp_gti_requests      ADD INDEX idx_status_created (status, created_at);
ALTER TABLE wp_gti_requests      ADD INDEX idx_assigned (assigned_to);
ALTER TABLE wp_gti_quotations    ADD INDEX idx_status_created (status, created_at);
ALTER TABLE wp_gti_quotations    ADD INDEX idx_assigned (assigned_to);
ALTER TABLE wp_gti_sell_requests ADD INDEX idx_status_created (status, created_at);
ALTER TABLE wp_gti_equipment     ADD INDEX idx_type_status (type, status, deleted_at);
ALTER TABLE wp_gti_customers     ADD INDEX idx_email (email);
```

---

## 5. Fondasi Sistem Baru

Empat modul di bawah ini dipakai bersama oleh 3 halaman inbox. **Bangun ini lebih dulu** sebelum menyentuh halaman.

### 5.1 Status Machine (`inc/modules/status-machine.php`)

Satu sumber kebenaran untuk status, label, warna, dan transisi yang diizinkan.

```php
function gti_status_map($entity_type) : array
// return: [ status_key => ['label'=>, 'class'=>, 'next'=>[...], 'requires'=>'attachment|null'] ]
```

#### Request Equipment (`gti_requests`)

| Status | Label UI | Transisi ke | Syarat |
|---|---|---|---|
| `new` | New | `processing`, `closed` | — |
| `processing` | Processing | `proposal_sent`, `on_hold`, `closed` | — |
| `proposal_sent` | Proposal Sent | `closed`, `processing` | **wajib upload dokumen proposal** |
| `on_hold` *(baru)* | On Hold | `processing`, `closed` | — |
| `closed` | Closed | `processing` | — |

#### Request Quotation (`gti_quotations`)

| Status | Label UI | Transisi ke | Syarat |
|---|---|---|---|
| `new` | New | `processing`, `rejected` | — |
| `processing` | Processing | `waiting_customer`, `rejected` | — |
| `waiting_customer` | **Waiting Quotation Approval** | `approved`, `rejected`, `processing` | **wajib upload dokumen quotation** + `valid_until` |
| `approved` | Approved | `completed`, `rejected` | — |
| `rejected` | Rejected | `processing` | — |
| `completed` | Completed | — | — |

> Label `waiting_customer` diganti menjadi **"Waiting Quotation Approval"** di seluruh UI. **Kunci status di DB tidak berubah** supaya data lama tetap valid.

#### Sell Equipment (`gti_sell_requests`)

| Status | Label UI | Transisi ke | Syarat |
|---|---|---|---|
| `new` | New | `processing`, `rejected` | — |
| `processing` | Processing | `approved`, `rejected` | — |
| `approved` | Approved | `invoice_requested`, `completed`, `rejected` | — |
| `invoice_requested` *(baru)* | Invoice Requested | `completed`, `rejected` | mengirim email permintaan invoice |
| `rejected` | Rejected | `processing` | — |
| `completed` | Completed | — | — |

**Aturan implementasi:**
- Semua handler status **wajib** memvalidasi transisi lewat `gti_status_can_transition($entity, $from, $to)` dan menolak dengan `wp_send_json_error()` bila tidak sah.
- Setelah `UPDATE` sukses → tulis `gti_status_history` → `do_action('gti_status_changed', $entity_type, $id, $from, $to, $context)`.

### 5.2 Attachment Service (`inc/modules/attachments.php`)

```php
gti_attach_document(array $args) : int|WP_Error
// args: entity_type, entity_id, kind, $_FILES key, note
gti_get_attachments($entity_type, $entity_id, $kind = null) : array
gti_delete_attachment($id) : bool
```

**Aturan:**
- File diunggah lewat `media_handle_upload()` → masuk WP Media Library, jadi ikut tampil di `/dashboard/media-library`.
- MIME yang diizinkan: `application/pdf`, `application/msword`, `.docx`, `.xls`, `.xlsx`, `image/jpeg`, `image/png`. Ditolak selain itu.
- Maks **10 MB** per file (validasi client + server).
- Nama file di-rename: `{kind}-{entity_ref}-{8 char random}.{ext}` sebelum upload — mencegah URL ditebak.
  > `ponytail:` file tetap berada di uploads publik. Kalau nanti dokumen quotation harus benar-benar privat, ganti ke direktori di luar webroot + endpoint `?gti_dl={token}` yang mengecek nonce & capability.
- Filter `upload_dir` diarahkan ke subfolder `gti-documents/{YYYY}/{MM}/`.
- Tulis `noindex` via `.htaccess`/`robots.txt` untuk folder tersebut.

### 5.3 Mailer (`inc/modules/mailer.php`) — menggantikan `ajax-email-notifications.php`

```php
GTI_Mailer::send_status_email($entity_type, $entity_id, $to_status, $attachment_ids = [])
GTI_Mailer::send_custom_email($entity_type, $entity_id, $subject, $body, $attachment_ids = [], $cc = '')
GTI_Mailer::render($template_key, array $vars) : string
```

**Perubahan dari implementasi lama:**

1. **Trigger diganti.** Hapus 3 hook priority-5 (A-04). Ganti dengan:
   ```php
   add_action('gti_status_changed', ['GTI_Mailer', 'on_status_changed'], 10, 5);
   ```
   Hook ini hanya berjalan setelah nonce + capability + `UPDATE` sukses.
2. **Template dipindah ke file.** `inc/modules/email-templates/{entity}-{status}.php` + satu layout `layout.php`. Layout HTML tabel yang sudah ada (`ajax-email-notifications.php:209-270`) dipertahankan — sudah benar untuk email client.
3. **Attachment didukung.** Argumen ke-5 `wp_mail()` diisi path file dari `gti_attachments`.
4. **Selalu di-log** ke `gti_email_logs` termasuk `body_html` dan hasil kirim.
5. **Guard**: jika `customer_email` kosong → skip kirim, tetap tulis log dengan `send_result=0` dan `error_message='no recipient'`, dan kembalikan warning ke UI (toast kuning, bukan error merah).

**Matriks email:**

| Entity | Status | Subject (ID) | Lampiran |
|---|---|---|---|
| request | `new` | Pesanan Anda Telah Diterima — {ref} | — |
| request | `processing` | Pesanan Anda Sedang Diproses — {ref} | — |
| request | `proposal_sent` | Proposal Telah Dikirim — {ref} | **proposal** |
| request | `on_hold` | Pesanan Anda Ditunda Sementara — {ref} | — |
| request | `closed` | Pesanan Selesai — {ref} | — |
| quotation | `new` | Quotation Request Diterima — {ref} | — |
| quotation | `processing` | Quotation Sedang Disusun — {ref} | — |
| quotation | `waiting_customer` | Quotation Anda Sudah Siap — {ref} | **quotation** |
| quotation | `approved` | Quotation Disetujui — {ref} | — |
| quotation | `rejected` | Quotation Belum Dapat Diproses — {ref} | — |
| quotation | `completed` | Transaksi Selesai — {ref} | — |
| sell | `new` | Tawaran Equipment Diterima — {ref} | — |
| sell | `processing` | Tawaran Sedang Direview — {ref} | — |
| sell | `approved` | Tawaran Diterima — {ref} | — |
| sell | `invoice_requested` | **Mohon Kirimkan Invoice Anda — {ref}** | — |
| sell | `rejected` | Tawaran Belum Dapat Diterima — {ref} | — |
| sell | `completed` | Transaksi Selesai — {ref} | — |
| * | manual | *(diisi admin)* | opsional |

Template `sell_invoice_requested` harus memuat: nama unit, harga yang disepakati, alamat email tujuan pengiriman invoice, daftar dokumen pendukung yang diminta (STNK/BPKB, faktur, form A), dan deadline (default 7 hari).

### 5.4 Assignment & Scoping (`inc/modules/assignment.php`)

```php
gti_assign_entity($entity_type, $id, $user_id)      // set assigned_to, assigned_at, sales_pic
gti_assignable_users()                              // user dengan cap gti_manage_quotations
gti_scope_where_sql($entity_type, $alias = '')      // '' atau ' AND assigned_to = 123'
```

**Aturan visibilitas:**

| Kondisi | Melihat |
|---|---|
| Punya cap `gti_view_all_requests` (super admin, admin, administrator) | Semua baris |
| Tidak punya cap tersebut (gti_sales) | Hanya `assigned_to = current_user_id()` |

`gti_scope_where_sql()` **wajib** dipanggil di query list, query count, dan query stat card pada Request Quotation (dan opsional pada Request Equipment / Sell Equipment). Tanpa ini, stat card akan menampilkan angka yang tidak cocok dengan tabel.

Perubahan assignment ditulis ke `gti_status_history` dengan `to_status` = status berjalan dan `note` = `"Assigned to {nama}"`, serta ke activity log.

---

## 6. Spesifikasi Per Halaman

Semua halaman mengikuti kerangka bersama: sidebar (§7.5 PRD v1), header, stat cards, toolbar filter (GET form), tabel, pagination, drawer detail kanan (in-flow ≥1600px / fixed <1600px), modal konfirmasi hapus, toast.

### 6.1 Used Equipment — `/dashboard/used-equipment`

**Status:** ✅ berfungsi. Perbaikan minor saja.

| Aspek | Spesifikasi |
|---|---|
| Sumber | `wp_gti_equipment WHERE type='used' AND deleted_at IS NULL` |
| Filter | search (name/code/brand), category, brand, status, condition |
| Pagination | `page_num`, 10/halaman ✅ |
| Aksi baris | View drawer, Edit (modal inline), Publish, Delete (modal + toast) |
| AJAX | `gti_save_equipment`, `gti_get_equipment`, `gti_publish_equipment`, `gti_delete_equipment`, `gti_get_next_code` |
| Auto-code | `GTI-{ABBR}-{YYYY}-{NNN}`, regenerate tiap ganti kategori ✅ |

**Perbaikan v2:** B-06, B-07, B-08.

### 6.2 Rental Equipment — `/dashboard/rental-equipment`

**Status:** ✅ berfungsi. **Form Add punya bug data-loss.**

Sama dengan §6.1, `type='rental'`, plus status `rented`.

**Perbaikan v2 (prioritas tinggi):** A-02, A-03. Setelah perbaikan, jalankan cek: submit form dengan semua field terisi → semua kolom `gti_equipment` yang bersesuaian harus non-null.

### 6.3 Spare Parts — `/dashboard/spare-parts`

**Status:** ✅ berfungsi.

| Aspek | Spesifikasi |
|---|---|
| Sumber | `wp_gti_spare_parts` |
| Status stok | Turunan: `stock <= 0` → `out_of_stock`; `stock <= minimum_stock` → `low_stock`; else `in_stock`. Dihitung server (`gti_spare_stock_status()`), dicerminkan di client. |
| Auto-code | `SP-{ABBR}-{YYYY}-{NNN}` ✅ |
| Draft | `status='draft'` disembunyikan dari katalog publik ✅ |

**Perbaikan v2:** B-06, B-07.

### 6.4 Add/Edit Equipment (Used & Rental) — 5 langkah

| Step | Isi |
|---|---|
| 1 | General Info + Basic Info |
| 2 | Technical Specifications + Features (checkbox → JSON) |
| 3 | Pricing + Status & Availability |
| 4 | Images & Media (main, gallery, video) |
| 5 | Additional Info + Documents + Location + Preview + Checklist |

**Aturan wajib (agar tidak terulang A-02):**
> Dalam satu `<form>`, satu `name=` hanya boleh muncul **satu kali**. Sebelum menambah field baru, jalankan:
> ```bash
> grep -o 'name="[a-z_]*"' templates/page-add-rental-equipment.php | sort | uniq -d
> ```
> Output harus kosong. Jadikan ini bagian dari review checklist.

### 6.5 Request Equipment — `/dashboard/request-equipment`

**Sumber:** FluentForm ID 3 → `wp_gti_requests`.

#### 6.5.1 Tabel

| Kolom | Sumber | Lebar |
|---|---|---|
| Request ID | `request_id` | 110px |
| Customer | `customer_name` + `customer_company` | 150px |
| Requested Equipment | `equipment` | 160px |
| Quantity | `quantity` | 70px |
| Location | `location` | 110px |
| Budget | `budget` (format IDR) | 100px |
| Status | badge | 120px |
| Request Date | `request_date` ?: `created_at` | 110px |
| PIC *(baru)* | `sales_pic` ?: "Unassigned" | 110px |
| Actions | dropdown | 70px |

#### 6.5.2 Drawer

Section **Customer Information** — Name, Company, Email, Phone, Address (`customer_address`).
Section **Request Information** — Equipment, Category, Brand, Quantity, Location, Budget, Required Date, Usage Purpose (`usage_purpose`), Request Date, Message (`message`).
Section **Timeline** — dari `gti_status_history`, terbaru = dot emas, sisanya abu.
Section **Documents** *(baru)* — daftar `gti_attachments` kind=`proposal` dengan nama file, ukuran, tanggal, tombol Download.

#### 6.5.3 Footer drawer

| Tombol | Aksi |
|---|---|
| **Update Status** (primary) | buka dropdown status sah menurut §5.1 |
| **Reply** | buka modal Compose Email (§6.6.4) |
| **Assign PIC** | buka dropdown user |
| ⋮ More | Mark Processing / Send Proposal / On Hold / Close / Delete |

#### 6.5.4 Alur "Send Proposal" (C-01)

1. Admin klik **Send Proposal**.
2. **Modal Upload Proposal** terbuka:
   - Drop-zone file (PDF/DOC/DOCX/XLS/XLSX, maks 10 MB) — **wajib**
   - Textarea "Catatan untuk klien" (opsional, disisipkan ke body email)
   - Preview nama file + ukuran, tombol hapus
   - Tombol `Kirim Proposal` (disabled sampai ada file)
3. Submit → `POST gti_send_proposal` (multipart):
   - verifikasi nonce `gti_nonce` + cap `gti_manage_requests`
   - validasi transisi `processing → proposal_sent`
   - `gti_attach_document(kind='proposal')`
   - `UPDATE gti_requests SET status='proposal_sent', updated_by=...`
   - `INSERT gti_status_history`
   - `do_action('gti_status_changed', 'request', $id, $from, 'proposal_sent', ['attachment_ids'=>[...], 'note'=>...])`
   - Mailer kirim email + lampiran, tulis `gti_email_logs`
   - `gti_log_activity('status_change', 'request', $id, ...)`
4. Response `{success, message, status, status_label, email_sent, attachment}`.
5. UI: tutup modal → toast hijau "Proposal terkirim ke {email}" → update badge status + timeline + daftar dokumen **tanpa reload**.
6. Jika `email_sent=false` → toast kuning: "Proposal tersimpan, tapi email gagal terkirim. Cek konfigurasi SMTP."

> Menghilangkan `location.reload()` (dipakai sekarang di `updateStatus()`, `page-request-equipment.php:1376`) — ganti update DOM parsial supaya posisi scroll & drawer tidak hilang.

**Perbaikan v2:** A-01, B-01, B-06, B-07 + hapus `confirm()` native, ganti modal konfirmasi bergaya sama dengan modal delete yang sudah ada.

### 6.6 Request Quotation — `/dashboard/request-quotation`

**Sumber:** 3 shortcode katalog → `wp_gti_quotations`. Ini halaman paling banyak berubah.

#### 6.6.1 Scoping

Query list, count, dan stat card **wajib** menyertakan `gti_scope_where_sql('quotation')`. Untuk user `gti_sales` tanpa cap `gti_view_all_requests`, hanya baris `assigned_to = current user` yang muncul — termasuk di angka stat card.

#### 6.6.2 Tabel

Quotation ID · Customer · Company · Requested Items · **Sales PIC** · Status · Request Date · Actions.
Filter: search, status (6 nilai), brand, PIC, rentang tanggal.

#### 6.6.3 Alur "Create Quotation" (C-02) — menggantikan B-09

1. Admin klik **Create Quotation**.
2. **Modal Upload Quotation**:

   | Field | Tipe | Wajib |
   |---|---|---|
   | Dokumen Quotation | file (PDF/DOC/DOCX/XLS/XLSX ≤10 MB) | ✅ |
   | Nomor Quotation | text, default `quotation_id` | ✅ |
   | Total Nilai (IDR) | number → `total` | ✅ |
   | Berlaku Sampai | date → `valid_until` | ✅ |
   | Payment Terms | select → `payment_terms` | — |
   | Delivery Location | text → `delivery_location` | — |
   | Catatan untuk klien | textarea → body email | — |

3. Submit → `POST gti_create_quotation` (multipart):
   - nonce + cap `gti_manage_quotations` + (jika sales) cek `assigned_to = current_user`
   - validasi transisi → `waiting_customer`
   - simpan attachment kind=`quotation`
   - `UPDATE` field di atas + `status='waiting_customer'`
   - status history + `gti_status_changed` → email + lampiran
4. Badge berubah menjadi **"Waiting Quotation Approval"**.
5. Setelah ini, dropdown status menampilkan **Approved** dan **Rejected**.

#### 6.6.4 Modal "Reply to Customer" (C-03)

Dipakai bersama oleh Request Equipment, Request Quotation, dan Sell Equipment.

| Field | Perilaku |
|---|---|
| To | readonly, dari `customer_email`; kalau kosong → banner merah + tombol kirim disabled |
| CC | opsional, divalidasi sebagai email |
| Subject | prefilled `Re: {ref_id} — {equipment/company}`, editable |
| Message | textarea, `wp_kses_post` di server, `nl2br` di email |
| Lampiran | opsional, multi-file, aturan sama dengan §5.2 |
| Sisipkan tanda tangan | checkbox, default on — menambahkan blok signature dari user aktif |

Submit → `POST gti_send_customer_email` → `GTI_Mailer::send_custom_email()` → `gti_email_logs` (`type='manual'`) + activity log `email_sent`.
Balasan klien masuk ke inbox email admin biasa (header `Reply-To` = email user pengirim, bukan `admin_email`) — **bukan** ke dalam panel.

#### 6.6.5 Assign PIC (C-04)

- Tombol **Assign PIC** di footer drawer + item di dropdown aksi baris.
- Modal berisi `<select>` daftar `gti_assignable_users()` + opsi "Unassigned".
- `POST gti_assign_entity` → `{entity_type, id, user_id}` → set `assigned_to`, `assigned_at`, `sales_pic`.
- Opsional (default **on**): kirim email notifikasi internal ke PIC baru berisi link `?gti_page=request-quotation&highlight={id}`.
- Setelah assign, jika user aktif adalah sales dan meng-assign ke orang lain, baris hilang dari daftarnya → tampilkan toast "Pesanan dialihkan ke {nama}" lalu hapus baris dari DOM.

#### 6.6.6 Drawer

Customer Information · Quotation Details (Requested Date, Valid Until, **Payment Terms**, Delivery Location, Needed Date, Rental Period bila ada, Notes) · Items (dari `items` JSON: nama, qty, catatan) · Summary (Total Items, Est. Total Value, Sales PIC) · **Documents** (kind=`quotation`) · Timeline · Email History (5 terakhir dari `gti_email_logs`).

**Perbaikan v2:** A-01, B-02, B-09, B-06, B-07.

### 6.7 Sell Equipment — `/dashboard/sell-equipment`

**Sumber:** FluentForm ID 4 → `wp_gti_sell_requests`.

#### 6.7.1 Perubahan tombol footer

| Tombol | Sebelum | Sesudah |
|---|---|---|
| **Contact Customer** | `tel:` | **WhatsApp** — `https://wa.me/{62xxx}?text={template}` di tab baru (B-10) |
| **Email** | `mailto:` | Modal Compose Email (§6.6.4) |
| **Request Invoice** *(baru)* | — | Modal konfirmasi → status `invoice_requested` + email (C-05) |

**Normalisasi nomor WhatsApp** (`gti_wa_number($phone)`):
```
buang semua non-digit
"0"     di awal → ganti "62"
"+62"   → "62"
"8"     di awal → prepend "62"
sudah "62" → biarkan
kosong / < 9 digit → return '' (tombol disabled + tooltip "Nomor tidak tersedia")
```
Template pesan: `Halo {nama}, terima kasih atas penawaran unit {equipment_name} ({brand} {model}, {year}) kepada PT Global Tractors Indonesia. Kami ingin menindaklanjuti penawaran Anda.`

#### 6.7.2 Alur "Request Invoice" (C-05)

Tersedia hanya saat status `approved`.

1. Klik **Request Invoice** → modal konfirmasi menampilkan: nama unit, harga disepakati, email tujuan, deadline (default 7 hari, editable), daftar dokumen yang diminta (checkbox: Invoice, STNK/BPKB, Faktur, Form A, Sertifikat Unit), catatan tambahan.
2. `POST gti_request_invoice` → validasi transisi `approved → invoice_requested` → set `invoice_requested_at` → history → email template `sell_invoice_requested`.
3. Setelah invoice diterima admin (di luar sistem), admin klik **Mark Completed** → set `invoice_received_at` + status `completed`.

#### 6.7.3 Drawer

Customer Information (+ WhatsApp) · Equipment Information (+ Location) · Offer Details (Offered Price, Submission Date, Last Updated, Invoice Requested At) · Equipment Images (grid, klik → lightbox) · Message · Timeline · Email History.

**Perbaikan v2:** A-01, B-06, B-07 + gambar: parsing `images` JSON saat ini membuang entri yang berupa angka murni (`page-sell-equipment.php:1245`) — resolve attachment ID menjadi URL di server (`wp_get_attachment_url()`) sebelum dikirim ke client, jangan dibuang.

### 6.8 Customers — `/dashboard/customers`

**Status:** ✅ berfungsi. Auto-sync dari `gti_submission_received` + backfill throttled 5 menit.

**Perbaikan v2:**
- Pindahkan `gti_fmt_currency()`, `gti_fmt_date()`, `gti_customer_initials()` dari template ke `inc/helpers/format-helpers.php` dengan guard `function_exists` — mencegah fatal error kalau template ter-include dua kali.
- Tambah tombol **Add Customer** (AJAX `gti_save_customer` sudah tersedia, belum ada UI).
- Tambah filter rentang `registered_date`.
- B-06, B-07.

### 6.9 Customer Detail — `/dashboard/customer-detail?id={customer_id}`

**Yang dihapus** (B-03, B-04, B-05):
- KPI **Rating** (tidak pernah ada input → selalu 0.0)
- Tab **Rentals** (tidak ada modul)
- Tab **Documents** (tidak ada modul)
- Dropdown item: Create Quotation, Request Equipment, Log Call, Export Data (semua `href="#"`)

**Yang diaktifkan:**

| Aksi | Implementasi |
|---|---|
| **Edit Customer** | Modal berisi field yang benar-benar ada di tabel: name, company, email, phone, address, industry, city, province, country, website, npwp, contact_person, contact_position, contact_phone, contact_email, contact_whatsapp, status. → `gti_save_customer` |
| **Change Status** | Toggle active/inactive → `gti_save_customer` |
| **Delete Customer** | Modal konfirmasi + peringatan bahwa request/quotation terkait **tidak** ikut terhapus → AJAX baru `gti_delete_customer` (cap `gti_manage_customers`) |
| **Send Email** | Modal Compose Email (§6.6.4), `entity_type='customer'` |

**Tab final:** Overview · Request & Inquiry · Quotations · Transactions · Activity Log (5 tab).

**Statistik:** tetap dihitung `gti_refresh_customer_stats()` sebelum render. KPI: Total Transactions, Total Spent, Total Inquiry, Total Quotation.

### 6.10 News & Articles — `/dashboard/news-articles`

**Status:** ✅ sudah benar-benar terhubung ke WP Posts via `inc/modules/news-articles.php`.

| Aspek | Mapping |
|---|---|
| Sumber | `WP_Query` post type `post` |
| Status | `published` ↔ `publish`, `draft` ↔ `draft`, `archived` ↔ `private` |
| Kategori | taxonomy `category` (term dibuat otomatis bila belum ada) |
| Tags | taxonomy `post_tag` |
| Featured image | `media_handle_upload()` → post thumbnail (ikut muncul di Media Library) |
| Author | WP user; reassign butuh cap `edit_others_posts` |
| Views | post meta `gti_article_views` |
| Edit di wp-admin | `edit_url` → `post.php?post={id}&action=edit` |
| Permalink | `permalink` → link publik |

**Gap yang harus ditutup:**

1. **Counter views** — meta `gti_article_views` dibaca tapi tidak pernah bertambah. Tambahkan di `inc/modules/news-articles.php`:
   ```php
   add_action('wp_head', function () {
       if (!is_singular('post') || is_user_logged_in()) return;
       // sekali per pengunjung per artikel per 12 jam (cookie / transient by IP hash)
       gti_increment_article_views(get_the_ID());
   });
   ```
2. **Editor konten** — `<textarea>` polos. Naikkan ke `wp_editor()` (TinyMCE) supaya paritas dengan wp-admin. Butuh `wp_enqueue_editor()` di halaman ini.
3. **Delete** → saat ini `wp_trash_post`. Tambahkan pilihan "Hapus permanen" untuk item yang sudah di trash.
4. **SEO fields** — jika Yoast/RankMath aktif, tampilkan meta title & description. Kalau tidak, lewati (jangan buat field yang tidak menyimpan ke mana-mana).
5. Slug editable pada form Add/Edit.

**Route Add/Edit:** `/dashboard/news-articles/add` dan `/dashboard/news-articles/add?id={post_id}` (mode edit) ✅ sudah jalan.

### 6.11 Media Library — `/dashboard/media-library`

**Status:** ✅ sudah benar-benar terhubung ke `wp_posts` type `attachment` via `inc/modules/media-library.php`.

Yang sudah ada: grid/list view (persisted di localStorage), filter type & bulan, search, pagination `page_num`, drawer detail, upload multi-file (`gti_upload_media`), delete permanen (`gti_delete_media`), copy URL.

**Gap yang harus ditutup:**

1. **Edit metadata (B-11)** — `gti_update_media` sudah terdaftar tapi tanpa UI. Tambahkan di drawer: field editable Title, Alt Text, Caption, Description + tombol Save.
2. **Progress bar upload nyata** — sekarang lompat 0→100%. Ganti `fetch` dengan `XMLHttpRequest` + `upload.onprogress`.
3. **Validasi ukuran client-side** sebelum upload (baca `wp_max_upload_size()`, teruskan ke JS).
4. **Bulk select + bulk delete** — checkbox `.gti-ml-card-check` sudah ada di DOM tapi tidak berfungsi. Aktifkan atau hapus.
5. **Guard capability** — `gti_delete_media` harus cek `current_user_can('delete_post', $id)`.

### 6.12 Users — `/dashboard/users`

**Status:** ✅ profil sendiri + direktori tim + CRUD user sudah berfungsi (`gti_save_user`, `gti_delete_user`).

**Gap yang harus ditutup:**

1. **Pisahkan halaman.** Saat ini "My Profile" dan "System Users" menumpuk di satu halaman. Jadikan dua tab: **My Profile** | **System Users** (tab kedua hanya tampil bila `current_user_can('list_users')`).
2. **Pagination + search** pada direktori (sekarang `number => 200` tanpa paging).
3. **Filter role**.
4. **Reassign konten saat hapus user** — `wp_delete_user()` tanpa reassign akan menghapus post milik user tsb. Modal hapus wajib menyediakan pilihan "Alihkan konten ke: {select user}".
5. **Cegah menghapus super admin terakhir** — validasi server.
6. **Kolom "Assigned Orders"** — jumlah request/quotation yang di-assign ke user tsb (mendukung C-04).
7. **Avatar upload** — atau hapus, jangan biarkan hanya Gravatar tanpa penjelasan.
8. Ganti hardcoded `<small>Super Admin</small>` di header semua template dengan role label sesungguhnya (B-07).

### 6.13 Activity Log — `/dashboard/activity-log`

**Status:** ✅ berfungsi. Tabel di-`ensure` tiap load, filter search/action/date-range, pagination `page_num`.

**Gap yang harus ditutup:**

1. **Cakupan pencatatan.** Standarkan — setiap aksi mutasi **wajib** memanggil `gti_log_activity($action, $entity_type, $entity_id, $description, $meta = [])`:

   | Action | Kapan |
   |---|---|
   | `login` / `logout` / `failed_login` | ✅ sudah |
   | `create` / `update` / `delete` | equipment, spare part, customer, user, article, media |
   | `status_change` | 3 modul inbox — simpan `from`/`to` di `details` |
   | `assign` | ganti PIC |
   | `upload` | dokumen proposal/quotation, media |
   | `email_sent` | tiap kiriman email (otomatis/manual) |
   | `publish` / `unpublish` | equipment, spare part, artikel |

2. **Detail drawer** — klik baris → drawer menampilkan `details` JSON dalam bentuk terbaca (diff before/after untuk `update`).
3. **Filter user** (`<select>` daftar user).
4. **Export CSV** dari hasil filter aktif (cap `gti_manage_settings`).
5. **Retensi** — cron harian `gti_purge_activity_log` menghapus baris > 12 bulan. Simpan durasi di option `gti_activity_retention_months`.
6. **Guard capability** — halaman ini menampilkan data lintas user; batasi ke cap `gti_manage_settings` atau tampilkan hanya baris milik sendiri untuk role lain.

### 6.14 Dashboard — `/dashboard`

Di luar daftar permintaan, tetapi harus konsisten setelah §4–§5:
- Stat card memakai `GTI_Database::get_dashboard_stats()` — tambahkan `sell_requests_new` dan hormati `gti_scope_where_sql()` untuk role sales.
- Panel "Recent Activity" mengambil 10 baris terakhir `gti_activity_log`.
- Panel "Needs Attention": request/quotation berstatus `new` > 24 jam atau `waiting_customer` melewati `valid_until`.

---

## 7. Spesifikasi Shortcode Website

Tiga shortcode ini adalah **portal klien**. Semua submission masuk ke `wp_gti_quotations` dan tampil di `/dashboard/request-quotation`.

| Shortcode | File | Sumber data |
|---|---|---|
| `[gti_used_equipment_filter]` | `inc/shortcodes/equipment-filter-used-equipment.php` | `gti_equipment` type=used |
| `[gti_rental_equipment_filter]` | `inc/shortcodes/equipment-filter-rental-equipment.php` | `gti_equipment` type=rental |
| `[gti_spare_parts_filter]` | `inc/shortcodes/equipment-filter-spare-parts.php` | `gti_spare_parts` |

### 7.1 Struktur (sudah ada)

Mode katalog: sidebar filter + top bar (sort, grid/list) + grid kartu + skeleton loading + no-results + pagination. Filter async via `gti_filter_equipment` / `gti_filter_spare_parts`.
Mode detail (`?id={n}`): breadcrumb, hero (galeri + summary + CTA), spec table, "Why buy from GTI", related carousel, **form inquiry**.

### 7.2 Form inquiry — field saat ini vs. yang harus ada

**Sekarang** (5 field): `ed_name`, `ed_company`, `ed_phone`, `ed_email`, `ed_message`.

Akibatnya banyak kolom di drawer admin kosong terus. Berikut field final:

#### Field bersama (ketiga shortcode)

| Name | Label | Tipe | Wajib | → Kolom DB |
|---|---|---|---|---|
| `ed_name` | Nama Lengkap | text | ✅ | `customer_name` |
| `ed_company` | Perusahaan | text | — | `customer_company` |
| `ed_phone` | Telepon / WhatsApp | tel | ✅ | `customer_phone` |
| `ed_email` | Email | email | ✅ | `customer_email` |
| `ed_address` | Alamat | textarea | — | `customer_address` |
| `ed_quantity` | Jumlah Unit | number, min 1, default 1 | ✅ | `quantity` + `items[0].quantity` |
| `ed_needed_date` | Dibutuhkan Tanggal | date, min hari ini | — | `needed_date` |
| `ed_delivery_location` | Lokasi Pengiriman | text | — | `delivery_location` |
| `ed_payment_terms` | Metode Pembayaran | select | — | `payment_terms` |
| `ed_budget` | Estimasi Budget (IDR) | text (numeric mask) | — | `budget` |
| `ed_message` | Pesan / Kebutuhan Spesifik | textarea | — | `additional_notes` |
| `ed_consent` | Persetujuan dihubungi | checkbox | ✅ | tidak disimpan (gate submit) |

Opsi `ed_payment_terms`: `cash` (Tunai) · `transfer` (Transfer Bank) · `credit` (Kredit / Cicilan) · `leasing` (Leasing) · `other` (Lainnya).

#### Field khusus Rental Equipment

| Name | Label | Tipe | → Kolom DB |
|---|---|---|---|
| `ed_rental_start` | Mulai Sewa | date | `rental_start_date` |
| `ed_rental_end` | Selesai Sewa | date | `rental_end_date` |
| `ed_rental_duration` | Durasi Sewa | select (`1 bulan`/`3 bulan`/`6 bulan`/`12 bulan`/`custom`) | `rental_duration` |
| `ed_operator_needed` | Butuh Operator? | select (ya/tidak) | disisipkan ke `additional_notes` |

Validasi: `rental_end > rental_start`; bila `ed_rental_duration != custom`, `rental_end` dihitung otomatis dari `rental_start`.

#### Field khusus Spare Parts

| Name | Label | Tipe | → |
|---|---|---|---|
| `ed_part_number` | Part Number | readonly, prefilled | `items[0].part_number` |
| `ed_unit_model` | Model Unit Terpasang | text | `items[0].unit_model` |
| `ed_urgency` | Tingkat Urgensi | select (`normal`/`urgent`/`critical`) | `items[0].urgency` |

#### Field tersembunyi (ketiga shortcode)

| Name | Isi |
|---|---|
| `gti_quot_nonce` | `wp_create_nonce('gti_customer_quotation')` ✅ sudah ada |
| `equipment_id` | dari `data-id` wrapper ✅ |
| `equipment_name` | dari `data-name` ✅ |
| `equipment_type` | `used` \| `rental` \| `spare_part` **(baru)** |
| `source_url` | `home_url(add_query_arg([]))` **(baru)** |

### 7.3 Perubahan handler `gti_customer_submit_quotation`

File: `inc/ajax/ajax-customer-quotation.php`

1. Sanitasi & simpan semua field baru di §7.2.
2. `valid_until` → `null`, bukan `''` (A-06).
3. **Rate limit** — maks 3 submit per IP per 10 menit via transient (`inc/security/rate-limit.php` sudah ada; pakai itu). Melebihi → `wp_send_json_error` dengan pesan sopan.
4. **Honeypot** — field `ed_website` tersembunyi via CSS; jika terisi → return sukses palsu tanpa insert.
5. `items` JSON diperkaya:
   ```json
   [{ "equipment_id": 12, "type": "rental", "name": "KOMATSU PC200-8",
      "part_number": "", "quantity": 2, "unit_model": "", "urgency": "",
      "notes": "..." }]
   ```
6. Email konfirmasi ke **klien** (`GTI_Mailer::send_status_email('quotation', $id, 'new')`) — sekarang hanya admin yang dapat email.
7. Email notifikasi ke **admin** dipertahankan, plus link langsung ke drawer: `{dashboard}/request-quotation?highlight={id}`.
8. Auto-assign PIC jika ada option `gti_default_sales_pic`; jika tidak, biarkan unassigned.

### 7.4 UX form

- Progressive disclosure: tampilkan 5 field inti; field lanjutan di balik toggle "Detail kebutuhan (opsional)". Jangan menakuti pengunjung dengan 12 field sekaligus.
- Validasi inline saat blur, bukan hanya saat submit.
- Setelah sukses: ganti isi form dengan kartu konfirmasi berisi **nomor quotation** dan estimasi waktu respons. (Sudah ada di `assets/js/equipment-detail.js:130+` — pertahankan, tambahkan nomor quotation.)
- Tombol disabled + spinner selama request ✅ sudah ada.

### 7.5 Konsistensi katalog

- **Data dummy** — `gti_get_dummy_spare_parts_data()` dan padanannya di shortcode equipment tampil ketika tabel kosong. Ini berbahaya untuk produksi (pengunjung melihat unit yang tidak ada). Bungkus dengan `if (defined('WP_DEBUG') && WP_DEBUG)` atau option `gti_show_demo_data` (default off), dan tampilkan empty-state yang benar di produksi.
- **Draft** — `status != 'draft'` sudah difilter di spare parts; pastikan hal yang sama berlaku di equipment (`deleted_at IS NULL AND status != 'draft'`).
- **Harga kedaluwarsa** — badge "Price no longer valid" ketika `price_valid_until < today` ✅ sudah ada di used equipment; terapkan juga ke rental.
- **Pagination shortcode** — `gti_spare_render_pagination()` selalu me-render `$current = 1` (`equipment-filter-spare-parts.php:315`); tombol dikelola JS. Pastikan JS memperbarui state aktif, atau render server-side dari parameter.

---

## 8. Kontrak AJAX API

Semua endpoint: `POST` ke `admin-ajax.php`, body `FormData`, response `wp_send_json_success/error`.
Semua **wajib**: verifikasi nonce `gti_nonce` → cek `current_user_can()` → validasi input → aksi → activity log.

### 8.1 Endpoint yang sudah ada

| Action | Cap | Ket. |
|---|---|---|
| `gti_save_equipment` | `gti_manage_equipment` | create/update |
| `gti_get_equipment` | `gti_manage_equipment` | |
| `gti_publish_equipment` | `gti_manage_equipment` | |
| `gti_delete_equipment` | `gti_manage_equipment` | soft delete (`deleted_at`) |
| `gti_get_next_code` | `gti_manage_equipment` | |
| `gti_save_spare_part` / `gti_publish_spare_part` / `gti_delete_spare_part` / `gti_get_next_spare_part_code` | `gti_manage_spare_parts` | |
| `gti_update_request_status` / `gti_delete_request` | `gti_manage_requests` | |
| `gti_save_quotation` / `gti_update_quotation_status` / `gti_delete_quotation` | `gti_manage_quotations` | |
| `gti_update_sell_request_status` / `gti_delete_sell_request` / `gti_get_sell_request_detail` | `gti_manage_requests` | |
| `gti_save_customer` | `gti_manage_customers` | |
| `gti_save_user` / `gti_delete_user` | `gti_manage_users` | |
| `gti_upload_media` / `gti_delete_media` / `gti_update_media` | `upload_files` / `delete_posts` | |
| `gti_update_article_status` / `gti_delete_article` | `edit_posts` | |
| `gti_get_dashboard_stats` | `gti_access` | |
| `gti_customer_submit_quotation` | publik | + nonce khusus + rate limit |
| `gti_filter_equipment` / `gti_filter_spare_parts` | publik | |

### 8.2 Endpoint baru

| Action | Cap | Payload | Response |
|---|---|---|---|
| `gti_send_proposal` | `gti_manage_requests` | `id`, `file` (multipart), `note` | `{status, status_label, email_sent, attachment:{id,name,url,size}, timeline_html}` |
| `gti_create_quotation` | `gti_manage_quotations` | `id`, `file`, `quotation_number`, `total`, `valid_until`, `payment_terms`, `delivery_location`, `note` | idem |
| `gti_request_invoice` | `gti_manage_requests` | `id`, `deadline_days`, `documents[]`, `note` | `{status, status_label, email_sent}` |
| `gti_send_customer_email` | `gti_manage_requests` \| `gti_manage_quotations` | `entity_type`, `id`, `subject`, `message`, `cc`, `files[]`, `signature` | `{email_sent, log_id}` |
| `gti_assign_entity` | `gti_manage_requests` \| `gti_manage_quotations` | `entity_type`, `id`, `user_id` | `{assigned_to, sales_pic, notified}` |
| `gti_get_timeline` | sesuai entity | `entity_type`, `id` | `{items:[{event,date,actor,note}]}` |
| `gti_get_email_history` | sesuai entity | `entity_type`, `id`, `limit` | `{items:[{subject,to,sent_at,result}]}` |
| `gti_delete_attachment` | sesuai entity | `id` | `{deleted:true}` |
| `gti_delete_customer` | `gti_manage_customers` | `id` | `{deleted:true}` |
| `gti_export_activity_log` | `gti_manage_settings` | filter aktif | CSV download |

### 8.3 Format response standar

```jsonc
// sukses
{ "success": true,  "data": { "message": "Proposal terkirim ke budi@x.co.id", /* ... */ } }
// gagal
{ "success": false, "data": { "message": "Transisi status tidak valid.", "code": "invalid_transition" } }
```

Client **wajib** membaca `res.data.message` dan menampilkannya lewat `showUeToast()` — bukan `alert()`. Pola toast sudah tersedia di `page-request-equipment.php:1454`; ekstrak ke `assets/js/dashboard-ui.js` supaya tidak diduplikasi di 8 template.

---

## 9. Roles & Capabilities

### 9.1 Capability yang sudah ada

`gti_access`, `gti_manage_users`, `gti_manage_equipment`, `gti_manage_spare_parts`, `gti_manage_requests`, `gti_manage_quotations`, `gti_manage_customers`, `gti_manage_settings`

### 9.2 Capability baru

| Cap | Untuk |
|---|---|
| `gti_view_all_requests` | Melihat baris milik semua PIC (super admin, admin, administrator) |
| `gti_send_email` | Mengirim email manual ke klien |
| `gti_upload_documents` | Upload proposal/quotation |

### 9.3 Matriks

| Cap | super_admin | admin | sales | inventory |
|---|:--:|:--:|:--:|:--:|
| `gti_access` | ✅ | ✅ | ✅ | ✅ |
| `gti_manage_equipment` | ✅ | ✅ | — | ✅ |
| `gti_manage_spare_parts` | ✅ | ✅ | — | ✅ |
| `gti_manage_requests` | ✅ | ✅ | ✅ | — |
| `gti_manage_quotations` | ✅ | ✅ | ✅ | — |
| `gti_manage_customers` | ✅ | ✅ | ✅ | — |
| `gti_manage_users` | ✅ | — | — | — |
| `gti_manage_settings` | ✅ | — | — | — |
| `gti_view_all_requests` | ✅ | ✅ | **—** | — |
| `gti_send_email` | ✅ | ✅ | ✅ | — |
| `gti_upload_documents` | ✅ | ✅ | ✅ | — |

Naikkan versi role dan panggil ulang `GTI_Roles::add_roles()` saat `gti_db_version < 1.3.0`.

### 9.4 Penegakan

Capability **wajib** ditegakkan di **tiga lapis**:
1. **Menu** — sembunyikan item sidebar yang tidak diizinkan (belum dilakukan; semua template me-render sidebar identik).
2. **Halaman** — `gti_require_cap('gti_manage_quotations')` di awal template, redirect ke `/dashboard` bila gagal. Saat ini hanya ada `gti_require_login()`.
3. **AJAX** — cek di setiap handler (sebagian sudah, harus diseragamkan).

---

## 10. Non-Functional Requirements

### 10.1 Keamanan

| Aturan | Status |
|---|---|
| Semua query `$wpdb` memakai `prepare()` | ⚠️ sebagian query count/filter dirakit tanpa prepare saat `$params` kosong — aman karena tanpa input user, tetapi seragamkan |
| Output di-escape (`esc_html`, `esc_attr`, `esc_url`, `esc_js`) | ✅ mayoritas |
| `json_encode()` ke atribut HTML dibungkus `esc_attr()` | ✅ sudah diperbaiki untuk equipment & spare parts — **cek ulang** `page-request-equipment.php:884` dan `page-sell-equipment.php:825` yang masih `onclick='showX(<?php echo json_encode($req); ?>)'` tanpa escape |
| Nonce di semua form & AJAX | ✅ |
| Capability check di semua AJAX | ⚠️ seragamkan |
| Upload: whitelist MIME + batas ukuran + rename | 🆕 §5.2 |
| Rate limit form publik | 🆕 §7.3 |
| Tidak ada debug log di path produksi | 🔧 A-05, A-07 |
| `Reply-To` email = user pengirim, bukan `admin_email` | 🆕 |

### 10.2 Performa

- Query list: 1 count + 1 select per halaman, `LIMIT` selalu ada ✅
- Tambah indeks §4.8 sebelum data > 5.000 baris
- Stat card: gabungkan 4–6 `COUNT(*)` menjadi satu query `GROUP BY status` (pola ini sudah dipakai Request Equipment, tapi Customers menjalankan 4 query terpisah)
- Gambar katalog: `loading="lazy"` ✅
- Target: TTFB < 400 ms, halaman list interaktif < 1,5 s pada 3G cepat
- Font Awesome & Google Fonts dari CDN di setiap template — pertimbangkan enqueue terpusat via `inc/setup/enqueue.php` agar cache konsisten

### 10.3 Kode & maintainability

Setiap template dashboard saat ini berisi ~150–600 baris CSS inline yang **hampir identik** (drawer, modal delete, toast, action dropdown, pagination). Ekstrak ke:
- `assets/css/dashboard-drawer.css`
- `assets/css/dashboard-modal.css`
- `assets/js/dashboard-ui.js` (toast, dropdown positioning, drawer open/close, delete modal, escape key)

Ini bukan kosmetik: bug seperti A-01 muncul justru karena logika yang sama disalin ke 8 file dan hanya sebagian yang diperbaiki.

### 10.4 Responsive

| Breakpoint | Perilaku |
|---|---|
| ≥1600px | Drawer in-flow di kanan tabel |
| <1600px | Drawer fixed slide-in dari kanan + backdrop |
| ≤768px | Stat card 2 kolom, toolbar vertikal, tabel scroll horizontal |
| ≤560px | Drawer bottom-sheet (slide dari bawah, max-height 85vh) |

✅ Sudah konsisten di semua halaman inbox. Terapkan pola yang sama ke halaman baru.

### 10.5 Bahasa

- UI dashboard: **English** (konsisten dengan kondisi sekarang)
- Email ke klien: **Bahasa Indonesia**
- Pesan validasi form publik: **Bahasa Indonesia**

### 10.6 Retensi data

| Data | Retensi |
|---|---|
| Activity log | 12 bulan (cron purge) |
| Email log | 24 bulan; `body_html` dikosongkan setelah 6 bulan |
| Dokumen attachment | permanen sampai dihapus manual |
| Equipment terhapus | soft delete; hard delete manual setelah 90 hari |

---

## 11. Rencana Implementasi

### Fase 0 — Perbaikan blocking (½ hari)

| # | Tugas | File |
|---|---|---|
| 1 | A-01 pagination `paged` → `page_num` | `page-request-equipment.php`, `page-request-quotation.php`, `page-sell-equipment.php` |
| 2 | A-02 + A-03 duplikat field | `page-add-rental-equipment.php` |
| 3 | A-05 + A-07 hapus debug log | `ajax-email-notifications.php`, `functions.php` |
| 4 | A-06 `valid_until` null | `ajax-customer-quotation.php` |
| 5 | A-08 hapus handler duplikat | `ajax-profile.php` / `ajax-dashboard.php` |
| 6 | Escape `json_encode` di atribut `onclick` | `page-request-equipment.php:884`, `page-sell-equipment.php:825` |

**Selesai bila:** klik halaman 2 di ketiga inbox tidak 404; submit form rental menyimpan seluruh field; tidak ada file ditulis ke `/tmp` atau `wp-content/*.log`.

### Fase 1 — Fondasi (2 hari)

| # | Tugas | File |
|---|---|---|
| 1 | Migrasi schema v1.3.0 (§4) | `includes/class-gti-activator.php` |
| 2 | Status machine | `inc/modules/status-machine.php` *(baru)* |
| 3 | Attachment service | `inc/modules/attachments.php` *(baru)* |
| 4 | Mailer v2 + template file | `inc/modules/mailer.php`, `inc/modules/email-templates/*` *(baru)* |
| 5 | Assignment & scoping | `inc/modules/assignment.php` *(baru)* |
| 6 | Hapus `ajax-email-notifications.php`, ganti `gti_status_changed` | `inc/ajax/`, `includes/class-gti-ajax.php` |
| 7 | Ekstrak CSS/JS bersama | `assets/css/dashboard-*.css`, `assets/js/dashboard-ui.js` |
| 8 | Capability baru + `gti_require_cap()` | `includes/class-gti-roles.php`, `inc/security/capabilities.php` |

**Selesai bila:** ubah status lewat dropdown lama tetap mengirim email (kini via `gti_status_changed`), baris masuk `gti_status_history` dan `gti_email_logs` lengkap dengan `body_html`.

### Fase 2 — Shortcode & intake (1,5 hari)

| # | Tugas | File |
|---|---|---|
| 1 | Field baru form inquiry (bersama + rental + spare part) | 3 file `inc/shortcodes/equipment-filter-*.php` |
| 2 | Kirim field baru dari client | `assets/js/equipment-detail.js` |
| 3 | Handler menyimpan field baru + rate limit + honeypot + email klien | `inc/ajax/ajax-customer-quotation.php` |
| 4 | Gate data dummy di belakang option | 3 shortcode |
| 5 | Badge harga kedaluwarsa untuk rental | `equipment-filter-rental-equipment.php` |

**Selesai bila:** submit dari halaman detail rental menghasilkan baris `gti_quotations` dengan `equipment_type`, `quantity`, `needed_date`, `rental_start_date`, `payment_terms` terisi, dan klien menerima email konfirmasi.

### Fase 3 — Inbox (3 hari)

| # | Tugas |
|---|---|
| 1 | Request Equipment: kolom PIC, drawer `usage_purpose`/`message`/Documents, modal Upload Proposal, `gti_send_proposal`, timeline dari history, hapus `location.reload()` |
| 2 | Request Quotation: scoping, modal Create Quotation, `gti_create_quotation`, modal Assign PIC, modal Compose Email, drawer Payment Terms/Documents/Email History, label "Waiting Quotation Approval" |
| 3 | Sell Equipment: WA deep-link + normalisasi nomor, modal Compose Email, modal Request Invoice + `gti_request_invoice`, resolve `images` ID → URL |
| 4 | Modal Compose Email dipakai ulang di ketiganya |

**Selesai bila:** §12 skenario 1–5 lulus.

### Fase 4 — Management & System (2 hari)

| # | Tugas |
|---|---|
| 1 | Customer Detail: hapus B-03/B-04/B-05, aktifkan Edit/Delete/Status/Email |
| 2 | Customers: Add Customer, helper dipindah, filter tanggal |
| 3 | News: counter views, `wp_editor()`, slug, hapus permanen |
| 4 | Media: edit metadata, progress nyata, bulk delete, cap guard |
| 5 | Users: tab, pagination, filter role, reassign saat hapus, kolom Assigned Orders |
| 6 | Activity Log: cakupan lengkap, drawer detail, filter user, export CSV, cron retensi |
| 7 | B-06, B-07, B-08 di seluruh template |

### Fase 5 — Pengerasan (1 hari)

Capability 3 lapis · sembunyikan menu sidebar sesuai role · indeks DB · uji beban 5.000 baris · uji email dengan SMTP nyata · uji lampiran 10 MB · uji cross-browser.

**Total estimasi: ~10 hari kerja.**

---

## 12. Acceptance Test Scenarios

### Skenario 1 — Request Equipment end-to-end
1. Submit FluentForm ID 3 → baris muncul di `/dashboard/request-equipment` status `New`; nama pengirim muncul di `/dashboard/customers`.
2. Klik baris → drawer terbuka dengan Address, Usage Purpose, Message terisi.
3. Mark Processing → toast hijau, badge berubah tanpa reload, klien menerima email "Pesanan Anda Sedang Diproses".
4. Send Proposal tanpa file → tombol kirim disabled.
5. Upload PDF 2 MB + catatan → email masuk ke inbox klien **dengan lampiran**; status `Proposal Sent`; dokumen tampil di section Documents; timeline bertambah 1 titik; 1 baris baru di `gti_email_logs` dan `gti_activity_log`.
6. Buka halaman 2 → tidak 404.

### Skenario 2 — Request Quotation end-to-end
1. Submit form inquiry di halaman detail rental dengan seluruh field terisi.
2. Baris masuk `/dashboard/request-quotation` status `New`; drawer menampilkan Payment Terms, Needed Date, Rental Period, Delivery Location, Quantity.
3. Klien menerima email konfirmasi berisi nomor quotation.
4. Assign ke user sales `budi` → `sales_pic` = "Budi"; `budi` menerima email notifikasi internal.
5. Login sebagai `budi` → hanya baris ini yang tampil; stat card menunjukkan `1`.
6. Create Quotation: upload PDF + total + valid until → status **Waiting Quotation Approval**; klien menerima email dengan lampiran.
7. Reply to Customer: kirim pesan + 1 lampiran → email masuk, `Reply-To` = email `budi`, tercatat di Email History.
8. Mark Approved → email "Quotation Disetujui".
9. Login sebagai admin lain → baris tidak tampil di daftar `budi`, tetap tampil untuk admin.

### Skenario 3 — Sell Equipment end-to-end
1. Submit FluentForm ID 4 → baris muncul status `New`, gambar tampil di drawer (termasuk yang tersimpan sebagai attachment ID).
2. Contact Customer → membuka `wa.me/628...` di tab baru dengan pesan terisi.
3. Nomor telepon kosong → tombol disabled + tooltip.
4. Mark Approved → email ke penjual.
5. Request Invoice: pilih dokumen + deadline 7 hari → status `Invoice Requested`, email berisi daftar dokumen dan deadline.
6. Mark Completed → `invoice_received_at` terisi.

### Skenario 4 — Keamanan
1. Login sebagai `gti_inventory` → buka `/dashboard/request-quotation` → redirect ke `/dashboard`.
2. `curl` ke `gti_send_proposal` tanpa nonce → `{"success":false}`, tidak ada email terkirim, tidak ada file tersimpan.
3. Upload `.php` yang di-rename `.pdf` → ditolak oleh cek MIME server.
4. Upload 15 MB → ditolak client dan server.
5. Submit form publik 4× dalam 1 menit → percobaan ke-4 ditolak rate limit.
6. Isi honeypot → response sukses, tapi tidak ada baris baru di DB.

### Skenario 5 — Regresi katalog
1. Tambah rental equipment dengan semua field 5 step → seluruh kolom `gti_equipment` yang bersesuaian non-null (khususnya `operating_weight`, `bucket_capacity`, `stock_number`, `location`, `condition_status`).
2. `grep -o 'name="[a-z_]*"' templates/page-add-rental-equipment.php | sort | uniq -d` → output kosong.
3. Set `price_valid_until` = kemarin → katalog publik menampilkan badge "Price no longer valid".
4. Set status `draft` → unit hilang dari katalog publik, tetap tampil di dashboard.
5. Database kosong → katalog publik menampilkan empty-state, **bukan** data dummy.

### Skenario 6 — Management
1. News: buat artikel dari dashboard → muncul di `wp-admin/edit.php` dengan kategori, tag, featured image, author benar.
2. Edit artikel yang sama di wp-admin → perubahan tampil di dashboard.
3. Buka artikel sebagai pengunjung → counter views bertambah 1; muat ulang dalam 12 jam → tidak bertambah.
4. Media: upload dari dashboard → muncul di `wp-admin/upload.php`; edit Alt Text di dashboard → tersimpan dan terlihat di wp-admin.
5. Users: hapus user yang memiliki artikel → wajib memilih penerima alih konten; artikel tidak hilang.
6. Activity Log: seluruh langkah 1–5 di atas menghasilkan baris log dengan user, action, entity, dan IP yang benar.

---

## Lampiran A — Checklist Review Setiap PR

- [ ] Tidak ada `name=` duplikat dalam satu form
- [ ] Pagination memakai `page_num`, bukan `paged`
- [ ] Semua AJAX baru: nonce → capability → validasi → aksi → activity log
- [ ] Semua output di-escape; `json_encode` ke atribut dibungkus `esc_attr()`
- [ ] Perubahan status melewati `gti_status_can_transition()`
- [ ] Email dikirim **setelah** DB update sukses, tidak sebelumnya
- [ ] Tidak ada `alert()` / `confirm()` native — pakai modal + toast
- [ ] Tidak ada `location.reload()` untuk perubahan yang bisa di-patch di DOM
- [ ] Kolom baru punya entri migrasi di `class-gti-activator.php`
- [ ] Tidak ada penulisan file debug di path produksi
