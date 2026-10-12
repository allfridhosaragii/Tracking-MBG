# HANDOFF-LOG — MBG-Track Sprint 0: Project Foundation

Log ini mencatat ringkasan status implementasi, pengujian yang telah dieksekusi, artefak yang dihasilkan, dan panduan untuk sprint berikutnya. Terletak di root proyek di luar direktori dokumentasi.

---

## 1. Status Ringkas Sprint 0
- **Fase:** Sprint 0 — Project Foundation
- **Tanggal:** 12 Oktober 2026
- **Status Akhir:** **DONE & VERIFIED**
- **Test Suite Result:** **24 Tests Passed, 80 Assertions, 0 Failures**
- **Visual Verification:** **PASS (Mobile 390px & Desktop 1440px via Browser Automation)**
- **Integritas Dokumentasi:** **PASS (76 berkas utuh tanpa perubahan)**

---

## 2. Lingkungan & Versi Teknologi
- **PHP:** 8.3.28 (cli) (ZTS Visual C++ 2019 x64)
- **Composer:** 2.8.12
- **Node.js:** v24.1.0
- **npm:** 11.3.0
- **MySQL:** 8.4.3 Community Server (Database: `mbg_track_dev`)
- **Laravel Framework:** 13.35.0
- **Tailwind CSS / Vite:** Vite 8.3.4, Tailwind CSS 4.0.0
- **Repository Root:** `P:\Tugas PPL MBG`

---

## 3. Komponen & Berkas yang Dibuat

### A. Konfigurasi & Lingkungan
- `.env`: Dikonfigurasi untuk database MySQL `mbg_track_dev`, locale `id`.
- `.gitignore`: Mengabaikan `.env`, `vendor`, `node_modules`, dan `/MBG-Track-Documentation/`.

### B. Enums & Database
- `app/Enums/UserRole.php`: Definisi 5 peran internal: `KEPALA_SPPG`, `PENGAWAS_GIZI`, `ASISTEN_LAPANGAN`, `KURIR`, `PIC_SEKOLAH`.
- `app/Models/Sppg.php`: Model SPPG dengan relasi `schools` dan `users`.
- `app/Models/School.php`: Model Sekolah dengan relasi `sppg` dan `picUsers`.
- `app/Models/User.php`: Model Pengguna terintegrasi dengan peran, tenant scope, relasi SPPG & sekolah, serta helpers multi-tenant.
- Migrations:
  - `0001_01_01_000000_create_sppgs_table.php`
  - `0001_01_01_000001_create_schools_table.php`
  - `0001_01_01_000002_create_users_table.php`
  - `0001_01_01_000003_create_cache_table.php`
  - `0001_01_01_000004_create_jobs_table.php`
- Seeders:
  - `database/seeders/MbgTrackBaselineSeeder.php`: Membangun 1 SPPG (`SPPG-SUB-01`), 3 sekolah (`SCH-SDN-01`: 340, `SCH-SMPN-02`: 276, `SCH-SMAN-05`: 410 -> Total 1.026 porsi), 5 peran internal demo, 1 PIC isolasi uji, dan 1 akun nonaktif.

### C. Keamanan & Authorization
- `app/Http/Middleware/EnsureUserIsActive.php`: Memutus sesi dan menolak akses jika akun nonaktif.
- `app/Http/Middleware/EnsureUserHasRole.php`: Mengembalikan HTTP 403 bila role tidak diizinkan.
- `app/Policies/SchoolPolicy.php`: Mengisolasi data sekolah antar PIC sekolah dan menegakkan wewenang SPPG.
- `app/Policies/SppgPolicy.php`: Mengisolasi tenant antar SPPG.
- `app/Policies/UserPolicy.php`: Mengatur wewenang data pegawai.

### D. Controllers & Routing
- `app/Http/Controllers/Auth/LoginController.php`: Login rate-limited, update timestamp `last_login_at`, validasi status aktif, pengalihan dashboard per role, dan logout aman.
- `app/Http/Controllers/Head/DashboardController.php`: Operations Briefing Kepala SPPG (`/head/dashboard`).
- `app/Http/Controllers/Nutrition/DashboardController.php`: Review Desk Pengawas Gizi (`/nutrition/dashboard`).
- `app/Http/Controllers/Production/DashboardController.php`: Production Runbook Asisten Lapangan (`/production/dashboard`).
- `app/Http/Controllers/Driver/DashboardController.php`: Active Mission Kurir (`/driver/dashboard`).
- `app/Http/Controllers/School/DashboardController.php`: Receipt Workflow PIC Sekolah (`/school/dashboard`).
- `app/Http/Controllers/School/SchoolOverviewController.php`: Detail sekolah berpagar `SchoolPolicy` (`/schools/{school}`).
- `app/Http/Controllers/Public/SchoolPublicController.php`: Portal transparansi publik whitelist tanpa bocoran privat (`/public/schools/{slug}`).
- `routes/web.php`: Seluruh pemetaan rute dengan middleware `auth`, `active`, dan `role`.

