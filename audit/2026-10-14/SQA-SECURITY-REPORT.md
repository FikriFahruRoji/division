# Audit SQA dan keamanan TTD Digital

Tanggal: 14 Oktober 2026, Asia/Jakarta. Baseline Git: `695dc79`, ditambah perubahan workspace yang sedang berlangsung. Audit lokal; tidak menguji server operasional.

## Putusan

**NO-GO untuk pilot dengan dokumen sensitif sampai temuan prioritas pertama ditutup.** Sistem dapat menampilkan dokumen tanpa tanda tangan sebagai VALID, memberi akses file kepada pemegang token publik, serta menerima tindakan akun tidak aktif. Ini merusak kepercayaan terhadap hasil persetujuan dan kerahasiaan dokumen. Test fungsional yang lulus tidak menguji batas keamanan ini.

Keputusan pengguna selama audit:

1. Pemakaian: pilot internal terbatas.
2. Tujuan: persetujuan internal organisasi.
3. QR publik: hanya status minimum; metadata sensitif dibatasi.
4. Akun: hanya dibuat/diundang admin.
5. Operator: dokumen sendiri dan penugasan eksplisit.
6. Model target: catatan persetujuan per orang dan satu segel sistem pada PDF akhir.

Dengan keputusan keenam, perbaikan sebaiknya mengubah model persetujuan dan menyegel file akhir sekali. Jangan mempertahankan klaim bahwa setiap pengguna memiliki tanda tangan kriptografis di PDF akhir jika implementasinya hanya menyimpan persetujuan di database.

## Metode dan batas bukti

- Membaca routes, requests, controllers, policies, models, services, migrations, konfigurasi, template, dan tests.
- Menjalankan PHPUnit dengan SQLite `:memory:`, fake storage dan fixture sintetis; tidak memodifikasi database operasional.
- Menguji pola rekonstruksi PDF menggunakan FPDI/TCPDF dan dua sertifikat sintetis.
- Menguji interleaving terkendali pada logika `signDocument`; pemrosesan PDF diganti stub untuk mengisolasi kondisi balapan. Ini bukan benchmark konkurensi HTTP atau uji beban produksi.
- `composer audit --locked --format=json` terhadap lockfile. Versi paket terpasang dibandingkan dengan lockfile dan tidak ditemukan perbedaan pada pemeriksaan itu.
- Menanyakan advisory ke API bulk resmi npm memakai versi dalam `package-lock.json`. Ini bukan `npm audit` lengkap dan tidak menghitung seluruh jalur keterjangkauan atau paket induk terdampak.
- Memverifikasi advisory PDF.js dan FPDI pada halaman security maintainer.
- Tidak melakukan serangan DoS, eksploitasi browser PDF berbahaya, pengujian OAuth Google langsung, atau pengujian host/deployment/backup.
- CSRF dinonaktifkan oleh lingkungan test Laravel sebagaimana lazimnya; hasil feature tests ini tidak membuktikan konfigurasi CSRF browser produksi.

Kode aplikasi tidak diperbaiki pada audit ini. Ada perubahan workspace lain selama audit berupa penambahan NIP/NIDN pada model, input pengguna, PDF dan verifikasi publik; diff diperiksa dan test dijalankan ulang setelah perubahan tersebut. Temuan tetap berlaku. Snapshot hash sumber disertakan sebagai bukti batas audit, karena workspace tetap bisa berubah setelah laporan disimpan.

## Hasil pengujian

| Pemeriksaan | Hasil | Makna |
|---|---|---|
| Suite bawaan | 156 tests, 293 assertions, 130 PHPUnit deprecations; lulus | Fitur yang diuji berjalan; cakupan keamanan belum cukup |
| Reproduksi auth/akses | 7 tests, 30 assertions; lulus | Assertion mengonfirmasi celah/bug, bukan keamanan |
| Reproduksi verifikasi | 3 tests, 16 assertions; lulus | Draft, rejected dan file diubah tetap dilayani/dinyatakan VALID |
| Rekonstruksi PDF | File pertama 1 ByteRange/A; file kedua 1 ByteRange/B, A hilang | Tanda tangan kriptografis sebelumnya tidak dipertahankan |
| Interleaving signing | 2 signature rows untuk 1 assignment, status signed_valid | Aksi tidak atomik/idempoten |
| Composer advisory scan | 49 entri advisory pada 14 paket | Versi cocok dengan rentang rentan; bukan 49 exploit aplikasi yang terbukti |
| npm registry bulk query | 63 entri pada 14 paket, 61 URL advisory unik | Bukan full npm audit; termasuk tool build/dev dan advisory dengan beberapa rentang versi |

