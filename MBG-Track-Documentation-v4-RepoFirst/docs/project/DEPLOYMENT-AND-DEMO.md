# Deployment dan Demo — Checklist Tanpa Janji Palsu

## Lingkungan
Lokal: PHP/Composer sesuai Laravel versi repo, MySQL, Node/NPM untuk asset build, HTTPS saat pengujian GPS memakai tunnel/hosting; `localhost` exception browser bisa berlaku, tetapi demo multi-perangkat wajib HTTPS publik/tunnel valid. Tidak simpan secret di Git.

## Checklist sebelum deploy
- `composer install`, set APP_KEY, migrasi pada DB kosong terpisah; `.env` aman.
- Cache/config/route secara sesuai; storage private writable, queue worker + scheduler berjalan.
- HTTPS aktif, cookie secure, APP_DEBUG=false, CORS/CSRF benar.
- Rate limit login, GPS, evidence; backup & restore tested; map provider keys/quotas valid.
- `php artisan test` / suite yang tersedia benar-benar PASS; seed demo tidak memakai akun/data nyata.
- Path publik tidak memuat raw koordinat/PII. Audit dan foto private tidak dapat dibuka tanpa policy.

## Skrip demo ±10–15 menit (usulan)
1. Kepala login dan lihat SD A, SD B, SMP C sebagai tiga tujuan SPPG Demo.
2. PIC A/B/C konfirmasi 340/276/410, total 1026 muncul sekali tanpa input ulang.
3. Gizi aktifkan menu versi; asisten jalankan batch dan foto ready; gizi release QC.
4. Kepala buat trip 3 stop, driver melakukan loading/checklist, mulai trip.
5. GPS simulasi bergerak pada tab aktif; PIC A mendapat ETA sekali dan arrived.
6. PIC A menerima 338 dari 340, incident shortage otomatis; Kepala memeriksa.
7. PIC A konfirmasi pemeriksaan sebelum makan dan pembagian (jika aman).
8. Wadah siap dijemput, pickup/return dengan hitungan; kepala melihat histori.
9. Halaman publik SD A hanya menampilkan menu/status, tidak membuka GPS raw.
10. Uji 403: PIC A coba SD B, driver coba trip orang lain.

## Perlu dibuktikan sebelum menyebut berhasil
Rute maps provider berfungsi, izin GPS di HP asli, API usage di bawah kuota, notif dedup, foto tersimpan privat, migration reset dev berhasil, backup dapat direstore dan browser background disimulasikan stale.

## Akademik
Ikuti format proposal dosen melalui `docs/academic/PROPOSAL-MAPPING.md`; mockup, use case, class diagram dan BPMN adalah deliverable tersendiri, dokumentasi saja tidak otomatis menggantikan gambar yang diminta.

## Checklist lingkungan dan demo berbasis data simulasi
**Lingkungan:** `.env` tidak di-commit, HTTPS aktif, APP_DEBUG=false saat deploy, DB migration dijalankan dengan backup terlebih dulu, storage private untuk foto, scheduler/queue worker jika notifikasi dipakai, tiles/routing sesuai ToS, cron purge GPS jika kebijakan ditetapkan.

### Rute demo dosen 10–15 menit (durasi rencana, bukan janji operasional)
1. Buka publik tanpa login, pilih sekolah A; jelaskan label DEMO dan informasi yang tidak ditampilkan.
2. Login PIC: 340 porsi; tunjukkan bagaimana Kepala melihatnya tanpa input ulang.
3. Kepala memfinalisasi rencana untuk 340+276+410; Asisten memroses batch; Gizi mengesahkan satu batch dan menahan contoh QC bermasalah.
4. Kepala assign Trip 3 stop; kurir memulai trip dari halaman HP. GPS menggunakan browser dengan izin; tampilkan fallback bila tidak tersedia.
5. PIC sekolah A menerima 338 dari 340 dengan 2 rusak; perlihatkan dua kategori selisih tanpa double count, bukti bersama dan incident.
6. PIC menjalankan pemeriksaan sebelum makan, distribusi aman, ompreng siap; kurir pickup, Asisten menerima retur (contoh selisih 2 wadah).
7. Buka dashboard Kepala dan audit; tutup dengan test report dan keterbatasan (tidak terhubung BGN, tidak pakai data real siswa).

### Fallback presentasi
Jika internet/routing API mati: tampilkan map fallback alamat dan status transaksi tetap jalan. Jika browser tidak mengizinkan GPS: pakai data replay **berlabel SIMULASI**, bukan menampilkan marker seolah live. Jika upload kamera gagal: gunakan fixture lokal berlabel data demo dan jelaskan kebijakan exception.


## V3 | Public credibility and demonstration
Jika deployment demo tersedia, label "PROTOTIPE / DATA SIMULASI" terlihat. Jangan gunakan fake counters, reviews atau screenshot seolah produksi BGN. Semua links pada demo harus benar-benar bekerja dan public projection tidak mengungkap raw GPS. Sebelum presentasi, jalankan Delivery Gate pada alur PIC→produksi→driver→sekolah dan tes demo di HP.
