# MBG-Track anti-slop skill lens | UI

> MBG-Track v3.0 | Asal inspirasi: [anti-slop oleh Miqdad Badjuber](https://github.com/miqdadbadjuber/anti-slop/tree/9685ca7eac70283d083fc23baa9a88e7d3cd93c4) (MIT, snapshot 12-10-2026). Dokumen ini adalah **adaptasi orisinal khusus MBG-Track**, bukan salinan skill resmi ataupun hasil pengujian aplikasi.

## Audit komposisi visual dan orisinalitas
1. **Pusat informasi**: satu keputusan terpenting per first fold. Contoh P04 adalah jumlah diterima dan pemeriksaan, bukan ilustrasi hero.
2. **Kartu seperlunya**: K01 bisa mempunyai satu panel exception luas + status strip + map; G03 QC queue + inspeksi terfokus; D03 route map + tombol aman. Jangan paksa 6 KPI card universal.
3. **Identitas**: palet Calm Utility aktual diverifikasi terhadap `DESIGN.md`. Pine sebagai warna aksi, muted gold bukan tanda QC PASS/FAIL. Sinyal keselamatan pangan mesti punya teks eksplisit.
4. **Hierarchy typography**: judul pekerjaan, subheading konteks tanggal/sekolah, angka dan unit, kemudian metadata. Hindari ALLCAPS letter-spacing dan pola mono tech hanya demi gaya.
5. **Geometry**: radius berlapis, garis divider bernalar, shadow untuk dialog/overlay, whitespace sebagai grouping; hindari semua rounded-xl/blur/gradient/card shadow.
6. **Liveliness**: motif garis progres hanya pada perjalanan nyata, bukan wallpaper; satu detail editorial seperti timeline bermakna lebih baik daripada dekorasi seluruh card.
7. **Dashboard arus data**: tanggal/filter/status dibaca dari DB, updated timestamp. Tidak boleh "LIVE" jika GPS lokasi lama atau status hanya dummy.
8. **Public portal**: permukaan naratif ringan, foto menu yang sudah disetujui (bukan stok), status publik ringkas; no official brand imitation.

## UI review per halaman
Lihat dua screenshot layar sama 390 dan 1440, cek struktur konten lebih dulu baru kosmetik. Ukur kontras dan lihat kondisi kosong/error. Lakukan swap-test: jika nama produk ditukar dengan SaaS generik dan halaman tetap masuk akal tanpa perubahan apa pun, tingkatkan konteks workflow/identitas sampai punya karakter MBG-Track.

## Pengecualian sah
Tabel dan daftar seragam boleh ketika item benar-benar sebobot; jangan menciptakan asimetri hanya agar terlihat premium. Hindari menciptakan elemen UI yang tidak punya route/status dalam PBI.
