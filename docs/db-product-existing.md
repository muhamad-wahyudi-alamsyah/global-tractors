# Database Produk Existing - Global Tractors Indonesia

> **Terakhir diperbarui:** 2026-08-29  
> **Total Produk:** 18 items  
> **Database:** globaltractors (MySQL/MariaDB)

---

## 📊 Ringkasan

| Metric | Jumlah |
|--------|--------|
| Total Produk | 18 |
| Kategori | 3 (used equipment, sparepart, tanpa kategori) |
| Status Stok | Semua `instock` |
| Harga | Belum diisi |

---

## 📂 Kategori Produk

| Kategori | Slug | Jumlah |
|----------|------|--------|
| Used Equipment | `used-equipment` | 12 produk |
| Sparepart | `sparepart` | 5 produk |
| Tanpa Kategori | `tanpa-kategori` | 6 produk |

---

## 📋 Daftar Produk Lengkap

### 1. Suku Cadang (Sparepart) - ID 891-895

| ID | Nama Produk | Kategori | Status Stok | Gambar ID |
|----|-------------|----------|-------------|-----------|
| 891 | Volvo Middle Diff DT100H | sparepart | instock | 933 |
| 892 | Komatsu PC300-8MO | sparepart | instock | 888 |
| 893 | Engine Mercedes-Benz Type OM906LA | sparepart | instock | 887 |
| 894 | Renault Cabin Assy | sparepart | instock | 886 |
| 895 | York Argonaut Axle | sparepart | instock | 885 |

### 2. Used Equipment - ID 910-926

| ID | Nama Produk | Kategori | Status Stok | Gambar ID |
|----|-------------|----------|-------------|-----------|
| 910 | KOMATSU PC200-8 | used equipment | instock | 993 |
| 911 | CAT 320D2 | used equipment | instock | 1001 |
| 912 | HITACHI ZX200-5G | used equipment | instock | 997 |
| 913 | DOOSAN DX225LCA | used equipment | instock | 999 |
| 914 | CAT 950H | used equipment | instock | 1000 |
| 915 | HITACHI ZX330-5G | used equipment | instock | 996 |
| 919 | KOMATSU PC300-8 | used equipment | instock | 992 |
| 920 | VOLVO EC210B | tanpa kategori | instock | 990 |
| 921 | KOMATSU WA380-6 | used equipment | instock | 991 |
| 922 | HINO 500 FM260JD | used equipment | instock | 998 |
| 923 | KOMATSU GD511A-1 | used equipment | instock | 994 |
| 924 | KATO KR-25H-V | used equipment | instock | 995 |
| 926 | VOLVO EC210B | used equipment | instock | 990 |

---

## 🔍 Detail Meta Data Produk

### Meta Keys yang Tersedia

| Meta Key | Keterangan | Status |
|----------|------------|--------|
| `_price` | Harga produk | ❌ Kosong (belum diisi) |
| `_stock` | Jumlah stok | ❌ Kosong |
| `_stock_status` | Status stok | ✅ `instock` |
| `_sku` | SKU/kode produk | ❌ Kosong |
| `_thumbnail_id` | ID gambar utama | ✅ Ada |
| `_weight` | Berat | ❌ Kosong |
| `_length` | Panjang | ❌ Kosong |
| `_width` | Lebar | ❌ Kosong |
| `_height` | Tinggi | ❌ Kosong |
| `_manage_stock` | Kelola stok | ✅ Ada |
| `_backorders` | Backorder | ✅ Ada |
| `_virtual` | Produk virtual | ✅ Ada |
| `_downloadable` | Produk downloadable | ✅ Ada |

---

## 🖼️ Gambar Produk

