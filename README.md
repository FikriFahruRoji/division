<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel 12">
  <img src="https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/TailwindCSS-3-06B6D4?style=for-the-badge&logo=tailwindcss&logoColor=white" alt="TailwindCSS">
  <img src="https://img.shields.io/badge/Alpine.js-3-8BC0D0?style=for-the-badge&logo=alpinedotjs&logoColor=white" alt="Alpine.js">
  <img src="https://img.shields.io/badge/Vite-7-646CFF?style=for-the-badge&logo=vite&logoColor=white" alt="Vite">
</p>

# TTD Digital — Sistem Tanda Tangan Digital Dokumen

**TTD Digital** adalah aplikasi web untuk mengelola dan menandatangani dokumen secara digital. Aplikasi ini ditujukan bagi organisasi atau instansi yang membutuhkan proses tanda tangan yang aman, terverifikasi, dan dapat diaudit. TTD Digital menggantikan alur tanda tangan basah dengan proses digital berbasis sertifikat dan QR Code.

---

## Fitur Utama

### Manajemen Dokumen
- Mengunggah dokumen PDF beserta metadata, seperti nomor surat, tanggal, jenis, dan klasifikasi
- Mencatat setiap revisi dan menghubungkannya dengan versi sebelumnya
- Melihat pratinjau dan mengunduh dokumen melalui browser
- Melacak status dokumen: `Draft` → `Pending Signature` → `Signed Valid` / `Rejected` / `Revoked` / `Superseded`

### Tanda Tangan Digital
- **Sertifikat digital (X.509)** — setiap penandatangan mendapat sertifikat PKI yang dibuat otomatis dengan RSA 2048-bit
- **QR Code verifikasi** — disematkan ke PDF pada posisi yang dipilih penandatangan
- **Dukungan banyak penandatangan** dengan tiga mode:
  - `Single` — satu penandatangan
  - `Sequential` — penandatangan mengikuti urutan yang ditentukan
  - `Parallel` — semua penandatangan menerima notifikasi secara bersamaan
- **Integritas hash** — sistem menghitung hash SHA-256 setelah setiap tanda tangan untuk menjaga keaslian dokumen
- Menyetujui atau menolak dokumen dengan menyertakan alasan penolakan

### Verifikasi Publik
- Memverifikasi dokumen melalui QR Code tanpa perlu login
- Melihat status dokumen, daftar penandatangan, fingerprint, dan metadata
- Membatasi jumlah permintaan untuk mencegah scraping

### Pengguna dan Peran
- **Empat peran**: Super Admin, Admin, Operator, dan Signer
- **Super Admin** — memiliki akses ke semua departemen
- **Admin** — mengelola pengguna dan dokumen di departemen yang ditugaskan
- **Operator** — mengunggah dan mengelola dokumen
- **Signer** — menandatangani, menyetujui, atau menolak dokumen; juga dapat mengunggah dokumen sendiri
- Menggunakan soft delete pada pengguna untuk menjaga integritas relasi data

### Departemen
- Mengelompokkan organisasi berdasarkan departemen dan mengisolasi data tiap departemen
- Membatasi Admin agar hanya dapat melihat dokumen dan pengguna di departemennya untuk mencegah IDOR
- Mengelola departemen melalui Super Admin

### Audit Log
- Mencatat aktivitas, termasuk unggah, finalisasi, tanda tangan, penolakan, pencabutan, dan verifikasi publik
- Menyimpan metadata tambahan seperti alamat IP, user agent, dan detail tindakan
- Menyediakan tampilan audit log untuk admin

### Notifikasi
- Mengirim notifikasi dalam aplikasi kepada penandatangan saat dokumen siap ditandatangani
- Menandai satu atau semua notifikasi sebagai telah dibaca

### Keamanan
- **Security headers** — HSTS, CSP, X-Frame-Options, X-Content-Type-Options, dan Permissions-Policy
- **Rate limiting** — membatasi permintaan untuk login, unggah, tanda tangan, verifikasi, dan akses web umum
- **Middleware berbasis peran** — membatasi akses fitur berdasarkan peran pengguna
- **Otorisasi berbasis policy** — memeriksa setiap tindakan terhadap dokumen melalui Laravel Policy
- **hCaptcha** — melindungi formulir login dan pendaftaran dari bot
- **Google OAuth (Socialite)** — mendukung login dengan akun Google

---

## Tech Stack

