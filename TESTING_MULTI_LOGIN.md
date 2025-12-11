# 🧪 Multi-Login Testing & Implementation Guide

## Status Implementasi ✅

### ✅ Selesai Diimplementasikan

- [x] **SessionManager** - Manage session tokens per device
- [x] **SessionChecker** - Validate session tokens
- [x] **loginForm.php** - Remove hard redirect (allow multi-login)
- [x] **manage_sessions.php** - View & manage all active sessions
- [x] **logout.php** - Support logout from specific device
- [x] **Profile Modal** - Link to session management
- [x] **Database** - user_sessions table exists
- [x] **Documentation** - Complete README

## Cara Menggunakan Multi-Login

### Untuk End User

#### 1. Login Biasa (Pertama Kali)

```
1. Buka https://kosconnect.local/auth/loginForm.php
2. Input email & password
3. Pilih role (penyewa, pemilik, atau auto-detect admin)
4. Klik "Login"
5. Redirect ke dashboard sesuai role
6. Session token sudah tersimpan di browser cookie ✅
```

#### 2. Login dari Device/Browser Lain
Booking Anda untuk kamar 'Superior Double' di 'Capital Kost' berhasil dibuat. Silakan tunggu konfirmasi dari pemilik kos.

27 Nov 2025 13:22
Lihat Detail →
Booking Anda untuk kamar 'Superior Double' di 'Capital Kost' berhasil dibuat. Silakan tunggu konfirmasi dari pemilik kos.

27 Nov 2025 13:22
Lihat Detail →''''''\'\

```
1. Buka device/browser baru
2. Pergi ke login page
3. Login dengan email & password sama
4. ✅ BOTH DEVICE NOW ACTIVE SIMULTANEOUSLY
5. Tidak ada redirect loop, tidak force logout device lama
```

#### 3. Manage Active Sessions

```
Path 1: Via Dashboard
- Klik avatar/profil user
- Klik "Profil Saya"
- Scroll ke bagian "Kelola Device"
- Klik "Lihat Semua Device"

Path 2: Direct URL
- /user/manage_sessions.php

Result:
- Lihat semua device yang login
- Lihat IP address setiap device
- Lihat waktu login terakhir
- Bisa logout device tertentu
- Bisa logout semua device sekaligus
```

#### 4. Logout dari Device Tertentu

```
Di manage_sessions.php:
1. Cari device yang ingin di-logout
2. Klik tombol "Logout" pada device tersebut
3. Confirm dialog muncul
4. Klik "OK"
5. ✅ Device tersebut logout, device lain tetap aktif
```

#### 5. Emergency: Logout dari Semua Device

```
Di manage_sessions.php:
1. Scroll ke bawah
2. Klik tombol "Logout dari Semua Device" (warna merah)
3. Confirm dialog: "Logout dari SEMUA device? Anda harus login kembali."
4. Klik "OK"
5. ✅ SEMUA device akan logout sekaligus
6. Harus login kembali dari awal
```

## Testing Scenarios

### ⚡ Quick Test (5 menit)

```
1. Buka 2 browser tab / 2 browser window / 2 private window

TAB 1:
- Go to auth/loginForm.php
- Login dengan akun test@example.com / password123
- Pilih role "penyewa"
- Check: Masuk ke dashboard ✅

TAB 2:
- Go to auth/loginForm.php SAME TIME
- Login dengan akun TEST SAMA
- Pilih role "penyewa"
- Check: Masuk ke dashboard ✅

KEDUA TAB:
- Klik profile → Profil Saya → Kelola Device
- Check: HARUS MUNCUL 2 SESSION (2 browser/tab)
  - Session 1: "Chrome on Windows" dari Tab 1
  - Session 2: "Firefox on Windows" dari Tab 2
  - Atau "Chrome on Windows" x2 jika same browser (beda tab)
✅ MULTI-LOGIN WORKING

CLEANUP:
- Logout dari TAB 2 saja
- Check: TAB 1 still active ✅
- Check: TAB 2 redirected to login ✅
```

