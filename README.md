# E-Tiket System

Sistem manajemen e-tiket berbasis web menggunakan framework CodeIgniter 4.

## 📋 Daftar Isi

- [Fitur Utama](#fitur-utama)
- [Persyaratan Sistem](#persyaratan-sistem)
- [Instalasi](#instalasi)
- [Konfigurasi](#konfigurasi)
- [Pemakaian](#pemakaian)
- [Struktur Proyek](#struktur-proyek)
- [Contributing](#contributing)
- [Lisensi](#lisensi)

## ✨ Fitur Utama

- Dashboard untuk monitoring tiket
- Manajemen kategori e-tiket
- Manajemen pelaksana/petugas
- Proses penanganan tiket
- Sistem autentikasi dengan role-based access control
- API integration dengan Kanza Bridge

## 📦 Persyaratan Sistem

- PHP 7.4 atau lebih tinggi
- MySQL 5.7 atau MariaDB 10.2
- Composer
- Web Server (Apache, Nginx, atau built-in PHP server)

## 🚀 Instalasi

### 1. Clone Repository dari GitHub

```bash
git clone https://github.com/username/e-tiket.git
cd e-tiket
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Setup Environment

Copy file `env` ke `.env`:

```bash
cp env .env
```

Edit file `.env` dan sesuaikan konfigurasi:

```env
CI_ENVIRONMENT = development

# Database
database.default.hostname = localhost
database.default.username = root
database.default.password = your_password
database.default.database = e_tiket
database.default.DBDriver = MySQLi
database.default.port = 3306

# API Configuration
ROLE_ADMIN = J002
API_KANZA_BRIDGE = 'http://localhost:8080/api/'
```

### 4. Setup Database

Buat database baru:

```bash
mysql -u root -p -e "CREATE DATABASE e_tiket CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Jalankan migrasi:

```bash
php spark migrate
```

Jalankan seeder (opsional):

```bash
php spark db:seed
```

### 5. Generate Encryption Key

```bash
php spark key:generate
```

### 6. Set Permissions (Linux/Mac)

```bash
chmod -R 755 writable/
chmod -R 755 public/
```

## ⚙️ Konfigurasi

### Database Configuration

Edit file `app/Config/Database.php` atau gunakan `.env`:

```php
public array $default = [
    'DBDriver' => 'MySQLi',
    'hostname' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'e_tiket',
    'DBPrefix' => '',
    'port'     => 3306,
    'charset'  => 'utf8mb4',
    'DBCollat' => 'utf8mb4_unicode_ci',
];
```

### API Configuration

Edit file `.env` untuk konfigurasi API:

```env
API_KANZA_BRIDGE = 'http://localhost:8080/api/'
```

### Role Configuration

Admin role dapat diatur di `.env`:

```env
ROLE_ADMIN = J002
```

## 📖 Pemakaian

### Menjalankan Development Server

```bash
php spark serve
```

Server akan berjalan di `http://localhost:8080`

### Login ke Aplikasi

Akses aplikasi di browser:

```
http://localhost:8080
```

Gunakan kredensial yang telah dibuat saat setup awal.

### Routes Utama

- **Dashboard** - `/dashboard`
- **E-Ticket** - `/eticket`
- **Kategori E-Ticket** - `/kategori-eticket`
- **Pelaksana** - `/pelaksana`
- **Admin** - `/admin`

### Struktur Folder Penting

```
app/
├── Controllers/      # Kontroler aplikasi
├── Models/          # Model database
├── Views/           # Template tampilan
├── Config/          # File konfigurasi
├── Database/
│   ├── Migrations/  # Database migrations
│   └── Seeds/       # Database seeders
└── Filters/         # Route filters & middleware

public/             # Folder publik (CSS, JS, assets)
vendor/             # Dependencies dari Composer
writable/           # Folder yang dapat ditulis (cache, logs, uploads)
```

## 🗂️ Struktur Proyek

### Controllers

- `BaseController.php` - Controller basis untuk semua controller
- `Dashboard.php` - Dashboard utama
- `ETicket.php` - Manajemen e-tiket
- `KategoriETiket.php` - Manajemen kategori
- `Pelaksana.php` - Manajemen pelaksana
- `Auth.php` - Autentikasi
- `Admin.php` - Panel admin

### Models

- `ETicketModel.php` - Model e-tiket
- `ETicketProsesModel.php` - Model proses e-tiket
- `KategoriETiketModel.php` - Model kategori
- `UsersModel.php` - Model pengguna
- `ProsesModel.php` - Model proses

### Views

Setiap fitur memiliki folder view terpisah:

- `Views/e-tiket/` - View e-tiket
- `Views/kategoriEticket/` - View kategori
- `Views/pelaksana/` - View pelaksana

## 🔧 Commands Useful

### Database

```bash
# Jalankan migrasi
php spark migrate

# Rollback migrasi
php spark migrate:rollback

# Refresh database
php spark migrate:refresh

# Jalankan seeder
php spark db:seed
```

### Generate

```bash
# Generate controller
php spark make:controller NamaController

# Generate model
php spark make:model NamaModel

# Generate migration
php spark make:migration create_nama_table
```

### Cache & Logs

```bash
# Clear cache
php spark cache:clear

# Clear all caches
php spark cache:clear:all
```

## 🐛 Troubleshooting

### Error: Database Connection Failed

- Pastikan MySQL/MariaDB sudah running
- Cek username, password, dan nama database di `.env`
- Pastikan database sudah dibuat

### Error: Folder writable tidak writable

```bash
# Linux/Mac
chmod -R 755 writable/

# Windows (Run as Administrator)
icacls "writable" /grant Everyone:F /T
```

### Error: 404 Page Not Found

- Pastikan `.htaccess` ada di folder `public/` (untuk Apache)
- Untuk Nginx, konfigurasi sesuai dokumentasi CodeIgniter 4
- Cek routes di `app/Config/Routes.php`

## 📝 Migrasi Data

Untuk memindahkan data dari server lain:

```bash
# Export database dari server lama
mysqldump -u root -p e_tiket > e_tiket_backup.sql

# Import ke server baru
mysql -u root -p e_tiket < e_tiket_backup.sql
```

## 🤝 Contributing

1. Fork repository ini
2. Buat branch fitur (`git checkout -b feature/AmazingFeature`)
3. Commit perubahan (`git commit -m 'Add some AmazingFeature'`)
4. Push ke branch (`git push origin feature/AmazingFeature`)
5. Buka Pull Request

## 📄 Lisensi

Project ini dilisensikan di bawah MIT License. Lihat file [LICENSE](LICENSE) untuk detail.

## 📞 Support

Untuk pertanyaan atau masalah, silakan buka issue di GitHub atau hubungi tim development.

---

**Last Updated:** 2026-02-17
