# Audit Kesiapan Sistem — GTI Child Theme

**Status dokumen:** rencana / analisis. Tidak ada kode yang diubah.
**Tanggal:** 22 September 2026 · **Commit:** `c0fcf5d` (branch `main`, working tree clean)
**Metode:** pembacaan kode end-to-end — routing → intake → penyimpanan → tampilan → aksi.
`php -l` bersih di seluruh 27.323 baris PHP; 5 test suite di `tests/` semuanya lulus.

**Legenda keyakinan:**
- **[PASTI]** — terbukti dari kode, tidak butuh verifikasi runtime.
- **[CEK]** — tergantung konfigurasi situs/DB; perlu dibuktikan di staging. Cara buktinya ditulis.

---

## 0. Ringkasan Eksekutif

| Modul | Siap? | Catatan |
|---|---|---|
| Katalog publik (used / rental / spare parts) | ⚠️ Sebagian | Tergantung cara shortcode dipasang; unit bisa hilang sendiri karena tanggal harga |
| Form inquiry publik → Request Quotation | ✅ Hampir | Ada jalur gagal pada nomor quotation & cache nonce |
| FluentForms → Request Equipment / Sell Equipment | ⚠️ Sebagian | Foto unit dari penjual tidak pernah tersimpan |
| Dashboard: Request Equipment | ✅ Siap | |
| Dashboard: Request Quotation | ✅ Siap | Pencarian tidak mencakup nama unit |
| Dashboard: Sell Equipment | ⚠️ Sebagian | Tidak bisa assign PIC; galeri foto selalu kosong |
| Dashboard: Customers | ❌ Belum | Tidak bisa tambah/edit/hapus customer sama sekali |
| Dashboard: Users | ❌ Belum | Hanya WP administrator yang bisa kelola user |
| Dashboard: News & Media Library | ❌ Belum | Tidak muncul untuk role GTI manapun |
| Login | ✅ Siap | |
| Register / Lupa Password | ❌ Belum | Halaman tidak punya URL, handler tidak di-load |
| Email (status + manual) | ⚠️ Sebagian | Riwayat email bisa tercampur antar modul |
| Contact Messages / Website Settings | ❌ Belum | "Coming Soon" JANGAN BUAT FITUR INI |

**Blocker sebelum go-live: 6 item** (§1). **Major: 11 item** (§2). **Minor: 13 item** (§3).

---

## 1. BLOCKER — Harus selesai sebelum dipakai orang

### B-1. User lupa password → tidak ada jalan keluar sama sekali

**Skenario pengguna**
> Sales bernama Rina lupa password. Di halaman `/dashboard/login` tidak ada link "Lupa Password". Dia menebak URL `/dashboard/forgot-password` → **halaman 404 WordPress**. Dia telepon admin; admin juga tidak punya menu reset. Satu-satunya jalan: masuk ke `/wp-admin` sebagai administrator atau ubah langsung di database.

**Error persisnya**
- `/dashboard/forgot-password` → `404 Not Found` (halaman "Oops! That page can't be found" dari tema induk).
- Kalau URL-nya dipaksa jalan, tombol "Kirim Link Reset" tetap gagal: response `admin-ajax.php` = `0`, alert menampilkan pesan kosong.

**Akar masalah [PASTI]**
1. `inc/setup/rewrite.php` — tidak ada `add_rewrite_rule` untuk `forgot-password`, dan `$map` (baris ~45–65) tidak punya entri-nya. Template `templates/page-forgot-password.php` ada tapi tidak pernah dipanggil.
2. `inc/bootstrap.php` tidak pernah `require_once` `inc/auth/reset-password.php`, jadi `add_action('wp_ajax_nopriv_gti_forgot_password', …)` di baris 62 file itu **tidak pernah dijalankan**. Action tidak terdaftar → admin-ajax balas `0`.
3. `templates/page-login.php` tidak punya link ke halaman itu.

**Rencana perbaikan**
- Tambah 2 rewrite rule + 2 entri `$map` (`register`, `forgot-password`) dan masukkan keduanya ke `$public_pages`.
- Tambah `require_once` untuk `inc/auth/reset-password.php` di blok AUTH baru di `inc/bootstrap.php`.
- Tambah link "Lupa Password?" dan "Daftar" di `templates/page-login.php`.
- **Hati-hati:** lihat B-2 sebelum menambah `inc/auth/login.php`.

---

### B-2. Ranjau fatal error: `gti_ajax_login()` didefinisikan dua kali

**Skenario pengguna**
> Developer berikutnya melihat folder `inc/auth/` tidak ter-load, lalu menambahkannya ke `bootstrap.php` (persis yang dibutuhkan B-1). Begitu file disimpan, **seluruh website mati** — halaman depan, dashboard, wp-admin, semuanya *White Screen of Death*.

**Error persisnya**
```
PHP Fatal error: Cannot redeclare gti_ajax_login() (previously declared in
.../inc/ajax/ajax-helpers.php:37) in .../inc/auth/login.php on line 43
```

**Akar masalah [PASTI]**
Fungsi `gti_ajax_login()` punya dua implementasi berbeda:
- `inc/ajax/ajax-helpers.php:37` — **yang aktif**, dipakai form login (`action=gti_ajax_login`, nonce `gti_nonce`). Login sederhana lewat `wp_signon`.
- `inc/auth/login.php:43` — versi lebih lengkap (dukungan login pakai nomor HP, rate limit 5×/15 menit, logging aktivitas), terdaftar di action `gti_login`, nonce `gti_login_action`. **Tidak pernah di-load.**

Artinya fitur yang sudah ditulis dan tidak dipakai: login pakai nomor HP, rate limit login, pencatatan aktivitas login.

**Rencana perbaikan**
Pilih satu, jangan dua:
1. Pindahkan isi `inc/auth/login.php` menjadi satu-satunya sumber; hapus blok baris 35–52 di `ajax-helpers.php`; ubah `templates/page-login.php` agar kirim `action=gti_login` + `wp_nonce_field('gti_login_action','gti_login_nonce')` + field `username`/`password` (bukan `log`/`pwd`).
2. Atau hapus `inc/auth/login.php` dan pindahkan rate limit + log aktivitas ke `ajax-helpers.php`.

