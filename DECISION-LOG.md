# DECISION-LOG — MBG-Track Development

File ini mencatat seluruh keputusan arsitektur, database, dan implementasi yang diambil selama pengembangan MBG-Track. File ini berlokasi di root proyek dan terpisah dari protected documentation directory.

---

## DEC-001: Safe Bootstrap Melalui Staging Directory Tanpa Mengganggu Dokumentasi
- **Tanggal:** 12 Oktober 2026
- **Status:** APPROVED & IMPLEMENTED
- **Konteks:** Root direktori `P:\Tugas PPL MBG` sudah berisi folder `MBG-Track-Documentation` yang strictly read-only. Menjalankan perintah bootstrap standard secara langsung berpotensi menimbulkan collision.
- **Keputusan:** Melakukan inisialisasi Laravel melalui direktori staging `_staging_laravel`, memverifikasi tidak ada konflik nama berkas, memindahkan struktur Laravel ke root, dan menambahkan `/MBG-Track-Documentation/` ke `.gitignore` agar terisolasi dari Git.
- **Konsekuensi:** Folder dokumentasi tetap 100% utuh (76 berkas), sedangkan Laravel terpasang langsung di `P:\Tugas PPL MBG\`.

---

## DEC-002: Modular Monolith Stack (Laravel 13, MySQL 8.4, Blade, Tailwind CSS)
- **Tanggal:** 12 Oktober 2026
- **Status:** APPROVED & IMPLEMENTED
- **Konteks:** Menjaga performa tinggi, stabilitas, kemudahan deployment, serta kesesuaian dengan arsitektur pada `LARAVEL-ARCHITECTURE.md`.
- **Keputusan:** Menggunakan Laravel 13, MySQL 8.4 (`mbg_track_dev`), Blade templating engine, Vite, dan Tailwind CSS dengan custom semantic design tokens "Calm Utility". Menghindari library bloated atau framework SPA berlebihan.
- **Konsekuensi:** Performa loading cepat, bundle CSS di bawah 70kB gzip ~14kB, aset terkompilasi dalam <1 detik.

---

## DEC-003: Multi-Tenant Scoping & Strict Backend Authorization
- **Tanggal:** 12 Oktober 2026
- **Status:** APPROVED & IMPLEMENTED
- **Konteks:** Kebutuhan isolasi data multi-sekolah: PIC Sekolah A dilarang mengakses data Sekolah B; kurir dibatasi pada penugasan sendiri; pengguna publik hanya menerima data whitelist non-sensitif.
- **Keputusan:** Penegakan izin tidak mengandalkan tampilan tombol di UI, melainkan melalui:
  1. `EnsureUserHasRole` middleware yang menolak role tidak sah dengan HTTP 403.
  2. `EnsureUserIsActive` middleware yang membatalkan sesi dan menolak akses pengguna nonaktif.
  3. `SchoolPolicy` yang menegakkan bahwa PIC hanya dapat membaca sekolah miliknya (`$user->school_id === $school->id`), sedangkan Kepala SPPG hanya dapat mengakses sekolah dalam SPPG binaannya.
  4. `SchoolPublicController` yang secara ketat menyajikan DTO whitelist tanpa koordinat GPS, telepon, atau identitas pribadi.
- **Konsekuensi:** Seluruh skenario penyerobotan lintas tenant tertolak dengan HTTP 403 atau 404, lolos uji otomatis pada test suite.

---

## DEC-004: Baseline Seeder Terkontrol (1 SPPG, 3 Sekolah, 1.026 Porsi Target)
- **Tanggal:** 12 Oktober 2026
- **Status:** APPROVED & IMPLEMENTED
- **Konteks:** Penyiapan data simulasi Sprint 0 sesuai kontrak dokumentasi:
  - SDN Percobaan 01 Surabaya: 340 porsi
  - SMPN 02 Harapan Bangsa: 276 porsi
  - SMAN 05 Teladan Surabaya: 410 porsi
  - Total Target: 1.026 porsi
- **Keputusan:** Membuat `MbgTrackBaselineSeeder` dengan 5 kategori role internal dan akun demo berlabel `(DEMO/SIMULASI)` secara eksplisit untuk menjaga kejujuran operasional.

---

## DEC-005: Guard Preset Demo & Kredensial Otomatis untuk Lingkungan Non-Produksi
- **Tanggal:** 12 Oktober 2026
- **Status:** APPROVED & IMPLEMENTED
- **Konteks:** Hasil audit teknis Sprint 0 menemukan potensi celah keamanan jika tombol quick-login preset dan default credential demo terekspos di lingkungan produksi.
- **Keputusan:** Membungkus seluruh tombol preset demo dan nilai password default di `LoginController.php` dan `resources/views/auth/login.blade.php` dengan pengecekan `app()->isProduction()` serta `config('app.env') === 'production'`. Di lingkungan produksi, variabel preset diubah menjadi array kosong dan tombol preset disembunyikan total dari DOM.
- **Konsekuensi:** Fitur kemudahan demo tetap berfungsi saat testing/development, namun 100% aman dan tidak meninggalkan celah di production. Ditambahkan uji regresi otomatis di `AuthTest.php`.

---

## DEC-006: Pembedaan Konseptual & Data Master registered_beneficiaries Terhadap Kebutuhan Porsi Harian
- **Tanggal:** 12 Oktober 2026
- **Status:** APPROVED & IMPLEMENTED
- **Konteks:** Field `registered_beneficiaries` pada tabel `schools` sering disalahartikan sebagai transaksi kebutuhan porsi harian.
- **Keputusan:** Menegaskan secara eksplisit di level model, formulir CRUD (K02), dan dashboard briefing bahwa `registered_beneficiaries` adalah Master Data pokok jumlah siswa terdaftar. Kebutuhan porsi riil harian adalah transaksi dinamis harian yang dikonfirmasi oleh PIC Sekolah secara terpisah (PBI-04).
- **Konsekuensi:** Mencegah percampuran antara data pokok institusi (master data) dan rekaman transaksi logistik harian.

---

## DEC-007: Cryptographic SHA-256 Hash Verification Manifest untuk Read-Only Documentation
- **Tanggal:** 12 Oktober 2026
- **Status:** APPROVED & IMPLEMENTED
- **Konteks:** Audit integritas folder `MBG-Track-Documentation` memerlukan bukti verifikasi matematis yang dapat direproduksi, bukan sekadar asumsi atau klaim sepihak.
- **Keputusan:** Menghasilkan baseline hash SHA-256 untuk seluruh 76 berkas di folder dokumentasi yang disimpan di `tests/fixtures/documentation_sha256_manifest.json`, kemudian membuat automated test `tests/Unit/DocumentationIntegrityTest.php` yang memverifikasi setiap berkas terhadap hash baseline-nya (155 assertions).
- **Konsekuensi:** Setiap perubahan sekecil apa pun pada folder dokumentasi akan langsung menggagalkan test suite (`phpunit`). Integritas dokumentasi terbukti 100% matematis.

---

## DEC-008: PBI-01 Non-Destructive Lifecycle & Dedicated School Management Module
- **Tanggal:** 12 Oktober 2026
- **Status:** APPROVED & IMPLEMENTED
- **Konteks:** Kontrak PBI-01 (`SCHOOL-MANAGEMENT.md` dan `DATA-DICTIONARY.md`) melarang hard delete pada sekolah yang memiliki keterkaitan operasional atau histori distribusi logistik.
- **Keputusan:**
  1. Mengimplementasikan `SchoolManagementService` dan `SchoolManagementController` dengan method `toggleActive(School $school)` beroperasi via HTTP `PATCH` untuk soft-deaktivasi.
  2. Menyediakan form request validasi ketat (`StoreSchoolRequest`, `UpdateSchoolRequest`) untuk koordinat GPS (-90..90, -180..180), unique code, unique slug, dan validasi jenjang pendidikan.
  3. Menyediakan antarmuka responsif K02 (`index`, `create`, `edit`, `show`) bergaya Clean Luxury Calm Utility dengan touch target >=44px.
  4. Menyediakan suite pengujian komprehensif `SchoolManagementTest.php` (13 test cases, 63 assertions) menguji isolasi SPPG, izin role, soft deactivation, dan assignment PIC.
- **Konsekuensi:** Sekolah dapat dinonaktifkan tanpa kehilangan jejak audit histori. Integritas referensial database terjaga penuh.
