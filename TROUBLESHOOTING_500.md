# Troubleshooting HTTP 500 Error di InfinityFree

## Penyebab Umum HTTP 500 Error

### 1. **File .htaccess Bermasalah** ⚠️
InfinityFree memiliki limitasi pada beberapa directive .htaccess.

**Solusi:**
- Rename file `.htaccess` menjadi `.htaccess_backup`
- Upload file `.htaccess_infinityfree` yang sudah disediakan
- Test website lagi

**Cara via cPanel:**
```
1. Login ke cPanel
2. File Manager → public_html
3. Klik kanan .htaccess → Rename → .htaccess_backup
4. Upload file .htaccess_infinityfree
5. Rename .htaccess_infinityfree → .htaccess
```

### 2. **Database Connection Error** 🔴
Kredensial database salah atau database belum di-import.

**Solusi:**
1. Cek file `config/db.php`
2. Pastikan kredensial sesuai dengan cPanel:
   ```php
   $host = "sql###.infinityfree.com"; // Cek di cPanel
   $user = "epiz_xxxxx"; // Username database
   $pass = "password_anda"; // Password database
   $dbname = "epiz_xxxxx_kosconnect"; // Nama database
   ```
3. Pastikan database sudah di-import via phpMyAdmin

### 3. **PHP Version Incompatible** ⚙️
InfinityFree menggunakan PHP 8.x, mungkin ada syntax yang tidak kompatibel.

**Solusi:**
- Cek PHP version di cPanel → MultiPHP Manager
- Set ke PHP 8.0 atau 8.1
- Avoid deprecated functions

### 4. **File Permission Error** 🔒
Permission folder/file tidak tepat.

**Solusi:**
```
Folders: 755
Files: 644
uploads/ folder: 755 atau 777
```

**Cara set permission:**
```
1. File Manager → pilih folder/file
2. Klik kanan → Change Permissions
3. Set sesuai di atas
```

### 5. **Missing PHP Extensions** 📦
Extension PHP yang dibutuhkan tidak aktif.

**Yang Dibutuhkan:**
- mysqli
- mbstring
- json
- session

**Cek di cPanel:**
- PHP Version → Extensions
- Pastikan semua extension di atas aktif

### 6. **Error di index.php atau File Utama** 💥
Syntax error atau path yang salah.

**Solusi:**
- Cek error log di cPanel → Error Log
- Fix syntax error yang muncul
- Pastikan semua `include` dan `require` path benar

---

## Langkah Troubleshooting Sistematis

### Step 1: Cek Error Log
```
1. cPanel → Error Log
2. Lihat error terakhir
3. Catat pesan error
```

### Step 2: Test Database Connection
Buat file `test_db.php` di root:
```php
<?php
include 'config/db.php';
echo "Database connected successfully!";
?>
```

Upload dan akses: `http://kosconnectonline.infinityfree.com/test_db.php`

### Step 3: Disable .htaccess Temporarily
```
1. Rename .htaccess → .htaccess_backup
2. Test website
3. Jika works, berarti .htaccess bermasalah
```

### Step 4: Check File Permissions
```
public_html/ → 755
config/ → 755
uploads/ → 777
*.php files → 644
```

### Step 5: Verify PHP Version
```
cPanel → MultiPHP Manager
Set to PHP 8.0 or 8.1
```

---

## Quick Fixes

### Fix 1: Replace .htaccess
```bash
# Gunakan .htaccess yang sudah disederhanakan
# File: .htaccess_infinityfree
```

### Fix 2: Update db.php
```bash
# Gunakan template: config/db_production.php
# Copy kredensial dari cPanel
```

### Fix 3: Set Correct Permissions
```bash
# Via File Manager:
uploads/ → 777
config/ → 755
*.php → 644
```

---

## Jika Masih Error

### Hubungi Support InfinityFree
1. Forum: https://forum.infinityfree.com
2. Ticket: Via cPanel
3. Sertakan:
   - Error message dari Error Log
   - File yang bermasalah
   - Screenshot error

### Alternative: Pindah ke Hosting Berbayar
**Recommended:**
- **Hostinger** (Rp 20k/bulan)
  - Lebih stabil
  - Support 24/7
  - No limitasi .htaccess
  
- **Niagahoster** (Rp 15k/bulan)
  - Support Indonesia
  - cPanel lengkap
  - Free SSL

---

## Checklist Debugging

- [ ] Cek Error Log di cPanel
- [ ] Test database connection
- [ ] Verify database credentials di config/db.php
- [ ] Rename .htaccess temporarily
- [ ] Check file permissions
- [ ] Verify PHP version (8.0 or 8.1)
- [ ] Import database via phpMyAdmin
- [ ] Clear browser cache
- [ ] Test di browser lain/incognito

---

## Contact Me

Jika masih stuck, kirim:
1. Screenshot error
2. Error log dari cPanel
3. Isi file config/db.php (sensor password)

**Good luck! 🚀**