### 🔒 Security Test (10 menit)

```
SCENARIO 1: Logout Specific Device
- Login dari 3 device (A, B, C)
- Di device A, logout device B saja
- Check: A tetap login ✅, B logout ✅, C tetap login ✅

SCENARIO 2: Logout All Devices
- Login dari 3 device (A, B, C)
- Di device A, klik "Logout dari Semua Device"
- Check: A, B, C SEMUANYA logout ✅

SCENARIO 3: Session Persistence
- Login dari Device A
- Close browser window → Close browser completely
- Buka browser lagi (tapi jangan clear cookies)
- Akses langsung ke dashboard URL
- Check: Dashboard tetap load ✅ (session cookie valid)

SCENARIO 4: Invalid Token
- Manual delete session_token cookie
- Refresh dashboard
- Check: Redirect to login ✅
```

### 🌐 Cross-Browser Test (15 menit)

```
BROWSER 1: Chrome
- Login penyewa role
- Dashboard load ✅

BROWSER 2: Firefox  
- Login pemilik role (same email)
- Dashboard owner load ✅

BROWSER 3: Edge
- Login penyewa role (same email)
- Dashboard load ✅

manage_sessions.php:
- Check: 3 sessions dengan device name berbeda ✅
  - "Chrome on Windows"
  - "Firefox on Windows"  
  - "Edge on Windows"
- Check: 3 IP address (bisa sama jika local computer) ✅
- Check: 3 session token berbeda ✅
```

### 📱 Multi-Device Test (20 menit)

```
DEVICE 1: Desktop Windows (Chrome)
- Login test@example.com
- Bookmark dashboard

DEVICE 2: Android Phone (Chrome)
- Open wifi (same network/internet)
- Login test@example.com (same email)
- Save bookmarks

DEVICE 3: iPad/Tablet (Safari)
- Same network
- Login test@example.com
- Save bookmarks

Each device:
- Open manage_sessions.php
- Check: SHOULD SHOW 3 SESSIONS ✅
  - "Chrome on Windows"
  - "Chrome on Android"
  - "Safari on iPad"
- Check: 3 IP address BISA BERBEDA (jika dari provider berbeda) ✅

Interoperability:
- Update profile di DEVICE 1
- DEVICE 2 & 3 refresh
- Check: Update reflected everywhere ✅
- Wishlist add di DEVICE 2
- DEVICE 1 & 3 check wishlist
- Check: Wishlist synced ✅
```

## Debugging Commands

### Check Active Sessions (SQL)

```sql
-- Lihat semua active sessions per user
SELECT * FROM user_sessions 
WHERE is_active = 1
ORDER BY last_activity DESC;

-- Lihat sessions user tertentu
SELECT * FROM user_sessions 
WHERE id_user = 1 AND is_active = 1;

-- Count sessions per user
SELECT id_user, COUNT(*) as session_count
FROM user_sessions
WHERE is_active = 1
GROUP BY id_user
ORDER BY session_count DESC;

-- Lihat sessions lebih dari 1 jam tidak aktif
SELECT * FROM user_sessions 
WHERE is_active = 1 
AND last_activity < DATE_SUB(NOW(), INTERVAL 1 HOUR);
```

### Check Browser Console (JavaScript)

```javascript
// Check current session token
console.log(document.cookie)
// Output: session_token=abc123def456...

// Check all cookies
document.cookie.split(';').forEach(cookie => {
  console.log(cookie.trim())
})

// Check session storage
console.log(sessionStorage)
```

### Check Network Tab (Browser DevTools)

```
1. Buka Developer Tools (F12)
2. Tab "Network"
3. Refresh page
4. Lihat request ke manage_sessions.php
5. Check: Response status 200 ✅
6. Check: Session data kembali dengan benar
```

## Possible Issues & Solutions

### ❌ Issue 1: Cookie Tidak Tersimpan

**Symptom**: Logout dari tab lain, tapi session masih aktif

