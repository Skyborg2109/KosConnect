# Panduan Hosting KosConnect

## 📋 Persiapan Sebelum Hosting

### 1. Pilih Hosting Provider
Rekomendasi hosting untuk project PHP + MySQL:
- **Hostinger** (Rp 20.000/bulan) - Recommended
- **Niagahoster** (Rp 15.000/bulan)
- **InfinityFree** (Gratis, tapi ada limitasi)

### 2. Yang Anda Butuhkan
- ✅ Domain (opsional, bisa pakai subdomain gratis dari hosting)
- ✅ Hosting dengan PHP 7.4+ dan MySQL
- ✅ FTP Client (FileZilla) atau File Manager di cPanel
- ✅ Database backup (file SQL)

---

## 🚀 Langkah-Langkah Deployment

### STEP 1: Export Database dari Localhost

1. Buka **phpMyAdmin** di localhost (http://localhost/phpmyadmin)
2. Pilih database `kosconnect`
3. Klik tab **Export**
4. Pilih **Quick** export method
5. Format: **SQL**
6. Klik **Go** untuk download file `kosconnect.sql`

### STEP 2: Setup Database di Hosting

1. Login ke **cPanel** hosting Anda
2. Cari menu **MySQL Databases**
3. Buat database baru:
   - Database Name: `kosconnect` (atau nama lain)
   - Klik **Create Database**
4. Buat user database:
   - Username: pilih username (contoh: `kos_user`)
   - Password: buat password yang kuat
   - Klik **Create User**
5. Tambahkan user ke database:
   - Pilih user yang baru dibuat
   - Pilih database yang baru dibuat
   - Centang **ALL PRIVILEGES**
   - Klik **Add**
6. Catat informasi berikut:
   ```
   Database Host: localhost (atau sql###.infinityfree.com untuk InfinityFree)
   Database Name: nama_database_anda
   Database User: username_anda
   Database Password: password_anda
   ```

### STEP 3: Import Database ke Hosting

1. Di cPanel, buka **phpMyAdmin**
2. Pilih database yang baru dibuat
3. Klik tab **Import**
4. Klik **Choose File** dan pilih file `kosconnect.sql`
5. Klik **Go** untuk import
6. Tunggu sampai selesai (akan muncul pesan sukses)

### STEP 4: Upload Files ke Hosting

#### Opsi A: Menggunakan File Manager (Mudah)

1. Di cPanel, buka **File Manager**
2. Navigasi ke folder `public_html` (atau `htdocs`)
3. Klik **Upload**
4. Pilih semua file project KosConnect (atau zip dulu)
5. Jika upload file ZIP:
   - Klik kanan file ZIP
   - Pilih **Extract**
   - Hapus file ZIP setelah extract

#### Opsi B: Menggunakan FileZilla (Lebih Cepat)

1. Download dan install **FileZilla**
2. Buka FileZilla
3. Masukkan kredensial FTP dari hosting:
   - Host: ftp.domainanda.com
   - Username: username FTP
   - Password: password FTP
   - Port: 21
4. Klik **Quickconnect**
5. Di panel kanan, navigasi ke `public_html`
6. Di panel kiri, navigasi ke folder project KosConnect
7. Drag & drop semua file dari kiri ke kanan

### STEP 5: Konfigurasi Database di Hosting

1. Buka file `config/db.php` di hosting (via File Manager atau FileZilla)
2. Edit file tersebut:

```php
<?php
// === PRODUCTION CONFIGURATION ===
$host = "localhost"; // atau sql###.infinityfree.com
$user = "username_database_anda";
$pass = "password_database_anda";
$dbname = "nama_database_anda";

// === LOCAL DEVELOPMENT CONFIGURATION ===
// Comment bagian ini saat deploy ke production
/*
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "kosconnect";
*/

// Sisanya tetap sama...
```

3. **Save** file

### STEP 6: Konfigurasi Payment Gateway (Opsional)

Jika menggunakan Midtrans/Xendit, update kredensial di file terkait:

**Untuk Midtrans:**
- File: `config/midtrans_config.php` (jika ada)
- Ganti Server Key dan Client Key dengan yang production

**Untuk Xendit:**
- File: `config/xendit_config.php` (jika ada)
- Ganti API Key dengan yang production

### STEP 7: Setup .htaccess (Security & URL Rewriting)

File `.htaccess` sudah disediakan di root project. Pastikan file ini ter-upload.

### STEP 8: Testing

1. Buka website Anda: `http://domainanda.com`
2. Test fitur-fitur penting:
   - ✅ Login/Register
   - ✅ Upload gambar
   - ✅ Booking
   - ✅ Payment (jika sudah setup)
   - ✅ Admin dashboard

---

## 🔧 Troubleshooting

### Problem: "Database Connection Error"
**Solusi:**
1. Cek kredensial di `config/db.php`
2. Pastikan database sudah di-import
3. Pastikan user database punya privileges
4. Cek apakah hostname benar (localhost atau IP server)

### Problem: "404 Not Found" atau "Page Not Found"
**Solusi:**
1. Pastikan file `.htaccess` sudah ter-upload
2. Cek apakah mod_rewrite aktif di hosting
3. Periksa path file di URL

### Problem: Gambar tidak muncul
**Solusi:**
1. Cek folder `uploads/` sudah ter-upload
2. Set permission folder `uploads/` menjadi **755** atau **777**
3. Cek path gambar di database

### Problem: "500 Internal Server Error"
**Solusi:**
1. Cek error log di cPanel
2. Pastikan PHP version minimal 7.4
3. Cek syntax error di file PHP
4. Disable error display di production (edit `php.ini` atau `.htaccess`)

---

## 🔒 Security Checklist

Sebelum go-live, pastikan:

- [ ] Database credentials sudah diganti dari default
- [ ] Error display di-disable di production
- [ ] File `.env` atau config tidak accessible dari browser
- [ ] Folder `uploads/` hanya bisa upload file tertentu (jpg, png, pdf)
- [ ] Session timeout sudah diatur
- [ ] HTTPS sudah aktif (SSL certificate)
- [ ] Backup database secara berkala

---

## 📱 Post-Deployment

### Setup SSL Certificate (HTTPS)
1. Di cPanel, cari **SSL/TLS**
2. Pilih **Let's Encrypt** (gratis)
3. Install SSL untuk domain Anda
4. Update semua URL di project dari `http://` ke `https://`

### Setup Cron Job (Opsional)
Untuk cleanup session atau task otomatis:
1. Di cPanel, buka **Cron Jobs**
2. Tambah cron job sesuai kebutuhan

### Monitoring
- Setup Google Analytics (opsional)
- Monitor error logs secara berkala
- Backup database setiap minggu

---

## 📞 Support

Jika ada masalah:
1. Cek error log di cPanel
2. Hubungi support hosting
3. Review dokumentasi hosting provider

**Good luck! 🚀**
