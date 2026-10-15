# Product Brief — MBG-Track

**Status:** BASELINE v1.0 — 12 Oktober 2026.

## Masalah yang dipecahkan
Satu SPPG dapat melayani banyak sekolah. Kegiatan yang harus tersambung adalah kebutuhan porsi, menu, produksi, pengepakan, penugasan trip/stop, konfirmasi fisik di sekolah, pembagian makanan dan pengambilan ompreng. Bila hanya ada satu status untuk seluruh SPPG, sekolah berbeda bisa terlihat seolah-olah sudah menerima walaupun belum. Aplikasi prototype mengurangi kesenjangan informasi antar petugas, bukan mengklaim BGN belum punya sistem.

## Hasil pengguna
- Kepala: melihat kondisi SPPG dan menindaklanjuti pengecualian tanpa mengetik ulang data.
- Pengawas Gizi: mengendalikan menu, versi nutrisi, gate pemeriksaan batch dan tindak lanjut masalah makanan.
- Asisten: menjalankan produksi dengan target dan realisasi porsi yang dapat ditelusuri.
- Kurir: melihat penugasan multi-stop, unggah bukti, kirim status/GPS ketika halaman aktif.
- PIC: menyatakan kebutuhan, memeriksa penerimaan, pemeriksaan kondisi, distribusi dan wadah sekolah sendiri.
- Publik: melihat menu dan status terbatas sekolah pilihannya tanpa akun atau data anak.

## SDG 17
Kerja sama lintas SPPG, petugas distribusi dan sekolah diwujudkan melalui satu rekam alur yang sama, aktor berbeda, serah-terima terkonfirmasi dan transparansi informasi. Jangan mengklaim bahwa label SDG 17 adalah penetapan resmi BGN untuk aplikasi ini; ini framing proposal mahasiswa.

## Batas MVP
Satu deployment mendukung lebih dari satu SPPG tetapi setiap kepala dibatasi unitnya; data simulasi demonstrasi: 1 SPPG + 3 sekolah + beberapa driver. Tidak ada integrasi produksi pemerintah, pembayaran, chat, pengadaan full, riwayat siswa individual, scoring otomatis kelayakan makanan, pelacakan latar belakang terjamin, atau tracking publik presisi.

## Indikator berhasil (desain target, bukan data BGN)
1. Satu siklus sekolah berhasil dari permintaan porsi → penerimaan → ompreng kembali.
2. Satu trip memuat 3 stop dengan status terpisah.
3. Selisih porsi 340/338 tidak tertimpa, otomatis tercatat sebagai incident.
4. PIC sekolah A menerima 403 ketika meminta record B.
5. Tracking stale teridentifikasi tanpa mengarang pergerakan.
6. Bukti foto pada checkpoint terikat transaction ID dan uploader.

## Sumber
- Juknis MBG TA 2026: https://cdn-web.bgn.go.id/juknis/01KFZQGR85YKY1HRMNDK4RRM1P.pdf
- Portal resmi SIPGN: https://portal-doc-sipgn.bgn.go.id/panduan-sipgn

## Produk v2 — outcome yang dapat diamati
- **Bukan sistem pemerintah.** Demo memakai akun dan catatan simulasi, tidak mengklaim terhubung API BGN/SIPGN.
- **Outcome operasional utama:** sekolah mengajukan jumlah agregat, SPPG memproduksi dan melepas batch yang lolos QC, kurir melakukan serah-terima multi-stop dengan jejak, sekolah mengonfirmasi hasil, sistem melacak kembali ompreng.
- **Outcome transparansi:** publik memilih sekolah tanpa registrasi, melihat menu/gizi yang tersedia dan status layanan yang benar; tidak menyatakan anak tertentu telah menerima makan.
- **Outcome SDG 17:** koordinasi dan data bersama antar-kepala SPPG, pengawas gizi, petugas produksi, kurir, PIC sekolah dan keluarga. Ini interpretasi proyek akademik, bukan klaim dampak SDG terukur.

### Hipotesis nilai yang perlu diuji dalam demo
| Hipotesis | Tes nyata | Metrik observasi |
|---|---|---|
| Mengurangi pengetikan jumlah berulang | PIC konfirmasi 340 → otomatis masuk planning dan delivery | jumlah re-entry manual = 0 pada happy path |
| Meningkatkan jejak tanggung jawab | buka receipt shortage → lihat stop, batch, pengguna, waktu dan incident | transaksi terlacak end-to-end |
| Membantu kesiapan PIC | trip mendekati stop aktif dan GPS fresh | satu notifikasi per stop tanpa spam sekolah lain |
| Menjaga privasi | request public API & UI | tidak ada raw GPS/identitas personal |
| Memudahkan pengguna mobile | 390px task workflow | langkah utama dapat diselesaikan tanpa desktop |

### Non-goals eksplisit
Tidak ada presensi siswa individu, tidak mengontrol anggaran pemerintah, tidak mengadakan bahan/kontrak supplier penuh, bukan penilai otomatis keamanan pangan, tidak menjamin mobil dilacak saat browser berada di background, tidak berfungsi sebagai audit independen BGN. Seluruh aktivitas fisik tetap membutuhkan pemeriksaan manusia.

### Definition of success saat presentasi
Demo lintas peran 1 SPPG, tiga sekolah dengan angka 340/276/410, minimal satu mismatch diterima, satu QC HOLD, satu GPS stale dan satu selisih ompreng, semuanya terefleksi konsisten dari transaksi yang sama di UI dan tes. Angka tersebut **data uji**, bukan data SPPG aktual.


## V3 | Product voice & proof
MBG-Track adalah prototype koordinasi pangan sekolah, bukan sistem BGN resmi. Logo, screenshot dan public copy tidak boleh menyiratkan afiliasi pemerintah atau deployment operasional sungguhan. Brand motif journey line merefleksikan kebutuhan→produksi→sekolah, bukan klaim kualitas makanan tanpa QC. Publik perlu kejelasan status, bukan tagline buzzword.