Opsi 1 lebih baik — rate limit login adalah kontrol keamanan yang saat ini **tidak aktif sama sekali**.

---

### B-3. Role GTI tidak punya capability WordPress inti → 3 menu mati total

**Skenario pengguna**
> Owner membuat akun untuk staf marketing dengan role **GTI Admin**. Staf login, sidebar hanya menampilkan Dashboard, Equipment, Spare Parts, Request & Inquiry, Customers. **"News & Articles" dan "Media Library" tidak ada.** Owner protes: "Kan sudah saya kasih role Admin?"
>
> Lalu **GTI Super Admin** buka menu "Users" → yang muncul cuma halaman "My Profile". Tab "System Users" tidak ada. Tidak bisa menambah, mengedit, atau menghapus user siapa pun.

**Error persisnya**
- Menu "News & Articles" + "Media Library" **tidak dirender** di sidebar (bukan error, hilang diam-diam).
- Buka `/dashboard/media-library` langsung → redirect balik ke `/dashboard/`.
- Kalau tombol user sempat muncul: `{"success":false,"data":{"message":"You do not have permission to manage users."}}`

**Akar masalah [PASTI]**
`includes/class-gti-roles.php:73` — `create_roles()` hanya memberi `read` + capability `gti_*`. Tidak ada satu pun role GTI yang punya `upload_files`, `edit_posts`, `list_users`, `edit_users`, `create_users`, `delete_users`.

Tapi seluruh dashboard menggate pada capability inti itu:

| Pemakai | File | Capability diminta | Dimiliki role GTI? |
|---|---|---|---|
| Menu News | `inc/helpers/dashboard-menu.php:43` | `edit_posts` | ❌ |
| Menu Media | `inc/helpers/dashboard-menu.php:44` | `upload_files` | ❌ |
| Halaman Media | `templates/page-media-library.php:47` | `upload_files` | ❌ |
| Halaman News | `templates/page-news-articles.php:54` | `edit_posts` | ❌ |
| Tab System Users | `templates/page-users.php:44` | `list_users` | ❌ |
| Simpan user | `inc/ajax/admin/users.php:28` | `edit_users` / `create_users` | ❌ |
| Hapus user | `inc/ajax/admin/users.php:95` | `delete_users` | ❌ |
| Upload media AJAX | `inc/ajax/ajax-dashboard.php:26,96,117,309` | `upload_files` | ❌ |
| Publish/hapus artikel | `inc/ajax/ajax-dashboard.php:158,196` | `edit_posts` | ❌ |

Efek samping: capability `gti_manage_users` yang didefinisikan untuk `gti_super_admin` **tidak berarti apa-apa** — tidak ada satu pun kode yang memeriksanya.

**Rencana perbaikan**
Di `capability_matrix()`, tambahkan capability inti per role:
- `gti_super_admin`: `list_users`, `create_users`, `edit_users`, `delete_users`, `promote_users`, `upload_files`, `edit_posts`, `publish_posts`, `edit_others_posts`, `delete_posts`
- `gti_admin`: `upload_files`, `edit_posts`, `publish_posts`, `edit_others_posts`
- `gti_sales`: `upload_files`
- `gti_inventory`: `upload_files`

Lalu naikkan `GTI_DB_VERSION` (`inc/constants.php:17`) supaya `create_roles()` jalan ulang di instalasi yang sudah ada — saat ini hanya dipanggil pada migrasi `< 1.3.0` (`class-gti-activator.php:379`), yang sudah lewat.

> Catatan keamanan: `create_users`/`delete_users` di WordPress bersifat global. Kalau tidak mau GTI Super Admin bisa menyentuh user WP administrator, pasang filter `map_meta_cap` atau `editable_roles` untuk membatasi ke role `gti_*` saja.

---

### B-4. Customer tidak bisa ditambah, diedit, atau dihapus dari mana pun

**Skenario pengguna**
> Admin buka `/dashboard/customers`. Data nomor telepon salah ketik. Dia klik ikon ⋮ → pilihannya hanya **"View Details"** dan **"Send Email"**. Tidak ada Edit, tidak ada Delete, tidak ada tombol "+ Add Customer". Drawer detail di sebelah kanan pun hanya baca — footer-nya cuma tombol "Send Email".
>
> Kesimpulan admin: data customer sama sekali tidak bisa dikoreksi lewat dashboard.

**Akar masalah [PASTI]**
Semua bagiannya ada, tapi tidak terhubung:

| Komponen | Lokasi | Status |
|---|---|---|
| Endpoint simpan | `GTI_Ajax::save_customer` (`inc/ajax/admin/customers.php:22`) | ✅ Terdaftar |
| Endpoint hapus | `gti_ajax_delete_customer` (`inc/ajax/admin/management.php:14`) | ✅ Terdaftar |
| Modal form | `template-parts/dashboard/modal-customer.php` | ✅ Ada, 12 field |
| JS pemanggil | `assets/js/pages/customer-detail.js:62,84` | ✅ Ada |
| **Halaman yang memuat semuanya** | `templates/page-customer-detail.php:569` | ❌ **Tidak bisa diakses** |

`templates/page-customers.php:243` hanya memuat modal `delete` dan `email` — **bukan** `customer`. Dan `assets/js/pages/customers.js` tidak punya satu pun handler edit/hapus; isinya cuma pengisi drawer.

Ini konsekuensi dari penghapusan tombol & link "Full Profile" pada commit sebelumnya. Template `page-customer-detail.php` sengaja dipertahankan, tapi dampaknya — hilangnya satu-satunya jalan edit/hapus customer — tampaknya tidak disengaja.

