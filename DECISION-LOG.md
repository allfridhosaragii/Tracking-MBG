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
- **Konsekuensi:** Performa loading cepat, bundle CSS di bawah 50kB gzip ~11kB, aset terkompilasi dalam <1 detik.

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
