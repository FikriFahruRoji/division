# Plan Perbaikan: Menuju TTE yang Setara Tanda Tangan Basah

Status: draft · Dibuat: 2026-10-19

## Tujuan

Mengubah aplikasi dari "sistem approval internal + QR verifikasi" menjadi platform
Tanda Tangan Elektronik (TTE) yang memenuhi Pasal 11 UU ITE dan PP 71/2019, dengan
target akhir **TTE tersertifikasi** melalui PSrE yang diakui Komdigi.

## Kondisi saat ini (ringkas)

| # | Masalah | Lokasi |
|---|---------|--------|
| G1 | PDF ditandatangani satu kunci sistem (`system_seal`), bukan kunci milik penanda tangan | `app/Services/SigningService.php` (`applyVisualSignature`) |
| G2 | Sertifikat self-signed, bukan dari PSrE | `app/Services/CertificateService.php` |
| G3 | Private key tersimpan polos tanpa passphrase/HSM; ada kunci per-user sisa (`2.key`) dan fungsi `getUserCertificate` yang tidak dipakai | `storage/app/private/certificates/`, `CertificateService` |
| G4 | Tidak ada autentikasi ulang (OTP/PIN/password) saat menandatangani | `routes/web.php` → `signatures.approve` |
| G5 | Tidak ada timestamp terpercaya (TSA) dan LTV. TCPDF `applyTSA()` hanya stub `@TODO`. Kolom `tsa_token`, `ocsp_response`, `certificate_serial` tidak pernah diisi | `vendor/tecnickcom/tcpdf`, tabel `signatures` |
| G6 | Verifikasi hanya membandingkan file di server dengan hash di DB yang sama; penerima tidak bisa mengecek PDF yang mereka pegang | `app/Services/VerificationService.php` |
| G7 | Audit log bisa diubah/dihapus (tabel biasa, SQLite) | `app/Models/AuditLog.php`, `.env.example` |
| G8 | Re-finalisasi menghapus tanda tangan lama (`signatures()->delete()`) sehingga riwayat hilang | `SigningService::finalizeDocument` |
| G9 | Penandatanganan me-*render* ulang seluruh halaman via FPDI, bukan *incremental update*, sehingga tanda tangan digital sebelumnya hilang. Ini menghalangi multi-signer yang sah | `SigningService::applyVisualSignature` |
| G10 | Halaman verifikasi publik menampilkan NIP/NIDN, yang perlu ditinjau terhadap UU PDP | `VerificationService::verify` |

## Prinsip

1. **Aplikasi = orkestrator alur kerja, PSrE = penanda tangan.** Kita tidak membangun PKI sendiri.
2. **Abstraksi provider sejak awal.** Semua penandatanganan lewat satu interface supaya `LocalSeal` (dev/internal) dan `Psre` bisa ditukar tanpa mengubah alur.
3. **Bukti tidak boleh bisa diubah diam-diam.** Riwayat tanda tangan dan audit bersifat append-only.
4. **Verifikasi independen.** Orang luar harus bisa memvalidasi PDF tanpa harus memercayai server kita.

---

## Fase 0: Keputusan (manusia, sebelum coding besar)

| ID | Tugas | Output | Label |
|----|-------|--------|-------|
| D1 | Tentukan target hukum: TTE tersertifikasi (PSrE) atau tidak tersertifikasi yang diperkuat | ADR `docs/adr/0001-target-tte.md` | `ready-for-human` |
| D2 | Pilih PSrE. Instansi pemerintah/PTN → BSrE BSSN; swasta → Privy/Peruri/VIDA/dll. Bandingkan API, biaya per tanda tangan, model enrollment, dan dukungan PAdES-LTA | ADR `0002-pilihan-psre.md` | `ready-for-human` |
| D3 | Konsultasi bagian hukum: jenis dokumen apa saja yang boleh memakai TTE, siapa yang berwenang menandatangani, dan aturan retensi | Draf SK/Peraturan Pimpinan tentang TTE | `ready-for-human` |
| D4 | Tentukan engine PDF signing. TCPDF tidak mendukung TSA/PAdES/incremental update. Opsi: (a) pakai SDK/endpoint PSrE yang mengembalikan PDF tertandatangani, (b) sidecar khusus signing (mis. pyHanko atau EU DSS) | ADR `0003-pdf-signing-engine.md` | `ready-for-human` |

> Fase 1 bisa berjalan paralel dengan Fase 0 karena tidak bergantung pada pilihan PSrE.

---

## Fase 1: Pengerasan yang tidak bergantung PSrE

Tujuan: menutup celah paling berbahaya sekarang, dan menyiapkan fondasi untuk Fase 2.