**Rencana perbaikan** (pilih satu)
- **A (paling ringan):** kembalikan satu entri "Full Profile" di action menu `page-customers.php` saja (tanpa tombol drawer).
- **B (sesuai maksud semula):** pindahkan modal `customer` + handler edit/hapus dari `customer-detail.js` ke `customers.js`, tambahkan `'customer'` ke daftar modal di `page-customers.php:243`, dan tambah item "Edit" + "Delete" di action menu.

**Kalau memilih B, wajib sekalian perbaiki B-5.**

---

### B-5. Setengah field form customer akan hilang diam-diam saat disimpan

**Skenario pengguna**
> (Setelah B-4 diperbaiki.) Admin mengisi form customer lengkap: kota, provinsi, negara, nama kontak, telepon kontak, email kontak, WhatsApp kontak. Klik **Simpan** → toast **"Customer saved"** muncul, hijau, sukses. Tutup modal, buka lagi → **7 field tadi kosong semua.** Tidak ada error apa pun.

**Akar masalah [PASTI]**
`inc/ajax/admin/customers.php:30–42` hanya mengenal 10 kolom:
`customer_id, name, company, email, phone, address, industry, location, status, registered_date`

Sedangkan `modal-customer.php` mengirim:
`name, company, email, phone, address, city, province, country, contact_person, contact_phone, contact_email, contact_whatsapp, id, customer_id`

Baris 42 (`array_intersect_key($data, $_POST)`) memfilter dari arah sebaliknya, jadi field yang tidak ada di array `$data` **dibuang tanpa jejak**. Padahal kolom `city`, `province`, `country`, `contact_*` **memang ada** di tabel — dibuat oleh `inc/db/customers-sync.php:42–101` dan **ditampilkan** di drawer (`page-customers.php:229` menggabungkan `city, province, country`) serta di `page-customer-detail.php:290`.

Sebaliknya, `industry`, `status`, dan `registered_date` ada di daftar simpan tapi **tidak ada di form** — tidak akan pernah bisa diisi.

**Bug tambahan di baris yang sama:** `gti_refresh_customer_stats($data['customer_id'])` dipanggil tanpa penjagaan. Kalau form tidak mengirim `customer_id` (misalnya saat membuat customer baru), muncul `PHP Warning: Undefined array key "customer_id"`, dan pada instalasi dengan `display_errors` aktif warning itu **merusak JSON response** → toast berubah jadi "Terjadi kesalahan".

**Rencana perbaikan**
Samakan daftar kolom di `save_customer()` dengan field modal; hapus `industry`/`status`/`registered_date` atau tambahkan input-nya; pakai `$data['customer_id'] ?? ''` dan lewati refresh kalau kosong.

---

### B-6. Customer yang dihapus hidup lagi sendiri dalam 5 menit

**Skenario pengguna**
> Admin menghapus customer duplikat. Toast: *"Customer dihapus. Request dan quotation terkait tetap tersimpan."* Baris hilang dari tabel. Lima menit kemudian admin refresh halaman → **customer itu muncul kembali**, dengan `customer_id` baru (mis. `CUS-0042` → `CUS-0051`). Statistiknya ikut kembali. Dihapus lagi, muncul lagi.

**Akar masalah [PASTI]**
`templates/page-customers.php:16` memanggil `gti_sync_customers_from_sources()` setiap kali halaman dibuka (di-throttle transient 5 menit). Fungsi itu (`inc/db/customers-sync.php:362`) membaca ulang seluruh `gti_requests` + `gti_quotations` dan memanggil `gti_sync_customer()` untuk tiap email/telepon yang ditemukan. Karena baris request/quotation-nya **sengaja tidak ikut dihapus** (`management.php:37`), customer-nya langsung dibuat ulang.

**Rencana perbaikan**
Tambah kolom penanda, mis. `deleted_at DATETIME NULL` atau `suppressed TINYINT(1)`, di `gti_ensure_customers_table()`. `gti_ajax_delete_customer` melakukan soft-delete; `gti_sync_customer()` melewati email/telepon yang sudah ditandai; semua query daftar menambahkan `AND deleted_at IS NULL`. Atau: ubah tombolnya menjadi "Gabung / Merge" karena record ini memang turunan, bukan data master.

---

## 2. MAJOR — Bikin fitur terasa rusak, tapi tidak memblokir launch

### M-1. Foto unit dari penjual tidak pernah masuk sistem

**Skenario**
> Penjual mengunggah 6 foto excavator di form Sell Equipment. Tim sales membuka `/dashboard/sell-equipment`, klik barisnya, scroll ke bagian **"Equipment Images"** → **"No images uploaded"**. Sales harus menelepon penjual dan minta foto lewat WhatsApp.

**Akar masalah [PASTI]**
`inc/modules/fluentform-intake.php` — `$field_map` untuk sell (baris ~178–192) memetakan 14 field, **tidak satu pun untuk `images`**. Kolom `gti_sell_requests.images` (JSON) ada di schema dan dibaca oleh `gti_resolve_image_urls()` (`render-helpers.php`), lalu dirender di `drawer-sell.php`, dan penanganannya sudah cukup pintar (menerima URL maupun attachment ID). Yang kurang hanya pengisiannya.

Field lain yang sama nasibnya di jalur sell: `customer_whatsapp` (kolom ada, drawer punya baris "WhatsApp", intake tidak mengisi — jatuh ke `customer_phone`).

**Perbaikan:** tambahkan `'images' => ['images','photos','foto','foto_alat','equipment_images','upload_foto']` dan `'customer_whatsapp' => ['whatsapp','wa','no_wa']` ke `$field_map`, lalu normalkan nilai FluentForms (biasanya array URL) dengan `wp_json_encode()`.

---

### M-2. Penawaran Sell Equipment tidak bisa diberi PIC

**Skenario**
> Manager ingin menugaskan penawaran unit ke sales tertentu. Di Request Equipment dan Request Quotation ada tombol **"PIC"**. Di Sell Equipment tombol itu **tidak ada** — baik di footer drawer maupun di menu ⋮ baris tabel. Tabelnya juga tidak punya kolom "Sales PIC". Padahal kolom `assigned_to`, `assigned_at`, `sales_pic` sudah ada di tabel dan backend-nya **sudah mendukung** sell.