Advisory Composer bisa mewakili masalah sama dari beberapa sumber/ID: misalnya dua entri CRLF rule email Laravel memakai GHSA yang sama. Angka entri jangan diartikan sebagai jumlah kerentanan unik. Advisory npm tersimpan terpisah; risiko build/dev dan runtime browser harus ditriase berbeda.

## Temuan yang memblokir pilot

### SEC-01 — High: dokumen belum disetujui dapat tampil VALID

Sumber: [DocumentService.php:75](</Users/fikrifahruroji/Documents/gravity project/ttd-digital/app/Services/DocumentService.php:75>), [VerificationService.php:38](</Users/fikrifahruroji/Documents/gravity project/ttd-digital/app/Services/VerificationService.php:38>), [SigningService.php:383](</Users/fikrifahruroji/Documents/gravity project/ttd-digital/app/Services/SigningService.php:383>).

Upload membuat `VerificationRecord.status=valid` ketika `Document.status=draft`. Verifikasi membaca record itu tanpa memastikan persetujuan lengkap. Rejection mengubah Document menjadi rejected tetapi tidak menyinkronkan record verifikasi. Record versi baru juga dibuat valid sebelum disetujui.

**Bukti:** upload PDF sintetis melalui `DocumentService::upload` menghasilkan draft, 0 signature, record valid; GET publik menunjukkan VALID. Fixture rejected dengan pola record tersebut juga menunjukkan VALID.

**Prasyarat:** pemegang token atau pembuat dokumen yang membagikan token; bukan kemampuan menebak UUID. **Dampak:** dokumen belum disetujui/ditolak terlihat sah.

**Perbaikan:** satu state machine otoritatif; status publik mengikuti proses persetujuan dan segel akhir. Jangan membuat status valid saat upload. Validasi konsistensi state sebelum memberikan label; fail closed pada inkonsistensi.

**Kriteria lulus:** draft/pending/rejected tidak pernah diberi label final valid; hanya persetujuan lengkap dan segel akhir terverifikasi yang valid; revoke/versioning konsisten.

### SEC-02 — High: label VALID tidak memeriksa integritas file

Sumber: [VerificationService.php:15](</Users/fikrifahruroji/Documents/gravity project/ttd-digital/app/Services/VerificationService.php:15>), [VerificationController.php:49](</Users/fikrifahruroji/Documents/gravity project/ttd-digital/app/Http/Controllers/VerificationController.php:49>).

Verifikasi tidak membaca file untuk membandingkan hash atau memverifikasi signature/segel. Preview hanya memeriksa keberadaan path. Fingerprint dibuat dari hash awal dan ditampilkan kembali; tidak membuktikan isi file saat diakses. Link QR juga tidak menerima PDF milik pemindai untuk dibandingkan: menyalin QR dari dokumen asli ke dokumen palsu tetap membuka record asli.

**Bukti:** setelah isi file signed sintetis diubah, hash berbeda dari database, halaman tetap VALID dan preview mengirim byte yang telah diubah.

**Prasyarat:** perubahan file lewat akses storage, kesalahan operasional atau jalur tulis lain; audit ini tidak membuktikan penyerang anonim dapat menimpa storage. **Dampak:** deteksi manipulasi gagal.

**Perbaikan:** kunci file final sebagai immutable version; hash final penuh dan segel sistem diverifikasi saat validasi/akses relevan. Status online harus menjelaskan bahwa ia memeriksa record/file server. Jika perlu membuktikan file yang dipegang penerima, sediakan pembandingan file/hash atau validasi segel pada PDF tersebut.

**Kriteria lulus:** perubahan satu byte atau file hilang menghasilkan invalid/error, bukan VALID; fingerprint publik terikat pada file final; transplantasi QR tidak dianggap sebagai bukti keaslian kertas/file lain.

### SEC-03 — High: QR membocorkan PDF dan metadata sensitif

