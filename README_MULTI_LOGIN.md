# 🔐 Dokumentasi Fitur Multi-Login KosConnect

## Pengenalan

Fitur Multi-Login memungkinkan user KosConnect untuk login dari **berbagai device secara bersamaan**. Setiap user dapat aktif di laptop, smartphone, tablet, atau browser berbeda tanpa perlu logout dari device lain.

## Cara Kerja

### Arsitektur Multi-Login

1. **Session Token**: Setiap kali user login, sistem generate token unik untuk device tersebut
2. **Database Tracking**: Setiap session disimpan di tabel `user_sessions` dengan informasi:
   - Token unik
   - Device name (OS dan Browser)
   - IP address
   - Timestamp login dan last activity
   - Status aktif/tidak aktif

3. **Persistent Cookie**: Token disimpan di cookie browser untuk persistent access

### Alur Login Multi-Device

```
User Login (Device 1)
    ↓
Generate Session Token (Device 1)
    ↓
Simpan di Database + Cookie
    ↓
User dapat login dari Device 2, 3, ... secara bersamaan
    ↓
Setiap Device memiliki token sendiri dan session independen
```

## Fitur-Fitur

### 1. **Login dari Multiple Device**

**Sebelum (Single-Login)**:
- User login → Redirect ke dashboard
- Login dari device lain → Force logout dari device pertama
- User hanya bisa aktif di 1 device saja

**Sesudah (Multi-Login)**:
- User login dari Device A → Session A active
- User login dari Device B → Session A + B active
- User login dari Device C → Session A + B + C active
- Semua session dapat aktif bersamaan ✅

### 2. **Manage Active Sessions**

User dapat melihat semua device yang sedang login:

**Cara akses**:
```
Dashboard → Klik Profil/Avatar → "Profil Saya" 
→ Scroll ke "Kelola Device" → "Lihat Semua Device"
```

Atau langsung: `/user/manage_sessions.php`

**Informasi yang ditampilkan**:
- ✅ Device Name (Browser + OS)
- ✅ IP Address
- ✅ Waktu Login
- ✅ Akses Terakhir
- ✅ Status "Device Ini" untuk device saat ini

### 3. **Logout dari Device Tertentu**

User dapat logout dari device tertentu tanpa mempengaruhi device lain:

**Cara**:
1. Buka `/user/manage_sessions.php`
2. Cari device yang ingin di-logout
3. Klik tombol "Logout" pada device tersebut
4. Device tersebut akan otomatis logout, device lain tetap aktif ✅

### 4. **Logout dari Semua Device**

Fitur emergency logout untuk logout dari semua device sekaligus:

**Cara**:
1. Buka `/user/manage_sessions.php`
2. Klik tombol "Logout dari Semua Device" (warna merah)
3. Semua session akan ditutup
4. User harus login kembali dari awal

**Kapan gunakan**:
- Akun terasa tidak aman
- Lupa logout dari public computer
- Ingin reset semua sessions

## Keamanan

### Proteksi Multi-Login

✅ **Token Unik**: Setiap session punya token random 32-byte yang tidak bisa diprediksi

✅ **Database Verification**: Setiap akses di-verify dengan database untuk ensure token masih aktif

✅ **IP Tracking**: Setiap session track IP address user, bisa detect anomali login

✅ **Device Info**: Setiap session simpan device name untuk audit trail

✅ **Last Activity Tracking**: Monitor last activity setiap session, bisa detect inactive sessions

✅ **Session Expiration**: Automatic cleanup sessions lebih dari 30 hari

### Best Practices

1. **Periksa Device Secara Berkala**
   - Pastikan hanya device Anda sendiri yang login
   - Check setiap IP address familiar atau tidak

2. **Logout dari Public Device**
   - Jangan lupa logout dari internet cafe, lab, etc
   - Gunakan "Logout dari Device Tertentu" jika ada device mencurigakan

3. **Gunakan Password Kuat**
   - Multi-login hanya aman jika password kuat
   - Ganti password secara berkala

4. **Monitor Akses**
   - Check manage_sessions.php secara rutin
   - Perhatikan IP address yang tidak familiar

## File-File Terkait

### Database
- **Migration**: `migrations/20251115_add_user_sessions_table.sql`
  - Membuat tabel `user_sessions`
  - Menyimpan info semua active sessions

