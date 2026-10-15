# Motion-to-state catalog for MBG-Track

> MBG-Track v3.0 | Asal inspirasi: [anti-slop oleh Miqdad Badjuber](https://github.com/miqdadbadjuber/anti-slop/tree/9685ca7eac70283d083fc23baa9a88e7d3cd93c4) (MIT, snapshot 12-10-2026). Dokumen ini adalah **adaptasi orisinal khusus MBG-Track**, bukan salinan skill resmi ataupun hasil pengujian aplikasi.

## Prinsip dasar
Motion bukan wallpaper. Hanya animasikan hal yang berubah karena interaksi, transisi status yang sudah tersimpan, atau orientasi spasial yang benar. Semua komponen harus punya versi reduced-motion tanpa kehilangan informasi.

| Komponen/domain | Event pemicu valid | Motion yang diperbolehkan | Tidak boleh |
|---|---|---|---|
| Loading porsi | request server sedang berlangsung | shimmer/skeleton singkat dengan teks | fake count-up sebagai jumlah produksi |
| Simpan form PIC | HTTP 2xx dan commit sukses | inline check-state <200ms | sukses sebelum ACK |
| QC Batch | audit transition RELEASED/ON_HOLD | status swap ringkas + fokus teks | celebratory confetti saat `SAFE` |
| Production timeline | tahap status diperbarui | connector fill berdasarkan state baru | autoplay timeline tanpa real event |
| GPS trip | new geolocation fix valid | marker position tween terbatas sampai posisi terkini | marker bergerak saat stale/tab background |
| Prearrival notification | event dedupe tiba | badge indicator kecil dan screen-reader announcement | toast berulang setiap polling |
| Tray count discrepancy | server menemukan selisih | highlight satu kali lalu persistent text | screen shake berkepanjangan |
| Public status | projection berubah | crossfade satu field kecil | animation menyiratkan update live tanpa data |
| Disclosure/history | pengguna buka rincian | collapse 150–240ms | parallax berat dan scroll hijacking |

## Perf & proof
Uji perangkat mobile menengah, network throttling, `prefers-reduced-motion`, pointer coarse, keyboard, page visibility changed. Jangan mengklaim target Core Web Vitals tercapai tanpa metrik terukur. Jika browser tidak tersedia, status `NOT VERIFIED`.
