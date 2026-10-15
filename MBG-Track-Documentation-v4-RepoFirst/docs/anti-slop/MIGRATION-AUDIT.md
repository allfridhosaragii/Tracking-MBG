# V2 to V3: preserve approved behavior and raise visual craft

> MBG-Track v3.0 | Asal inspirasi: [anti-slop oleh Miqdad Badjuber](https://github.com/miqdadbadjuber/anti-slop/tree/9685ca7eac70283d083fc23baa9a88e7d3cd93c4) (MIT, snapshot 12-10-2026). Dokumen ini adalah **adaptasi orisinal khusus MBG-Track**, bukan salinan skill resmi ataupun hasil pengujian aplikasi.

## Matriks migrasi
| Lapisan | Apa yang dipertahankan | Yang dinaikkan di v3 | Bukti sebelum merge |
|---|---|---|---|
| 12 PBI | Seluruh tujuan dan acceptance bisnis | Anti-slop sebagai gate kualitas tambahan | 12 PBI tetap tercakup |
| Roles & tenancy | 5 role, publik read-only, Policy Laravel | UI tidak menampilkan aksi forbidden | Test 403, route scope |
| Schema | 22 baseline entities & planned constraints | Komposisi UI mengikuti data yang benar | DB MySQL tests, no migration guessing |
| Navigation | 34 spesifikasi halaman | R24 semua link punya tujuan yang nyata | route:list + clickthrough |
| Existing PBI-01 | CRUD sekolah & PIC seperti laporan agent | After audit K02, bukan rewrite massal | screenshot before/after, regression |
| PBI-02 onward | Backlog v2 | During mode + design read + gate | test + screenshots |
| Design | Clean Luxury, Calm Utility tokens, mobile | Liveliness dials, identity motif, anti-sterility | 6 representative-page proof |
| Accessibility | responsive, focus, AA targets | comprehensive contrast/zoom/keyboard | evidence, not claims |
| Evidence/photos/GPS | private uploads and scoped coords | placeholders honest, stale marker honest | public privacy tests |

## Aturan non-regression
- Jangan menghapus/mengubah hubungan PIC antar sekolah karena polish desain.
- Jangan menambahkan fake stats, marketing testimonials, hero sections, atau dummy chart hanya untuk terlihat mahal.
- Jangan merombak warna Calm Utility jika browser belum membuktikan masalah visual spesifik.
- Hindari mengubah route/DB tanpa ADR yang diotorisasi; anti-slop adalah **filter kualitas UI**, bukan alasan menambah PBI.

## Hasil audit yang dibutuhkan
Sebelum mengklaim v3 diimplementasikan pada source Laravel: `git diff`, `php artisan test`, `npm run build`, screenshot K02 mobile 320/390/1440, minimal satu login cross-role denied test. Semua itu belum dieksekusi saat ZIP dokumentasi ini dibuat.
