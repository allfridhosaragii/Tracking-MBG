# Safe adoption for existing Laravel project and protected documentation

> MBG-Track v3.0 | Asal inspirasi: [anti-slop oleh Miqdad Badjuber](https://github.com/miqdadbadjuber/anti-slop/tree/9685ca7eac70283d083fc23baa9a88e7d3cd93c4) (MIT, snapshot 12-10-2026). Dokumen ini adalah **adaptasi orisinal khusus MBG-Track**, bukan salinan skill resmi ataupun hasil pengujian aplikasi.

## Tidak menyentuh pekerjaan yang telah selesai
Berdasarkan laporan agent yang diberikan pengguna pada 12 Oktober 2026, Laravel 13/Blade/Tailwind, auth 5 role dan PBI-01 dilaporkan selesai dengan test suite. Ini **klaim dari agent report**, bukan verifikasi independen oleh pembuat ZIP v3. V3 adalah pembaruan **dokumentasi**, bukan source code.

### Cara pasang aman
1. Ekstrak ZIP v3 sebagai folder baru `P:\Tugas PPL MBG\MBG-Track-Documentation-v3`. Jangan overwrite `MBG-Track-Documentation` existing.
2. Periksa isi folder; siapkan snapshot/hash dokumentasi sebelum memberi agen akses tulis ke repo.
3. Buka workspace project `P:\Tugas PPL MBG` dan baca `MBG-Track-Documentation-v3\README.md`, `DESIGN.md`, `AGENTS.md`, serta file page target.
4. Agent hanya boleh melakukan operasi tulis pada **code Laravel dan development logs di luar** kedua folder dokumentasi.
5. Jika perlu agent entrypoint di project root, buat file kecil `DESIGN.md` atau `AGENTS.md` di root Laravel yang **berisi pointer** ke v3. Jangan copy seluruh 100 halaman agar konteks tidak bengkak. Periksa konflik dengan file root existing; jangan overwrite otomatis.
6. Jalankan **After audit** pada halaman K02 yang sudah ada, berdasarkan screenshot & tests. Jangan refactor CSS global hanya karena ada aturan baru.
7. Berikutnya PBI-02 K03 memakai mode **During** dan `docs/pages/kepala-sppg/EMPLOYEE-MANAGEMENT.md` dengan kontrak v3.

### Root pointer yang aman (contoh bila file belum ada)
```
# Project design entry point
Source of truth (read-only):
P:\Tugas PPL MBG\MBG-Track-Documentation-v3\DESIGN.md
For each page, read PAGE-REGISTRY + one page spec + DELIVERY-GATE.
Write application code only outside both documentation directories.
```

### Pembatas
- Paket ZIP bukan plugin otomatis yang menegakkan file ACL; `read-only` adalah instruksi proses, izin OS perlu diatur manusia bila membutuhkan enforcement teknis.
- Hash baru setelah ekstraksi dapat mendeteksi perubahan **sesudah baseline**, tidak membuktikan secara retrospektif isi lama tak pernah berubah.
- Jangan menjalankan `composer create-project`, `migrate:fresh`, `npm install` ulang tanpa meninjau app existing dan migrasi.
