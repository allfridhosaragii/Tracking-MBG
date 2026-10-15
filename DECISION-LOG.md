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

---

## DEC-011: Implementasi Resmi PBI-03 — Katalog Racikan Menu, Komposisi Gizi & Versioning Immutable (Halaman G02)
- **Tanggal:** 12 Oktober 2026
- **Status:** APPROVED & IMPLEMENTED
- **Konteks:** Implementasi menyeluruh PBI-03 (Menu dan Kandungan Gizi) berdasarkan kontrak `PBI-BACKLOG.md`, spesifikasi halaman G02 `MENU-MANAGEMENT.md`, integrasi G01 `NUTRITION-DASHBOARD.md`, dan data dictionary resmi.
- **Keputusan:**
  1. **Skema Database Resmi:** Membuat migration `2026_10_12_000004_create_menus_and_menu_items_tables.php` yang mendefinisikan tabel `menus` dan `menu_items` dengan tipe data presisi (`decimal(9,2)` untuk berat, `decimal(10,2)` untuk energi dan makronutrien), relasi cascade, serta unique constraint per-SPPG `['sppg_id', 'menu_code', 'version']`.
  2. **Backed Enum & State Machine:** Membuat enum `MenuStatus` (`DRAFT`, `ACTIVE`, `ARCHIVED`) dengan label bahasa Indonesia baku dan styling badge semantik yang konsisten.
  3. **Domain Service & Versioning:** Membangun `MenuManagementService` yang mengelola siklus hidup menu: pembuatan draft inisial, penambahan komponen bahan dengan validasi gramasi $> 0$, kalkulasi makronutrien backend tanpa angka rekaan, pencegahan aktivasi pada menu belum lengkap / tanpa sumber rujukan gizi resmi, aktivasi transaksional yang mengarsipkan versi aktif lama, serta pencabangan versi baru ($v+1$) yang menduplikasi komponen tanpa mengubah histori versi aktif yang telah digunakan.
  4. **Keamanan & Isolasi Tenant:** Menegakkan otorisasi `PENGAWAS_GIZI` dan scoping tenant via `MenuPolicy` (`viewAny`, `view`, `create`, `update`, `delete`, `activate`, `createNewVersion`) dan FormRequests (`StoreMenuRequest`, `UpdateMenuDraftRequest`, `StoreMenuItemRequest`). Request dari role lain atau lintas SPPG ditolak dengan HTTP 403.
  5. **UI/UX Editorial Workspace (G02):** Membangun 4 template Blade (`index`, `create`, `show`, `edit`) dengan bahasa desain *Calm Operational Editorial*, aksen Ocean Blue (`#285F80`), split dossier layout pada desktop (komponen bahan di kiri, ringkasan profil gizi dan kontrol aktivasi di kanan), zero horizontal scroll pada 320px–1440px, touch target $\ge 44 \times 44$ px, dan 0 karakter em dash (`—`).
  6. **Integrasi Modul G01 Review Desk:** Menghubungkan dashboard G01 dengan agregat menu aktif SPPG yang nyata dan navigasi dua arah pada header serta bottom navigation bar.
  7. **Otomasi Pengujian:** Menulis `MenuManagementTest.php` dengan 17 test cases (77 assertions) yang mencakup seluruh kriteria penerimaan, validasi nilai negatif, immutability versi aktif, transisi arsip otomatis, pencabangan versi, isolasi tenant lintas-SPPG, dan audit logging di `activity_logs`.
- **Konsekuensi:** PBI-03 terimplementasi secara lengkap dan aman. Seluruh test suite aplikasi lulus 100% (73 tests, 597 assertions, 0 errors). Frontend asset ter-build bersih via Vite.

---

## DEC-012: Implementasi Resmi PBI-04 : Kebutuhan Porsi Sekolah (Halaman P02) & Monitoring Kepala SPPG
- **Tanggal:** 13 Oktober 2026
- **Status:** APPROVED & IMPLEMENTED
- **Konteks:** Implementasi penuh Sprint 2 PBI-04 Kebutuhan Porsi Sekolah berdasarkan kontrak `PBI-BACKLOG.md`, spesifikasi halaman P02 `PORTION-CONFIRMATION.md`, monitoring Kepala SPPG, kontrak ERD, dan data dictionary resmi.
- **Keputusan:**
  1. **Skema Database Resmi:** Membuat migration `2026_10_13_000001_create_daily_portion_requests_table.php` dengan kolom `school_id`, `service_date`, `registered_count`, `present_count`, `requested_portions`, `status`, `confirmed_by`, `confirmed_at`, `locked_at`, `revision_reason`, timestamps, unique constraint `['school_id', 'service_date']`, dan index `['service_date', 'status']`. Nilai `registered_count` diambil sebagai snapshot dari data pokok `schools.registered_beneficiaries` saat record dibuat.
  2. **Backed Enum & State Machine:** Membuat enum `PortionRequestStatus` (`DRAFT`, `CONFIRMED`, `LOCKED`, `REVISED`, `CANCELLED`) dengan label resmi bahasa Indonesia dan semantic styling badge/dot.
  3. **Konfigurasi Cutoff Fleksibel:** Menyediakan konfigurasi terpusat pada `config/mbg.php` (`portion_cutoff_time` default '16:00', `portion_cutoff_days_prior` default 1 hari/H-1).
  4. **Domain Service & Aturan Agregasi Invariant:** Membangun `PortionRequestService` dengan penegakan:
     - Cutoff H-1 pukul 16:00 WIB: konfirmasi langsung diblokir setelah cutoff terlewat; perubahan setelah cutoff wajib melalui prosedur revisi resmi.
     - Porsi melebihi siswa terdaftar: mewajibkan alasan tertulis sebelum konfirmasi disetujui.
     - Revisi & Pembatalan: mewajibkan pencantuman alasan tertulis (minimal 5 karakter) dan mencatat jejak audit operasional.
     - Invariant Agregasi Kepala SPPG: Sekolah yang belum konfirmasi (pending/draft) **TIDAK diasumsikan nol porsi**, melainkan disajikan sebagai kuota belum terdata. Total porsi terkonfirmasi murni menjumlahkan data sah (`CONFIRMED`, `LOCKED`, `REVISED`).
  5. **Keamanan & Isolasi Tenant:** Penegakan ketat melalui `DailyPortionRequestPolicy` dan FormRequests. PIC hanya memiliki wewenang pada sekolahnya sendiri (`$user->school_id === $portionRequest->school_id`). Role lain (kurir, asisten, gizi) atau PIC sekolah lain ditolak dengan HTTP 403.
  6. **UI/UX Task-First Operations (P02):** Dibangun dengan standar *Clean Luxury*, mobile-first, zero em dash (`—`), touch target $\ge 44 \times 44$ px, kontras teks tinggi, date selector, cutoff banner, kartu snapshot data pokok, form input porsi & kehadiran siswa, accordion revisi terotorisasi, pembatalan layanan resmi, serta ledger histori permintaan 10 hari terakhir.
  7. **UI/UX Monitoring Terintegrasi (Kepala SPPG):** Halaman monitoring `/head/portions` menyajikan agregasi tanggal layanan, *Aggregation Invariant Warning Banner*, KPI porsi, tabel sekolah menunggu konfirmasi dengan kontak PIC dan batas cutoff, serta tabel rincian sekolah terkonfirmasi.
  8. **Otomasi Pengujian:** Menulis `PortionRequestTest.php` dengan 19 skenario pengujian komprehensif (83 assertions). Seluruh test suite (92 tests, 680 assertions) lulus 100% tanpa regresi.
- **Konsekuensi:** PBI-04 selesai secara utuh, aman, terdokumentasi, dan terverifikasi secara visual pada 6 ukuran viewport browser (320px, 360px, 390px, 430px, 768px, 1440px).

---

## DEC-013: Critical Hardening Gate PBI-04 : Integritas State Machine, Pemisahan Revisi & Non-Destructive GET
- **Tanggal:** 13 Oktober 2026
- **Status:** APPROVED & IMPLEMENTED
- **Konteks:** Critical Audit Gate PBI-04 sebelum PBI-05 untuk menyelesaikan 6 temuan struktural pada domain logic permintaan porsi:
  1. Jalur transisi status LOCKED belum lengkap dan tidak memiliki timestamp `locked_at`.
  2. Pengajuan revisi menggantikan langsung `requested_portions` dan masih dihitung dalam agregat terkonfirmasi padahal belum disetujui Kepala SPPG.
  3. Akses GET ke halaman P02 memanggil `getOrCreateForDate()` yang dapat menulis draft baru tanpa aksi pengguna.
  4. Pembatalan (`cancelRequest`) tidak memiliki guard terhadap status yang sudah dibatalkan atau request yang telah digunakan dalam rencana produksi.
  5. Pengajuan porsi di atas data pokok siswa memerlukan persetujuan otorisasi terpisah, bukan sekadar alasan tertulis.
  6. Cutoff dikonfirmasi sebagai parameter operasional proyek (default H-1 16:00 WIB), bukan klaim standar statuter BGN tanpa dasar.
- **Keputusan:**
  1. **Migration Non-Destruktif:** Menambahkan kolom `revised_portions`, `previous_status`, `revision_requested_at`, `revision_reviewed_by`, `revision_reviewed_at`, `revision_rejection_reason`, `is_exceeds_approved`, `exceeds_approved_by`, `exceeds_approved_at`, `exceeds_rejection_reason` via migration `2026_10_13_000002_add_hardening_columns_to_daily_portion_requests_table.php`.
  2. **Pemisahan Angka Resmi vs Usulan Revisi:** `submitRevision()` tidak lagi menimpa `requested_portions`. Usulan disimpan di `revised_portions` dengan status `REVISED`. Dalam `getSppgAggregateForDate()`, porsi `REVISED` diisolasi dari `total_confirmed_portions` sampai disetujui Kepala SPPG melalui `approveRevision()`.
  3. **Transisi Sah ke LOCKED:** Mengimplementasikan `lockRequest()` dengan row-level pessimistic locking (`lockForUpdate`), pengisian `locked_at`, sifat idempoten tanpa duplikasi audit log, dan penolakan edit sebagai draft bila sudah `LOCKED`.
  4. **Pemberlakuan GET Read-Only murni:** Mengganti `getOrCreateForDate()` menjadi `getForDate()`. GET halaman P02 murni membaca data dan merender empty state "Belum dibuat". Pembuatan draft baru hanya terjadi pada POST eksplisit dengan penanganan concurrent request aman.
  5. **Pengamanan Pembatalan:** Memblokir pembatalan berulang pada record berstatus `CANCELLED` dan memblokir pembatalan sepihak bila porsi telah digunakan dalam rencana produksi terfinalisasi (`isUsedInFinalizedProductionPlan()`).
  6. **Persetujuan Pengecualian Kuota:** Permintaan di atas `registered_count` ditandai `is_exceeds_approved = false` dan diisolasi dalam daftar `unapproved_exceptions` pada agregat sampai disetujui secara terpisah oleh Kepala SPPG.
  7. **Otomasi Pengujian:** Menulis `PortionRequestHardeningTest.php` dengan 11 feature tests komprehensif (54 assertions).
- **Konsekuensi:** Seluruh 6 temuan audit gate PBI-04 tuntas dan lulus 100%. Tidak ada mutasi database pada request GET.

---