### T1. Autentikasi ulang saat menandatangani (G4) ✅ Selesai 2026-10-19
> Dipilih TOTP (RFC 6238), bukan OTP email: login Google + OTP ke Gmail yang sama hanya satu faktor.
> Approve & reject wajib kode 6 digit; kode sekali pakai (anti-replay per time-step), maks. 5 salah per 5 menit.
> Secret terenkripsi di DB; `signatures.auth_method` dan audit log mencatat `totp`; admin dapat mereset 2FA user di departemennya.
> Belum: 2FA saat login (baru diwajibkan saat aksi tanda tangan), kode cadangan (recovery codes).
- Tambahkan TOTP 2FA (wajib untuk role `signer` dan `admin`) **atau** OTP email/WA berumur pendek di halaman approve.
- Langkah approve dan reject wajib memasukkan OTP. Simpan metode autentikasi di `signatures.metadata` dan audit log.
- **Selesai jika:** POST `signatures.approve` tanpa OTP valid ditolak (ada test), OTP sekali pakai dan kedaluwarsa ≤ 5 menit.
- Label: `ready-for-agent`

### T2. Abstraksi `SignatureProvider` (G1, persiapan Fase 2)
- Interface `App\Signing\SignatureProvider` dengan `sign(Document, User, SigningContext): SignedResult`.
- Implementasi pertama `LocalSealProvider` (memindahkan logika `applyVisualSignature` yang sudah ada). Provider dipilih lewat config.
- `SignedResult` membawa: path PDF, hash, serial sertifikat, token TSA (nullable), dan id transaksi provider.
- **Selesai jika:** semua test signing tetap hijau, `SigningService` tidak lagi memanggil TCPDF langsung.
- Label: `ready-for-agent`

### T3. Amankan kunci system seal (G3) ✅ Selesai 2026-10-19
> Kunci dienkripsi AES-256 dengan `SIGNING_SEAL_PASSPHRASE` (wajib, min. 16 karakter); kunci polos ditolak.
> `php artisan seal:rotate` mengarsipkan sertifikat lama ke `certificates/archive/` dan menerbitkan kunci baru.
> Kode & file sertifikat per-user dihapus; disk privat tidak lagi di-serve lewat `/storage/{path}`.
> Belum: secret manager/KMS di server produksi dan pengecualian kunci dari backup (tugas infra, lihat T6).

- Enkripsi private key dengan passphrase dari env/secret manager, dan berikan ke `setSignature()` lewat argumen password.
- Hapus `getUserCertificate`/`generateCertificate` beserta file `storage/app/private/certificates/2.*`.
- Batasi permission file (0600), keluarkan dari backup yang tidak terenkripsi, dan dokumentasikan rotasi kunci.
- Label sertifikat diubah menjadi jujur: "Segel Sistem Internal (tidak tersertifikasi PSrE)".
- **Selesai jika:** kunci di disk terenkripsi, tidak ada kunci per-user tersisa.
- Label: `ready-for-agent`

### T4. Riwayat tanda tangan append-only (G8)
- Ganti `signatures()->delete()` saat re-finalisasi menjadi penandaan `superseded_at`/`status = superseded`.
- Dokumen yang direvisi mendapat versi baru (`version`++). Hash setiap versi disimpan.
- **Selesai jika:** setelah reject → revisi → sign ulang, semua tanda tangan lama masih ada dengan status superseded (ada test).
- Label: `ready-for-agent`

### T5. Audit log tahan rusak (G7)
- Tambahkan kolom `prev_hash` dan `entry_hash` (SHA-256 dari isi entri + `prev_hash`) untuk membentuk hash chain.
- Blok UPDATE/DELETE pada `audit_logs` (trigger DB + guard di model).
- Command `audit:verify-chain` untuk mendeteksi rantai yang putus, dan job harian yang mengekspor hash terakhir ke lokasi terpisah (email ke pejabat, atau storage WORM).
- **Selesai jika:** mengubah satu baris secara manual membuat `audit:verify-chain` gagal.
- Label: `ready-for-agent`

### T6. Migrasi SQLite → PostgreSQL/MySQL (G7)
- Konfigurasi produksi, backup terjadwal terenkripsi, dan uji restore.
- Label: `ready-for-human` (keputusan infrastruktur)

