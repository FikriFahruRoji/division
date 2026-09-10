<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel 12">
  <img src="https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/TailwindCSS-3-06B6D4?style=for-the-badge&logo=tailwindcss&logoColor=white" alt="TailwindCSS">
  <img src="https://img.shields.io/badge/Alpine.js-3-8BC0D0?style=for-the-badge&logo=alpinedotjs&logoColor=white" alt="Alpine.js">
  <img src="https://img.shields.io/badge/Vite-7-646CFF?style=for-the-badge&logo=vite&logoColor=white" alt="Vite">
</p>

# 📝 TTD Digital — Sistem Tanda Tangan Digital Dokumen

**TTD Digital** adalah aplikasi web untuk pengelolaan dan penandatanganan dokumen secara digital. Aplikasi ini dirancang untuk organisasi/instansi yang membutuhkan proses tanda tangan dokumen yang aman, terverifikasi, dan dapat diaudit — menggantikan alur tanda tangan konvensional (basah) dengan solusi digital berbasis sertifikat dan QR Code.

---

## ✨ Fitur Utama

### 📄 Manajemen Dokumen
- Upload dokumen PDF dengan metadata lengkap (nomor surat, tanggal, jenis, klasifikasi)
- Versioning dokumen — setiap revisi tercatat dan terhubung ke versi sebelumnya
- Preview & download dokumen langsung dari browser
- Status lifecycle: `Draft` → `Pending Signature` → `Signed Valid` / `Rejected` / `Revoked` / `Superseded`

### ✍️ Tanda Tangan Digital
- **Sertifikat digital (X.509)** — setiap penandatangan memiliki sertifikat PKI yang di-generate otomatis (RSA 2048-bit)
- **QR Code verifikasi** — di-embed ke dalam PDF dengan posisi yang bisa dipilih oleh penandatangan
- **Multi-signer support** dengan tiga mode:
  - `Single` — satu penandatangan
  - `Sequential` — penandatangan berurutan sesuai urutan yang ditentukan
  - `Parallel` — semua penandatangan mendapat notifikasi sekaligus
- **Hash integrity** — SHA-256 hash dihitung setelah setiap tanda tangan untuk menjamin keaslian dokumen
- Approve / Reject dokumen dengan alasan penolakan

### ✅ Verifikasi Publik
- Halaman verifikasi publik via QR Code — tanpa perlu login
- Menampilkan status dokumen, daftar penandatangan, fingerprint, dan metadata
- Rate-limited untuk mencegah scraping

### 👥 Manajemen Pengguna & Peran
- **4 role**: Super Admin, Admin, Operator, Signer
- **Super Admin** — akses penuh ke semua departemen
- **Admin** — mengelola pengguna dan dokumen di departemen yang ditugaskan
- **Operator** — upload dan mengelola dokumen
- **Signer** — menandatangani, menyetujui, atau menolak dokumen; bisa upload dokumen sendiri
- Soft delete pengguna untuk menjaga integritas data relasi

### 🏢 Manajemen Departemen
- Organisasi berbasis departemen dengan isolasi data
- Admin hanya melihat dokumen dan pengguna di departemen sendiri (pencegahan IDOR)
- Pengelolaan departemen oleh Super Admin

### 📊 Audit Log
- Pencatatan lengkap setiap aktivitas: upload, finalize, sign, reject, revoke, verifikasi publik
- Dilengkapi metadata tambahan (IP address, user agent, detail aksi)
- Tampilan audit log untuk admin

### 🔔 Notifikasi
- Notifikasi in-app untuk penandatangan saat dokumen siap ditandatangani
- Mark as read / mark all as read

### 🔐 Keamanan
- **Security Headers**: HSTS, CSP, X-Frame-Options, X-Content-Type-Options, Permissions-Policy
- **Rate Limiting**: throttle terpisah untuk login, upload, tanda tangan, verifikasi, dan request web umum
- **Role-based Middleware**: akses fitur dibatasi berdasarkan role pengguna
- **Policy-based Authorization**: setiap aksi dokumen dicek melalui Laravel Policy
- **hCaptcha**: perlindungan bot pada form login/register
- **Google OAuth (Socialite)**: login via akun Google

---

## 🛠️ Tech Stack

| Layer | Teknologi |
|---|---|
| **Framework** | Laravel 12 (PHP 8.2+) |
| **Frontend** | Blade Templates, TailwindCSS 3, Alpine.js 3 |
| **Build Tool** | Vite 7 |
| **Database** | SQLite (default), mendukung MySQL/PostgreSQL |
| **PDF Processing** | TCPDF + FPDI (generate, merge, embed QR & sertifikat digital) |
| **QR Code** | endroid/qr-code |
| **PKI / Sertifikat** | OpenSSL (self-signed X.509, RSA 2048-bit) |
| **Autentikasi** | Laravel Breeze + Laravel Socialite (Google OAuth) |
| **Captcha** | hCaptcha (scyllaly/hcaptcha) |
| **Testing** | PHPUnit 11 |
| **Queue** | Database Queue (async notifications) |

---