**Cause**: 
- Cookie setting di browser tidak tepat
- SameSite cookie policy

**Solution**:
```php
// Di loginForm.php, session cookie settings
setcookie('session_token', $session_token, 
    time() + (30 * 24 * 60 * 60),  // 30 hari
    '/',                            // Path root
    '',                             // Domain
    isset($_SERVER["HTTPS"]),       // Secure (only HTTPS)
    true                            // HttpOnly
);
```

### ❌ Issue 2: Session Token Tidak Valid

**Symptom**: Redirect to login meskipun sudah login

**Cause**:
- Token di database tidak match
- Cookie sudah dihapus
- Session expired

**Solution**:
1. Clear cookies: Ctrl+Shift+Del → Clear cookies
2. Login ulang
3. Check database: `SELECT * FROM user_sessions WHERE session_token = 'xxx'`

### ❌ Issue 3: Redirect Loop di Login

**Symptom**: Klik login terus, selalu kembali ke login page

**Cause**:
- Header sudah dikirim sebelum redirect
- Session variable tidak set dengan benar

**Solution**:
1. Cek error log: `php_errors.log`
2. Pastikan `session_start()` di awal file
3. Pastikan tidak ada output sebelum redirect

### ❌ Issue 4: Multiple Sessions Tidak Muncul

**Symptom**: Login dari 2 device, tapi manage_sessions.php cuma show 1

**Cause**:
- Cache browser
- SessionManager tidak properly save session
- Database connection error

**Solution**:
1. Hard refresh: Ctrl+Shift+R
2. Clear cache
3. Check database: `SELECT COUNT(*) FROM user_sessions WHERE id_user = ?`
4. Check error log

## Performance Optimization

### Database Indexes ✅
Already implemented:
```sql
INDEX idx_user (id_user)        -- Fast lookup by user
INDEX idx_token (session_token) -- Fast token validation
INDEX idx_active (is_active)    -- Fast active session queries
```

### Session Cleanup ✅
Automatic cleanup di SessionManager:
```php
public function cleanExpiredSessions() {
    // Delete sessions older than 30 days
    DELETE FROM user_sessions 
    WHERE last_activity < DATE_SUB(NOW(), INTERVAL 30 DAY)
}
```

### Recommendation
Jalankan cleanup seminggu sekali (bisa via cron job):
```bash
0 0 * * 0 php /path/to/cleanup_sessions.php
```

## Deployment Checklist

- [x] Database migration applied (user_sessions table exists)
- [x] SessionManager class loaded di loginForm.php
- [x] SessionChecker included di semua protected pages
- [x] Login form tidak ada hard redirect di awal
- [x] logout.php support specific device logout
- [x] manage_sessions.php page created
- [x] Profile modal punya link ke manage_sessions
- [x] Documentation written
- [x] All files punya proper error handling

## Next Steps (Optional Enhancements)

1. **Admin Dashboard**: Show all user sessions globally
   ```php
   // admin/view_all_sessions.php
   - List semua active sessions dari semua user
   - Filter by user, device, IP
   - Admin bisa force logout jika suspicious
   ```

2. **Notifications**: Alert user jika ada login baru
   ```php
   // Send email: "New login detected from [Device] [IP] [Time]"
   ```

3. **Geolocation**: Show device location based on IP
   ```php
   // Integrate MaxMind GeoIP API
   // Show: "Chrome on Windows - New York, USA"
   ```

4. **Device Trust**: Remember device, reduce 2FA requirement
   ```php
   // user_sessions table add: trusted_device BOOLEAN
   // New device = require verification
   // Trusted device = skip verification
   ```

5. **Activity Log**: Track what user did di masing-masing device
   ```php
   // Create user_activity table
   - session_id
   - action (view_kos, booking, etc)
   - timestamp
   ```

## Conclusion

✅ **Multi-Login System Fully Implemented!**

Sistem sudah siap untuk production dengan:
- Multi-device support
- Session management
- Security tracking
- User-friendly interface
- Complete documentation

Happy testing! 🚀
