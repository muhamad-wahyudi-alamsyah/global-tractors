# Email Notification Flow - GTI Admin Panel

> Dokumen ini menjelaskan flow komunikasi email antara pelanggan dan admin GTI
> untuk setiap perubahan status di tiga halaman utama.

---

## Overview

Ketiga halaman dashboard admin memiliki flow status masing-masing:
1. **Request Equipment** — Pelanggan minta equipment tertentu
2. **Request Quotation** — Pelanggan minta penawaran harga
3. **Sell Equipment** — Pelanggan tawarkan equipment untuk dijual

**Prinsip dasar:**
- ✅ Setiap perubahan status WAJIB ada email notifikasi ke pelanggan
- ✅ Auto-reply saat pertama kali submit form
- ✅ Semua email harus di-log untuk tracking
- ✅ Template email konsisten dengan branding GTI
- ✅ Bahasa Indonesia untuk target pasar lokal

---

## 1. Request Equipment

### Flow Status
```
[new] ──→ [processing] ──→ [proposal_sent] ──→ [closed]
```

### Email Notifications

| Trigger | Email Subject | Isi Email |
|---------|--------------|-----------|
| **Submit form** | "Pesanan Anda Telah Diterima - [REQ-ID]" | Konfirmasi penerimaan, nomor referensi, estimasi waktu |
| **→ processing** | "Pesanan Anda Sedang Diproses - [REQ-ID]" | Beritahu admin sedang memproses, tunggu info lanjutan |
| **→ proposal_sent** | "Proposal Telah Dikirim - [REQ-ID]" | Lampirkan detail proposal, harga, syarat |
| **→ closed** | "Pesanan Selesai - [REQ-ID]" | Konfirmasi penutupan, terima kasih |

### Status Meanings
- **new**: Pesanan baru masuk dari website, menunggu review admin
- **processing**: Admin sedang memproses/mencari equipment yang sesuai
- **proposal_sent**: Admin sudah mengirim proposal/penawaran ke customer
- **closed**: Deal selesai (baik diterima maupun ditolak customer)

---

## 2. Request Quotation

### Flow Status
```
[new] ──→ [processing] ──→ [waiting_customer] ──→ [approved] ──→ [completed]
                                                    └──→ [rejected]
```

### Email Notifications

| Trigger | Email Subject | Isi Email |
|---------|--------------|-----------|
| **Submit form** | "Quotation Request Diterima - [QUO-ID]" | Konfirmasi penerimaan, nomor referensi |
| **→ processing** | "Quotation Sedang Disusun - [QUO-ID]" | Admin sedang menyiapkan quotation |
| **→ waiting_customer** | "Quotation Telah Dikirim - [QUO-ID]" | Lampirkan quotation detail, berlaku sampai tanggal tertentu |
| **→ approved** | "Quotation Disetujui - [QUO-ID]" | Terima kasih, konfirmasi order akan diproses |
| **→ rejected** | "Quotation Belum Dapat Diproses - [QUO-ID]" | Mohon maaf, tawarkan alternatif jika ada |
| **→ completed** | "Transaksi Selesai - [QUO-ID]" | Finalisasi, terima kasih, kontak untuk follow-up |

### Status Meanings
- **new**: Quotation request baru dari customer
- **processing**: Admin sedang menyusun quotation/penawaran harga
- **waiting_customer**: Quotation sudah dikirim, menunggu keputusan customer
- **approved**: Customer menyetujui quotation
- **rejected**: Customer menolak quotation
- **completed**: Transaksi/quotation sudah selesai

---

## 3. Sell Equipment

### Flow Status
```
[new] ──→ [processing] ──→ [approved] ──→ [completed]
                   └──→ [rejected]
```

### Email Notifications

| Trigger | Email Subject | Isi Email |
|---------|--------------|-----------|
| **Submit form** | "Tawaran Equipment Diterima - [Sell ID]" | Konfirmasi penerimaan tawaran |
| **→ processing** | "Tawaran Sedang Direview - [Sell ID]" | Admin sedang mengevaluasi equipment |
| **→ approved** | "Tawaran Diterima - [Sell ID]" | Konfirmasi penerimaan, langkah selanjutnya |
| **→ rejected** | "Tawaran Belum Dapat Diterima - [Sell ID]" | Mohon maaf, alasan penolakan umum |
| **→ completed** | "Transaksi Selesai - [Sell ID]" | Finalisasi pembelian, terima kasih |

