# Antislop per role, bukan satu dashboard generik

| Role/halaman | Yang harus menjadi fokus pertama | Komposisi yang beralasan | Fitur anti-slop yang harus diuji |
|---|---|---|---|
| Kepala SPPG | Incident dan pengecualian operasional saat ini | Exception first + daftar sekolah/trip; map hanya bila ada trip aktif | Tanggal/freshness, angka target vs aktual, akses multi-school, tidak ada KPI palsu |
| Pengawas gizi | Batch butuh QC dan keputusan aman/tidak | QC queue dan context menu-version, hasil pemeriksaan | CTA sesuai status, dua checkpoint, tidak memanipulasi status lewat animasi |
| Asisten lapangan | Tahap kerja dan jumlah porsi aktual | Progress stage, jumlah sebelum/sesudah, bukti siap | Idempotency, offline, foto bukan placeholder evidence |
| Driver | Stop aktif, tujuan aman, GPS freshness | One-hand task-first; peta subordinate pada keselamatan | Tidak perlu tap saat bergerak, GPS stale, ETA tidak pasti, stop urut |
| PIC sekolah | Kebutuhan/terima/bagi/ompreng | Form pencocokan expected-received; inspection flow | Input terisi ulang saat validasi gagal, tidak ada double confirm |
| Publik | Sekolah, menu, status makanan hari ini | Editorial menu dan timeline sederhana | Tidak ada lokasi driver presisi, tidak ada ulasan palsu, sumber gizi jelas |
| Login | Masuk cepat dan aman | Brand minimal, satu form fokus | Demo credentials hanya development, errors dekat field, keyboard/zoom |

Matriks ini melengkapi 34 page specs. Gunakan source upstream `antislop-ui`, `antislop-human`, `antislop-layoutmobile` untuk audit visual/interaksi dan `DESIGN.md` untuk hasil positif.