### Backend
- **SessionManager** (`config/SessionManager.php`)
  - `createSessionToken()` - Generate token baru
  - `validateSessionToken()` - Verify token masih aktif
  - `getUserSessions()` - List semua active sessions
  - `logoutSession()` - Logout device tertentu
  - `logoutAllSessions()` - Logout semua devices

- **SessionChecker** (`config/SessionChecker.php`)
  - `checkMultiDeviceSession()` - Validate session token dari session/cookie
  - `getCurrentSessionInfo()` - Get info session saat ini

- **Login Form** (`auth/loginForm.php`)
  - Tidak redirect otomatis ke dashboard
  - Allow multiple login tanpa redirect loop
  - Generate session token setiap login

- **Logout** (`auth/logout.php`)
  - Logout device saat ini saja
  - Device lain tetap active

### Frontend
- **Manage Sessions** (`user/manage_sessions.php`)
  - Halaman untuk manage semua active sessions
  - Show info setiap device
  - Logout device tertentu atau semua

- **Profile Modal** (`user/_user_profile_modal.php`)
  - Link ke manage_sessions.php di profile modal
  - Info tentang fitur multi-login

## Implementasi Teknis

### Flow Login dengan Multi-Device Support

```php
// 1. User submit login form
POST /auth/loginForm.php
  - Email & Password verification
  - Generate session token via SessionManager
  - Save token di database + cookie
  - Tidak redirect otomatis (allow multi-login)

// 2. Di Dashboard, validate session
// SessionChecker verify token di database
checkMultiDeviceSession($conn)
  - Read token dari session atau cookie
  - Query database: SELECT dari user_sessions WHERE token
  - Update last_activity timestamp
  - Set session variables

// 3. Logout dari device tertentu
POST /user/manage_sessions.php?action=logout_session
  - Get token dari POST
  - Update user_sessions SET is_active=0 WHERE token
  - Clear session variables
  - Jika device saat ini, redirect ke login
```

### Database Schema

```sql
CREATE TABLE user_sessions (
    id_session INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    session_token VARCHAR(255) NOT NULL UNIQUE,
    device_name VARCHAR(255),           -- "Chrome on Windows"
    user_agent TEXT,                    -- Full user agent string
    ip_address VARCHAR(45),             -- IPv4 atau IPv6
    login_time TIMESTAMP DEFAULT NOW(), -- Saat login
    last_activity TIMESTAMP,            -- Last activity update
    is_active BOOLEAN DEFAULT 1,        -- Active atau tidak
    FOREIGN KEY (id_user) REFERENCES user(id_user) ON DELETE CASCADE
);
```

## Testing Multi-Login

### Test Case 1: Login dari 2 Device Berbeda

```
1. Buka browser 1 (Chrome)
   - Pergi ke login page
   - Login dengan akun test
   - Redirect ke dashboard → ✅ Login sukses device 1

2. Buka browser 2 (Firefox) 
   - Pergi ke login page
   - Login dengan akun yang sama
   - Redirect ke dashboard → ✅ Login sukses device 2

3. Periksa manage_sessions.php di kedua browser
   - Browser 1: Should show "Chrome on Windows" dan "Firefox on Windows"
   - Browser 2: Should show "Chrome on Windows" dan "Firefox on Windows"
   - Setiap device punya session token berbeda ✅

4. Logout dari 1 device
   - Di Browser 1: Klik logout device Firefox
   - Browser 1: Tetap aktif ✅
   - Browser 2: Redirect ke login page ✅
```

### Test Case 2: Logout dari Semua Device

```
1. Login dari 3 device berbeda
   - Browser 1, 2, 3 semuanya aktif

2. Di salah satu browser, klik "Logout dari Semua Device"
   - Konfirmasi dialog muncul

3. Hasil:
   - Semua 3 browser akan redirect ke login page ✅
   - Semua session di database set is_active=0 ✅
```

### Test Case 3: Session Persistence

```
1. Login dari device A
2. Close browser window → Close browser completely
3. Buka browser lagi (same device)
4. Akses langsung ke dashboard URL
   
Result:
   - Page should still load (session token di cookie tetap valid)
   - Last activity timestamp updated
   - Session tetap active ✅
```

