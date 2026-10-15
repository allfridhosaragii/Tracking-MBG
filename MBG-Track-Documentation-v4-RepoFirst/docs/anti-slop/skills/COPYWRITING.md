# MBG-Track anti-slop skill lens | COPYWRITING

> MBG-Track v3.0 | Asal inspirasi: [anti-slop oleh Miqdad Badjuber](https://github.com/miqdadbadjuber/anti-slop/tree/9685ca7eac70283d083fc23baa9a88e7d3cd93c4) (MIT, snapshot 12-10-2026). Dokumen ini adalah **adaptasi orisinal khusus MBG-Track**, bukan salinan skill resmi ataupun hasil pengujian aplikasi.

## Pedoman bahasa produk
Antislop-copywriting upstream memotong filler, hyperbole dan detail fiktif. Untuk MBG-Track gunakan **bahasa Indonesia tugas operasional**: ringkas, spesifik, dapat ditindaklanjuti.

| Konteks | Hindari | Gunakan |
|---|---|---|
| Submit porsi | `Submit`, `Get Started` | `Konfirmasi 340 porsi` / `Simpan kebutuhan` |
| GPS | `Live realtime seamless` | `Lokasi terakhir 08.13, pembaruan terhenti` |
| QC | `Semua aman!` bila belum dicek | `Menunggu pemeriksaan kualitas` |
| Driver | `Lanjutkan petualangan` | `Buka rute SD A` |
| Penerimaan | `Success!` sebelum commit | `Penerimaan 338 porsi tersimpan` setelah ACK |
| Public | `Terpercaya ribuan orang tua` | `Lihat menu dan status MBG sekolah` |
| Empty | `No data` tanpa konteks | `Belum ada laporan penerimaan untuk hari ini` |
| Error | `Oops something went wrong` | `Belum tersimpan. Periksa koneksi lalu coba lagi` |

## Content provenance
- Sumber menu: versi menu disetujui ahli gizi di DB; bila tidak ada tampil `Belum ada menu disahkan`.
- Jumlah porsi: bedakan `registered_beneficiaries`, `requested`, `allocated`, `loaded`, `received`, `served`. Tampilkan label dan timestamp sesuai tahap.
- ETA: `Perkiraan tiba`, jangan janjikan akurasi traffic real-time jika provider hanya geometri rute.
- Incident: teks berorientasi tindakan, tidak menakuti pengguna atau mengungkap data sensitif.
- Angka 340/276/410 dan total 1.026 adalah **fixture SIMULASI**; tidak boleh tampil sebagai statistik produksi live.
- Tidak ada testimoni, bukti foto lapangan, logo lembaga, atau nama petugas sungguhan tanpa sumber/izin.

## Audit gaya
Hapus kata universal yang tak menambah informasi (revolutionary, cutting-edge, powerfully seamless, dan padanan "solusi inovatif mutakhir"). Hindari tiga kata abstrak berjejer, emoji dekoratif, caps teatrikal, pembuka bot, dan kalimat tanpa aktor yang dapat disebut. Jika pemilik memberi copy asli, jangan memutarnya menjadi marketing generik. ``R-02`` menghindari em dash pada **copy baru**; kutipan dan spesifikasi teknis dikecualikan seperti dijelaskan pada core.