**Akar masalah [PASTI]**
- Backend siap: `inc/ajax/admin/inbox.php:361` — `gti_ajax_assign_entity()` menerima `'sell'` di daftar entity yang sah.
- Modal siap: `page-sell-equipment.php` bahkan sudah memuat modal `assign`.
- UI-nya tidak ada: `template-parts/dashboard/drawer-sell.php` tidak punya `data-action="assign"`; `page-sell-equipment.php:144` tidak punya item `js-assign`.

Efek lanjutan: karena `assigned_to` selalu 0 untuk sell, `gti_guard_row_scope()` menolak setiap user **GTI Sales** (yang tidak punya `gti_view_all_requests`) saat mereka mencoba mengubah status penawaran → `403 "Pesanan ini bukan tanggung jawab Anda."` **Sales tidak akan pernah bisa memproses penawaran sell.**

**Perbaikan:** tambah tombol PIC di footer `drawer-sell.php`, item "Assign PIC" di action menu, kolom `col-pic` di tabel, dan `'sales_pic'`/`'assigned_to'` sudah ada di `gti_sell_row_payload()`.

---

### M-3. Riwayat email tercampur antar modul

**Skenario**
> Sales membalas email pelanggan di **Request Equipment REQ-0007** (id baris = 7). Lalu dia membuka **Request Quotation** dan memilih quotation yang kebetulan juga id = 7. Di bagian **"Email History"** muncul email tadi — subjek dan alamat pelanggan dari modul lain. Sales mengira dia sudah membalas quotation itu, padahal belum.

**Akar masalah [PASTI]**
`inc/modules/mailer.php:375` —
```sql
WHERE related_id = %d AND (type = %s OR type = 'manual')
```
Untuk email manual, `dispatch()` menulis `type = 'manual'` (baris ~262) dan **tidak pernah menyimpan `entity_type`** ke tabel log, walaupun nilainya dikirim ke `dispatch()` di `$args['entity_type']`. Jadi semua email manual dengan `related_id` yang sama tampil di semua modul.

Tabel `gti_email_logs` juga dipakai untuk entity `customer` (`send_custom_email` mendukung `'customer'`), sehingga email ke customer id 7 bisa muncul di drawer request id 7.

**Perbaikan:** tambah kolom `entity_type VARCHAR(20) NULL` di `create_v130_tables()`, isi dari `$args['entity_type'] ?? $args['type']` di `dispatch()`, dan ubah `history()` menjadi `WHERE related_id = %d AND (type = %s OR (type = 'manual' AND entity_type = %s))`. Baris lama tanpa `entity_type` sebaiknya ditampilkan apa adanya atau dibackfill.

---

### M-4. Unit bisa hilang sendiri dari website karena tanggal harga

**Skenario**
> Admin menambahkan unit dan mengisi **"Harga berlaku sampai: 30 Sep 2026"** — mengira itu sekadar catatan. Tanggal 1 Oktober, marketing menelepon: "Unit PC200 hilang dari website." Admin cek dashboard: unitnya **masih ada**, status **Available**, tidak ada tanda apa pun. Link yang sudah dibagikan ke WhatsApp calon pembeli sekarang menampilkan halaman detail kosong / "Equipment not found".

**Akar masalah [PASTI]**
`inc/helpers/format-helpers.php:179` — `gti_price_expired_sql()` dipakai sebagai filter keras di **tiga** tempat:
- daftar katalog: `equipment-filter-used-equipment.php:381` (dan padanannya di rental)
- halaman detail: `equipment-filter-used-equipment.php:1551`
- AJAX filter: `inc/ajax/ajax-equipment-filter.php:84`

Sementara dashboard (`page-used-equipment.php`, `page-rental-equipment.php`) tidak memakai filter itu sama sekali — jadi dashboard dan website menampilkan inventaris yang berbeda, tanpa peringatan.

**Perbaikan** (pilih sesuai maksud bisnis)
- Kalau tujuannya "sembunyikan harganya, bukan unitnya": jangan filter di SQL; tampilkan unit dengan label **"Hubungi kami untuk harga"**.
- Kalau memang harus hilang: tambahkan badge **"Harga kedaluwarsa — tidak tampil di website"** di tabel dashboard, plus kartu statistik "Expired" supaya admin sadar.
- Minimal: ubah label field menjadi *"Harga berlaku sampai (setelah tanggal ini unit disembunyikan dari website)"* di `page-add-used-equipment.php` dan `page-add-rental-equipment.php`.

---

### M-5. Filter merek & lokasi di website menawarkan pilihan yang pasti nihil

**Skenario**
> Pengunjung membuka halaman Used Equipment saat inventaris masih sedikit. Sidebar filter menampilkan 12 merek: CAT, Komatsu, Hitachi, Volvo, Doosan, Hino, Kato, SANY, SDLG, XCMG, Kobelco, Hyundai — dan 6 lokasi Kalimantan. Pengunjung klik **Volvo** → **"No equipment found"**. Klik **Hitachi** → kosong juga. Kesan: website-nya rusak atau perusahaannya tidak punya stok.

**Akar masalah [PASTI]**
`inc/shortcodes/equipment-filter-used-equipment.php:448–466` — `gti_get_used_equipment_brands()` dan `gti_get_used_equipment_locations()` mengembalikan daftar hardcoded ketika data DB kosong. Pola yang sama ada di file rental dan spare parts.

**Perbaikan:** kembalikan array kosong dan sembunyikan blok filter yang tidak punya opsi, atau turunkan opsi hanya dari `SELECT DISTINCT` data nyata (`gti_distinct_values()` di `inc/db/repository.php` sudah melakukan ini untuk dashboard — pakai ulang).

---

### M-6. Dua customer bersamaan mengisi form → salah satu dapat "Gagal menyimpan data"