### T7. Verifikasi dengan unggah file (G6)
- Halaman `/verify` menerima unggahan PDF, menghitung SHA-256, dan mencocokkan dengan **semua** `signatures.signed_hash` (bukan hanya file server).
- Hasil: "Cocok dengan versi X yang ditandatangani pada …" / "Tidak cocok: file sudah diubah atau bukan dokumen asli".
- Tampilkan fingerprint dokumen sebagai teks kecil di samping QR, supaya pemindai bisa mencocokkan secara visual.
- Validasi juga struktur tanda tangan PDF (ByteRange/PKCS#7) bila memungkinkan.
- **Selesai jika:** PDF yang diubah satu byte dinyatakan tidak cocok (ada test).
- Label: `ready-for-agent`

### T8. Minimisasi data di verifikasi publik (G10)
- Tampilkan NIP/NIDN secara tersamar (mis. `1987******0012`) untuk pengunjung yang tidak login. Tinjau field lain sesuai UU PDP.
- Label: `ready-for-agent` (setelah D3 mengonfirmasi aturan)

### T9. Timestamp eksternal sementara (G5, parsial)
- Sebelum PSrE ada, minta timestamp RFC 3161 dari TSA publik atas hash PDF final, lalu simpan ke `signatures.tsa_token`. Token disimpan sebagai bukti terpisah, bukan di-embed ke PDF, karena TCPDF tidak mendukung embedding.
- Isi juga `certificate_serial`.
- **Selesai jika:** setiap tanda tangan baru punya token TSA yang bisa diverifikasi dengan `openssl ts -verify`.
- Label: `ready-for-agent`

---

## Fase 2: Integrasi PSrE (setelah D1, D2, D4)

### T10. Enrollment penanda tangan
- Alur pendaftaran sertifikat per user ke PSrE (verifikasi NIK/biometrik dilakukan oleh PSrE).
- Simpan status sertifikat (`none`/`pending`/`active`/`revoked`/`expired`) beserta tanggal kedaluwarsanya. Hanya signer dengan sertifikat `active` yang bisa ditugaskan.
- Label: `ready-for-agent` setelah dokumentasi API PSrE didapat

### T11. `PsreProvider`
- Implementasi `SignatureProvider` yang mengirim PDF (sudah berisi QR) ke API PSrE. Penanda tangan memasukkan passphrase/OTP **milik PSrE**, bukan milik aplikasi.
- Target format **PAdES-B-LT atau -LTA** (TSA + data revokasi tertanam).
- Tangani timeout/retry dengan idempotency key. Status transaksi via webhook atau polling.
- Label: `ready-for-agent`

### T12. Multi-signer dengan incremental update (G9)
- QR dan stempel visual ditempel **sekali sebelum** tanda tangan pertama. Setiap tanda tangan berikutnya memakai incremental update dari PSrE/engine D4, sehingga tanda tangan sebelumnya tetap valid.
- Aktifkan kembali mode `sequential`/`parallel` yang sekarang dipaksa `single`.
- **Selesai jika:** PDF dengan 2 penanda tangan menunjukkan 2 tanda tangan valid di Adobe Reader.
- Label: `ready-for-agent`

### T13. Verifikasi berbasis tanda tangan PDF
- Halaman verifikasi menampilkan hasil validasi kriptografis (rantai sertifikat ke Root CA Indonesia, status revokasi, timestamp), bukan hanya status DB.
- Arahkan juga ke layanan verifikasi resmi PSrE/pemerintah.
- Label: `ready-for-agent`

### T14. Pensiunkan `LocalSealProvider` untuk dokumen bernilai hukum
- `LocalSeal` hanya boleh untuk tipe dokumen internal yang ditetapkan di D3. Tipe lain wajib `PsreProvider`.
- Label: `ready-for-agent`

---

## Fase 3: Tata kelola & operasional

| ID | Tugas | Label |
|----|-------|-------|
| O1 | SOP penandatanganan, pencabutan, dan penanganan sengketa | `ready-for-human` |
| O2 | Kebijakan retensi & arsip (PDF + bukti TSA + audit chain) sesuai aturan kearsipan | `ready-for-human` |
| O3 | Penetration test pihak ketiga sebelum go-live | `ready-for-human` |
| O4 | Monitoring: kedaluwarsa sertifikat, gagal TSA/PSrE, rantai audit putus | `ready-for-agent` |
| O5 | DR plan + uji restore berkala | `ready-for-human` |

---

## Urutan & ketergantungan

```
Fase 0: D1 ─┬─ D2 ─┐
            ├─ D3 ─┼──────────────► T8, T14, O1, O2
            └─ D4 ─┤
                   ▼
Fase 1: T1, T3, T4, T5, T7, T9 (paralel, bebas)
        T2 ───────────────────────► T11
        T6 (infra)
Fase 2: T10 → T11 → T12 → T13 → T14
Fase 3: O1–O5 menjelang go-live
```

## Hasil yang diharapkan per fase

- **Setelah Fase 1:** masih TTE *tidak tersertifikasi*, tetapi jauh lebih bisa dipertanggungjawabkan: ada OTP, kunci terlindungi, audit tahan rusak, dan verifikasi independen. Layak untuk dokumen internal berisiko rendah.
- **Setelah Fase 2 + 3:** TTE *tersertifikasi* via PSrE. Inilah yang secara hukum bisa diperlakukan setara tanda tangan basah untuk dokumen yang diizinkan SK.

## Langkah berikutnya

Tugas Fase 1 bisa dipecah menjadi GitHub issues dengan `/to-tickets` (konvensi ada di `docs/agents/issue-tracker.md`).