### Status Meanings
- **new**: Tawaran jual equipment baru dari customer
- **processing**: Admin sedang mereview/mengevaluasi equipment
- **approved**: Tawaran diterima, akan dilanjutkan transaksi
- **rejected**: Tawaran ditolak (kondisi, harga, dll)
- **completed**: Transaksi pembelian selesai

---

## Template Email (Bahasa Indonesia)

### Auto-Reply (Submit Form)
```
Halo [Nama Customer],

Terima kasih telah menghubungi PT Global Tractors Indonesia.

Pesanan/Quotation/Tawaran anda dengan nomor [ID] telah kami terima.
Tim kami akan segera memproses dan menghubungi anda dalam 1-2 hari kerja.

Nomor Referensi: [ID]
Tanggal: [Tanggal]

Jika ada pertanyaan, silakan hubungi kami di:
Email: info@globaltractors.co.id
Telepon: [Nomor Telepon]

Salam hangat,
PT Global Tractors Indonesia
```

### Status Processing
```
Halo [Nama Customer],

Pesanan/Quotation anda dengan nomor [ID] sedang kami proses.

Tim kami sedang bekerja untuk memberikan yang terbaik untuk anda.
Kami akan menghubungi anda segera jika ada update.

Terima kasih atas kesabaran anda.

Salam,
PT Global Tractors Indonesia
```

### Status Proposal Sent / Waiting Customer
```
Halo [Nama Customer],

Quotation/Proposal untuk pesanan [ID] telah kami kirim ke email anda.

Silakan cek email anda untuk detail penawaran.
Jika ada pertanyaan atau membutuhkan perubahan, jangan ragu untuk menghubungi kami.

Berlaku sampai: [Tanggal Valid]

Salam,
PT Global Tractors Indonesia
```

### Status Approved
```
Halo [Nama Customer],

Dengan senang hati kami informasikan bahwa quotation/penawaran [ID] telah disetujui.

Tim kami akan segera memproses pesanan anda.
Kami akan menghubungi anda untuk langkah selanjutnya.

Terima kasih atas kepercayaan anda.

Salam,
PT Global Tractors Indonesia
```

### Status Rejected
```
Halo [Nama Customer],

Terima kasih atas quotation/penawaran [ID] yang telah anda ajukan.

Mohon maaf, untuk saat ini kami belum dapat memproses permintaan anda.
[Jika ingin menambahkan alasan spesifik, tambahkan di sini]

Jika ada yang bisa kami bantu di masa mendatang, silakan hubungi kami.

Salam,
PT Global Tractors Indonesia
```

### Status Completed
```
Halo [Nama Customer],

Transaksi untuk [ID] telah selesai diproses.

Terima kasih atas kepercayaan anda kepada PT Global Tractors Indonesia.
Kami berharap dapat melayani anda kembali di masa mendatang.

Untuk pertanyaan atau layanan after-sales, silakan hubungi kami.

Salam hangat,
PT Global Tractors Indonesia
```

---

## Implementation Notes

### Database
- Tambahkan tabel `gti_email_logs` untuk menyimpan history email
- Field: `id`, `recipient_email`, `subject`, `body`, `status`, `related_type` (request/quotation/sell), `related_id`, `sent_at`, `created_at`

### WordPress Function
- Gunakan `wp_mail()` untuk mengirim email
- Setup email template di `inc/email/` directory
- Hook ke `wp_ajax_gti_update_*_status` untuk trigger email

### Email Configuration
- Dari: `info@globaltractors.co.id`
- Reply-To: `info@globaltractors.co.id`
- Content-Type: `text/html; charset=UTF-8`

### Cron Job (Optional)
- Untuk reminder otomatis jika quotation belum di-respons dalam X hari
- Untuk follow-up otomatis setelah status tertentu

---

*Document created: 2026-09-02*
*Author: GTI Development Team*