### E. Desain & Komponen Frontend
- `resources/css/app.css`: Sistem token warna Calm Utility (`--color-canvas: #F5F7F3`, `--color-brand-700: #1C6654`, `--color-ink: #172923`, `--color-line: #DCE5DD`), font tabular nums, dan aturan reduced motion.
- `resources/views/components/app-shell.blade.php`: Shell aplikasi adaptif mobile-first, indikator banner simulasi, header dengan profil dan logout.
- `resources/views/components/role-badge.blade.php`: Badge peran berlabel jelas dan kontras tinggi.
- `resources/views/components/card.blade.php`: Kartu informasi berbatas garis halus tanpa shadow berlebihan.
- `resources/views/auth/login.blade.php`: Layar masuk clean luxury dengan preset satu ketuk untuk kemudahan evaluasi demo.
- `resources/views/dashboard/head.blade.php`: Tampilan briefing Kepala SPPG dengan matriks 3 sekolah.
- `resources/views/dashboard/nutrition.blade.php`: Tampilan review mutu & gizi.
- `resources/views/dashboard/production.blade.php`: Tampilan runbook dapur.
- `resources/views/dashboard/driver.blade.php`: Tampilan manifest multi-stop kurir dengan pesan keselamatan mengemudi.
- `resources/views/dashboard/school.blade.php`: Tampilan penerimaan ompreng terisolasi PIC.
- `resources/views/public/school.blade.php`: Tampilan publik transparansi tanpa data rahasia.

---

## 4. Hasil Pengujian Nyata

### A. PHPUnit Feature & Unit Tests
Perintah: `php artisan test --testdox`
Hasil: **24 tests, 80 assertions, 0 failures (Passed in 1.55s)**
Cakupan:
1. `AuthTest`: Render login, redirect root, login kelima role ke dashboard masing-masing, pembaruan `last_login_at`, penolakan akun nonaktif, penolakan password salah, dan logout.
2. `AuthorizationTest`: Proteksi unauthenticated, penolakan akses lintas role dengan 403 (Driver membuka head dashboard, PIC membuka nutrition dashboard, Asisten membuka driver dashboard), pemisahan tenant (PIC SD A dapat melihat sekolahnya, tetapi ditolak 403 saat mencoba melihat SMP B), dan terminasi sesi pengguna yang dinonaktifkan.
3. `BaselineDataTest`: Validasi 1 SPPG, 3 sekolah (340 + 276 + 410 = 1.026 porsi), 5 kategori role, dan kecocokan hash password.
4. `PublicSchoolPortalTest`: Akses publik tanpa otentikasi, respon JSON hanya memuat field whitelist (bebas dari password, koordinat mentah, dan nomor HP), serta penanganan 404 untuk sekolah nonaktif/tidak terdaftar.

### B. Pengujian Visual Browser (Playwright Subagent)
- **Mobile Viewport (390x844):** Komposisi tombol, input tinggi 44px, banner pengujian simulasi, dan tata letak single-column terverifikasi responsif tanpa overflow.
- **Desktop Viewport (1440x900):** Reflow grid metrik 4 kolom dan tabel distribusi porsi rapi dengan tipografi tabular-nums.
- **Screenshot Artefak:**
  - `login_mobile_1791813105740.png`
  - `head_dashboard_mobile_1791813130405.png`
  - `head_dashboard_desktop_1791813135250.png`
- **Video Sesi Browser:** `login_verification_1791813092543.webp`

---

## 5. Kredensial Demo untuk Pengujian Cepat

Semua akun demo menggunakan kata sandi: `password`

| Role | Email | Target Dashboard | Catatan |
|---|---|---|---|
| **Kepala SPPG** | `kepala@mbgtrack.test` | `/head/dashboard` | Akses briefing 3 sekolah |
| **Pengawas Gizi** | `gizi@mbgtrack.test` | `/nutrition/dashboard` | Akses review mutu |
| **Asisten Lapangan** | `asisten@mbgtrack.test` | `/production/dashboard` | Akses tahapan dapur |
| **Kurir** | `kurir@mbgtrack.test` | `/driver/dashboard` | Akses manifest stop |
| **PIC SDN 01** | `pic.sdn01@mbgtrack.test` | `/school/dashboard` | Terisolasi di SDN 01 (340 porsi) |
| **PIC SMPN 02** | `pic.smpn02@mbgtrack.test` | `/school/dashboard` | Terisolasi di SMPN 02 (276 porsi) |
| **Akun Nonaktif** | `inactive@mbgtrack.test` | Ditolak `/login` | Status is_active = false |

Portal Publik (Tanpa Login): `http://127.0.0.1:8000/public/schools/sdn-percobaan-01-surabaya`

---

## 6. Rekomendasi Langkah Selanjutnya (Sprint 1)
1. **PBI-01 (Sekolah dan PIC):** Form manajemen CRUD sekolah oleh Kepala SPPG, penugasan PIC dinamis, validasi slug publik dan penonaktifan sekolah.
2. **PBI-02 (Pegawai dan Hak Akses):** Manajemen pegawai oleh Kepala SPPG, pergantian peran, deaktivasi akun, serta audit log mutasi akun.
3. **PBI-03 (Menu dan Gizi Terversi):** Skema `menus` dan `menu_items`, penegakan versi menu tidak dapat diedit saat aktif, dan approval mutu oleh Pengawas Gizi.