## 📁 Struktur Proyek

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── DocumentController.php      # CRUD & lifecycle dokumen
│   │   ├── SignatureController.php      # Alur penandatanganan
│   │   ├── VerificationController.php   # Verifikasi publik via QR
│   │   ├── UserController.php           # Manajemen pengguna (Admin)
│   │   ├── DepartmentController.php     # Manajemen departemen
│   │   ├── AuditLogController.php       # Riwayat audit
│   │   ├── SignerDocumentController.php # Upload mandiri oleh signer
│   │   └── NotificationController.php   # Notifikasi in-app
│   ├── Middleware/
│   │   ├── RoleMiddleware.php           # Pembatasan akses berdasarkan role
│   │   ├── SecurityHeadersMiddleware.php # HTTP security headers
│   │   ├── ThrottleApiRequests.php      # Rate limit API
│   │   └── ThrottleLoginAttempts.php    # Rate limit login
│   └── Policies/
│       ├── DocumentPolicy.php           # Otorisasi aksi dokumen
│       └── UserPolicy.php              # Otorisasi aksi pengguna
├── Models/
│   ├── Document.php                     # Model dokumen (UUID, QR token, versioning)
│   ├── User.php                         # Model pengguna (role, department, soft delete)
│   ├── Signature.php                    # Record tanda tangan digital
│   ├── SignerAssignment.php             # Penugasan penandatangan
│   ├── VerificationRecord.php           # Record verifikasi publik
│   ├── AuditLog.php                     # Log audit
│   └── Department.php                   # Departemen organisasi
└── Services/
    ├── DocumentService.php              # Upload, update file, revoke
    ├── SigningService.php               # Logika penandatanganan & embed sertifikat
    ├── CertificateService.php           # Generate sertifikat X.509 per user
    ├── QrCodeService.php                # Generate & embed QR ke PDF
    └── VerificationService.php          # Logika verifikasi publik
```

---

## 🚀 Instalasi

### Prasyarat

- PHP 8.2 atau lebih baru
- Composer
- Node.js & npm
- Ekstensi PHP: `openssl`, `gd`, `mbstring`, `pdo_sqlite` (atau driver DB lain)

### Langkah Instalasi

```bash
# 1. Clone repository
git clone https://github.com/FikriFahruRoji/division.git
cd division

# 2. Install dependencies & setup otomatis
composer setup
```

Script `composer setup` akan menjalankan secara otomatis:
- `composer install`
- Copy `.env.example` → `.env`
- Generate application key
- Jalankan migrasi database
- `npm install`
- `npm run build`

### Konfigurasi Tambahan (Opsional)

Salin `.env.example` dan sesuaikan:

```bash
cp .env.example .env
```

Konfigurasi yang perlu diperhatikan:

```env
APP_NAME="TTD Digital"
APP_URL=http://localhost:8000

# Database (default SQLite, bisa diganti MySQL/PostgreSQL)
DB_CONNECTION=sqlite

# Queue untuk notifikasi async
QUEUE_CONNECTION=database

# Google OAuth (opsional)
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=

# hCaptcha (opsional)
HCAPTCHA_SITEKEY=
HCAPTCHA_SECRET=
```

---

## 💻 Menjalankan Aplikasi

### Mode Development (Recommended)

```bash
composer dev
```

Perintah ini menjalankan secara bersamaan:
- 🌐 **Laravel Server** — `php artisan serve`
- ⚡ **Vite Dev Server** — hot reload untuk frontend
- 📨 **Queue Worker** — memproses notifikasi async
- 📋 **Laravel Pail** — log viewer real-time

### Mode Manual

```bash
# Terminal 1: Laravel server
php artisan serve

# Terminal 2: Vite dev server
npm run dev

# Terminal 3: Queue worker (untuk notifikasi)
php artisan queue:listen
```

Akses aplikasi di: **http://localhost:8000**

---

## 🧪 Testing

```bash
# Jalankan semua test
composer test

# Atau langsung
php artisan test
```

Test suite mencakup:
- **Feature Tests**: Upload dokumen, penandatanganan, verifikasi, manajemen pengguna, audit log, departemen
- **Unit Tests**: Model Document, User, DocumentPolicy, UserPolicy

---

## 📋 Alur Kerja Aplikasi

```
┌──────────┐    ┌────────────┐    ┌──────────────────┐    ┌──────────────┐
│  Upload   │───▶│  Assign    │───▶│   Finalize &     │───▶│   Signer     │
│  Dokumen  │    │  Signer(s) │    │   Kirim ke       │    │  Approve /   │
│  (Draft)  │    │            │    │   Penandatangan   │    │  Reject      │
└──────────┘    └────────────┘    └──────────────────┘    └──────┬───────┘
                                                                 │
                                          ┌──────────────────────┼──────────────┐
                                          │                      │              │
                                    ┌─────▼─────┐      ┌────────▼───┐   ┌──────▼──────┐
                                    │  Signed    │      │  Rejected  │   │  Revoked    │
                                    │  Valid ✅  │      │  (Edit &   │   │  (by Admin) │
                                    │            │      │  Resubmit) │   │             │
                                    └─────┬──────┘      └────────────┘   └─────────────┘
                                          │
                                    ┌─────▼──────┐
                                    │  QR Code   │
                                    │  Verifikasi│
                                    │  Publik 🔍 │
                                    └────────────┘
```

---

## 📄 Lisensi

Proyek ini dilisensikan di bawah [MIT License](https://opensource.org/licenses/MIT).