| Layer | Teknologi |
|---|---|
| **Framework** | Laravel 12 (PHP 8.2+) |
| **Frontend** | Blade Templates, TailwindCSS 3, Alpine.js 3 |
| **Build Tool** | Vite 7 |
| **Database** | SQLite (default), mendukung MySQL/PostgreSQL |
| **Pemrosesan PDF** | TCPDF + FPDI (membuat dan menggabungkan PDF, menyematkan QR Code dan sertifikat digital) |
| **QR Code** | endroid/qr-code |
| **PKI / Sertifikat** | OpenSSL (sertifikat X.509 self-signed, RSA 2048-bit) |
| **Autentikasi** | Laravel Breeze + Laravel Socialite (Google OAuth) |
| **Captcha** | hCaptcha (scyllaly/hcaptcha) |
| **Pengujian** | PHPUnit 11 |
| **Queue** | Database Queue (notifikasi asinkron) |

---

## Struktur Proyek

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── DocumentController.php      # CRUD & siklus dokumen
│   │   ├── SignatureController.php      # Alur penandatanganan
│   │   ├── VerificationController.php   # Verifikasi publik melalui QR Code
│   │   ├── UserController.php           # Manajemen pengguna (Admin)
│   │   ├── DepartmentController.php     # Manajemen departemen
│   │   ├── AuditLogController.php       # Riwayat audit
│   │   ├── SignerDocumentController.php # Unggah mandiri oleh Signer
│   │   └── NotificationController.php   # Notifikasi dalam aplikasi
│   ├── Middleware/
│   │   ├── RoleMiddleware.php           # Pembatasan akses berdasarkan peran
│   │   ├── SecurityHeadersMiddleware.php # HTTP security headers
│   │   ├── ThrottleApiRequests.php      # Pembatasan permintaan API
│   │   └── ThrottleLoginAttempts.php    # Pembatasan percobaan login
│   └── Policies/
│       ├── DocumentPolicy.php           # Otorisasi tindakan pada dokumen
│       └── UserPolicy.php              # Otorisasi tindakan pada pengguna
├── Models/
│   ├── Document.php                     # Model dokumen (UUID, token QR, versioning)
│   ├── User.php                         # Model pengguna (peran, departemen, soft delete)
│   ├── Signature.php                    # Data tanda tangan digital
│   ├── SignerAssignment.php             # Penugasan penandatangan
│   ├── VerificationRecord.php           # Data verifikasi publik
│   ├── AuditLog.php                     # Log audit
│   └── Department.php                   # Departemen organisasi
└── Services/
    ├── DocumentService.php              # Unggah, pembaruan file, dan pencabutan
    ├── SigningService.php               # Proses tanda tangan dan penyematan sertifikat
    ├── CertificateService.php           # Pembuatan sertifikat X.509 per pengguna
    ├── QrCodeService.php                # Pembuatan dan penyematan QR Code ke PDF
    └── VerificationService.php          # Proses verifikasi publik
```

---

## Instalasi

### Prasyarat

- PHP 8.2 atau lebih baru
- Composer
- Node.js dan npm
- Ekstensi PHP: `openssl`, `gd`, `mbstring`, `pdo_sqlite` (atau driver database lain)

### Langkah Instalasi

```bash
# 1. Clone repository
git clone https://github.com/FikriFahruRoji/division.git
cd division

# 2. Install dependencies dan jalankan setup
composer setup
```

Perintah `composer setup` menjalankan langkah-langkah berikut:

- `composer install`
- Menyalin `.env.example` menjadi `.env`
- Membuat application key
- Menjalankan migrasi database
- `npm install`
- `npm run build`

### Konfigurasi Tambahan (Opsional)

Salin `.env.example`, lalu sesuaikan nilainya:

```bash
cp .env.example .env
```

Perhatikan konfigurasi berikut:

```env
APP_NAME="TTD Digital"
APP_URL=http://localhost:8000

# Database (default SQLite; dapat diganti dengan MySQL atau PostgreSQL)
DB_CONNECTION=sqlite

# Queue untuk notifikasi asinkron
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

## Menjalankan Aplikasi

### Mode Development (Disarankan)

```bash
composer dev
```

Perintah ini menjalankan beberapa proses sekaligus:

- **Laravel Server** — `php artisan serve`
- **Vite Dev Server** — hot reload untuk frontend
- **Queue Worker** — memproses notifikasi asinkron
- **Laravel Pail** — menampilkan log secara real time

### Mode Manual

```bash
# Terminal 1: Laravel server
php artisan serve

# Terminal 2: Vite dev server
npm run dev

# Terminal 3: Queue worker (untuk notifikasi)
php artisan queue:listen
```

Buka aplikasi di **http://localhost:8000**.

---

## Pengujian

```bash
# Jalankan semua test
composer test

# Atau jalankan langsung
php artisan test
```

Test suite mencakup:

- **Feature test**: unggah dokumen, penandatanganan, verifikasi, manajemen pengguna, audit log, dan departemen
- **Unit test**: model Document dan User, serta DocumentPolicy dan UserPolicy

---

## Alur Kerja Aplikasi

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

## Lisensi

Proyek ini menggunakan [MIT License](https://opensource.org/licenses/MIT).