**Skenario**
> Dua calon pembeli menekan **KIRIM PERMINTAAN** dalam detik yang sama. Satu dapat *"Permintaan quotation berhasil dikirim!"*. Yang lain dapat merah: **"Gagal menyimpan data. Silakan coba lagi."** Datanya hilang; sales tidak pernah tahu ada lead kedua.

**Akar masalah [PASTI]**
`inc/ajax/ajax-customer-quotation.php:118–125` — nomor quotation dibuat dengan pola *baca lalu tulis*:
```php
SELECT quotation_id … WHERE quotation_id LIKE 'Q-202609-%' ORDER BY id DESC LIMIT 1
$sequence = (int) substr(...) + 1;
```
Tidak ada penguncian. Dua request paralel membaca sequence yang sama → INSERT kedua ditolak oleh `UNIQUE KEY quotation_id`.

**Kasus kedua, lebih sering terjadi [PASTI]:** `gti_ajax_create_quotation()` (`inc/ajax/admin/inbox.php:180`) **menimpa** `quotation_id` dengan nomor yang diketik admin. Kalau admin mengetik format lain (mis. `QT/2026/IX/001`), baris itu tidak lagi cocok dengan `LIKE 'Q-202609-%'`, sehingga generator publik kembali membaca baris yang lebih lama dan **mengulang sequence yang sudah terpakai** → submission pelanggan berikutnya gagal.

Masalah identik ada di `inc/modules/fluentform-intake.php:51–61` untuk `REQ-XXXX`. Di sana `$seq` diambil dari baris dengan `id` tertinggi, bukan sequence tertinggi, dan format dummy (`REQ-2026-001`) vs format baru (`REQ-0001`) bercampur.

**Perbaikan:** jangan simpan nomor yang diketik admin ke kolom kunci — tambahkan kolom terpisah `quotation_number VARCHAR(50)` untuk nomor dokumen resmi, dan biarkan `quotation_id` jadi kunci internal yang dibuat sistem. Untuk race condition, bungkus dengan retry pada kegagalan `UNIQUE`, atau pakai `MAX(CAST(SUBSTRING_INDEX(quotation_id,'-',-1) AS UNSIGNED))`.

---

### M-7. Aset katalog mungkin tidak ter-load sama sekali [CEK]

**Skenario**
> Halaman katalog terbuka tapi tampil **tanpa gaya** — daftar teks polos, sidebar filter tidak bisa diklik, tombol "Muat lebih banyak" diam, form inquiry tidak mengirim apa pun saat ditekan. Di konsol browser: `Uncaught ReferenceError` atau tidak ada request ke `admin-ajax.php`.

**Akar masalah**
`inc/setup/enqueue.php:177–179` hanya memuat CSS/JS katalog bila shortcode ada di **`$post->post_content`**:
```php
has_shortcode($post->post_content, 'gti_used_equipment_filter') || …
```
Tema induknya adalah **Themify Ultra**. Halaman yang dibangun dengan Themify Builder menyimpan isinya di **post meta** (`_themify_builder_settings_json`), bukan di `post_content`. Kalau shortcode ditaruh lewat modul builder, widget, atau template part, kondisi ini bernilai `false` dan **tidak satu pun file CSS/JS katalog dimuat.**

**Cara verifikasi:** buka halaman katalog di staging → View Source → cari `equipment-filter.css`. Kalau tidak ada, masalahnya aktif.

**Perbaikan:** ganti deteksi dengan pemeriksaan yang tahan builder, mis. flag yang di-set oleh shortcode itu sendiri lalu enqueue di `wp_footer`, atau cek `has_shortcode()` pada `post_content` **dan** meta builder, atau paling sederhana — panggil `wp_enqueue_*` dari dalam handler shortcode.

---

### M-8. Form publik gagal setelah 24 jam kalau ada page cache [CEK]

**Skenario**
> Pengunjung membuka halaman unit dari hasil Google, mengisi form, klik kirim → **"Sesi Anda sudah kedaluwarsa. Muat ulang halaman lalu coba lagi."** Dia refresh, isi ulang, dan mendapat pesan yang sama. Lead hilang.

**Akar masalah**
`inc/shortcodes/inquiry-form.php:53` menanam `wp_create_nonce('gti_customer_quotation')` ke dalam HTML. Nonce WordPress untuk pengunjung anonim berumur **maksimal 24 jam**. Setiap plugin page cache (LiteSpeed, WP Rocket, WP Super Cache) atau CDN akan menyajikan HTML yang sama selama berjam-jam/berhari-hari → nonce-nya basi → `wp_verify_nonce()` gagal (`inc/ajax/ajax-customer-quotation.php:22`).

Nonce filter katalog (`gti_used_equipment_filter`, dst.) punya masalah yang sama → filter berhenti bekerja di halaman yang ter-cache.

**Cara verifikasi:** cek apakah ada plugin caching aktif. Kalau ya, masalahnya pasti muncul.

**Perbaikan:** ambil nonce lewat panggilan AJAX kecil yang dikecualikan dari cache saat form di-submit, atau kecualikan nonce dari cache dengan placeholder ESI, atau untuk form publik anonim ganti proteksi ke honeypot + rate limit (keduanya **sudah ada** di `ajax-customer-quotation.php:29,37`) dan longgarkan pemeriksaan nonce.

---

### M-9. Unit yang sudah terjual tetap bisa di-inquiry & status tidak pernah berubah otomatis

**Skenario**
> Sales menyelesaikan penjualan: quotation diubah ke **Completed**. Unit itu **tetap** tampil di website dengan badge **"READY STOCK"** dan form inquiry aktif. Pelanggan baru mengirim permintaan untuk unit yang sudah terjual. Sales harus menolak, berkali-kali.

**Akar masalah [PASTI]**
`inc/modules/stock.php:38` hanya menangani `equipment_type === 'spare_part'`. Untuk quotation bertipe `used`/`rental` tidak ada efek samping apa pun — `gti_equipment.status` harus diubah manual oleh admin.

