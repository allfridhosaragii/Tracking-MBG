# Role Experience — Alur Peran dan Prioritas Informasi

> Satu design system; enam konteks pengalaman. Halaman tidak boleh berbeda hanya dengan mengganti judul sidebar.

## Kepala SPPG — “Command & exception”
- Pertanyaan pertama: “Apakah makanan aman dan semua sekolah akan/ sudah terlayani?”
- Prioritas mobile: batch hold, sekolah belum konfirmasi porsi, trip terlambat, incident critical, ringkasan hari ini, drilldown.
- Prioritas desktop: overview per sekolah dan per trip, monitor distribusi, audit/laporan. Chart sekunder, data mentah dapat diunduh jika diimplementasikan.
- CTA utama: tindak lanjut insiden/finalisasi plan (bergantung status), bukan tombol generic “Tambah”.
- Hindari: menampilkan semua data 22 tabel sekaligus.

## Pengawas Gizi — “Evidence before approval”
- Prioritas: batch menunggu QC, menu aktif/version, kekurangan informasi gizi, uji sekolah gagal.
- Flow: pilih batch → cek foto/item/kondisi → isi checkpoint → lihat konsekuensi → sahkan; `UNSAFE` memblokir pelepasan batch dan memunculkan eskalasi.
- Hindari: mengklaim aplikasi mampu menilai keamanan pangan dari gambar otomatis.

## Asisten Lapangan — “Hands-busy workflow”
- Prioritas: tahap produksi aktif, hitungan target/aktual, masalah yang harus dilaporkan, satu aksi lanjut.
- Input cepat dan tahan gangguan: angka besar, kamera/file fallback, state “belum terkirim”, tombol mudah disentuh.
- Hindari: mewajibkan navigasi panjang saat bekerja di dapur.

## Kurir — “Stop-driven trip”
- Prioritas: stop aktif, alamat, jumlah muatan, bukti yang perlu dibuat, next stop, GPS freshness.
- Saat berkendara: **readable glance**, tidak ada interaksi yang harus dilakukan sambil menyetir; konfirmasi hanya setelah berhenti aman.
- Stop delivery dan pickup dibedakan jelas; jumlah tray adalah hitungan wadah, bukan otomatis sama dengan porsi makanan.
- Hindari: monitoring kurir di luar trip, menampilkan lokasi warga, background GPS palsu.

## PIC Sekolah — “Receive, check, distribute”
- Prioritas: kebutuhan porsi → ETA → kuantitas saat serah terima → pemeriksaan saat tiba → pemeriksaan sebelum makan → realisasi → tray ready.
- Fokus pada kesalahan lapangan: shortage/kerusakan, kondisi tidak aman, tidak ada internet. Form harus menjaga data saat kesalahan.
- Hindari: meminta PIC mengisi ulang target produksi yang sudah berasal dari request awal.

## Publik — “Understand, not administer”
- Prioritas: sekolah dipilih, tanggal, menu/gizi, status layanan yang jujur, riwayat sederhana.
- Tidak ada login; tidak ada koordinat GPS presisi, nomor driver, internal incident detail, atau identitas siswa.
- Jika belum ada data resmi dari prototype: label jelas belum tersedia/demonstrasi, bukan screenshot bukti distribusi palsu.

## Matriks konsekuensi interaksi
| Event | Actor | Viewer segera | Notifikasi tugas | Jangan dilakukan |
|---|---|---|---|---|
| Portion confirmed | PIC | Kepala, Asisten (read-only) | Kepala jika melewati cutoff | duplicate entry oleh kepala |
| Batch on hold | Gizi | Kepala/Asisten terkait | Kepala/Asisten | driver load batch on hold |
| Trip en route | Kurir | Kepala, PIC stop terkait | PIC mendekati tujuan | publik melihat pinpoint GPS |
| Receipt discrepancy | PIC | Kepala, Gizi sesuai kategori | Kepala/Gizi terkait | menimpa jumlah sent |
| Trays ready | PIC | Kepala/kurir pickup | assigned kurir | asumsi jumlah porsi=wadah |

## Definition of experience success
Role dapat menyelesaikan 1 skenario kritis di mobile tanpa desktop; menu dan langkah konsisten; status tidak ambigu; kesalahan jelas dan dapat diperbaiki; aplikasi tidak menghalangi kegiatan fisik karena jaringan/izin GPS.


## V3 | Visual fingerprints bukan skin berbeda
Lihat `ROLE-VISUAL-FINGERPRINTS.md`. Pengalaman kepala berpusat exception; ahli gizi quality evidence; asisten active step; kurir current destination; PIC penerimaan; publik menu dan status. Shared tokens tetap konsisten, tetapi susunan, densitas, focal point dan motion sengaja berbeda. Untuk laporan keberhasilan user tidak perlu mempelajari dashboard lain. 

Uji dari screenshot tanpa logo/judul: reviewer harus bisa menebak role berdasarkan pekerjaan yang diperlihatkan. Jika semua bisa ditukar logo & judul tanpa mengubah isi, UI generik dan harus diperbaiki pada susunan informasi.