Sumber: [routes/web.php:27](</Users/fikrifahruroji/Documents/gravity project/ttd-digital/routes/web.php:27>), [VerificationController.php:36](</Users/fikrifahruroji/Documents/gravity project/ttd-digital/app/Http/Controllers/VerificationController.php:36>), [VerificationService.php:41](</Users/fikrifahruroji/Documents/gravity project/ttd-digital/app/Services/VerificationService.php:41>).

Pemegang token publik dapat membaca seluruh PDF; tidak ada cek classification, permission atau status final. Metadata nomor/judul/unit dan identitas penandatangan dipublikasikan. Perubahan workspace terbaru menambahkan `nip_nidn` pada hasil/verifikasi publik bila data tersedia. Ini bertentangan dengan keputusan pengguna tentang status minimum.

**Bukti:** preview dokumen sintetis `classification=rahasia` dan rejected mengembalikan 200 tanpa login. Draft upload asli pada SEC-01 juga dapat dipreview publik.

Menghapus tombol download tidak melindungi file. `Content-Disposition: inline` hanya mengatur cara browser menampilkan byte; pengguna tetap dapat menyimpan atau mengambilnya.

**Perbaikan:** endpoint status publik hanya menampilkan informasi minimum yang disepakati. Preview/download memerlukan autentikasi dan policy. Pisahkan token status dari izin file; jangan menjadikan token statis sebagai otorisasi universal.

**Kriteria lulus:** pemegang token tanpa login tidak memperoleh PDF, judul sensitif, identitas lengkap atau NIP/NIDN; akses file pengguna login dibatasi policy.

### SEC-04 — High: pendaftaran publik memberi operator aktif

Sumber: [RegisteredUserController.php:38](</Users/fikrifahruroji/Documents/gravity project/ttd-digital/app/Http/Controllers/Auth/RegisteredUserController.php:38>), [User.php:14](</Users/fikrifahruroji/Documents/gravity project/ttd-digital/app/Models/User.php:14>), [routes/auth.php:23](</Users/fikrifahruroji/Documents/gravity project/ttd-digital/routes/auth.php:23>).

Registration membuat akun dan login langsung. Default database menghasilkan operator aktif. Model User tidak mengimplementasikan `MustVerifyEmail`, sehingga middleware `verified` tidak menegakkan verifikasi email. Tidak ada validasi captcha backend registration; pada login rule captcha dikomentari.

**Bukti:** POST register akun sintetis menjadi operator active dengan email belum terverifikasi, lalu dashboard dapat dibuka. Penandatangan dengan email belum terverifikasi juga dapat membuka `/signing/pending`.

**Perbaikan:** matikan jalur self registration sesuai keputusan akun melalui admin/invitation; verifikasi invitation, status, departemen dan role sebelum akses. Implementasikan verifikasi email apabila tetap menjadi syarat identitas. Tampilan captcha bukan kontrol jika backend tidak memvalidasi.

**Kriteria lulus:** akun anonim tidak dapat membuat akun operasional; invitation tidak bisa digunakan ulang atau mengganti role/departemen; akun belum memenuhi syarat ditolak.

### SEC-05 — High: operator dapat melihat metadata lintas departemen

Sumber: [DashboardController.php:29](</Users/fikrifahruroji/Documents/gravity project/ttd-digital/app/Http/Controllers/DashboardController.php:29>), [DashboardController.php:81](</Users/fikrifahruroji/Documents/gravity project/ttd-digital/app/Http/Controllers/DashboardController.php:81>).

Dashboard memperlakukan operator seperti super admin untuk query recent documents dan tidak membatasi statistik. Pagination memberi akses daftar lintas departemen, termasuk judul dokumen serta email pembuat. Ini juga memperbesar dampak registration publik pada SEC-04.

**Bukti:** operator A melihat judul dan email pembuat dokumen B pada dashboard; GET detail B benar-benar 403. Jadi bukti adalah kebocoran metadata, bukan bypass download lintas departemen.

**Perbaikan:** satu scope own-or-explicit-assignment diterapkan pada list, dashboard, pencarian, statistik, ekspor dan detail sesuai keputusan pengguna.

**Kriteria lulus:** dokumen tanpa izin tidak tampil dalam list/statistik; test mencakup operator A/B, admin A/B dan super admin.

### SEC-06 — High: akun inactive masih login, download dan revoke