Terkait: bahkan kalau admin mengubah status menjadi `sold`, katalog publik **tetap menampilkan unitnya** (hanya badge yang berubah jadi "SOLD") dan **form inquiry tetap ditampilkan** — lihat `equipment-filter-used-equipment.php:266–281` dan `:638`.

**Perbaikan:**
- Perluas `gti_stock_consume_on_quotation_completed()` menjadi: untuk `used` → set `gti_equipment.status = 'sold'`; untuk `rental` → set `'rented'` sampai `rental_end_date`.
- Di katalog, ganti form inquiry dengan pesan "Unit ini sudah terjual — lihat unit serupa" bila `status === 'sold'`.

---

### M-10. Stok spare part dipotong sebesar jumlah yang *diminta*, bukan yang dijual

**Skenario**
> Pelanggan meminta 50 filter di form inquiry (sekadar menanyakan harga borongan). Akhirnya hanya 5 yang dibeli. Sales menandai quotation **Completed**. Stok di dashboard turun **50** → dari 60 menjadi 10, dan statusnya berubah jadi **Low Stock**. Katalog website ikut salah.

**Akar masalah [PASTI]**
`inc/modules/stock.php:44` memakai `$row['quantity']` — angka yang diketik pelanggan di form publik — sebagai jumlah yang keluar gudang. Tidak ada langkah konfirmasi.

Kasus terkait: kalau `equipment_id` bernilai 0 (inquiry dikirim dari halaman daftar, bukan detail), stok **tidak dipotong sama sekali** dan tidak ada notifikasi.

**Perbaikan:** tambahkan input "Jumlah terjual" di modal Create Quotation / dialog konfirmasi Completed, simpan ke kolom tersendiri (mis. `fulfilled_quantity`), dan gunakan itu untuk pemotongan stok. Tampilkan toast bila pemotongan dilewati.

---

### M-11. Dokumen yang salah upload tidak bisa dihapus

**Skenario**
> Sales tidak sengaja melampirkan proposal milik pelanggan lain saat mengubah status ke **Proposal Sent**. Dokumen itu sudah terkirim via email dan tampil permanen di bagian "Documents" pada drawer. Tidak ada tombol hapus di mana pun.

**Akar masalah [PASTI]**
Endpoint `gti_delete_attachment` terdaftar (`inc/ajax/admin/inbox.php:441`) tapi **tidak ada satu pun file JS yang memanggilnya**. `renderDocuments()` di `assets/js/inbox-ui.js:88` hanya merender ikon, nama, ukuran, dan link unduh — tanpa tombol hapus. Sama untuk versi server-side `gti_render_documents()` di `render-helpers.php`.

**Perbaikan:** tambahkan tombol hapus (dengan konfirmasi) di `renderDocuments()` dan `gti_render_documents()`, batasi ke `gti_upload_documents` + dokumen yang belum berstatus `emailed_at`.

---

## 3. MINOR — Gesekan, inkonsistensi, dan utang teknis

| # | Skenario pengguna | Penyebab | Keyakinan |
|---|---|---|---|
| m-1 | Cari **"PC200"** di Request Quotation → **0 hasil**, padahal kolom "Requested Items" jelas menampilkan PC200 | `inc/db/repository.php:52` — kolom pencarian quotation hanya `quotation_id, customer_name, customer_company, customer_email`. Nama unit ada di JSON `items`, bukan kolom | [PASTI] |
| m-2 | Klik **"Review"** dari kartu *Needs Attention* di Dashboard, atau tombol di email notifikasi → baris ter-scroll & ter-highlight, tapi **drawer detail tidak terbuka**; harus klik sekali lagi | `dashboard-ui.js:456` `initHighlight()` hanya menambah class + scroll. Yang membuka drawer adalah parameter `?open=` di `inbox-ui.js`, sedangkan link-link itu memakai `?highlight=` (`page-dashboard.php:196`, `ajax-customer-quotation.php:211`) | [PASTI] |
| m-3 | Halaman **Customers** menampilkan "Request & Sell: 0" untuk pelanggan yang jelas punya riwayat | `customers-sync.php:271–275` mencocokkan `customer_phone = %s` **persis**, sementara `gti_find_customer()` (baris 150) mencocokkan longgar (9 digit terakhir, tanda baca diabaikan). Pelanggan tanpa email yang nomornya ditulis beda format tidak akan terhitung | [PASTI] |
| m-4 | Pelanggan yang **hanya** pernah mengirim penawaran Sell Equipment dan baris customernya pernah hilang tidak pernah muncul kembali di halaman Customers | `gti_sync_customers_from_sources()` (`customers-sync.php:362`) hanya membaca `gti_requests` + `gti_quotations`, tidak `gti_sell_requests`. (Submission **baru** tetap masuk lewat hook `gti_submission_received`, jadi ini hanya soal backfill historis) | [PASTI] |
| m-5 | Kolom **"Total Spent"** di Customers selalu **IDR 0** | `customer_stats` menjumlahkan `gti_quotations.total`, yang hanya terisi lewat modal *Create Quotation* (`inbox.php:141`). Quotation dari form publik selalu `total = 0` (`ajax-customer-quotation.php:172`) | [PASTI] |
| m-6 | Menu **Contact Messages** dan **Website Settings** membuka halaman "Coming Soon" | `inc/setup/rewrite.php` memetakan keduanya ke `page-coming-soon`. Tabel `gti_messages` dibuat (`class-gti-activator.php:193`) tapi **tidak ada satu pun kode yang menulis ke sana**. Catatan: kedua item ini sudah tidak ada di sidebar (`dashboard-menu.php`), jadi hanya bisa dicapai lewat URL langsung | [PASTI] |
| m-7 | Kotak pencarian di beranda mengarahkan ke halaman 404 | `inc/setup/enqueue.php:166` — `home_url('/equipment/')` di-hardcode, tanpa opsi/filter. Kalau slug halaman katalog bukan `/equipment/`, semua pencarian berakhir 404 | [CEK] |
| m-8 | Intake FluentForms mati total tanpa pesan apa pun | `inc/constants.php:27,31` — ID form di-hardcode `3` dan `4`. Kalau plugin FluentForms tidak aktif, atau form dibuat ulang (ID berubah), `gti_fluentform_to_request()` langsung `return` di baris 40. Pelanggan tetap melihat "Terima kasih", tapi **tidak ada data yang masuk dashboard** | [CEK] |
| m-9 | Angka statistik palsu bisa muncul (148 unit, 372 part, 12.540 pengunjung) | `inc/ajax/ajax-helpers.php:15–32` mendaftarkan `wp_ajax_gti_get_dashboard_stats` dengan **angka hardcode**, dan `class-gti-ajax.php:62` mendaftarkan action **yang sama** dengan query asli. Yang pertama menang. Saat ini tidak ada JS yang memanggilnya (halaman dashboard merender server-side), jadi belum terlihat — tapi ini jebakan | [PASTI] |
| m-10 | `industry`, `status`, `registered_date` tidak bisa diisi; `source_url` quotation tidak pernah ditampilkan | Field ada di whitelist simpan / payload tapi tidak ada di form / template. `source_url` dikirim ke drawer (`render-helpers.php` `gti_quotation_row_payload`) tapi `drawer-quotation.php` tidak merendernya — padahal berguna untuk tahu halaman mana yang menghasilkan lead | [PASTI] |
| m-11 | Dua definisi skema untuk tabel yang sama | `gti_customers` dibuat di `class-gti-activator.php:154` (12 kolom) **dan** di `customers-sync.php:42` (dengan `total_transactions`, `total_spent`, `last_contact`, `city`, `province`, `country`, `contact_*`). Keduanya jalan; `dbDelta` dari activator bisa mencoba mengecilkan `customer_id` dari `VARCHAR(30)` ke `VARCHAR(20)` pada setiap upgrade | [PASTI] |
| m-12 | `equipment_id` di tabel quotation menunjuk ke **dua tabel berbeda** | Untuk `equipment_type='spare_part'` berisi `gti_spare_parts.id`; selain itu `gti_equipment.id`. `stock.php` sudah menangani dengan benar, tapi setiap query baru yang lupa memeriksa `equipment_type` akan mengambil baris yang salah | [PASTI] |
| m-13 | 8 file PHP tidak pernah di-load | `inc/auth/login.php`, `register.php`, `reset-password.php`, `block-admin.php`, `filters.php`, `inc/ajax/ajax-login.php`, `ajax-register.php`, `inc/user/user-validation.php`. Akibat paling penting: **`block-admin.php` tidak aktif**, jadi tidak ada yang mencegah user dengan role GTI membuka `/wp-admin` langsung | [PASTI] |