| Produk ID | Thumbnail ID | URL Gambar |
|-----------|--------------|------------|
| 891 | 933 | `/wp-content/uploads/...` |
| 892 | 888 | `/wp-content/uploads/...` |
| 893 | 887 | `/wp-content/uploads/...` |
| 894 | 886 | `/wp-content/uploads/...` |
| 895 | 885 | `/wp-content/uploads/...` |
| 910 | 993 | `/wp-content/uploads/...` |
| 911 | 1001 | `/wp-content/uploads/...` |
| 912 | 997 | `/wp-content/uploads/...` |
| 913 | 999 | `/wp-content/uploads/...` |
| 914 | 1000 | `/wp-content/uploads/...` |
| 915 | 996 | `/wp-content/uploads/...` |
| 919 | 992 | `/wp-content/uploads/...` |
| 920 | 990 | `/wp-content/uploads/...` |
| 921 | 991 | `/wp-content/uploads/...` |
| 922 | 998 | `/wp-content/uploads/...` |
| 923 | 994 | `/wp-content/uploads/...` |
| 924 | 995 | `/wp-content/uploads/...` |
| 926 | 990 | `/wp-content/uploads/...` |

---

## 📝 SQL Queries Berguna

### Lihat Semua Produk
```sql
SELECT ID, post_title, post_status 
FROM wp_posts 
WHERE post_type = 'product' 
ORDER BY ID;
```

### Lihat Produk dengan Harga
```sql
SELECT 
    p.ID,
    p.post_title,
    pm_price.meta_value as harga
FROM wp_posts p
LEFT JOIN wp_postmeta pm_price ON p.ID = pm_price.post_id 
    AND pm_price.meta_key = '_price'
WHERE p.post_type = 'product' AND p.post_status = 'publish';
```

### Lihat Produk per Kategori
```sql
SELECT 
    p.ID,
    p.post_title,
    GROUP_CONCAT(t.name SEPARATOR ', ') as kategori
FROM wp_posts p
JOIN wp_term_relationships tr ON p.ID = tr.object_id
JOIN wp_term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
JOIN wp_terms t ON tt.term_id = t.term_id
WHERE p.post_type = 'product' 
    AND p.post_status = 'publish' 
    AND tt.taxonomy = 'product_cat'
GROUP BY p.ID, p.post_title;
```

### Update Harga Produk
```sql
-- Contoh: Update harga untuk produk ID 910
UPDATE wp_postmeta 
SET meta_value = '850000000' 
WHERE post_id = 910 AND meta_key = '_price';
```

---

## ⚠️ Catatan Penting

1. **Harga belum diisi** - Semua produk memiliki `_price` kosong
2. **Stok tidak dikelola secara numerik** - Hanya ada status `instock`
3. **SKU belum diisi** - Tidak ada kode SKU untuk semua produk
4. **Dimensi kosong** - Berat, panjang, lebar, tinggi belum diisi
5. **Gambar tersedia** - Semua produk sudah memiliki thumbnail

---

## 🔧 Rekomendasi Update

Untuk melengkapi data produk, perlu diupdate:

```sql
-- 1. Update harga (contoh)
UPDATE wp_postmeta SET meta_value = '850000000' WHERE post_id = 910 AND meta_key = '_price';
UPDATE wp_postmeta SET meta_value = '720000000' WHERE post_id = 911 AND meta_key = '_price';
-- ... dst

-- 2. Update SKU (contoh)
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES 
(910, '_sku', 'GTI-EXC-001'),
(911, '_sku', 'GTI-EXC-002');
-- ... dst

-- 3. Update stok (contoh)
UPDATE wp_postmeta SET meta_value = '1' WHERE post_id = 910 AND meta_key = '_stock';
```

---

## 📁 Lokasi File Terkait

| File | Keterangan |
|------|------------|
| `wp-content/plugins/woocommerce/includes/class-wc-post-types.php` | Registrasi post type product |
| `wp-content/plugins/woocommerce/includes/class-wc-product-simple.php` | Handler produk |
| `wp-content/themes/global-tractors/database/dummy-equipment.php` | Data dummy equipment (custom table) |