Sumber: [LoginRequest.php:56](</Users/fikrifahruroji/Documents/gravity project/ttd-digital/app/Http/Requests/Auth/LoginRequest.php:56>), [RoleMiddleware.php:23](</Users/fikrifahruroji/Documents/gravity project/ttd-digital/app/Http/Middleware/RoleMiddleware.php:23>), [routes/web.php:76](</Users/fikrifahruroji/Documents/gravity project/ttd-digital/routes/web.php:76>).

Login password memeriksa email/password saja. Active check hanya ada pada RoleMiddleware; route show/download/preview/revoke/destroy dan profil tidak semuanya melewatinya. Policy tidak memeriksa active.

**Bukti:** operator inactive login sukses; download PDF miliknya 200; POST revoke mengubah signed_valid menjadi revoked.

**Perbaikan:** cek active pada login dan semua authenticated request, invalidasi session ketika akun dinonaktifkan, cek syarat pelaku lagi pada tindakan kritis.

**Kriteria lulus:** akun inactive dan session lama yang baru dinonaktifkan gagal mengakses file maupun melakukan mutasi.

### SEC-07 — High: viewer PDF menggunakan library dengan eksekusi JavaScript berbahaya

Sumber: [signatures/show.blade.php:299](</Users/fikrifahruroji/Documents/gravity project/ttd-digital/resources/views/signatures/show.blade.php:299>), [SecurityHeadersMiddleware.php:75](</Users/fikrifahruroji/Documents/gravity project/ttd-digital/app/Http/Middleware/SecurityHeadersMiddleware.php:75>).