---

## 4. Tabel cakupan end-to-end

✅ tersambung · ⚠️ sebagian · ❌ putus

| Alur | Input | Simpan | Tampil dashboard | Aksi | Email | Status |
|---|---|---|---|---|---|---|
| Inquiry katalog → Quotation | ✅ | ✅ | ✅ | ✅ | ✅ | ⚠️ M-6, M-8 |
| FluentForm → Request Equipment | ✅ | ✅ | ✅ | ✅ | ✅ | ⚠️ M-6, m-8 |
| FluentForm → Sell Equipment | ✅ | ⚠️ foto hilang | ✅ | ⚠️ tanpa PIC | ✅ | ⚠️ M-1, M-2 |
| Tambah/edit equipment | ✅ | ✅ | ✅ | ✅ | — | ✅ |
| Tambah/edit spare part | ✅ | ✅ | ✅ | ✅ | — | ✅ |
| Quotation completed → stok turun | ✅ | ✅ | ✅ | ✅ | — | ⚠️ M-10 |
| Quotation completed → unit jadi *sold* | ❌ | ❌ | ❌ | ❌ | — | ❌ M-9 |
| Customer (otomatis dari submission) | ✅ | ✅ | ✅ | — | — | ✅ |
| Customer (kelola manual) | ❌ | ⚠️ 7 field dibuang | ✅ baca saja | ❌ | — | ❌ B-4, B-5 |
| Kelola user | ✅ | ✅ | ⚠️ admin WP saja | ⚠️ | — | ❌ B-3 |
| News & Media Library | ✅ | ✅ | ⚠️ admin WP saja | ⚠️ | — | ❌ B-3 |
| Register akun | ✅ template | ✅ handler | — | — | ✅ | ❌ B-1 |
| Lupa password | ✅ template | ✅ handler | — | — | ✅ | ❌ B-1 |
| Contact Messages | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ m-6 |
| Website Settings | ❌ | ❌ | ❌ | ❌ | — | ❌ m-6 |

---

## 5. Rencana pengerjaan

### Fase 0 — Verifikasi di staging (1 hari, sebelum menulis kode)
Empat temuan **[CEK]** harus dibuktikan dulu karena menentukan besar pekerjaannya:
1. **M-7** — buka halaman katalog, View Source, cari `equipment-filter.css`. Ada? aman. Tidak ada? ini jadi blocker. ✅ Aman
2. **M-8** — cek plugin caching aktif (`wp plugin list --status=active`). dari plugin wordpress dashboard sih gaada, mungkin akunya aja yang gatau, tapi aku lihat ada themify cache diheader wordpress dashboard, mungkin juga ada plugin yang sudah ditanam dari dalam.
3. **m-7** — konfirmasi slug halaman katalog. katalog produk shortcode ada di http://global-tractors.test/used-equipment/, http://global-tractors.test/rental-equipment/, http://global-tractors.test/spare-parts-2/
4. **m-8** — konfirmasi FluentForms aktif dan ID form-nya benar `3` & `4`. ✅ Benar

### Fase 1 — Blocker (perkiraan 2–3 hari)
Urutan ini disengaja: B-2 mendahului B-1 karena B-1 memicu fatal error-nya.

