# Content truth and asset provenance protocol

> MBG-Track v3.0 | Asal inspirasi: [anti-slop oleh Miqdad Badjuber](https://github.com/miqdadbadjuber/anti-slop/tree/9685ca7eac70283d083fc23baa9a88e7d3cd93c4) (MIT, snapshot 12-10-2026). Dokumen ini adalah **adaptasi orisinal khusus MBG-Track**, bukan salinan skill resmi ataupun hasil pengujian aplikasi.

## Kategori konten
- **REAL**: berasal dari data transaksi yang diverifikasi sistem dan berada dalam scope izin user; timestamp mengikuti field sebenarnya.
- **OFFICIAL SOURCE**: didukung rujukan BGN/SIPGN dengan URL dan waktu akses, bukan nama modul yang diklaim sebagai endpoint BGN.
- **PROJECT DECISION**: ETA threshold 10 menit, update GPS 10–15 detik, struktur role prototype; diberi label keputusan internal, bukan kebijakan pemerintah.
- **SIMULASI**: sekolah A 340, B 276, C 410, total 1.026; tidak dipresentasikan sebagai performa produksi lapangan.
- **PLACEHOLDER**: `[FOTO MENU BELUM DISETUJUI]`, `Belum ada rencana produksi`, `Data lokasi belum tersedia`, bukan gambar palsu yang kelihatan final.

## Asset registry minimal
| Asset | Sumber pemilik | Izin | Lingkup tampil | Status |
|---|---|---|---|---|
| Foto menu harian | unggahan Pengawas Gizi berotorisasi | cek upload owner | publik jika ditandai `public_approved` | unknown sebelum produk dibuat |
| Bukti serah terima | driver/PIC dalam transaksi | private internal | scoped, jangan publik | private |
| Foto ompreng | PIC/driver | private internal | scoped | private |
| Logo MBG-Track | hanya desain orisinal yang disetujui | owner approval | publik/internal | placeholder bila belum approved |

## Checklist sebelum menampilkan angka
Dapatkan tabel sumber, definisi ukuran, unit, periode, scope per-user, kondisi stale/late dan label DEMO. `registered_beneficiaries` bukan `daily_portion_requests.requested_portions`; porsi yang dibagikan bukan ompreng kembali. Tombol success setelah commit, bukan sebelum server ACK.
