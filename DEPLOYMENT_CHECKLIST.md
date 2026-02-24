# Pre-Deployment Checklist

Gunakan checklist ini sebelum hosting project ke production:

## 📋 Database & Configuration

- [ ] Export database dari localhost (`kosconnect.sql`)
- [ ] Buat database baru di hosting
- [ ] Import database ke hosting
- [ ] Update `config/db.php` dengan kredensial hosting
- [ ] Test koneksi database

## 📁 File Upload

- [ ] Upload semua file project ke `public_html`
- [ ] Upload file `.htaccess`
- [ ] Pastikan folder `uploads/` ter-upload
- [ ] Set permission folder `uploads/` ke 755 atau 777
- [ ] Pastikan folder `config/` tidak accessible dari browser

## 🔐 Security

- [ ] Ganti database credentials dari default
- [ ] Disable error display (`display_errors = Off`)
- [ ] Aktifkan error logging
- [ ] Protect sensitive files via `.htaccess`
- [ ] Pastikan file `config/db.php` tidak accessible dari browser
- [ ] Update session security settings

## 💳 Payment Gateway (Jika Ada)

- [ ] Update Midtrans credentials ke production
- [ ] Update Xendit credentials ke production
- [ ] Test payment flow
- [ ] Verifikasi callback URLs

## 🌐 Domain & SSL

- [ ] Setup domain (atau gunakan subdomain)
- [ ] Install SSL certificate (Let's Encrypt gratis)
- [ ] Force HTTPS (uncomment di `.htaccess`)
- [ ] Update base URL di config jika perlu

## ✅ Testing

- [ ] Test halaman utama loading
- [ ] Test login/register
- [ ] Test upload gambar
- [ ] Test booking flow
- [ ] Test payment (jika ada)
- [ ] Test admin dashboard
- [ ] Test owner dashboard
- [ ] Test user dashboard
- [ ] Test responsive design (mobile)

## 📊 Performance

- [ ] Enable GZIP compression
- [ ] Enable browser caching
- [ ] Optimize images di folder `uploads/`
- [ ] Test page load speed

## 📝 Documentation

- [ ] Baca `DEPLOYMENT_GUIDE.md`
- [ ] Catat kredensial database
- [ ] Catat kredensial FTP
- [ ] Catat kredensial cPanel

## 🔄 Post-Deployment

- [ ] Setup backup otomatis database
- [ ] Setup monitoring (Google Analytics, dll)
- [ ] Test error logging
- [ ] Buat admin account untuk production
- [ ] Hapus data testing jika ada

---

## 🚨 Emergency Contacts

**Hosting Support:**
- Email: support@hostinganda.com
- Phone: +62-xxx-xxx-xxxx

**Developer:**
- Email: developer@email.com

---

**Tanggal Deploy:** _____________

**Deployed By:** _____________

**Notes:**
_________________________________
_________________________________
_________________________________
