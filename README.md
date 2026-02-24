# KosConnect - Aplikasi Manajemen Kost

Platform terpadu untuk pencarian dan manajemen kost di Indonesia.

## 🚀 Quick Start

### Local Development
1. Clone repository
2. Setup database:
   - Create database `kosconnect`
   - Run migrations in `migrations/` folder in order
3. Akses `http://localhost/KosConnect`

### Production Deployment
Lihat [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md) untuk panduan lengkap deploy ke hosting.

## 📋 Requirements

- PHP 7.4+
- MySQL 5.7+
- Apache/Nginx
- Composer (untuk dependencies)

## 🔧 Configuration

Edit `config/db.php` dengan kredensial database Anda:

```php
$host = "your_host";
$user = "your_username";
$pass = "your_password";
$dbname = "your_database";
```

## 📁 Project Structure

```
KosConnect/
├── admin/          # Admin dashboard
├── auth/           # Authentication
├── config/         # Configuration files
├── css/            # Stylesheets
├── dashboard/      # User dashboards
├── img/            # Images
├── pemilik_kos/    # Owner features
├── user/           # User features
├── uploads/        # User uploads
└── index.php       # Landing page
```

## 🌟 Features

- 🔍 Pencarian kos dengan filter
- 🏠 Manajemen kos untuk pemilik
- 💳 Sistem pembayaran QRIS
- 📊 Dashboard analytics
- 💬 Chat dengan pemilik
- 📱 Mobile responsive


## 🖼️ Galeri Aplikasi

Bagian ini menampilkan diagram Use Case dan tangkapan layar (screenshot) dari aplikasi KosConnect.

### 📊 Use Case Diagram
Simpan gambar diagram use case Anda di folder: `assets/images/documentation/use_cases/`
contoh: `assets/images/documentation/use_cases/use_case_main.png`

![Use Case Diagram](assets/images/documentation/use_cases/your_use_case_here.png)

### 💻 Tampilan Website
Simpan screenshot aplikasi Anda di folder: `assets/images/documentation/screenshots/`

#### Landing Page
![Landing Page](assets/images/documentation/screenshots/landing_page.png)

#### Dashboard User
![User Dashboard](assets/images/documentation/screenshots/user_dashboard.png)

#### Dashboard Admin
![Admin Dashboard](assets/images/documentation/screenshots/admin_dashboard.png)

## 🔐 Default Login

**Admin:**
- Email: admin@kosconnect.com
- Password: admin123

## 📞 Support

Email: info@kosconnect.com

## 📄 License

© 2025 KosConnect. All rights reserved.
