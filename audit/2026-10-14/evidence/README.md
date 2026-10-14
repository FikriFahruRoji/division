# Menjalankan kembali bukti audit

Jalankan dari root proyek `/Users/fikrifahruroji/Documents/gravity project/ttd-digital` pada checkout yang memiliki dependencies terpasang. Feature tests menggunakan konfigurasi PHPUnit proyek (SQLite `:memory:`, test environment) dan fake storage. Jangan mengganti konfigurasi DB tests menjadi database operasional.

```bash
php vendor/bin/phpunit --no-progress audit/2026-10-14/evidence/AuthSecurityReproductionTest.php
php vendor/bin/phpunit --no-progress audit/2026-10-14/evidence/VerificationSecurityReproductionTest.php
php audit/2026-10-14/evidence/ttd-sqa-signing-proof.php
php audit/2026-10-14/evidence/ttd-sqa-signing-race-proof.php
```

Feature tests sengaja mengonfirmasi perilaku rentan yang ditemukan. Setelah perbaikan, hasil aman akan membuat assertion lama gagal. Ubah tests menjadi expected denial/invalid setelah remediasi.

Proof PDF menggunakan sertifikat sintetis dan temp directory yang dibersihkan. Ia menguji pola import/sign FPDI/TCPDF yang sama dengan SigningService, bukan seluruh alur aplikasi. Proof race menggunakan service asli dengan rendering stub dan controlled interleaving untuk isolasi konsistensi state; tidak menguji beban HTTP nyata. Kedua script memiliki path root absolut dan harus disesuaikan jika checkout dipindah.

`ttd-sqa-composer-audit.json` adalah output Composer `audit --locked --format=json`. `ttd-sqa-npm-advisories.json` berasal dari API bulk registry npm terhadap lockfile, bukan full npm audit. Script npm query memuat lokasi output `/tmp` dan runtime tidak disertakan. Hasil advisory adalah snapshot tanggal audit dan dapat berubah.

`source-snapshot-sha256.json` merekam hash source yang relevan setelah perubahan NIP/NIDN bersamaan. Tidak memuat `.env`, nilai secret, key privat, database operasional atau dokumen pengguna.
