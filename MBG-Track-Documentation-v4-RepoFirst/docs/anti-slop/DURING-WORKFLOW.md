# During mode: prevention while implementing

> MBG-Track v3.0 | Asal inspirasi: [anti-slop oleh Miqdad Badjuber](https://github.com/miqdadbadjuber/anti-slop/tree/9685ca7eac70283d083fc23baa9a88e7d3cd93c4) (MIT, snapshot 12-10-2026). Dokumen ini adalah **adaptasi orisinal khusus MBG-Track**, bukan salinan skill resmi ataupun hasil pengujian aplikasi.

## Pemakaian
Default saat membuat halaman baru dan memperbaiki PBI: mode **During**. Semua keputusan antislop dilakukan ketika desain dan kode ditulis, bukan hanya sekali setelah jadi. Jangan menyerahkan halaman ketika gate belum punya bukti.

1. Baca `AGENTS.md`, `DESIGN.md`, PBI, page spec, kontrak hak akses dan current Git diff. Identifikasi apakah screenshot nyata aplikasi lama perlu dipertahankan.
2. Nyatakan Design Read + dial ENERGY/RHYTHM/MOTION dan satu focal point. Tulis komposisi handphone dahulu sebelum desktop.
3. Buat **behavior contract**: setiap tombol mempunyai route/action nyata, Policy dan tiga state data (empty/loading/error), kesalahan 401/403/409/422.
4. Buat layout langsung di source Blade/CSS/JS, bukan script text-replace (R-33). Warna/type/motion dari token design system, bukan inline random.
5. Asset baru? Jika tidak ada approval, gunakan placeholder yang jelas; jangan generate seolah foto dapur/BGN asli. Catat asal setiap gambar.
6. Uji test suite Laravel, build Vite, keyboard, viewport 320/360/390/430/768/1440, zoom 200%, reduced motion, GPS stale jika relevan.
7. Review 38 aturan dengan tier; buat catatan alasan satu kalimat untuk keputusan penting. Eksekusi empat blok `DELIVERY-GATE.md` dengan bukti.
8. Catat hasil di log kerja **luar** folder dokumentasi yang dilindungi. Jangan tandai selesai jika screenshot/test tidak tersedia.

## Penerapan ke PBI-02 berikutnya
Audit hasil Sprint 0+PBI-01 dulu (berdasarkan laporan agent, belum diverifikasi independen), cek index sekolah, form, preset demo dev guard; baru bangun page K03 Pegawai. Hindari mengganti palet seluruh aplikasi saat menambah akun. Pastikan CRUD pegawai/toggle aktif berfungsi, CTA spesifik, dan negative test PIC lintas sekolah berjalan.