Halaman signer memakai PDF.js 3.11.174 dan `getDocument(pdfUrl)` tanpa `isEvalSupported:false`. CSP mengizinkan unsafe-eval. [Advisory maintainer Mozilla, CVE-2024-4367](https://github.com/mozilla/pdf.js/security/advisories/GHSA-wgrm-67xf-hhpq) menyebut versi <=4.1.392 terkena dan 4.2.67 sebagai patch pertama; default eval true memungkinkan JavaScript penyerang di origin aplikasi ketika PDF berbahaya dibuka.

**Bukti:** versi dan konfigurasi rentan serta alur pembukaan upload terkonfirmasi dari kode; exploit PDF/browser tidak dijalankan. Normalisasi hanya terjadi bila FPDI tidak kompatibel, dan bukan jaminan sanitasi terhadap PDF yang kompatibel.

**Dampak yang mungkin:** tindakan sebagai pengguna melalui origin aplikasi atau pembacaan data yang dapat dijangkau session. Cookie HttpOnly tidak mencegah JavaScript mengirim request memakai session.

**Perbaikan:** gunakan versi terpelihara yang sudah dipatch, pin/bundle dependency, dan nonaktifkan eval bila tersedia. Uji kompatibilitas API/worker saat upgrade; 4.2.67 adalah batas patch historis, bukan saran untuk berhenti memperbarui pada versi itu.

**Kriteria lulus:** tidak ada PDF.js rentan di template/bundle/CDN; uji fixture berbahaya dilakukan di lingkungan terisolasi dan tidak mengeksekusi script.

### SEC-08 — High: tanda tangan kriptografis sebelumnya hilang

Sumber: [SigningService.php:149](</Users/fikrifahruroji/Documents/gravity project/ttd-digital/app/Services/SigningService.php:149>), [SigningService.php:216](</Users/fikrifahruroji/Documents/gravity project/ttd-digital/app/Services/SigningService.php:216>).

Signing membuat objek PDF baru, mengimpor isi halaman dari PDF sebelumnya, lalu memberi signature pengguna saat ini. Proses itu bukan incremental signing yang mempertahankan signature lama.

**Bukti sintetis:** stage satu: satu `/ByteRange`, signature A ada. Stage dua: tetap satu `/ByteRange`, A hilang, B ada. Proof menguji pola library yang sama, bukan seluruh alur UI organisasi.

**Perbaikan menurut keputusan pengguna:** ubah model menjadi catatan approval immutable per orang, terikat versi/hash yang ditinjau; setelah lengkap, buat dan segel PDF final sekali. UI/README harus mengungkapkan model itu secara akurat. Jika kelak wajib multisignature kriptografis, gunakan implementasi incremental signing dan validasi independen semua signature.

**Kriteria lulus:** catatan persetujuan terikat isi yang benar, final disegel satu kali, dan identitas pemberi persetujuan tidak diklaim sebagai sertifikat per orang pada file jika memang tidak ada.

### SEC-09 — High: signing tidak atomik dan tidak idempoten

Sumber: [SigningService.php:130](</Users/fikrifahruroji/Documents/gravity project/ttd-digital/app/Services/SigningService.php:130>), [SigningService.php:296](</Users/fikrifahruroji/Documents/gravity project/ttd-digital/app/Services/SigningService.php:296>), [QrCodeService.php:34](</Users/fikrifahruroji/Documents/gravity project/ttd-digital/app/Services/QrCodeService.php:34>).

Assignment dibaca sebagai pending, file dibuat, signature dicatat, document diperbarui, assignment diubah, kemudian completeness dihitung tanpa transaksi/lock/idempotency. Dua request dapat lolos check yang sama. Nama output/QR berbasis detik juga dapat bertabrakan.

**Bukti interleaving terkendali:** B disisipkan setelah A membaca assignment pending; hasil 2 signature rows, hanya 1 assignment signed, document signed_valid, file akhir dari outer request. Rendering PDF diganti stub untuk mengisolasi race; tidak mengklaim throughput atau tingkat kejadian produksi.

**Perbaikan:** transaksi untuk keputusan state, unique constraint untuk approval per assignment/version, idempotency key, lock/CAS sesuai database, nama file unik, tulis atomik, segel sekali dan retry aman. Transaksi DB saja tidak menggulung balik file di filesystem.

**Kriteria lulus:** double-submit/dua request bersamaan menghasilkan satu persetujuan/segel akhir, tanpa overwrite; reject/revoke yang bersamaan tidak dapat dibatalkan oleh stale approval; crash tidak menyisakan status valid tanpa file.

### SEC-10 — High: token file dikirim ke penyedia QR pihak ketiga

Sumber: [documents/show.blade.php:231](</Users/fikrifahruroji/Documents/gravity project/ttd-digital/resources/views/documents/show.blade.php:231>).

Image URL ke `api.qrserver.com` memuat URL verifikasi penuh, termasuk token yang saat ini dapat dipakai mengakses PDF. Provider menerima token ketika browser memuat gambar. Audit tidak memanggil provider atau membuktikan penyalahgunaan token.

**Perbaikan:** render QR di aplikasi memakai `QrCodeService` yang sudah ada; jangan kirim capability token ke layanan eksternal. Terapkan pembatasan file pada SEC-03, hentikan kebocoran token di log pihak ketiga, dan evaluasi rotasi token terdahulu tanpa merusak cara verifikasi dokumen lama.

**Kriteria lulus:** network trace halaman dokumen tidak mengirim token/dokumen ke provider QR luar; token status tidak membuka file.

## Temuan tambahan dan bersyarat

| ID | Tingkat | Temuan, bukti dan tindak lanjut |
|---|---|---|
| SEC-11 | Medium | Callback Google memakai `stateless()` di SocialiteController:28 sehingga state check OAuth dinonaktifkan. Risiko login CSRF, bukan bukti takeover akun korban. Kembalikan stateful callback; perbaiki domain/session/cookie. Belum diuji dengan Google live. |
| SEC-12 | High bersyarat | SuperAdminSeeder:25 dan UserSeeder:32/48 memakai password tetap; DatabaseSeeder memanggilnya tanpa guard lingkungan. UserSeeder updateOrCreate dapat mengembalikan password akun seed saat seed ulang. Belum dicek apakah akun operasional memakai credential tersebut. Hapus default password, bedakan seed demo/bootstrap, dan rotasi bila pernah dipakai. Nilai password tidak disalin ke laporan. |
| SEC-13 | Medium | CertificateService:20 hanya memeriksa keberadaan file; :54 berlaku 365 hari; :58/:64-65 mengekspor private key tanpa passphrase ke storage aplikasi. Tidak ada lifecycle expiry/revocation atau vault terpisah. Tidak membuktikan key bisa diakses publik. Untuk target internal, kelola kunci segel sistem dengan akses terbatas/vault, SHA-256 eksplisit, serial unik, rotasi/renew dan pencatatan penggunaan. Repro default OpenSSL lokal menghasilkan RSA-SHA1/serial 0; ini bergantung openssl.cnf dan tidak digeneralisasi ke produksi. |
| SEC-14 | Medium | PdfCompatibilityService melakukan parsing semua halaman dan exec normalisasi synchronous tanpa timeout/output quota. `--stream-data=uncompress` dapat memperbesar file setelah batas upload 10MB. FPDI 2.6.4 terpasang cocok advisory [CVE-2026-45802 maintainer](https://github.com/Setasign/FPDI/security/advisories/GHSA-2mgw-7q6p-8grg), patch pertama 2.6.7. Upgrade, isolasi worker, batasi CPU/memory/pages/output/time dan karantina upload. Tidak menjalankan payload DoS. Escaping argumen shell yang ada berarti tidak ditemukan command injection dari nama file pada pembacaan ini. |
| SEC-15 | Medium | DocumentPolicy memeriksa departemen pembuat saat ini, bukan departemen immutable dokumen. Repro memindahkan pembuat A->B: admin A kehilangan akses, admin B mendapat akses historis. Tentukan kepemilikan dokumen dan simpan department asal bila diperlukan. |
| SEC-16 | Medium | ProfileController:43-53 memungkinkan super admin terakhir menghapus dirinya sendiri meskipun self-delete di UserPolicy dilarang. Repro menyisakan 0 super admin. Terapkan guard admin terakhir lintas jalur dan prosedur recovery. |
| SEC-17 | Medium | SignatureController:46 melewatkan assignment untuk admin, tanpa policy department; view:150 membaca assignment null dan menghasilkan 500. Repro benar-benar 500, bukan kebocoran metadata yang terbukti. Tambahkan policy view dan penanganan null. Debug production akan memperbesar informasi error yang terbuka. |
| SEC-18 | Medium | Finalisasi ulang rejected document mereset assignments tetapi tidak menghapus/memisahkan Signature records sebelumnya; satu ledger bisa berisi persetujuan versi isi berbeda. Terlihat dari SigningService:75-91 dan relasi signatures. Dengan model target, setiap revisi wajib membentuk versi/hash dan ronde persetujuan baru. Belum diuji seluruh siklus UI revisi. |
| SEC-19 | Medium bersyarat | `.env` lokal menggunakan APP_ENV=local, APP_DEBUG=true, SESSION_SECURE_COOKIE=false, APP_URL HTTP. Wajar untuk lokal; tidak boleh dijadikan bukti produksi rentan. Verifikasi konfigurasi deployment, HTTPS/proxy trust, cookie Secure/HttpOnly, debug off dan docroot public sebelum pilot nyata. |
| SEC-20 | Medium/hardening | CSP mengizinkan unsafe-inline, unsafe-eval dan beberapa CDN; Alpine `@3.x.x` tidak dipin. Binary responses keluar sebelum HSTS/Referrer-Policy dipasang. Build asset terkontrol, gunakan nonce/hash dan kurangi eval/CDN; pertahankan header yang tidak mengganggu preview. Tidak ditemukan XSS input Blade biasa pada audit ini, selain library rentan SEC-07. |
| SEC-21 | Medium/hardening | AuditLog writable DB biasa tanpa tamper-evident sink; log verifikasi menyimpan token, admin log scoping berdasarkan actor dapat menghilangkan log guest, preview publik tidak mencatat akses sendiri. Catatan lengkap dan immutable diperlukan untuk model approval/segel. Batasi token di log, log akses sensitif dan kirim peristiwa penting ke penyimpanan terpisah. Belum membuktikan pengguna biasa dapat memodifikasi log. |
| SEC-22 | Medium | DocumentService:256-258 menghapus file original sebelum menyimpan/normalisasi/hash/update file baru pada :263-278. Jika operasi berikutnya gagal, DB dapat menunjuk file yang sudah hilang. Terverifikasi urutan kode; kegagalan I/O tidak diinjeksi. Simpan dan validasi file baru dahulu, ganti pointer atomik setelah commit dan hapus file lama belakangan. |

## Dependency: jangan menyamakan advisory dengan exploit terjangkau

Composer: 49 entri pada 14 paket, termasuk Laravel 12.49.0, FPDI 2.6.4, Guzzle/PSR7, CommonMark, phpseclib, Symfony dan Flysystem. JSON mentah menyertakan judul, versi terdampak, CVE/GHSA dan URL untuk triase. Sebagian advisory memerlukan fitur yang tidak digunakan aplikasi, misalnya adapter atau extension tertentu; tidak semuanya memiliki jalur exploit yang dibuktikan.

Query npm mencakup paket yang ada di lockfile. Package.json menaruh semuanya sebagai devDependencies, tetapi axios diimpor kode browser dan sebagian aset justru dimuat dari CDN di luar lockfile. Risiko Vite/build tool harus dipisahkan dari aplikasi yang hanya menyajikan build statis. Advisory PDF.js CDN tidak akan ditemukan dari npm lockfile proyek ini.

Prioritaskan PDF.js viewer dan FPDI upload sebagai jalur yang langsung memproses PDF pengguna, lalu perbarui Laravel dan dependencies secara terkendali. Jangan menjalankan update mayor massal tanpa uji regresi. Pasang advisory scan di CI dengan triase reachability dan pengecualian terdokumentasi, bukan mengabaikan seluruh hasil.

## Rencana perbaikan dan syarat keluar pilot

### Prioritas 1 — tutup akses dan klaim palsu

- Tutup pendaftaran publik; pastikan hanya akun admin/invitation yang memenuhi syarat.
- Terapkan active check menyeluruh dan invalidasi session saat dinonaktifkan.
- Samakan scope operator sendiri/penugasan di seluruh endpoint.
- Batasi QR ke status minimum; file dan identitas sensitif memerlukan policy.
- Hentikan pengiriman token ke QR provider.
- Hilangkan VALID untuk draft/pending/rejected serta lakukan validasi integritas final.
- Patch PDF.js dan FPDI; periksa credential seed yang pernah dipakai.

### Prioritas 2 — model persetujuan yang benar

- Simpan approval immutable yang mengikat user, assignment, versi/hash isi yang ditinjau, waktu, dan hasil persetujuan.
- Simpan satu segel sistem final setelah seluruh persetujuan lengkap. Label UI/README mengikuti model internal approval + system seal.
- Gunakan transaksi/constraint/idempotency dan file atomik; segel hanya sekali; uji interleaving approve/reject/revoke dan crash.
- Revisi membentuk ronde baru; histori lama dipertahankan sebagai histori versi lama, tidak dihitung ulang sebagai persetujuan isi baru.
- Kelola expiry/rotasi kunci segel dan audit yang dapat dipercaya.

### Prioritas 3 — buktikan di staging

- Uji matriks anonymous/active/inactive/unverified x operator/admin/signer/super admin x own/assigned/other department, termasuk dashboard/search/statistik.
- Uji draft/pending/rejected/revoked/superseded, file hilang/manipulasi, QR yang ditempel ke dokumen lain, revisi dan approval paralel.
- Verifikasi konfigurasi server riil: HTTPS, cookie, debug, document root, private storage, access control backup, pemulihan dan log.
- Pastikan akun seed/default tidak tersedia dan dependencies relevan dipatch.
- Pilot baru dapat memakai dokumen sensitif setelah temuan High relevan ditutup dan uji regresi negatif lulus. Sebelum itu, batasi eksperimen ke fixture sintetis pada lingkungan terisolasi.

## Bukti yang disertakan

Folder `evidence/` memuat suite output, dua feature reproduction tests dan outputnya, proof rekonstruksi PDF, proof interleaving, serta JSON advisory PHP/npm dan snapshot hash sumber. Fixture menggunakan data sintetis; tidak memuat kunci privat atau dokumen pengguna.

Untuk mengulang dari root proyek, jalankan PHPUnit pada masing-masing feature reproduction file dan PHP pada proof scripts. Kedua proof script memiliki referensi absolut ke workspace audit ini; sesuaikan jika checkout dipindahkan. Reproduction tests sengaja mengassert perilaku saat ini yang rentan, sehingga perbaikan yang benar akan membuat assertion lama gagal: ubah menjadi expected denial/invalid setelah remediation.

Laporan ini bukan sertifikasi keamanan atau pentest deployment. Putusan NO-GO didasarkan pada cacat lokal yang sudah cukup untuk melanggar kebutuhan pilot yang dipilih pengguna.