## FAQ Multi-Login

### Q: Apakah aman login dari multiple device?

**A**: Ya, sangat aman karena:
- Setiap device punya token unik
- Token tidak bisa diprediksi (random 32-byte)
- IP address di-track untuk deteksi anomali
- Anda bisa monitor semua device yang login

### Q: Berapa banyak device yang bisa login bersamaan?

**A**: Tidak ada batasan hard-limit. Tapi best practice adalah:
- Personal device: 2-3 device (phone, laptop, tablet)
- Jika > 5 device mencurigakan → Segera logout

### Q: Apa yang terjadi jika saya logout dari 1 device?

**A**: 
- Device tersebut akan logout
- Device lain tetap aktif
- Harus login kembali di device yang di-logout

### Q: Bagaimana cara tahu device mana yang suspicious?

**A**:
- Buka `/user/manage_sessions.php`
- Lihat IP address dan device name
- Jika ada device/IP yang tidak familiar → Logout dari device tersebut

### Q: Berapa lama session bertahan?

**A**:
- Session active selama token valid
- Token di-cleanup setelah 30 hari inactivity
- User bisa manual logout kapan saja

### Q: Bisa tidak ada orang lain login dengan akun saya?

**A**: Multi-login adalah untuk device/browser berbeda milik Anda sendiri. Jika akun Anda kena hack:

1. Segera ubah password
2. Logout dari semua device
3. Cek email untuk aktivitas mencurigakan
4. Login kembali hanya dari device Anda

## Integrasi dengan Fitur Lain

### Dengan Role-Based Access

Multi-login **tidak support** multiple role sekaligus. Setiap session bind ke 1 role tertentu:

```
Session A: penyewa role → Dashboard penyewa
Session B: pemilik role → Dashboard pemilik
Session C: admin role   → Dashboard admin

Harus login terpisah untuk setiap role di device berbeda
```

### Dengan Wishlist & Booking

Multi-login **fully compatible** dengan:
- ✅ Wishlist (synced across all devices)
- ✅ Booking history (synced)
- ✅ Notifications (sent to all active devices)
- ✅ Profile updates (reflected everywhere)

## Maintenance & Monitoring

### Admin Monitoring

Admin bisa monitor user sessions via database:

```sql
-- Lihat semua active sessions
SELECT us.*, u.email, u.nama_lengkap
FROM user_sessions us
JOIN user u ON us.id_user = u.id_user
WHERE us.is_active = 1
ORDER BY us.last_activity DESC;

-- Lihat sessions per user
SELECT * FROM user_sessions 
WHERE id_user = ? AND is_active = 1;

-- Cleanup old sessions
DELETE FROM user_sessions 
WHERE last_activity < DATE_SUB(NOW(), INTERVAL 30 DAY);
```

### Performance Optimization

Tabel `user_sessions` sudah diindex untuk performance:

```sql
INDEX idx_user (id_user)       -- Optimize getUserSessions query
INDEX idx_token (session_token) -- Optimize validateSessionToken query
INDEX idx_active (is_active)   -- Optimize cleanup queries
```

## Troubleshooting

### Issue: Tidak bisa login di device kedua

**Solution**:
1. Clear cookies browser kedua
2. Cek apakah email password benar
3. Cek apakah akun sudah active
4. Coba login di incognito/private mode

### Issue: Device lama masih show active padahal sudah dimatikan

**Solution**:
1. Device akan auto-inactive setelah 30 hari
2. Manual logout dari manage_sessions.php
3. Atau gunakan "Logout dari Semua Device"

### Issue: Session hilang padahal belum logout

**Solution**:
1. Browser crash/close → Session token di cookie hilang
2. Login ulang untuk generate session baru
3. Session di database tidak hilang, hanya di browser

## Kesimpulan

Fitur Multi-Login KosConnect memberikan:

✅ **Fleksibilitas**: Login dari berbagai device  
✅ **Keamanan**: Token tracking, device monitoring, IP logging  
✅ **Kontrol**: Bisa logout device tertentu atau semua  
✅ **Productivity**: Stay login di multiple device tanpa hassle  

Gunakan fitur ini dengan bijak dan monitor secara berkala untuk menjaga keamanan akun Anda! 🔐
