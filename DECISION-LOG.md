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

---

## DEC-009: PBI-02 Pegawai dan Hak Akses (Page K03) Implementation Standard
- **Tanggal:** 12 Oktober 2026
- **Status:** APPROVED & IMPLEMENTED
- **Konteks:** Kontrak PBI-02 (`EMPLOYEE-MANAGEMENT.md`, `PBI-BACKLOG.md`, dan `DATA-DICTIONARY.md`) menetapkan pengelolaan pelaksana internal SPPG (Pengawas Gizi, Asisten Lapangan, Kurir) dengan isolasi multi-tenant ketat, pencegahan self-demote / privilege escalation, non-destructive status toggle, password reset ber-audit trail, dan pencatatan riwayat perubahan izin pada `activity_logs`.
- **Keputusan:**
  1. Membuat migrasi dan model `ActivityLog` sesuai spesifikasi `DATA-DICTIONARY.md` (actor_id, auditable_type, auditable_id, event, old_values, new_values, metadata, ip_address, user_agent).
  2. Mengembangkan `EmployeeManagementService` yang mengelola transaksi atomik pendaftaran pegawai, pembaruan role, penonaktifan akun dengan proteksi sopir aktif (`hasActiveTrips()`) dan proteksi kepala tunggal (`isSoleActiveHead()`), serta reset kredensial.
  3. Memperluas `UserPolicy` untuk memastikan Kepala SPPG hanya dapat melihat, menyunting, menonaktifkan, atau mereset pegawai yang berada dalam `sppg_id` miliknya (HTTP 403 untuk akses cross-tenant).
  4. Menyediakan FormRequest `StoreEmployeeRequest` dan `UpdateEmployeeRequest` dengan validasi unik email, konfirmasi kata sandi minimal 8 karakter, dan whitelist role internal sah.
  5. Membangun 4 antarmuka Blade K03 (`index`, `create`, `edit`, `show`) berbasis mobile-first 390px, visual Clean Luxury Calm Operational Editorial, ENERGY 1 / RHYTHM 2 / MOTION 1, touch target >= 44px, dan 0 em dash (`—`).
  6. Menyediakan suite pengujian lengkap `EmployeeManagementTest.php` (13 tests, 93 assertions) yang mencakup isolasi tenant, proteksi self-demote, blokade login akun nonaktif, dan verifikasi jejak audit.
- **Konsekuensi:** PBI-02 terimplementasi secara end-to-end tanpa merusak fitur PBI-01 maupun modul existing lainnya. Regression suite lulus 100% (52 tests, 486 assertions, 0 errors).

---

## DEC-010: Anti-Slop Audit Findings (FINDING-01 s/d FINDING-08) & PBI-02 Security Hardening
- **Tanggal:** 12 Oktober 2026
- **Status:** APPROVED & IMPLEMENTED
- **Konteks:** Perbaikan 8 temuan audit Anti-Slop Mode AFTER yang telah disetujui pengguna (em dash cleanup, mobile nav, empty/error state, bounding box touch targets, de-genericizing dashboards, kurir status informatif, dynamic public school slug, dan 320px layout reflow) serta audit keamanan khusus fitur PBI-02 Manajemen Pegawai.
- **Keputusan:**
  1. Membersihkan 100% karakter em dash (`—`) dari seluruh copy antarmuka Blade, digantikan dengan penataan tipografi baku (titik dua `:` atau penataan terstruktur).
  2. Membangun navigasi mobile bawah (bottom navigation bar) yang benar-benar berfungsi, role-specific, safe-area aware (`pb-[env(safe-area-inset-bottom)]`), dengan indikator aktif (`aria-current="page"`), tanpa menutupi konten (`mb-24 md:mb-8` di `<main>`).
  3. Mengubah kontrol aksi tabel manajemen sekolah dan pegawai agar strictly memiliki target interaktif minimal 44x44 CSS px (`min-h-[44px] min-w-[44px] inline-flex items-center justify-center`). Terukur via browser: Detail (56.4x44px), Edit (47.9x44px), Nonaktifkan (93.9x44px).
  4. Menghilangkan ketergantungan hardcoded school slug pada halaman login (`/login`) dengan resolusi dinamis sekolah aktif pertama yang valid, mencegah link mati atau sekolah palsu.
  5. Mengganti tombol kurir tiruan dan data trip simulasi dengan status siaga operasional informatif yang jujur, menegaskan bahwa penugasan rute beroperasi pada PBI-07 / PBI-08.
  6. Mengimplementasikan honest empty states dan recovery actions pada dashboard Kepala SPPG dan Kurir, dilengkapi test fixtures otomatis pada test suite.
  7. Mengeliminasi kesan dashboard generik dengan penataan visual hierarchy terarah, executive masthead, kurir purple theme, dan reflow responsif 320px tanpa font kerdil.
  8. Memperketat keamanan PBI-02: mencegah eskalasi wewenang (privilege escalation) ke `KEPALA_SPPG` via FormRequest dan Service, memastikan tidak ada kata sandi/hash/token tersimpan di `activity_logs`, dan menambahkan security tests di `EmployeeManagementTest.php`.
- **Konsekuensi:** Seluruh 8 temuan audit dan audit keamanan PBI-02 terselesaikan dengan bukti. Test suite lulus 100% (56 tests, 520 assertions, 0 errors).