## DEC-014: Implementasi Resmi PBI-05 : Rencana Produksi & Alokasi Dapur SPPG (Halaman K04)
- **Tanggal:** 13 Oktober 2026
- **Status:** APPROVED & IMPLEMENTED
- **Konteks:** Mengubah kebutuhan porsi harian sekolah yang telah disahkan secara resmi menjadi rencana produksi SPPG dan alokasi sekolah yang dapat dipertanggungjawabkan (Page K04 `/head/production`), serta integrasi dashboard Asisten Lapangan (`/production/dashboard`).
- **Keputusan:**
  1. **Skema Database:** Membuat migration `2026_10_13_000003_create_production_plans_and_allocations_tables.php` untuk tabel `production_plans` dan `production_allocations` lengkap dengan foreign key constraints, unique key `['sppg_id', 'service_date', 'menu_id']`, index performa, dan relasi Eloquent terpadu.
  2. **State Machine Produksi:** Membuat backed enum `ProductionPlanStatus` (`PLANNED`, `FINALIZED`, `IN_PRODUCTION`, `READY_FOR_DISTRIBUTION`, `COMPLETED`, `CANCELLED`). Untuk lingkup PBI-05 diimplementasikan transisi legal `PLANNED -> FINALIZED`.
  3. **Domain Service & Atomicity Finalisasi:** Membangun `ProductionPlanService` yang mengeksekusi:
     - Hanya menu `ACTIVE` milik SPPG yang dapat digunakan.
     - Hanya permintaan porsi berstatus `CONFIRMED` atau `LOCKED` tanpa pengecualian tertahan yang dapat dialokasikan.
     - Sekolah berstatus pending/draft tidak diasumsikan nol porsi; keberadaan sekolah pending atau revisi belum disahkan memblokir finalisasi rencana kerja.
     - Pengecekan ketat batas kapasitas dapur SPPG (`max_daily_portions`). Alokasi melebihi kapasitas dapur diblokir dengan validasi ramah pengguna.
     - Finalisasi transaksional atomik via `DB::transaction()` dan `lockForUpdate()`: mengunci plan, mengunci seluruh `DailyPortionRequest` menjadi `LOCKED` (dengan `locked_at` dan audit log), membuat baris `production_allocations`, dan mencatat `activity_logs` event `production_plan.finalized`.
     - Finalisasi ganda (double-submit) ditangani secara idempoten tanpa duplikasi baris atau efek samping ganda.
  4. **Otorisasi & Kebijakan:** `ProductionPlanPolicy` membatasi pembuatan dan finalisasi rencana produksi hanya untuk role `KEPALA_SPPG` dalam tenant yang sama. Asisten Lapangan hanya diberikan hak baca (read-only) untuk rencana yang telah berstatus `FINALIZED`.
  5. **UI/UX Operational Planning Workspace (K04):** Dirancang dengan paradigma *Operational Production Planning Workspace, Premium Calm Utility, Forest Green (#1C6654), Mobile-First*:
     - Mobile 390px / 320px: tanggal layanan -> ringkasan kesiapan & kapasitas -> daftar pengecualian/blocker -> seleksi menu aktif -> tabel matriks alokasi sekolah -> kontrol finalisasi.
     - Desktop 1440px: split layout dengan matriks alokasi sekolah di sisi kiri, menu hidangan aktif, gramatur komponen, dan kontrol finalisasi di sisi kanan.
     - Zero em dashes (`—`), touch targets $\ge 44 \times 44$ px, kontras WCAG 2.2 AA.
  6. **Integrasi Asisten Lapangan (Runbook):** Dashboard `/production/dashboard` diperbarui untuk menampilkan data target sah rencana `FINALIZED`, rincian komponen menu aktif, dan tabel alokasi sekolah read-only tanpa memalsukan simulasi batch memasak (data aktual produksi dipisahkan untuk PBI-06).
  7. **Otomasi Pengujian:** Menulis `ProductionPlanTest.php` dengan 13 feature tests komprehensif (59 assertions). Seluruh test suite (116 tests, 794 assertions) lulus 100% tanpa regresi.
- **Konsekuensi:** PBI-05 selesai secara end-to-end, aman dari race condition, multi-tenant terlindungi, dan terverifikasi secara visual pada 6 resolusi browser.---

## DEC-015: Critical Hardening Gate PBI-05 : Hard Gates A - F Integritas Alokasi, Rekonsiliasi & Kapasitas
- **Tanggal:** 13 Oktober 2026
- **Status:** APPROVED & IMPLEMENTED
- **Konteks:** Menuntaskan 6 Hard Gate kritis audit independen sebelum memasuki implementasi PBI-06:
  1. **Hard Gate A (Kelengkapan Alokasi):** Menolak payload frontend yang sengaja menghilangkan satu sekolah eligible. Memvalidasi daftar sekolah aktif secara langsung dari database dalam transaksi atomik.
  2. **Hard Gate B (Rekonsiliasi Porsi 1:1):** Menolak alokasi di bawah atau di atas kebutuhan resmi (under/over allocation) tanpa prosedur revisi yang sah.
  3. **Hard Gate C (Multiple Plans & Kapasitas Kumulatif):** Memblokir alokasi berulang pada request yang sama lintas rencana produksi, serta memastikan akumulasi seluruh rencana produksi pada tanggal yang sama tidak melampaui `sppgs.max_daily_portions`.
  4. **Hard Gate D (Immutability Pasca-Finalisasi):** Memblokir `submitRevision()`, `approveRevision()`, `approveExceedingException()`, dan `rejectExceedingException()` terhadap permintaan porsi yang telah terkunci dalam rencana produksi berstatus `FINALIZED`.
  5. **Hard Gate E (Kapasitas Dapur & Integritas Foreign Key):** Menolak finalisasi jika kapasitas dapur bernilai 0, null, atau tidak sah. Mengubah foreign key master data pada `production_plans` dan `production_allocations` menjadi `restrictOnDelete`.
  6. **Hard Gate F (Segregasi Agregat UI):** Memisahkan secara ketat `total_eligible_portions`, `total_exception_pending_portions`, dan `total_administrative_portions` pada `PortionRequestService::getSppgAggregateForDate()`.
- **Keputusan:**
  1. Mengimplementasikan invariant lengkap di `ProductionPlanService::finalizePlan()`.
  2. Menambahkan `ProductionPlanHardeningTest.php` dengan 11 feature tests (28 assertions). Seluruh tes lulus 100% (127 tests, 822 assertions).
- **Konsekuensi:** Pondasi rencana produksi aman mutlak dari manipulasi frontend, kebocoran alokasi, double counting, dan inkonsistensi snapshot.

---

## DEC-016: Implementasi PBI-06 : Eksekusi Produksi, Pelacakan Batch & Quality Control (Halaman A02 & G03)
- **Tanggal:** 13 Oktober 2026
- **Status:** APPROVED & IMPLEMENTED
- **Konteks:** Menghubungkan rencana produksi yang telah difinalisasi (`ProductionPlan FINALIZED`) dengan eksekusi nyata di dapur, pencatatan batch kerja, realisasi porsi memasak, uji organoleptik mutu pangan, dan otorisasi pelepasan ke distribusi sekolah (Page A02 `/production/plans/{plan}/execute` dan Page G03 `/nutrition/quality-control`).
- **Keputusan:**
  1. **Skema Database:** Membuat migration `2026_10_13_000004_create_food_batches_quality_checks_and_evidence_photos_tables.php` untuk tabel:
     - `food_batches`: `batch_code`, `portion_category`, `planned_portions`, `actual_portions`, `status`, `released_by`, `released_at`, `created_by`, timestamps, indeks tenant.
     - `production_stage_logs`: `food_batch_id`, `stage`, `started_at`, `completed_at`, `updated_by`, `notes`.
     - `quality_checks`: `food_batch_id`, `check_stage`, `checked_by`, `appearance_ok`, `aroma_ok`, `texture_ok`, `taste_ok`, `temperature_c`, `conclusion` (SAFE, UNSAFE, HOLD), `notes`, `checked_at`.
     - `evidence_photos`: polymorphic relation (`evidenceable_type`, `evidenceable_id`), `file_path`, `mime_type`, `file_size`, `uploaded_by`.
  2. **Invarian 4 Angka Berbeda:** Menegakkan pemisahan tegas antara:
     - `requested_portions` (kebutuhan sekolah terkonfirmasi)
     - `allocated_portions` (alokasi sah rencana kerja)
     - `actual_portions` (porsi yang benar-benar selesai dimasak)
     - `accepted_portions` (porsi yang lolos inspeksi QC)
     Target awal tidak pernah ditimpa atau dimanipulasi saat mencatat realisasi aktual; selisih dicatat secara transparan.
  3. **State Machine & Urutan Tahapan Dapur:**
     - Enums: `ProductionStage` (`PREPARATION`, `COOKING`, `PORTIONING`, `PACKING`, `READY_FOR_QC`), `FoodBatchStatus` (`PREPARING`, `COOKING`, `PORTIONING`, `PACKED`, `QC_CHECK`, `RELEASED`, `ON_HOLD`, `CANCELLED`), `QualityCheckConclusion` (`SAFE`, `UNSAFE`, `HOLD`).
     - Urutan tahapan ditegakkan secara sekuensial tanpa boleh dilompati: `PREPARATION -> COOKING -> PORTIONING -> PACKING`.
     - Pembuatan batch pertama mentransisikan `ProductionPlan` dari `FINALIZED -> IN_PRODUCTION`.
     - Pelepasan seluruh batch rencana kerja mentransisikan `ProductionPlan` dari `IN_PRODUCTION -> READY_FOR_DISTRIBUTION`.
  4. **Pemeriksaan Mutu & Keamanan Pangan (Pengawas Gizi):**
     - Seluruh 4 parameter organoleptik (Penampilan/Warna, Aroma, Tekstur, Rasa) wajib dievaluasi.
     - Pengukuran suhu inti pangan (°C) dicatat dengan panduan standar saji panas $>60^\circ\text{C}$.
     - Status `HOLD` / `UNSAFE` secara mutlak mengunci batch pada status `ON_HOLD` dan memblokir pelepasan ke armada distribusi kurir.
     - Hanya Pengawas Gizi dari SPPG yang sama yang dapat mengeksekusi inspeksi QC.
  5. **UI/UX Operational Kitchen & QC Decision Workspace:**
     - **Page A02 (Asisten Lapangan):** Dirancang sebagai *Operational Kitchen Workspace, Functional Precision, Forest Green (#1C6654)*, kartu 4 invarian, stepper tahapan batch, form realisasi porsi aktual dengan kalkulator selisih, upload bukti foto makanan siap kemas, zero em dashes (`—`), touch target $\ge 44 \times 44$ px.
     - **Page G03 (Pengawas Gizi):** Dirancang sebagai *QC Decision Workspace, Ocean Blue (#285F80)*, antrean batch menunggu QC, metrik pemantauan, lembar evaluasi organoleptik terperinci, kartu radio semantik (SAFE vs HOLD), dan riwayat pemeriksaan.
  6. **Otomasi Pengujian:** Menulis `ProductionExecutionTest.php` dengan 15 feature tests (65 assertions). Seluruh test suite (142 tests, 887 assertions) lulus 100% tanpa regresi.
- **Konsekuensi:** Rantai data operasional lengkap dari kebutuhan porsi, rencana dapur, realisasi memasak, hingga otorisasi QC makanan aman selesai dan dapat diaudit secara penuh.

---

## DEC-017: Critical Hardening Gate PBI-06 : Keamanan Pangan Nyata, Bukti Foto Wajib, Rekonsiliasi Porsi & MySQL Constraints
- **Tanggal:** 13 Oktober 2026
- **Status:** APPROVED & IMPLEMENTED
- **Konteks:** Menuntaskan seluruh Hard Gate kritis A hingga F untuk audit keamanan pangan, integritas data, dan otorisasi sebelum implementasi PBI-07:
  1. **Hard Gate A (Standar Keamanan Pangan & Keputusan QC Nyata):**
     - Ambang suhu $\ge 60^\circ\text{C}$ didefinisikan secara ilmiah sebagai parameter pendukung penyimpanan panas (hot-holding) sesuai pedoman BGN, BPOM, dan Kemenkes, bukan klaim jaminan laboratorium instan.
     - Protokol uji organoleptik non-paksaan: Petugas/Pengawas Gizi tidak dipaksa mencicipi makanan yang terindikasi rusak atau terkontaminasi (`taste_tested = false` dengan `taste_skipped_reason`). Jika pengujian rasa dilewati karena indikasi bahaya/kontaminasi, kesimpulan `SAFE` diblokir secara absolut.
     - Kewajiban referensi SOP (`sop_reference`, default `SOP-BGN-MUTU-01`) dan alasan keputusan manusia (`decision_reason` minimal 10 karakter) yang mencatat identitas pemeriksa, waktu server, dan versi menu.
  2. **Hard Gate B (Bukti Foto FOOD_READY Wajib & Alur Pengecualian Beralasan):**
     - Foto `FOOD_READY` wajib diunggah pada checkpoint packing sebelum penyerahan ke QC.
     - Disediakan alur pengecualian beralasan resmi (`has_evidence_exception = true`, `evidence_exception_reason` $\ge 15$ karakter, `evidence_exception_approved_by`) bila kondisi darurat operasional/kegagalan teknis terjadi tanpa menghentikan penanganan fisik makanan berisiko.
     - Keamanan berkas: Penyimpanan pada private storage (`local`), validasi MIME asli melalui isi berkas (JPEG, PNG, WebP), pembatasan ukuran $\le 5$ MB, sanitasi nama berkas menggunakan UUID acak, dan otorisasi akses unduh melalui route privat `/evidence-photos/{evidencePhoto}` yang dikawal `EvidencePhotoPolicy`.
  3. **Hard Gate C (Rekonsiliasi Porsi 5 Angka):**
     - Pemisahan mutlak 5 angka: `requested_portions`, `allocated_portions`, `planned_portions`, `actual_portions`, dan `accepted_portions`.
     - Batas kumulatif: Akumulasi seluruh `planned_portions` batch tidak boleh melebihi kuota rencana produksi.
     - Penegakan `accepted_portions <= actual_portions` dan penolakan pelepasan porsi melebihi hasil masak nyata.
  4. **Hard Gate D (Status, Concurrency & Append-Only QC History):**
     - Riwayat `QualityCheck` bersifat append-only: Pemeriksaan ulang setelah status `ON_HOLD` membuat baris baru tanpa menimpa histori pemeriksaan terdahulu.
     - Batch `RELEASED` terkunci dari pengeditan bebas oleh Asisten Lapangan.
     - Penolakan pelepasan langsung batch `ON_HOLD` tanpa alur re-inspeksi yang sah.
  5. **Hard Gate E (Migrasi Korektif MySQL Non-Destruktif):**
     - Eksekusi migration `2026_10_13_000005_harden_pbi06_and_implement_pbi07_traceability.php` pada database MySQL `mbg_track_dev`.
     - Mengubah foreign key `production_plans` dan `production_allocations` dari `ON DELETE CASCADE` menjadi `ON DELETE RESTRICT` (diverifikasi melalui `information_schema.REFERENTIAL_CONSTRAINTS`).
     - Menambahkan kolom hardening pada `food_batches` dan `quality_checks`.
  6. **Hard Gate F (Otorisasi & Bukti QC):**
     - Akses lintas SPPG diblokir (HTTP 403) pada seluruh alur batch, QC, dan evidence photos.
     - Publik diblokir secara menyeluruh dari data operasional internal.
- **Konsekuensi:** Pondasi PBI-06 sepenuhnya aman, memenuhi standar audit independen dan tata kelola keamanan pangan resmi.

---

## DEC-018: Implementasi Resmi PBI-07 : Batch & Keterlacakan Dua Arah (Bidirectional Traceability) & Halaman C02
- **Tanggal:** 13 Oktober 2026
- **Status:** APPROVED & IMPLEMENTED
- **Konteks:** Membangun keterlacakan penuh dan audit trail dari batch dapur hingga sekolah penerima tanpa melanggar batas lingkup PBI-08 (Trip & Pengiriman).
- **Keputusan:**
  1. **Skema Hubungan Traceability (Tabel `delivery_items`):**
     - Membuat tabel `delivery_items` yang menghubungkan `food_batch_id` dengan `production_allocation_id` secara resmi sesuai kontrak dokumentasi v4.
     - Mendukung staging pengiriman dengan `status = PENDING_DISPATCH` dan `trip_stop_id` bernilai NULL (tanpa membuat trip/driver/kendaraan palsu yang merupakan wewenang PBI-08).
  2. **Keterlacakan Dua Arah (Bidirectional Traceability):**
     - Arah Maju: Dari `FoodBatch` -> `ProductionPlan` -> Menu & Versi -> Riwayat Tahapan Dapur (`production_stage_logs`) -> Bukti Foto / Pengecualian -> Riwayat QC (`quality_checks`) -> Sekolah Penerima (`delivery_items` -> `productionAllocation` -> `school`).
     - Arah Mundur: Dari Sekolah/Alokasi -> `delivery_items` -> `FoodBatch` asal.
  3. **Penyajian Halaman C02 (`/batches/{batch}`):**
     - Dibangun dengan Anti-Slop Mode DURING (Calm Operational Editorial, Forest Green #1C6654, Ocean Blue #285F80, Slate #334E68).
     - Menampilkan 4 metrik porsi invariant (`planned`, `actual`, `accepted`, `rejected`), status batch, menu hidangan dengan versi, timeline tahapan dapur dengan operator dan timestamp WIB, riwayat inspeksi QC dengan parameter organoleptik lengkap, alasan keputusan, dan referensi SOP, galeri bukti foto privat, serta tabel sekolah tujuan dengan label status jujur: *"Belum dijadwalkan untuk pengiriman"*.
     - Desain responsif mobile-first pada 390px, 320px hingga desktop 1440px. Zero em dashes (`—`), touch target $\ge 44 \times 44$ px, kontras WCAG 2.2 AA.
  4. **Otorisasi & Kebijakan:**
     - Route `/batches/{batch}` dikawal oleh `FoodBatchPolicy::view`, membatasi akses hanya untuk pengguna dalam SPPG yang sama dan menolak pihak luar/publik dengan HTTP 403.
  5. **Otomasi Pengujian:**
     - Menulis `BatchTraceabilityAndSafetyHardeningTest.php` dengan 19 skenario pengujian menyeluruh (73 assertions).
     - Seluruh test suite (161 tests, 960 assertions) lulus 100% tanpa regresi.
---

## DEC-019: PBI-07 Integrity Hardening: Rekonsiliasi Kelayakan Produksi, Otorisasi Pengecualian Foto, dan Kontrol Bukti
- **Tanggal:** 13 Oktober 2026
- **Status:** APPROVED & IMPLEMENTED
- **Konteks:** Menuntaskan seluruh Critical Hardening Gates A hingga F yang diwajibkan sebelum membuka modul logistik PBI-08:
  1. **Gate A (Integritas READY_FOR_DISTRIBUTION):**
     - Memperbaiki `QualityControlService::checkAndUpdatePlanReadiness()`. Status `READY_FOR_DISTRIBUTION` dilarang diberikan jika masih terdapat kuota alokasi yang belum dibuatkan batch, atau jika terjadi kekurangan porsi produksi (shortage) terhadap komitmen alokasi sah sekolah.
     - Porsi rencana, realisasi, dan diterima dipisahkan secara matematis. Partial release tetap diakui pada level batch individual tanpa menyesatkan status keseluruhan production plan.
  2. **Gate B (DeliveryItem Berbasis Kuantitas Nyata):**
     - Memperbaiki `FoodBatchService::linkBatchToAllocations()`. Menghapus fallback berbahaya yang menambahkan sisa porsi ke sekolah pertama jika melampaui kuota alokasi sah.
     - Mengaitkan `DeliveryItem.released_portions` dengan `FoodBatch.accepted_portions` nyata hasil QC, sehingga muatan logistik tidak dapat melampaui porsi yang disahkan.
  3. **Gate C (Persetujuan Pengecualian Bukti Foto Independen):**
     - Memperketat `FoodBatchService::requestEvidenceException()` dan `approveEvidenceException()`. Pengaju pengecualian dicatat (`evidence_exception_requested_by`).
     - Self-approval dicegah secara mutlak: pengaju dilarang menyetujui permohonannya sendiri (`approved_by != requested_by`). Penyetujuan diwajibkan oleh pejabat berwenang (Kepala SPPG atau Pengawas Gizi).
     - QC menolak batch yang memiliki pengecualian berstatus pending (belum disetujui).
  4. **Gate D (Eksplisit QC Accepted Portions):**
     - Memperbarui `PerformQualityCheckRequest` dan `QualityControlService::recordQualityCheck()`. Jika kesimpulan `SAFE`, `accepted_portions` wajib diisi secara eksplisit oleh petugas (tidak boleh fallback diam-diam ke `actual_portions`).
     - Validasi matematis ketat: $0 \le accepted \le actual$.
  5. **Gate E (Otorisasi Bukti Foto Berbasis Sekolah PIC):**
     - Memperketat `EvidencePhotoPolicy::view()`. PIC Sekolah dibatasi hanya dapat melihat foto dari batch yang secara sah ditautkan ke sekolahnya via `DeliveryItem`. Akses lintas batch/sekolah lain dan lintas SPPG ditolak (HTTP 403).
     - Penambahan header keamanan `Content-Type` dan `Content-Disposition: inline` pada `EvidencePhotoController`.
  6. **Gate F (Standar SOP & Keputusan Manusia):**
     - Penegakan `decision_reason` minimal 10 karakter dan pemisahan referensi operasional. Uji rasa pada makanan rusak/terkontaminasi tetap dilarang keras.
- **Konsekuensi:** Integritas data hulu pangan 100% aman dan teruji sebelum pengiriman logistik dimulai.

---

## DEC-020: PBI-08 Perencanaan Logistik, Kendaraan, Rute Pengiriman, dan Penugasan Kurir
- **Tanggal:** 13 Oktober 2026
- **Status:** APPROVED & IMPLEMENTED
- **Konteks:** Mengimplementasikan modul logistik PBI-08 sesuai kontrak resmi dokumentasi v4 (`DELIVERY-PLANNING.md`, `DRIVER-TRIP.md`, `ROUTE-DETAIL.md`, `DATA-DICTIONARY.md`):
  1. **Skema Database & Migrasi Non-Destruktif:**
     - Menjalankan migrasi `2026_10_13_000006_create_vehicles_trips_and_trip_stops_tables.php` pada MySQL `mbg_track_dev`.
     - Tabel `vehicles`: `sppg_id`, `plate_number`, `vehicle_name`, `vehicle_type`, `capacity_portions`, `is_active`.
     - Tabel `trips`: `sppg_id`, `trip_code`, `service_date`, `vehicle_id`, `driver_id`, `trip_type`, `status` (`ASSIGNED`, `VEHICLE_CHECKED`, `READY`, `IN_PROGRESS`, `COMPLETED`, `CANCELLED`), timestamps per transisi (`assigned_at`, `vehicle_checked_at`, `departed_at`, `completed_at`, `cancelled_at`), dan `vehicle_check_passed`.
     - Tabel `trip_stops`: `trip_id`, `school_id`, `stop_order`, `planned_portions`, `loaded_portions`, `status` (`PENDING`, `ARRIVED`, `HANDED_OVER`, `SKIPPED`).
     - Mengaitkan `delivery_items.trip_stop_id` ke `trip_stops.id`.
  2. **Domain Service `TripDispatchService`:**
     - Penjadwalan trip atomik dengan DB transaction dan row locking (`lockForUpdate`).
     - Validasi kelayakan muatan: Hanya batch `RELEASED` yang dapat dimuat, jumlah loaded tidak boleh melebihi sisa `accepted_portions` yang tersedia.
     - Validasi kapasitas armada: Total porsi trip tidak boleh melampaui `capacity_portions` kendaraan.
     - Validasi konflik ketersediaan: Kurir dan kendaraan yang sedang aktif pada trip berjalan dilarang ditugaskan ganda pada tanggal yang sama.
     - State machine perjalanan kurir: Alur transisi status resmi `ASSIGNED` -> `VEHICLE_CHECKED` -> `READY` -> `IN_PROGRESS` dengan audit trail lengkap pada `activity_logs`.
  3. **Antarmuka Premium Calm Utility (Mode DURING):**
     - Halaman K05 (`/head/distribution`): Workspace operasional Kepala SPPG untuk pemantauan armada, kurir, alokasi makanan siap muat, perumusan rute multi-stop, dan ringkasan perjalanan.
     - Halaman D01 (`/driver/trips`): Antarmuka mobile-first kurir dengan kartu tugas aktif, riwayat perjalanan, dan pengingat keselamatan berkendara (aplikasi hanya digunakan saat kendaraan berhenti sempurna).
     - Halaman D02 (`/driver/trips/{trip}/prepare`): Checklist kesiapan armada & manifest muatan batch sebelum keberangkatan.
     - Halaman C03 (`/trips/{trip}`): Detail rute perjalanan lintas-role dengan manifest detail per stop sekolah.
  4. **Pencegahan Eksploitasi & Batasan Cakupan:**
     - Multi-tenant scoping: Kepala SPPG dan Kurir hanya dapat mengelola data dalam SPPG masing-masing.
     - Request tampering prevention: Foreign keys divalidasi di backend service, bukan mempercayai input tersembunyi.
     - Strict scope boundary: Tidak mengimplementasikan GPS live tracking palsu, estimasi waktu fiktif, penerimaan sekolah, atau retur ompreng (dicadangkan untuk PBI berikutnya).
  5. **Otomasi Pengujian:**
     - Menulis `TripDispatchTest.php` dengan 15 skenario pengujian komprehensif (92 assertions).
     - Seluruh test suite (181 tests, 1070 assertions) lulus 100% tanpa regresi.
- **Konsekuensi:** Sistem logistik operasional kini mampu menerbitkan jadwal pengiriman yang dapat dipertanggungjawabkan secara fisik dan administratif.

---

## DEC-021: PBI-08 Critical Hardening & Smart Dispatch Maps (MapLibre GL JS, OpenFreeMap, OSRM Routing, Swipe-to-Depart & 4-Block Dispatch Gate)
- **Tanggal:** 13 Oktober 2026
- **Status:** APPROVED & IMPLEMENTED
- **Konteks:** Menuntaskan audit independen dan hardening kritis pada modul logistik PBI-08 sebelum keberangkatan fisik armada:
  1. Status LOADED ditetapkan saat trip dibuat, bukan setelah muatan fisik diverifikasi.
  2. Kurir dapat memulai perjalanan hanya dengan satu checklist kendaraan tanpa verifikasi muatan resmi dan otorisasi Kepala SPPG.
  3. Belum ada Dispatch Gate 4-blok lengkap dan belum ada mekanisme swipe-to-depart anti-salah-tekan.
  4. Belum ada integrasi peta interaktif MapLibre GL JS, OpenFreeMap vector basemap, dan OSRM road network routing engine.
  5. Method `startTrip()` mengubah `tracking_status` menjadi `ACTIVE` padahal GPS telemetri (PBI-09) belum terpasang.
  6. Validasi tanggal alokasi terhadap tanggal layanan trip belum ditegakkan secara menyeluruh.
  7. Validasi urutan stop (`sequence_number`) belum menjamin urutan legal (mulai dari 1, berurutan, tanpa gap, tanpa duplikat).
  8. Kapasitas armada bernilai 0 atau null belum tertolak secara fail-safe.
  9. Potensi fallback `released_portions` ke `planned_portions` saat bernilai null.
- **Keputusan:**
  1. **Hard Gate A (Pemisahan PLANNED vs LOADED):**
     - Di `TripDispatchService::createTrip()`, item pengiriman di-stage pada status `PENDING_LOADING` dengan `loaded_portions = null`.
     - Pembuatan trip sama sekali tidak mengubah muatan menjadi `LOADED`.
     - Verifikasi fisik muatan dilakukan melalui `verifyLoading()` oleh Asisten Lapangan atau Kepala SPPG, mencatat jumlah fisik aktual dan mentransisikan status `DeliveryItem` menjadi `LOADED`.
  2. **Hard Gate B (Pre-Departure Verification & 4-Block Dispatch Gate):**
     - Ditegakkan 4-Block Dispatch Gate sebelum trip dinyatakan layak berangkat (`TripStatus::READY`):
       - Blok 1: Seluruh batch muatan telah disahkan QC (`FoodBatchStatus::RELEASED`).
       - Blok 2: Muatan fisik diverifikasi dan dicatat (`loading_verified = true`, `loading_verified_by`, `loading_verified_at`).
       - Blok 3: Kelaikan armada diperiksa oleh kurir (`vehicle_check_passed = true`, `vehicle_checked_at`).
       - Blok 4: Otorisasi keberangkatan disahkan oleh Kepala SPPG (`dispatch_approved = true`, `dispatch_approved_by`, `dispatch_approved_at`).
     - Pencegahan self-approval: Petugas pemeriksa muatan tidak boleh merangkap otorisasi dispatch bila peran berbeda disyaratkan; kurir dilarang mengesahkan dispatch sendiri.
     - Keberangkatan kurir diaktifkan via gesture **Swipe-to-Depart** yang divalidasi ulang secara atomik di backend service sebelum mentransisikan status trip menjadi `IN_PROGRESS`.
  3. **Hard Gate C (Rekonsiliasi Muatan 4-Angka & Kapasitas Armada):**
     - Pemisahan tegas: `planned_portions`, `released_portions`, `loaded_portions`, dan `delivered_portions`.
     - Aturan matematis: $loaded\_portions \le released\_portions$.
     - Sumber $released\_portions$ murni berasal dari $accepted\_portions$ hasil inspeksi QC sah; tidak ada fallback ke $planned\_portions$ jika bernilai null.
     - Kapasitas kendaraan wajib terdefinisi $>0$. Kapasitas null atau 0 ditolak secara fail-safe dengan pesan validasi resmi.
     - Satu batch dilarang dimuat melebihi sisa $accepted\_portions$ yang tersedia; alokasi ganda dalam satu payload ditolak.
  4. **Hard Gate D (Validasi Tanggal & Urutan Multi-Stop):**
     - Seluruh delivery item divalidasi memiliki `service_date` yang sama dengan `service_date` trip, berasal dari SPPG yang sama, dan ditujukan ke sekolah terdaftar dalam tenant.
     - Validasi `stop_order` / `sequence_number`: Wajib mulai dari 1, berurutan tanpa jeda angka, tidak ada nomor duplikat, dan jumlah stop sama persis dengan sekolah tujuan.
  5. **Hard Gate E (Pemisahan Status Perjalanan vs GPS Telemetri):**
     - Saat kurir melakukan swipe-to-depart, `Trip.status` berubah menjadi `IN_PROGRESS`, namun `tracking_status` disetel ke `'WAITING_FOR_LOCATION'` (`tracking_started_at = null`).
     - Dilarang keras menampilkan marker kurir live palsu, simulasi kecepatan, atau polyline GPS buatan sebelum modul telemetri PBI-09 diimplementasikan.
  6. **Arsitektur Peta & OSRM Engine:**
     - Map Engine: MapLibre GL JS (v6.14.0) via npm bundle Vite.
     - Basemap: OpenFreeMap Liberty style (`https://tiles.openfreemap.org/styles/liberty`), bebas API key publik.
     - Routing Adapter: `OsrmRoutingService` terisolasi dengan timeout 5 detik, caching koordinat berbasis MD5, proteksi SSRF (validasi rentang numerik ketat lat -90..90, lng -180..180), GeoJSON polyline decoding, dan graceful fallback (garis lurus dashed atau kartu peringatan rute offline) tanpa memalsukan geometri jalan.
  7. **Driver Mobile UX & Swipe-to-Depart:**
     - Antarmuka mobile kurir (320px–430px) dirancang one-hand usability, safe-area aware, bebas iklan/saldo/fitur ojol komersial.
     - Slider horizontal interaktif ("Geser untuk mulai perjalanan →") dengan ambang aktivasi 82%, visual fill progress bar, snap-back animation bila dilepas sebelum ambang, kunci double-submit, dan fallback keyboard ramah screen-reader.
  8. **Peta Publik Tersanitasi (Page U03):**
     - Endpoint `/public/trips/{tripCode}/map` dan `/public/trips/{tripCode}/map-data` bersifat read-only tanpa login.
     - DTO publik disanitasi ketat: Hanya menyajikan titik sekolah tujuan, nomor urut stop, status pengiriman makro, dan timestamp pembaruan terakhir.
     - Nomor telepon kurir, plat kendaraan, catatan operasional privat, dan koordinat presisi internal disaring total.
  9. **Pengujian Menyeluruh:**
     - 196 automated tests lulus (1,136 assertions, 0 errors, 100% pass rate).
     - Terdiri dari 15 feature tests `TripDispatchHardeningTest` + 15 tests `TripDispatchTest` + 166 tests modul baseline & PBI sebelumnya.
- **Konsekuensi:** Sistem logistik distribusi MBG-Track kini terlindungi penuh oleh 4-Block Dispatch Gate terotorisasi, verifikasi muatan fisik nyata, rute multi-stop berbasis jaringan jalan resmi, dan antarmuka mobile kurir yang ergonomis dan aman.

---

## DEC-015: PBI-08 Final Integrity Hardening & Conditional PBI-09 Real GPS Telemetry Implementation
- **Tanggal:** 13 Oktober 2026
- **Status:** APPROVED & IMPLEMENTED
- **Konteks:** Audit independen terhadap commit `e279e07` menemukan beberapa celah integrasi kritis pada penanganan AJAX swipe-to-depart, verifikasi parsial manifest, isolasi data lintas-role pada Map API, Stored XSS pada marker MapLibre, kebocoran rute sebelum keberangkatan, dan TTL caching routing kegagalan OSRM. Setelah seluruh hardening lulus tanpa celah Critical/High, pekerjaan dilanjutkan ke PBI-09 (Real GPS Telemetry & Active Trip Navigation) sesuai kontrak dokumentasi v4.
- **Keputusan:**
  1. **Critical Gate A (Swipe-to-Depart AJAX Contract):**
     - Memperbaiki `DriverTripController::start()` agar selalu mengembalikan respons JSON terstruktur `{success: true, trip_status: 'IN_PROGRESS', started_at: ..., tracking_status: 'WAITING_FOR_LOCATION'}` saat `expectsJson()` bernilai true.
     - Menyediakan status reconciliation idempotent: jika koneksi jaringan putus setelah server melakukan commit DB dan kurir menekan swipe ulang, server mengembalikan status 200/409 secara elegan tanpa menduplikasi activity log `trip.started`.
  2. **Critical Gate B (Full Manifest Verification):**
     - Mengubah `TripDispatchService::verifyLoading()` agar memvalidasi SELURUH delivery items yang terikat pada trip.
     - Menolak payload yang menghilangkan salah satu item manifest, menduplikasi item, menyertakan item asing dari trip lain, atau memuat kuantitas melebihi kuota rilisan QC yang sah.
     - Memvalidasi total muatan terhadap kapasitas kendaraan secara atomik dengan database lock (`lockForUpdate()`).
  3. **Critical Gate C (Dynamic Dispatch Approval Revocation):**
     - Otorisasi keberangkatan Kepala SPPG terikat secara ketat pada kondisi manifest.
     - Jika manifest muatan, armada, atau pengemudi diubah setelah trip berstatus `READY`, otorisasi `dispatch_approved` otomatis dicabut (`dispatch_approved = false`) dan status trip dikembalikan ke `VEHICLE_CHECKED`/`ASSIGNED`.
     - Validasi seluruh gate (QC release, kelaikan armada, kapasitas, status keaktifan kurir) dievaluasi ulang di dalam transaksi database saat swipe keberangkatan.
  4. **High Gate D (Isolasi Data Lintas Role pada Map API):**
     - Merevisi `TripDetailController::mapData()` untuk menerapkan field-level authorization berbasis DTO:
       - PIC Sekolah: Hanya menerima data sekolah miliknya. Detail sekolah lain disamarkan (`school_name` menjadi "Pemberhentian #X", kuantitas dan alamat disetel null). Objek driver dan vehicle dihapus total.
       - Kurir: Menerima rute operasional dan kendaraan tanpa data administratif sensitif.
       - Kepala SPPG: Menerima manifest operasional lengkap.
  5. **High Gate E (Pemberantasan Stored XSS pada MapLibre Markers):**
     - Menghapus raw HTML interpolation string pada `Popup.setHTML()` di `resources/js/dispatch-map.js`.
     - Menggunakan konstruksi safe DOM tree (`document.createElement()`, `textContent`) yang dimasukkan via `Popup.setDOMContent()`. Seluruh adversarial script injection pada nama atau alamat sekolah ternetralisasi sempurna.
  6. **High Gate F (Public Map Publication Policy):**
     - Menerapkan kebijakan visibilitas publik pada `PublicDeliveryMapController`: Trip dengan status `ASSIGNED`, `VEHICLE_CHECKED`, `READY`, atau `CANCELLED` ditolak dengan HTTP 404. Hanya trip `IN_PROGRESS` atau `COMPLETED` yang dapat diakses publik.
     - Rute publik dilindungi rate limiting `throttle:60,1`.
  7. **High Gate G (Kelengkapan Match Expression Enum):**
     - Menambahkan penanganan menyeluruh untuk seluruh nilai `TripStopStatus` (`ARRIVED_AWAITING_CONFIRMATION`, `HANDED_OVER`, `COMPLETED`, `SKIPPED`, `EXCEPTION`) dan `TripStatus` dengan safe `default` fallback, mengeliminasi risiko `UnhandledMatchError`.
  8. **Routing Quality Gate & Hybrid Navigation:**
     - Menetapkan pemisahan TTL cache OSRM: 7.200 detik (2 jam) untuk keberhasilan, dan 60 detik untuk kegagalan sementara.
     - Menambahkan validasi integritas GeoJSON LineString sebelum menyimpan `route_geometry`.
     - Menyediakan metode `invalidateTripRouteCache()`.
     - Endpoint GET `mapData` diubah menjadi strictly read-only tanpa memutasi state database.
     - Hybrid navigation: Preview rute keseluruhan ditampilkan lembut (Slate `#94A3B8` dashed), sedangkan leg aktif menuju tujuan saat ini ditonjolkan dengan Forest Pine `#1C6654`.
  9. **PBI-09 Implementation (Real GPS Telemetry & Driver Navigation):**
     - Membuat tabel `gps_locations` dan model `GpsLocation` sesuai kamus data v4.
     - Membuat `GpsTrackingService` untuk mencatat koordinat nyata dari Geolocation API perangkat kurir (`POST /driver/trips/{trip}/telemetry`).
     - Validasi ketat: identitas kurir terautentikasi, tenant scope, trip aktif, koordinat bumi sah (bukan 0,0), time skew (<5 menit ke depan, <24 jam ke belakang).
     - Komputasi status keaktifan jujur tanpa fake GPS: `ONLINE` (lokasi <120s), `STALE` (lokasi 120s–600s), `OFFLINE` (>600s), `WAITING_FOR_LOCATION`.
     - Halaman Kurir D03 (`/driver/trips/{trip}/active`) dengan izin lokasi transparan, penanganan browser limitasi mobile yang jujur, dan tombol konfirmasi kedatangan di sekolah tujuan (`POST /driver/trips/{trip}/stops/{stop}/arrive`).
- **Konsekuensi:**
  - 215 automated tests lulus (1,221 assertions, 0 errors, 100% pass rate).
  - Terdiri dari 11 tests `Pbi08IntegrityAuditTest`, 8 tests `Pbi09GpsTrackingTest`, 15 tests `TripDispatchHardeningTest`, 15 tests `TripDispatchTest`, dan 166 tests modul pendukung lainnya.
  - Dokumentasi v4 tetap 100% read-only tanpa perubahan. Seluruh berkas `.md` terisolasi dari Git remote.

---

## DEC-022: Critical MapLibre Worker Rendering Fix & PBI-09 GPS Telemetry Hardening
- **Tanggal:** 13 Oktober 2026
- **Status:** APPROVED & IMPLEMENTED
- **Konteks:** Investigasi mendalam terhadap tangkapan layar peta MapLibre yang pucat dan polos (hanya menampilkan kontrol zoom dan atribusi OpenFreeMap tanpa jalan, label, atau bangunan):
  1. **Root Cause:** MapLibre GL JS v6.14.0 dalam lingkungan bundling client Vite mencari Web Worker melalui `new URL('./maplibre-gl-worker.mjs', import.meta.url)`. Berkas worker tidak ter-emit secara otomatis ke dalam `public/build/assets/`, menyebabkan permintaan HTTP worker mengembalikan status `404 Not Found`. Akibatnya worker decoding vector tiles (`.pbf`) dan komputasi geometri WebGL gagal secara diam-diam (`Worker failed to load`), meninggalkan kanvas peta tanpa layer jalan dan bangunan.
  2. **Persetujuan Berbagi GPS (Gate A):** Halaman Kurir D03 (`/driver/trips/{trip}/active`) sebelumnya memanggil `startTracking()` secara otomatis pada `DOMContentLoaded`.
  3. **Integritas Kronologis Timestamp GPS (Gate B):** Titik telemetri jaringan yang datang terlambat (out-of-order) berpotensi menimpa koordinat terakhir trip dengan data kadaluarsa.
  4. **Validasi Telemetri Ketat (Gate C):** Diperlukan validasi rentang nilai akurasi ($\ge 0$), kecepatan ($\ge 0$), heading ($0..360^\circ$), penolakan future timestamp skew (>1 menit), deteksi outlier lonjakan koordinat tak masuk akal (speed implikasi $>60\text{ m/s}$), dan idempoten payload duplikat.
  5. **Urutan Kedatangan Sekolah Sekuensial (Gate D):** Driver tidak boleh menandai stop kedua atau ketiga tiba sebelum stop pertama berstatus tiba/selesai.
  6. **Pemantauan Kepala SPPG Berkelanjutan (Gate E):** Halaman pemantauan (`/trips/{trip}` dan `/head/distribution`) memerlukan polling otomatis berkala (10 detik) untuk memperbarui posisi marker driver tanpa me-refresh seluruh halaman.
  7. **Audit Informasi Publik & PIC:** Endpoint `map-data` publik harus menggunakan timestamp aktual perubahan model (`updated_at`), bukan `now()`.
- **Keputusan:**
  1. **Perbaikan MapLibre Worker Asset Bundling:**
     - Mengimpor berkas worker secara eksplisit di `resources/js/dispatch-map.js` via Vite URL loader: `import maplibreWorkerUrl from 'maplibre-gl/dist/maplibre-gl-worker.mjs?url'` dan menetapkan `setWorkerUrl(maplibreWorkerUrl)`.
     - Vite kini meng-emit `public/build/assets/maplibre-gl-worker-*.mjs` (508.31 kB) dengan respons HTTP 200.
     - Terverifikasi pada browser subagent: 111 layer aktif termuat (31 layer jalan, 2 layer bangunan, 15 layer label). Jalan tol, jalan utama, persimpangan, nama jalan, dan jejak bangunan tampak tajam pada zoom 12, 14, 16, dan 18.
  2. **Pemberlakuan Critical Gate A (Persetujuan Eksplisit Kurir):**
     - Menghapus auto-start `startTracking()` pada `DOMContentLoaded` di `resources/views/driver/trips/active.blade.php`.
     - Menambahkan kartu persetujuan terkelola dengan tombol aksi eksplisit "Mulai Bagikan Lokasi" dan "Hentikan Berbagi Lokasi".
     - Melampirkan edukasi hak akses privasi presisi, perilaku penguncian layar ponsel, dan tata cara pemberhentian pelacakan.
     - Membersihkan watcher secara otomatis pada event `beforeunload`.
  3. **Pemberlakuan Critical Gate B (Urutan Kronologis Timestamp):**
     - Di `GpsTrackingService::recordDriverLocation()`, seluruh titik telemetri dicatat ke `GpsLocation` untuk audit trail.
     - Cache posisi terakhir `Trip` (`last_latitude`, `last_longitude`, `last_accuracy_m`, `last_location_at`) HANYA diperbarui bila `recorded_at` titik baru secara kronologis lebih baru daripada `last_location_at` yang telah tersimpan. Data yang datang terlambat tidak dapat memundurkan posisi terakhir.
  4. **Pemberlakuan Critical Gate C (Validasi Telemetri & Outlier):**
     - Validasi ketat: `accuracy >= 0`, `speed >= 0`, `heading >= 0 && heading <= 360`, future skew $\le 60$ detik.
     - Deteksi outlier lonjakan koordinat berbasis formula Haversine: jika kecepatan implikasi antara titik sebelumnya dan titik baru melampaui $60\text{ m/s}$ (~$216\text{ km/jam}$) pada jarak $>500$ meter, request ditolak dengan 422 `ValidationException`.
     - Payload duplikat (`recorded_at`, `lat`, `lng` sama) ditangani secara idempoten tanpa duplikasi baris database.
  5. **Pemberlakuan Critical Gate D (Urutan Kedatangan Sekolah Sekuensial):**
     - Di `GpsTrackingService::markStopArrived()`, stop divalidasi tidak boleh mendahului stop sebelumnya.
     - Seluruh stop dengan nomor urut lebih kecil (`sequence_number < current`) wajib telah berstatus `ARRIVED`, `ARRIVED_AWAITING_CONFIRMATION`, `HANDED_OVER`, `COMPLETED`, atau `SKIPPED`. Aksi bersifat idempoten dan mencatat audit log.
  6. **Pemberlakuan Critical Gate E (Live Polling Kepala SPPG):**
     - Menambahkan parameter `pollInterval: 10000` pada `dispatch-map.js`.
     - Marker driver bergerak secara halus via `driverMarker.setLngLat(...)` tanpa me-reset zoom atau posisi kamera pengguna.
     - Jika telemetri tidak diperbarui atau koneksi terputus, status STALE/OFFLINE ditampilkan tanpa badge LIVE palsu.
  7. **Pemberlakuan Audit Publik (Gate 8):**
     - `PublicDeliveryMapController` mengembalikan `last_updated` dari `$trip->updated_at`, bukan `now()`.
     - Koordinat presisi kurir tetap disembunyikan dari publik dan PIC Sekolah.
- **Konsekuensi:**
  - Full PHPUnit test suite: **225 tests passed, 0 failures, 1,261 assertions, 100% pass rate**.
  - Frontend production build: Vite compiled successfully dengan asset worker `maplibre-gl-worker-DxGNRm0a.mjs` (508.31 kB).
  - Verifikasi browser subagent lulus di 6 viewport (320px, 360px, 390px, 430px, 768px, 1440px) pada K05, D02, D03, C03, dan U03.
  - Batas lingkup dipatuhi: Pekerjaan dihentikan setelah PBI-09 hardening tuntas. Tidak memulai PBI-10.

---

## DEC-023: Final PBI-09 Polish & Real-World Map Hardening (Keyed Marker Lifecycle, GPS Stop Sync & Stop Preconditions)
- **Tanggal:** 13 Oktober 2026
- **Status:** APPROVED & IMPLEMENTED
- **Konteks:** Penyempurnaan menyeluruh terhadap stabilitas visual MapLibre, sinkronisasi status penghentian GPS, penegakan prasyarat sekuensial stop, serta integritas angka dan tanggal pembaruan operasional:
  1. **Keyed Marker Lifecycle:** Sebelumnya `renderMapData()` di `dispatch-map.js` menghapus seluruh marker dan membuatnya ulang pada setiap polling, menyebabkan marker flicker dan menutup popup interaktif pengguna.
  2. **Sinkronisasi Penghentian GPS Kurir:** Tombol "Hentikan Berbagi Lokasi" kurir perlu terhubung secara atomik ke server melalui endpoint terotorisasi `POST /driver/trips/{trip}/telemetry/stop` agar status monitoring Kepala SPPG tidak mempertahankan status `ONLINE` palsu.
  3. **Integritas Status Terminal & Prasyarat Sekuensial Stop:** Stop dengan status final (`HANDED_OVER`, `COMPLETED`, `SKIPPED`) dilarang diubah kembali menjadi `ARRIVED`. Stop berikutnya dilarang tiba sebelum serah terima stop sebelumnya tuntas (dependensi eksplisit pada serah terima PBI-10).
  4. **Keamanan Diagnostic Endpoint:** Route `/dev/map-test` dibatasi secara ketat hanya pada environment `local` dan `testing` (`abort(404)` pada production).
  5. **Integritas Angka QC & Timestamp Publik:** `TripStop::getTotalReleasedPortionsAttribute()` dilarang fallback ke `planned_portions` saat `released_portions` bernilai null. Timestamp `last_updated` publik mencerminkan perubahan operasional nyata terakhir (maksimum dari trip, stop arrived/updated, dan GPS).
- **Keputusan:**
  1. Mengimplementasikan `schoolMarkers` Map dan `driverMarker` keyed lifecycle di `resources/js/dispatch-map.js`. Popup tetap terbuka saat polling dan kamera tidak reset.
  2. Menambahkan `GpsTrackingService::stopDriverTracking()` dan route `driver.trips.telemetry.stop`.
  3. Memperketat `GpsTrackingService::markStopArrived()` terhadap terminal state dan dependensi serah terima sekolah sebelumnya.
  4. Menambahkan environment guard pada route `/dev/map-test`.
  5. Memperbaiki `getTotalReleasedPortionsAttribute()` dan komputasi `last_updated` di `PublicDeliveryMapController`.
- **Konsekuensi:**
  - Full PHPUnit test suite: **233 tests passed, 0 failures, 1,292 assertions, 100% pass rate**.
  - Frontend production build: Vite compiled successfully dalam 3.15s.
  - Verifikasi browser nyata: Screenshot Zoom 16 (`zoom_16_surabaya_map_1791885406090.png`), Public Trip U03 (`public_trip_map_u03_1791885452443.png`), dan Driver D03 (`driver_active_trip_initial_1791885575494.png`).
  - Nol (0) file `.md` di-push ke Git remote.

---

## DEC-024: PBI-09 Critical Runtime Regression Fix (MapLibre Constructor Collision, GPS Stop Acknowledgement, Race Condition Guard & Smoothness)
- **Tanggal:** 13 Oktober 2026
- **Status:** APPROVED & IMPLEMENTED
- **Konteks:** Menuntaskan regresi runtime dan penguatan GPS telemetri pada commit baseline e2e63b2:
  1. **MapLibre Constructor Collision Fix:** Mengubah `import { Map as MapLibreMap, ... } from 'maplibre-gl'` pada `resources/js/dispatch-map.js`. Inisialisasi peta geografis menggunakan `MapLibreMap`, sedangkan keyed marker collection menggunakan native ES6 `new Map()` standar tanpa collision atau shadowing.
  2. **Stop GPS Acknowledgement Contract:** Pada `resources/views/driver/trips/active.blade.php`, membedakan secara tegas: sensor lokal dimatikan segera (`clearWatch` untuk privasi), sinkronisasi sedang berjalan, terkonfirmasi server (`response.ok` dan JSON success), status sedang diperiksa ulang (reconciliation), dan sinkronisasi gagal dengan tombol coba ulang tanpa menyalakan sensor.
  3. **GPS Start/Stop Race Condition Protection:** Menerapkan session generation sequencing (`currentSessionId`) dan validasi timestamp pada backend (`GpsTrackingService` & `DriverTripController`). Request stop yang datang terlambat setelah sesi baru aktif tidak dapat mematikan sesi aktif (`tracking_started_at > stopped_at`). Telemetri tertunda yang tiba setelah stop (`recorded_at <= tracking_ended_at`) tidak dapat menghidupkan kembali status `ONLINE`.
  4. **Smooth Map Rendering & Motion:** Keyed marker lifecycle dipertahankan, popup sekolah tetap terbuka saat update polling, kamera stabil tanpa reset bounds, dan pergerakan marker kurir diinterpolasi secara halus via `requestAnimationFrame` dengan penghormatan terhadap `prefers-reduced-motion`.
  5. **Public Map Status Refresh (U03):** Menambahkan polling ringan (15s) pada `delivery-map.blade.php` dengan callback `onTelemetryUpdate`. Data tetap tersanitasi ketat tanpa membocorkan koordinat atau identitas kurir. Polling otomatis dijeda saat tab tidak aktif (`document.hidden`).
- **Konsekuensi:**
  - Full PHPUnit test suite: **236 tests passed, 0 failures, 1,307 assertions, 100% pass rate**.
  - Frontend JS regression suite (`npm test`): **5/5 tests passed**.
  - Frontend production build: Vite compiled successfully (`maplibre-gl-worker-DxGNRm0a.mjs` 508.31 kB).
  - True browser E2E test via Chromium subagent: dev map zoom 16, public map U03, Kepala SPPG K05, trip detail C03, dan kurir D03 terverifikasi dengan screenshot resolusi tinggi dan rekaman video.
  - Sesuai instruksi: file `.md` tidak di-stage atau di-push ke Git remote.

---

## DEC-025: PBI-10 Critical Integrity & Security Hardening (Gates A–J)
- **Tanggal:** 14 Oktober 2026
- **Status:** APPROVED & IMPLEMENTED
- **Konteks:** Menindaklanjuti audit independen kesiapan produksi PBI-10 yang menemukan kelemahan integritas data, otorisasi akses foto, kalkulasi multi-batch, keamanan pangan, dan rendering blade:
  1. Multi-batch portion duplicate count: penulisan total received ke setiap delivery item menghasilkan angka delivered ganda.
  2. Fallback planned portions saat loaded portions nol/null melanggar batas pemisahan muatan nyata vs rencana masak.
  3. Over-receipt (received > expected) otomatis berstatus RECEIVED tanpa pencatatan selisih dan otorisasi khusus.
  4. Organoleptik taste_ok=false dapat di-override menjadi SAFE, pemaksaan uji rasa pada makanan rusak, dan inkonsistensi formula accepted vs damaged.
  5. Fallback batch QC lintas-stop mengambil batch dari sekolah lain dalam trip yang sama.
  6. Otorisasi foto privat membandingkan integer cast null (0 === 0) dan risiko file orphan saat rollback transaksi DB.
  7. Runtime error Blade memanggil method `$serviceRecord->isConfirmed()` yang belum terdefinisi pada model.
  8. Hak akses histori penerimaan terblokir oleh policy write `confirmReceipt` saat stop berstatus HELD/REJECTED.
  9. Transisi multi-stop mengaktifkan stop berikutnya secara prematur saat stop pertama berstatus HELD/EXCEPTION.
- **Keputusan:**
  1. **Gate A (Multi-Batch Portion Integrity):** Mengganti penulisan duplicate count dengan alokasi berurutan bertahap per item muatan yang dibatasi oleh `loaded_portions`, menjamin $\sum delivered = received$ tanpa manipulasi angka.
  2. **Gate B (Zero Loaded Fail-Safe):** Menghapus seluruh fallback ke `planned_portions`. Bila muatan fisik nol/null, sistem melempar `ValidationException` dan menampilkan banner peringatan di UI.
  3. **Gate C (Over-Receipt & Discrepancy):** Kondisi `received > expected` secara tegas diklasifikasikan sebagai `RECEIVED_WITH_DISCREPANCY` (bukan clean `RECEIVED`), dan input `notes` diwajibkan bila ada selisih.
  4. **Gate D (Food Safety & Non-Coercion):** Menegakkan bahwa jika salah satu indikator organoleptik bernilai `false`, kesimpulan `SAFE` ditolak keras. Tasting pada makanan berindikasi rusak dilewati secara aman (`taste_tested=false`). Formula QC ditegakkan: `accepted = received - damaged` saat SAFE.
  5. **Gate E (Batch Provenance):** Menghapus fallback batch lintas stop. Stop hanya merekam QC untuk batch makanan yang terhubung langsung pada manifest stop tersebut.
  6. **Gate F (Evidence Photo Policy & Orphan Cleanup):** Menambahkan handler polimorfik eksplisit untuk `TripStop` dan `SchoolServiceRecord` dengan isolasi sekolah PIC, trip kurir, dan tenant SPPG. File upload yang tersimpan sebelum DB exception dihapus dari disk dalam blok catch.
  7. **Gate G (Model Methods):** Menambahkan `isConfirmed()`, `isHeld()`, `isRejected()`, dan `hasDiscrepancy()` pada `SchoolServiceRecord`.
  8. **Gate H (Read vs Write Separation):** Memisahkan otorisasi `viewReceipt` (akses histori untuk PIC) dan `confirmReceipt` (hanya write). Idempoten pada double submission dan mengunci penyuntingan pada terminal state.
  9. **Gate I (Multi-Stop Route Consistency):** Next stop (`EN_ROUTE`) hanya diaktifkan jika stop saat ini berstatus `HANDED_OVER`. Bila terjadi `EXCEPTION` (HELD), stop berikutnya tidak diaktifkan.
  10. **Gate J (Regression Test Suite):** Menulis `tests/Feature/Pbi10IntegrityAndHardeningTest.php` dengan 14 regression test menyeluruh membuktikan seluruh bug berhasil direproduksi dan diselesaikan.
- **Konsekuensi:**
  - Full PHPUnit: **280 tests passed, 0 failures, 1,538 assertions (100% pass rate)**.
  - JS Tests: **9 passed, 0 failures**.
  - Production Build: Vite build sukses.
  - E2E Browser Testing: Terverifikasi di Chromium (1440px dan 390px).
  - Nol (0) file `.md` di-push ke remote repository.
  - Sesuai batas: Pekerjaan DIHENTIKAN setelah hardening PBI-10 tuntas. Tidak memulai PBI-11.

---

## DEC-026: PBI-10 Final Multi-Batch Reconciliation Gate (Gates A–H)
- **Tanggal:** 14 Oktober 2026
- **Status:** APPROVED & IMPLEMENTED
- **Konteks:** Menuntaskan audit independen integritas data multi-batch, physical provenance, batasan muatan per-item, keamanan penyimpanan berkas, terminologi tanda tangan digital, dan regresi tampilan P04:
  1. **Gate A (QC Multi-Batch Rounding Bug):** Pembagian rata `round(total / batchCount)` menghasilkan drift porsi fiktif (101 porsi dibagi 3 batch menjadi 34 * 3 = 102 porsi), porsi diterima melebihi muatan fisik pada batch kecil (batch muat 10 porsi menerima QC accepted 50 porsi), dan fabrikasi pembagian kerusakan matematis tanpa observasi fisik.
  2. **Gate B (Delivered Cannot Exceed Loaded Per Item):** Alokasi remainder pada item terakhir memaksakan porsi terkirim melampaui muatan nyata saat terjadi over-receipt (misal item muat 100 menerima delivered 110). Invarian dilanggar: `item.delivered_portions > item.loaded_portions`.
  3. **Gate C (Remove Per-Item Planned Fallback):** Penggunaan `$item->loaded_portions ?: $item->planned_portions` menyebabkan item dengan `loaded_portions = 0` dialokasikan porsi terkirim hanya karena memiliki `planned_portions > 0`.
  4. **Gate D (Physical Batch Provenance):** Alokasi shortage FIFO atau pembagian kerusakan rata mengklaim provenance palsu tanpa bukti fisik. Tidak boleh mengubah asumsi matematis menjadi fakta riwayat pangan.
  5. **Gate E (Evidence Storage Transactional Safety):** Berkas foto bukti yang diunggah ke storage lokal berisiko menjadi berkas yatim (orphan file) bila transaksi database dibatalkan (rollback) karena exception insert atau activity log gagal.
  6. **Gate F (Digital Receipt Terminology):** Antarmuka pengguna mengklaim teknologi "Digital Signature" kriptografis padahal sistem baru mengandalkan akun terotentikasi, identitas penerima, timestamp server, audit log, dan bukti foto.
  7. **Gate G & H (Test Coverage & Browser Validation):** Diperlukan pembuktian regresi pada test suite dan verifikasi tampilan Chromium pada desktop 1440px dan mobile 390px pada skenario 1 batch, 2 batch, 3 batch, shortage, damage, over-receipt, dan HELD.
- **Keputusan:**
  1. **Gate A (Eliminasi Pembagian Rata QC & Zero-Discrepancy Guarantee):**
     - Pada penerimaan lengkap tanpa selisih (`discrepancy = 0`), setiap batch makanan otomatis dicatat menerima porsi tepat sejumlah `loaded_portions` aslinya (`accepted = loaded, rejected = 0`). Hal ini menyelesaikan pembagian ganjil (101/3) dan batch kecil secara akurat tanpa drift angka atau porsi fiktif.
     - Jika terdapat selisih fisik (shortage/damage) tanpa rincian per-item, sistem menolak mengarang pembagian rata. `QualityCheck.accepted_portions` diset `null`, `rejected_portions = 0`, dan catatan resmi selisih disimpan pada tingkat stop di `SchoolServiceRecord`.
  2. **Gate B (Invarian Strict Capping Delivered <= Loaded):**
     - Setiap delivery item dialokasikan porsi terkirim dengan batas atas mutlak: `$allocated = min($itemLoaded, $remainingDelivered)`.
     - Invarian $0 \le item.delivered\_portions \le item.loaded\_portions$ dan $\sum delivered\_portions \le \sum loaded\_portions$ ditegakkan secara absolut.
     - Kelebihan fisik (over-receipt) tidak ditambahkan ke item manapun, melainkan dicatat transparan pada `SchoolServiceRecord.notes` sebagai overage yang belum direkonsiliasi.
  3. **Gate C (Penghapusan Total Planned Fallback):**
     - Menghapus pola fallback `?: planned_portions`. Bila item memiliki `loaded_portions` 0 atau null, porsi muatan dianggap 0 dan tidak pernah menerima porsi delivered.
  4. **Gate D (Verified Per-Batch Input & Honest Unattributed Handling):**
     - Menambahkan dukungan input per-batch pada `ConfirmSchoolReceiptRequest` (`items.*.received_portions`, `items.*.damaged_portions`).
     - Jika PIC memasukkan verifikasi per-batch, rekonsiliasi batch mencatat angka sesuai bukti fisik yang diverifikasi.
     - Jika verifikasi per-batch tidak tersedia, status dicatat jujur sebagai *Unattributed Stop-Level Discrepancy*.
  5. **Gate E (Transactional Compensation Storage Safety):**
     - Melacak seluruh path berkas yang tersimpan dalam array `$uploadedPaths`.
     - Dalam blok `catch (\Throwable $e)`, seluruh berkas pada `$uploadedPaths` dihapus dari disk sebelum exception dilempar kembali, mencegah berkas orphan saat rollback DB. Berkas yang transaksinya commit tetap utuh.
  6. **Gate F (Terminologi Akurat):**
     - Memperbarui copy UI: Mengganti klaim tanda tangan digital dengan *"Konfirmasi Penerimaan Elektronik"* dan *"Berita Acara Penerimaan Tercatat"* serta subjudul transparan: *"Penerimaan dicatat menggunakan otentikasi akun PIC terdaftar, stempel waktu server (WIB), log audit operasional, dan bukti foto fisik."*
  7. **Gate G (Otomasi Pengujian Integrasi):**
     - Membuat `tests/Feature/Pbi10FinalReconciliationGateTest.php` dengan 11 skenario pengujian komprehensif: pembuktian bug 101/3, pencegahan over-allocation batch kecil, pembuktian bug pembagian damage fiktif, pembuktian bug delivered melampaui loaded, penolakan delivered pada loaded=0/null, cleanup berkas storage saat rollback DB, dan idempoten receipt submission.
     - Seluruh test suite (291 tests, 1,588 assertions) lulus 100%. `npm test` lulus 9/9 suites. Vite build sukses.
  8. **Gate H (Verifikasi Chromium Browser):**
     - Pengujian browser nyata pada viewport 1440px dan 390px membuktikan:
       - 1 Batch: Tampil bersih dengan metrik presisi.
       - 2 Batch: Ledger multi-batch menampilkan 90 dan 60 porsi tanpa drift pembagian.
       - 3 Batch: Form verifikasi fisik per-batch aktif dan responsif.
       - Shortage: Banner selisih 5 porsi tampil dengan peringatan resmi.
       - Damage: Kerusakan 10 porsi tercatat dengan catatan fisik.
       - Over-Receipt: Porsi muatan terkunci pada 100 porsi, surplus 10 porsi tercatat sebagai catatan selisih tanpa mengubah angka item.
       - HELD: Status penahanan karantina dan alasan organoleptik tampil jelas.
       - Map Stability: Peta diagnostik dan publik beroperasi normal tanpa regresi.
- **Konsekuensi:**
  - Full PHPUnit: **291 tests passed, 0 failures, 1,588 assertions (100% pass rate)**.
  - JS Tests: **9 passed, 0 failures**.
  - Production Build: Vite compiled successfully.
  - Nol (0) file `.md` di-push ke remote repository.
  - Sesuai batas: Pekerjaan DIHENTIKAN setelah hardening PBI-10 tuntas. Tidak memulai PBI-11.

---

## DEC-027: PBI-10 Final Provenance & Partial Input Integrity Fix (Gates 1 - 8)
- **Tanggal:** 14 Oktober 2026
- **Status:** APPROVED & IMPLEMENTED
- **Konteks:** Audit integritas rantai pasok pangan menemukan bahwa meskipun perataan drift QC dan pembengkakan delivered portions telah diatasi, masih terdapat kerentanan provenance dan parsialitas input:
  1. **Gate 1 (Partial Per-Batch Input Handling):** Jika request menyertakan array `items` yang tidak lengkap (misal Batch A dikirim namun Batch B dihilangkan), service mengakses `$verifiedItemMap[$item->id]` yang menyebabkan `Undefined array key` (HTTP 500).
  2. **Gate 2 (Penghapusan Fictional FIFO Provenance):** Pada skenario multi-batch discrepancy tanpa verifikasi per batch, pemotongan FIFO sekuensial secara otomatis mengklaim Batch A delivered penuh dan Batch B dipotong, padahal kekurangan fisik dapat terjadi pada batch mana saja.
  3. **Gate 3 (Penegakan Strict Physical Bounds QC Per-Batch):** Pada anomali penerimaan di mana PIC melaporkan porsi diterima melebihi muatan SPPG pada batch tertentu (misal muatan 10 diterima 40 rusak 30), QC resmi wajib dibatasi strictly pada $accepted + rejected \le loaded$, dan surplus fisik dicatat sebagai catatan anomali tanpa dimasukkan ke angka QC resmi.
  4. **Gate 4 (Explicit Verification Mode di UI PIC):** Antarmuka pengguna harus menyediakan pilihan eksplisit:
     - **Mode B (AGGREGATE):** Default aman, hanya memastikan total fisik seluruh kontainer yang diterima. Seluruh input rincian per-batch dinonaktifkan (`disabled = true`) dan tidak dikirimkan ke server.
     - **Mode A (PER_BATCH):** Verifikasi rinci tiap batch, input per-batch aktif dan wajib lengkap, total otomatis disinkronkan ke Langkah 1.
  5. **Gate 5 (Koreksi Kalkulasi Discrepancy UI):** Mengganti rumus lama yang hanya menghitung `shortage = max(0, expected - received)` dengan kalkulasi seimbang: $shortage = \max(0, expected - received)$ dan $overage = \max(0, received - expected)$, menampilkan label dan warna yang jujur saat terjadi kelebihan porsi fisik.
  6. **Gate 6 (Transactional Storage Cleanup Safety):** Pengujian rollback penyimpanan berkas bukti foto dilakukan pada titik kegagalan nyata setelah berkas berhasil ditulis ke disk (melalui failure injection pada event pembuatan `ActivityLog`), membuktikan berkas sempat ada di disk, transaksi DB rollback, berkas baru dihapus bersih dari storage, tidak ada record `EvidencePhoto` yatim, dan berkas transaksi lain tetap utuh.
  7. **Gate 7 (Pengujian Negatif & Integrasi Komprehensif):** Menguji penolakan item ID milik sekolah/stop lain, penolakan items pada mode AGGREGATE, penolakan receipt oleh foreign PIC, idempoten submit ganda, penahanan delivery dengan status HELD yang memblokir aktivasi stop berikutnya, dan keharusan catatan keterangan pada overage/shortage/damage.
  8. **Gate 8 (Verifikasi Chromium Browser):** Verifikasi visual pada viewport desktop 1440px dan mobile 390px, mengonfirmasi Mode B default, Mode A per-batch breakdown, live preview overage/shortage/damage, sertifikat tanda terima dengan ledger multi-batch 5 kolom, dan stabilitas peta MapLibre.
- **Keputusan:**
  1. **Additive Schema Migration:** Membuat migration `2026_10_14_000003_make_rejected_portions_nullable_in_quality_checks_table.php` yang mengubah kolom `rejected_portions` pada tabel `quality_checks` menjadi nullable. Hal ini memungkinkan representasi data yang jujur: `null` ketika kerusakan batch tidak diketahui (bukan `0` palsu).
  2. **Boundary Validation (FormRequest & Service):**
     - Pada `ConfirmSchoolReceiptRequest`: Memvalidasi `verification_mode` (`AGGREGATE`, `PER_BATCH`, `SINGLE_BATCH`). Jika `AGGREGATE`, melarang pengiriman `items`. Jika `PER_BATCH`, memvalidasi bahwa seluruh delivery item yang sah pada stop tersebut hadir tanpa pengecualian (`missingItemIds === empty`), menolak foreign item ID, serta memvalidasi batasan nilai non-negatif.
     - Pada `SchoolHandoverService::confirmReceipt`: Menambahkan guardrail defensif di batas service yang melemparkan `ValidationException` (HTTP 422) jika terdapat manifest item yang hilang dari payload per-batch.
  3. **Eliminasi Fictional FIFO:**
     - Pada skenario multi-batch dengan discrepancy tanpa verifikasi per-batch: `delivered_portions` pada setiap `DeliveryItem` diset `null`, dan status diset `'UNRESOLVED_DISCREPANCY'`.
     - `QualityCheck.accepted_portions` dan `QualityCheck.rejected_portions` diset `null` untuk seluruh batch tersebut, dengan `decision_reason` eksplisit menjelaskan bahwa atribusi belum dirinci per batch demi menjaga integritas data rantai pasok.
  4. **Strict Bounds QC Per-Batch:**
     - Menghitung $legitReceived = \min(loaded, received)$, $legitDamaged = \min(legitReceived, damaged)$, $legitGood = \max(0, legitReceived - legitDamaged)$.
     - $batchAccepted = legitGood$ dan $batchRejected = legitDamaged$, sehingga $batchAccepted + batchRejected \le loaded$.
     - Surplus fisik ($received - loaded > 0$) dicatat secara transparan dalam catatan keputusan audit, tidak pernah digelembungkan ke angka porsi resmi.
  5. **UI Mode A & B serta Live Discrepancy Preview:**
     - Menambahkan kartu pilihan radio Mode B (default) dan Mode A pada form tanda terima di `show.blade.php`.
     - Fungsi `switchVerificationMode(mode)` secara dinamis mengaktifkan/menonaktifkan input per batch dan mensinkronkan total.
     - Fungsi `calculateDiscrepancy()` menghitung `shortage` dan `overage` secara akurat, menampilkan status "Diterima Lengkap & Sesuai Manifest", "-X Kekurangan Porsi (Shortage)", atau "+Y Kelebihan Porsi (Overage)" dengan styling kontras tinggi yang sesuai standar anti-slop.
  6. **Sertifikat & Ledger Multi-Batch:**
     - Memperbarui tabel rincian penerimaan multi-batch pada tanda terima selesai menjadi 5 kolom metrik fisik.
     - Menambahkan Multi-Batch Status Ledger yang menampilkan badge status terverifikasi atau `Atribusi Belum Terekonsiliasi (Unresolved)` saat `delivered_portions === null`.
- **Konsekuensi:**
  - Full PHPUnit: **301 tests passed, 0 failures, 1,635 assertions (100% pass rate)**.
  - JS Regression: **9 passed, 0 failures**.
  - Production Build: Vite compiled successfully.
  - Nol (0) file `.md` di-push ke remote repository (hanya tracked PHP/Blade/JS files).
  - Sesuai batas instruksi: Pekerjaan DIHENTIKAN setelah PBI-10 selesai. Tidak memulai PBI-11.

---

## DEC-028: PBI-11 Trip Closure, Fleet Return, and Operational Reconciliation (Gates 1–10)
- **Tanggal:** 15 Oktober 2026
- **Status:** APPROVED & IMPLEMENTED
- **Konteks:** Mengimplementasikan PBI-11: Penutupan Perjalanan, Kepulangan Armada, dan Rekonsiliasi Operasional sesuai spesifikasi beku dokumentasi v4 (`TRIP-CLOSURE.md`, `FLEET-RETURN.md`, `OPERATIONAL-RECONCILIATION.md`, `DATA-DICTIONARY.md`):
  1. **Gate 1 (Skema Database Non-Destruktif):**
     - Migrasi additive `2026_10_15_000001_add_fleet_return_and_closure_to_trips_and_create_returns_incidents.php` menambahkan field `return_started_at`, `arrived_at_sppg_at`, `closed_by` pada tabel `trips`.
     - Tabel `tray_returns` untuk mencatat sirkulasi wadah fisik terpisah dari makanan: `trays_returned`, `trays_damaged`, `trays_missing`.
     - Tabel `incidents` untuk audit ketidaksesuaian operasional (`category = 'TRAY_DISCREPANCY'`, `severity = 'MEDIUM'/'HIGH'`).
     - Enum baru: `IncidentCategory`, `IncidentSeverity`, `IncidentStatus`, `TrayReturnStatus`.
  2. **Gate 2 (Pemisahan Pengantaran Outbound vs Kepulangan Depot):**
     - Kepulangan armada hanya dapat dimulai ketika seluruh stop pengantaran berada pada status terminal (`HANDED_OVER`, `COMPLETED`, `SKIPPED`, `EXCEPTION`). Stop aktif (`PENDING`, `EN_ROUTE`, `ARRIVED`) memblokir proses kepulangan dengan HTTP 422.
     - Penahanan serah terima (status `EXCEPTION` / `HELD`) tetap diakui sah dan tidak diganggu atau diubah paksa menjadi completed.
  3. **Gate 3 (Pemicu Kepulangan Kurir & Routing OSRM Depot):**
     - Kurir memicu kepulangan via `POST /driver/trips/{trip}/return`, mencatat `return_started_at = now()`.
     - `OsrmRoutingService` menambahkan waypoint kepulangan ke depot SPPG saat trip berstatus returning/completed.
     - Kedatangan fisik di depot dicatat via `POST /driver/trips/{trip}/depot-arrival`, mengisi `arrived_at_sppg_at = now()`.
  4. **Gate 4 (Rekonsiliasi Wadah Fisik & Insiden Selisih):**
     - Rekonsiliasi wadah (`reconcileTrays`) independen secara konseptual dan struktural dari porsi makanan.
     - Validasi nilai non-negatif dan pencatatan tiket `Incident` otomatis saat ditemukan ompreng rusak atau hilang.
  5. **Gate 5 (Penutupan Final Trip & Terminasi Telemetri GPS Permanen):**
     - Penutupan trip via `TripClosureService::closeTrip()` dengan row lock `lockForUpdate()`.
     - Status trip disetel ke `TripStatus::COMPLETED`, `completed_at = now()`, `closed_by` dicatat.
     - Sesi pelacakan GPS dihentikan total: `tracking_status = 'OFFLINE'`, `active_tracking_session_id = null`, `tracking_ended_at = now()`.
     - Proteksi anti-kebangkitan telemetri (telemetry resurrection guard): Request lokasi baru ke trip yang sudah ditutup otomatis ditolak dengan HTTP 422 oleh `GpsTrackingService` tanpa merusak riwayat koordinat yang telah tersimpan.
  6. **Gate 6 (Integritas Provenance Mode B PBI-10):**
     - Porsi diterima berstatus `null` akibat serah terima darurat agregat (Mode B) dipertahankan apa adanya dan tidak pernah dipaksa menjadi `0` palsu.
  7. **Gate 7 (Otorisasi & Multi-Tenant):**
     - Kurir hanya dapat mengelola kepulangan trip miliknya sendiri. Kepala SPPG hanya dapat menutup trip dalam SPPG-nya. PIC Sekolah dan akses lintas-tenant ditolak dengan HTTP 403.
  8. **Gate 8 (UI Anti-Slop):**
     - Halaman Kurir D03 (`resources/views/driver/trips/active.blade.php`) dan Halaman Detail Trip C03 (`resources/views/trips/show.blade.php`) menggunakan token desain resmi (Forest Pine `#1C6654`, Deep Ink `#172923`, Ivory `#F5F7F3`), touch target $\ge 44$px pada mobile 390px dan desktop 1440px.
  9. **Gate 9 & 10 (Otomasi Pengujian, Build Bersih & Git Delivery):**
     - Full PHPUnit: **315 tests passed, 1,777 assertions, 0 failures (100% pass rate)**.
     - JavaScript Regression: **9 passed, 0 failures**.
     - Vite Production Build: Berhasil terkompilasi bersih dalam 6.66 detik.
     - Browser E2E Subagent: Merekam demonstrasi kurir & admin serta screenshot 1440px dan 390px.
     - Git: Commit `a97aaba` dipush ke branch `main` repositori `allfridhosaragii/Tracking-MBG`.
- **Konsekuensi:**
  - Nol (0) file `.md` di-push ke remote repository.
  ---

## DEC-029: PBI-11 Critical Trip Closure Integrity Gate & State Machine Hardening
- **Tanggal:** 15 Oktober 2026
- **Status:** APPROVED & IMPLEMENTED
- **Konteks:** Audit independen menemukan celah serius dalam integritas state machine penutupan perjalanan dan rekonsiliasi ompreng fisik pada PBI-11:
  1. **Gate A Bypass:** `closeTrip()` memalsukan (auto-fabricate) timestamp `return_started_at` dan `arrived_at_sppg_at` jika null saat trip ditutup, sehingga trip dapat ditutup instan tanpa armada pernah bergerak pulang atau tiba di depot.
  2. **Gate B Bypass:** `recordDepotArrival()` mengauto-fill `return_started_at` jika kurir belum pernah mencatat awal kepulangan, melanggar urutan fisik logistik.
  3. **Gate C Bypass:** Rekonsiliasi ompreng tidak membedakan `null` (belum direkonsiliasi) dengan `0`, sehingga trip dapat ditutup tanpa rekonsiliasi wadah fisik.
  4. **Gate D Mutability:** `reconcileTrays()` dapat dipanggil ulang untuk mengubah data ompreng pada trip yang statusnya telah `COMPLETED`.
  5. **Gate E Incident Spawns:** Setiap pengiriman selisih ompreng membuat row baru tiket `Incident` tanpa deduplikasi, dan mengubah selisih menjadi 0 dapat menghilangkan jejak audit insiden.
  6. **Gate F Authority Flaw:** Kurir dapat menutup trip meskipun terdapat insiden terbuka (`OPEN`); Kepala SPPG dapat melakukan self-approval menutup tripnya sendiri ketika bertindak sebagai kurir pada trip dengan insiden terbuka.
- **Keputusan:**
  1. **Gate A (Strict Physical Sequence & No Fabrication):** Menghapus seluruh auto-fabrication timestamp di `TripClosureService::closeTrip()`. Wajib memvalidasi `return_started_at !== null`, `arrived_at_sppg_at !== null`, dan `arrived_at_sppg_at >= return_started_at`.
  2. **Gate B (Depot Arrival Prerequisite):** `recordDepotArrival()` mewajibkan `return_started_at !== null` (menolak dengan HTTP 422 jika belum mulai pulang), menolak jika sudah selesai (`COMPLETED`), serta memvalidasi `arrived_at_sppg_at >= return_started_at`.
  3. **Gate C (Mandatory Tray Reconciliation):** `reconcileTrays()` hanya boleh dipanggil setelah armada tiba di SPPG (`arrived_at_sppg_at !== null`). Nilai `total_trays_returned` wajib diisi sebelum penutupan (`!== null`).
  4. **Gate D (Immutable Closed Trip):** `reconcileTrays()` menolak mutasi pada trip yang telah berstatus `COMPLETED` atau `CANCELLED` dengan HTTP 422.
  5. **Gate E (Incident Deduplication & Audit Preservation):** Saat rekonsiliasi menemukan selisih (rusak/hilang), query mencari tiket `Incident` berstatus `OPEN` kategori `TRAY_DISCREPANCY` pada trip tersebut. Jika ada, lakukan update ringkasan dan tingkat keparahan alih-alih membuat baris baru. Jika angka selisih diperbaiki menjadi 0, tiket tetap `OPEN` dengan catatan audit bahwa angka telah diperbaiki oleh kurir, memerlukan tinjauan formal Kepala SPPG.
  6. **Gate F (Separation of Duties & Authorization Guard):** Kurir dilarang menutup trip jika terdapat insiden terbuka (`OPEN`). Tiket insiden harus diselesaikan/ditutup secara administratif oleh Kepala SPPG di C03. Kepala SPPG dilarang melakukan self-closure jika bertindak sebagai kurir pada trip dengan insiden terbuka (mencegah konflik kepentingan).
  7. **Gate G (Comprehensive Regression Suite):** Membuat `tests/Feature/Pbi11TripClosureIntegrityGateTest.php` mencakup 15 uji skenario negatif (bypass prevention) dan skenario positif (legitimate workflows). Suite menghasilkan 330/330 tests pass.
  8. **Gate H (Browser Verification):** Menguji antarmuka D03 (Kurir) dan C03 (Kepala SPPG) pada resolusi mobile 390px dan desktop 1440px di Chromium nyata, memvalidasi badge status, tombol terkunci, blocker list administratif, dan notice pemisahan tugas.
- **Konsekuensi:**
  - State machine logistik anti-bypass dan data integritas terlindungi 100%.
  - Zero `.md` files committed ke git remote.
  - Sesuai batas instruksi: Pekerjaan DIHENTIKAN setelah PBI-11 Integrity Gate selesai. PBI-12 tidak disentuh.

---

## DEC-030: PBI-12 Reverse Logistics, Tray Return Traceability & Operational Exception Control
- **Tanggal:** 15 Oktober 2026
- **Status:** APPROVED & IMPLEMENTED
- **Konteks:** Mengimplementasikan PBI-12: Reverse Logistics, Tray Return Traceability & Operational Exception Control sesuai spesifikasi beku dokumentasi v4 (`TRAY-RETURN.md`, `OPERATIONAL-EXCEPTIONS.md`, `ROLE-SEPARATION.md`, `DATA-DICTIONARY.md`, `INCIDENT-WORKFLOW.md`, `PBI-BACKLOG.md`):
  1. **Lifecycle Wadah & Reverse Logistics:**
     - Siklus hidup ompreng/wadah makanan dipisahkan dari outbound delivery trip: `INITIATED` -> `READY_FOR_PICKUP` -> `ASSIGNED` -> `PICKED_UP` -> `RETURNED_TO_SPPG` / `DISCREPANCY`.
     - Wadah diinisiasi otomatis pada serah terima sekolah atau manual via `TrayReturnService::initiateForRecord()`.
  2. **Pemisahan Peran Tegas (Separation of Duties):**
     - PIC Sekolah: Menandai wadah siap ambil (`markReady`) dengan rincian fisik (`ready_count`, `clean_count`, `dirty_count`, `damaged_count`).
     - Kepala SPPG: Menugaskan kurir penjemputan (`assignPickup`).
     - Kurir: Mencatat pengambilan fisik di sekolah (`recordPickup`), memasukkan `picked_up_count` dan bukti foto.
     - Asisten Lapangan / Kepala SPPG: Menerima dan menghitung fisik wadah di depot SPPG (`receiveAtDepot`).
  3. **Integritas Provenance & Angka Fisik (Physical Count Integrity):**
     - Selisih Kurir: Jika `picked_up_count !== ready_count`, status disetel `DISCREPANCY` dan tiket `Incident` kategori `TRAY_DISCREPANCY` otomatis dibuat.
     - Selisih Depot: Dihitung `$totalAccounted = $returned_count + $damaged_count`. Jika `$totalAccounted === expectedAtDepot` dan `missing_count === 0`, status disetel `RETURNED_TO_SPPG`. Jika terdapat kekurangan atau `missing_count > 0`, status disetel `DISCREPANCY` dan insiden dicatat tanpa menimpa jumlah yang diambil kurir.
  4. **Operational Exception Control & Incident State Machine:**
     - Alur resmi insiden: `OPEN` -> `ACKNOWLEDGED` -> `IN_PROGRESS` -> `RESOLVED` -> `CLOSED`, dengan dukungan `REOPENED`.
     - Otorisasi terisolasi (`IncidentPolicy`):
       - `acknowledge`: Kepala SPPG atau Pengawas Gizi.
       - `startProgress`: Kepala SPPG, Pengawas Gizi, atau Asisten Lapangan.
       - `resolve`: Kepala SPPG atau Pengawas Gizi (wajib mengisi `resolution_notes`).
       - `close`: Kepala SPPG saja.
       - `reopen`: Pelapor asli atau Kepala SPPG (wajib mengisi `reopen_reason`).
  5. **Integrasi Trip Closure Guard:**
     - `TripClosureService` mengevaluasi insiden yang belum selesai (`whereNotIn('status', [RESOLVED, CLOSED])`) pada level Trip, Stop, ServiceRecord, dan TrayReturn.
     - Kurir diblokir dari menutup trip jika terdapat insiden unresolved.
     - Kepala SPPG diblokir dari self-approval menutup tripnya sendiri jika terdapat insiden unresolved.
  6. **Antarmuka Anti-Slop (Mode DURING & AFTER):**
     - Halaman P06 (`/school/trays`): Manajemen wadah siap ambil oleh PIC Sekolah.
     - Halaman D05 (`/driver/pickups`): Antarmuka penjemputan wadah oleh Kurir.
     - Halaman A03 (`/production/logistics`): Ledger retur ompreng SPPG & rekonsiliasi oleh Asisten Lapangan.
     - Halaman C01 (`/incidents` & `/incidents/{incident}`): Monitoring & alur penyelesaian insiden lintas-role.
     - Desain mematuhi warna Forest Pine (`#1C6654`), Deep Ink (`#172923`), Ivory (`#F5F7F3`), touch target $\ge 44$px pada mobile 390px dan desktop 1440px.
  7. **Hasil Pengujian & Verifikasi:**
     - Feature test baru: `tests/Feature/Pbi12ReverseLogisticsAndTrayReturnTest.php` (10 tests, 69 assertions, 100% pass rate).
     - Full PHPUnit: **340 tests passed, 0 failures, 1914 assertions (100% pass rate)**.
     - JavaScript Regression: **9 passed, 0 failures**.
     - Production Build: Vite compiled successfully (2.73s).
     - Browser E2E: Screenshot resolusi tinggi tersimpan untuk P06, D05, A03, C01 pada mobile 390px dan desktop 1440px.
- **Konsekuensi:**
  - PBI-12 reverse logistics & operational exception control tuntas 100%.
  - Seluruh 12 PBI pada MBG-Track kini telah berhasil diimplementasikan dan diverifikasi secara independen.
  - Nol (0) file `.md` di-stage atau di-push ke Git remote.

---

## DEC-031: PBI-12 Final Integrity & Security Exit Gate (Gates A–F)
- **Tanggal:** 15 Oktober 2026
- **Status:** APPROVED & IMPLEMENTED
- **Konteks:** Menuntaskan enam celah integritas dan otorisasi terarah hasil audit independen pada baseline `b6fe184` tanpa membangun ulang PBI-12:
  1. **Gate A — Expected Trays Verified Physical Source:**
     - Menghapus substitusi spekulatif `expected_trays = received_portions`. Tanpa manifest fisik resmi, `expected_trays` bernilai `null` dengan `is_expected_verified = false`.
     - Jumlah 0 wadah terverifikasi tetap 0 dan tidak dipaksa menjadi 1.
     - Perbedaan terhadap baseline yang belum terverifikasi tidak memicu insiden fisik palsu.
  2. **Gate B — Official Pickup Assignment Enforcement:**
     - Mengintegrasikan endpoint resmi Kepala SPPG: `POST /head/distribution/tray-returns/{trayReturn}/assign` (`head.distribution.trays.assign`).
     - Kurir hanya melihat dan dapat mengeksekusi penjemputan resmi yang ditugaskan ke dirinya (`pickupTripStop.trip.driver_id === auth()->id()`). Pengambilan tanpa assignment atau oleh kurir lain ditolak dengan HTTP 403 Forbidden.
  3. **Gate C — Cross-Tenant Incident Creation Security:**
     - Client input `sppg_id` diabaikan sepenuhnya; tenant SPPG diturunkan secara otoritatif dari authenticated user dan relasi domain resmi.
     - PIC Sekolah dibatasi hanya untuk sekolahnya sendiri. Pengguna internal dibatasi pada sekolah dalam unit SPPG-nya.
     - Whitelist ketat polymorphic subject types (`Trip`, `TripStop`, `TrayReturn`, `SchoolServiceRecord`, `FoodBatch`, `QualityCheck`, `School`, `Sppg`). Class arbitrer ditolak dengan HTTP 422.
     - Subject ID fiktif (0) dihapus; default dialihkan ke root entity domain yang autentik (`School` atau `Sppg`).
  4. **Gate D — Pickup Discrepancy Remains in Depot Queue:**
     - Rekor penjemputan dengan selisih kurir (`picked_up_at IS NOT NULL && returned_at IS NULL`) tetap berada pada antrean `$incomingReturns` di Halaman A03 (`LogisticsController::index`) hingga penerimaan fisik di depot selesai dicatat.
  5. **Gate E — Idempotency & Incident Deduplication:**
     - Retry identik pada `markReady`, `recordPickup`, dan `receiveAtDepot` bersifat idempoten tanpa mengubah timestamp asli, tanpa menduplikasi activity log, dan tanpa menggandakan tiket insiden.
  6. **Gate F — Read-Only GET Endpoint:**
     - Menghapus loop auto-inisialisasi dari `School\TrayReturnController::index()`. Halaman GET `/school/trays` murni membaca data tanpa efek samping mutasi database.
     - Command CLI `php artisan trays:backfill` disediakan untuk backfill terkontrol histori lama.
  7. **Hasil Verifikasi:**
     - 21 test baru pada `Pbi12FinalIntegrityAndSecurityExitGateTest` lulus 100%.
     - Full PHPUnit: **361 tests passed, 1996 assertions, 0 failures (100% pass rate)**.
     - JavaScript Regression: 9 tests passed.
     - Vite Build: Berhasil dalam 2.58s.
     - Browser Chromium E2E: Screenshot resolusi tinggi pada 390px dan 1440px terverifikasi untuk P06, D05, A03, C01.
- **Konsekuensi:**
  - PBI-12 Final Integrity & Security Exit Gate tuntas secara paripurna.
  - Rantai custody logistik balik, baseline fisik, dan otorisasi multi-tenant terlindungi penuh.
  - Nol (0) file `.md` di-stage atau di-commit ke Git remote.