1. **B-2** — satukan `gti_ajax_login()`; hapus duplikatnya. *(± 1 jam)*
2. **B-1** — rewrite rule + `$map` + `require_once` + link di halaman login. *(± 2 jam)*
3. **B-3** — tambah capability inti ke matriks role; naikkan `GTI_DB_VERSION`. *(± 2 jam + tes per role)*
4. **B-4 + B-5** — pasang modal customer ke `page-customers.php`, samakan daftar kolom di `save_customer()`. *(± 4 jam)*
5. **B-6** — soft delete customer + filter di sync. *(± 3 jam, butuh migrasi)*

**Gerbang keluar:** buat satu akun per role (`gti_super_admin`, `gti_admin`, `gti_sales`, `gti_inventory`), login satu per satu, telusuri setiap menu yang terlihat. Tidak boleh ada menu yang mengarah ke halaman kosong atau 403.

### Fase 2 — Major (perkiraan 3–4 hari)
6. **M-2** — tombol PIC untuk Sell Equipment *(memblokir role Sales, kerjakan duluan)*
7. **M-1** — mapping foto + WhatsApp di intake sell
8. **M-3** — kolom `entity_type` di email log
9. **M-6** — pisahkan nomor dokumen dari kunci `quotation_id`
10. **M-4** — perlakuan harga kedaluwarsa (putuskan maksud bisnisnya dulu)
11. **M-9 + M-10** — sinkronisasi status jual & konfirmasi jumlah terjual
12. **M-5** — buang daftar merek/lokasi hardcoded
13. **M-11** — tombol hapus dokumen
14. **M-7 / M-8** — hanya kalau Fase 0 membuktikannya

### Fase 3 — Minor & kebersihan (perkiraan 2 hari)
15. m-1, m-2, m-3, m-5, m-9, m-10 — perbaikan kecil
16. m-13 — hapus atau load file yatim; aktifkan `block-admin.php`
17. m-11, m-12 — satukan skema `gti_customers`; dokumentasikan `equipment_id` polimorfik
18. m-6 — putuskan: bangun Contact Messages / Website Settings, atau hapus route-nya

### Fase 4 — Keputusan produk (bukan pekerjaan kode)
- Apakah unit **SOLD** masih boleh tampil di website? Kalau ya, form inquiry-nya harus diganti.
- Apakah "Harga berlaku sampai" berarti *sembunyikan harga* atau *sembunyikan unit*?
- Apakah pendaftaran mandiri (self-registration) memang diinginkan? Kalau tidak, **hapus** `page-register.php` + `inc/auth/register.php` alih-alih menyambungkannya (B-1 jadi lebih kecil).
- Contact Messages & Website Settings: dibangun atau dibuang?

---

## 6. Yang sudah benar dan sebaiknya tidak diutak-atik

Supaya proporsional — arsitektur di bawah ini solid dan menjadi fondasi perbaikan di atas:

- **State machine status** (`inc/modules/status-machine.php`) — satu sumber kebenaran untuk label, warna badge, dan transisi yang sah. Frontend hanya menawarkan transisi yang diizinkan server. Rapi.
- **Urutan email** — email dikirim dari hook `gti_status_changed`, yang hanya menyala **setelah** penulisan DB berhasil. Ini memperbaiki bug lama di mana email terkirim sebelum nonce diverifikasi.
- **Pagination** — `GTI_PAGE_QUERY_VAR = 'page_num'` dipusatkan di `render-helpers.php:29`. <cc-memory filenames="pagination-fix.md">Ini memang penting: memakai `paged` membuat WordPress mengembalikan 404 sebelum template dijalankan, dan konstanta ini membuat bug tersebut tidak mungkin terulang.</cc-memory>
- **Keamanan berlapis** — nonce → capability → kepemilikan baris (`gti_guard_row_scope`) dijalankan konsisten di semua handler inbox. Honeypot + rate limit di form publik.
- **Pertahanan skema** — `array_intersect_key($data, SHOW COLUMNS)` dipakai sebelum insert/update di beberapa tempat, sehingga schema lama merosot pelan-pelan alih-alih gagal total.
- **Data demo mati secara default** — `gti_show_demo_data()` mengembalikan `no`, dan fungsi dummy-nya juga inert karena `database/dummy-*.php` tidak pernah di-load. Pengunjung tidak akan melihat unit fiktif.
- **Tes** — 5 suite di `tests/` lulus dan menjaga logika yang paling mudah rusak (parsing jumlah uang, pemisahan catatan, keputusan pemotongan stok, backfill).
- **`page-customer-detail.php`** — <cc-memory filenames="customer-detail-template-keep.md">template ini sengaja dipertahankan meskipun link ke sana sudah dihapus dari `page-customers.php`, jadi jangan dihapus saat mengerjakan B-4.</cc-memory>

---

## 7. Perintah verifikasi cepat

```bash
cd wp-content/themes/global-tractors

# 1. Semua file PHP ter-parse
find . -name '*.php' -not -path './.git/*' -exec php -l {} \; | grep -v "No syntax errors"

# 2. Semua tes lulus
for t in tests/test-*.php; do php "$t"; done

# 3. File PHP yang tidak pernah di-require (harus cocok dengan daftar m-13)
for f in $(find inc includes -name '*.php'); do
  b=$(basename $f)
  grep -rq "require.*$b" --include='*.php' inc includes functions.php || echo "ORPHAN: $f"
done

# 4. Action AJAX terdaftar vs yang dipanggil dari JS
grep -rhoE "wp_ajax(_nopriv)?_[a-z0-9_]+" --include='*.php' . | sed -E 's/^wp_ajax(_nopriv)?_//' | sort -u > /tmp/registered.txt
grep -rhoE "'gti_[a-z0-9_]+'" assets/js | tr -d "'" | sort -u > /tmp/called.txt
comm -13 /tmp/registered.txt /tmp/called.txt   # dipanggil tapi tidak terdaftar — harus kosong
comm -23 /tmp/registered.txt /tmp/called.txt   # terdaftar tapi tidak dipanggil — fitur tak terjangkau
```
