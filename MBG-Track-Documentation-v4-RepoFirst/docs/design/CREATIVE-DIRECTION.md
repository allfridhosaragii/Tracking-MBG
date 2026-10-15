# Creative Direction — MBG-Track, v2.0

> **Status:** keputusan desain proyek, bukan identitas visual resmi BGN. Berlaku bagi seluruh role dan AI pembuat frontend. Terakhir ditelaah: 12 Oktober 2026. **Tujuan:** aplikasi operasional pangan yang terpercaya, tenang, berkelas dan khas; **bukan** landing-page promosi atau template dashboard generik.

## 1. Posisi merek dan pengalaman
- **Produk:** prototype monitoring proses MBG dari kebutuhan agregat sekolah sampai produksi, QC, distribusi, konsumsi, bukti dan kembalinya ompreng. Tidak terkait resmi dengan BGN/SIPGN.
- **Kesan:** tenang (ketertiban), tegas (status), hangat (pangan dan sekolah), modern (tracking), dapat dipercaya (riwayat terverifikasi).
- **Janji pengalaman:** dalam 5 detik pengguna menemukan pekerjaan atau status terpenting; setelah aksi, konsekuensinya nyata dan terlihat.
- **Tolak tiga ekstrem:** (1) polos tanpa hirarki, (2) ramai dekorasi, (3) dashboard hasil template AI yang identik di setiap role.
- **Prioritas:** mobile-first untuk PIC/kurir/asisten/publik; desktop control-center untuk Kepala/Gizi, namun *tetap* optimal di HP.

## 2. Bahasa visual yang harus dipertahankan
| Prinsip | Wujud yang dapat diperiksa | Anti-pattern |
|---|---|---|
| Quiet confidence | tipografi konsisten; judul kuat; jarak dan alignment konsisten | heading gradien besar, motto AI kosong |
| Information clarity | status primer, nilai angka & satuan, freshness timestamp | KPI sama ukuran semua, 12 kartu sekaligus |
| Premium restraint | warna natural netral + satu accent fungsional; border lembut | neon, ungu default AI, pelangi badge |
| Operational honesty | waktu update akurat, sumber data jelas, status simulasi diberi label | badge LIVE saat data stale, success palsu |
| Layered depth | kedalaman visual untuk tindakan/overlay, bukan dekor | shadow besar di setiap card, glass blur tanpa tujuan |
| Originality | komposisi konten mengikuti pekerjaan spesifik role | salin langsung Shadcn admin starter/WordPress theme |

## 3. Konsep komposisi, bukan template
1. Kepala SPPG: *operations briefing*—ringkasan capaian dan pengecualian di atas, detail progres dan live map sebagai drilldown. Tampilkan akibat bisnis dulu, grafik belakangan.
2. Pengawas Gizi: *review desk*—menu/batch perlu keputusan, daftar cek yang dapat diverifikasi, nilai gizi dibaca ringkas.
3. Asisten: *production runbook*—tahap sekarang dan waktu, jumlah aktual, kamera, tombol lanjut yang terkunci jika prasyarat tidak lengkap.
4. Kurir: *active mission*—stop aktif, navigasi, posisi terakhir, kondisi GPS; jangan padati informasi organisasi. **Jangan meminta tap saat berkendara.**
5. PIC Sekolah: *receipt workflow*—cek jumlah dan kualitas lebih jelas daripada dashboard statistik.
6. Publik: *trust card*—sekolah terpilih, menu + foto sah, ringkasan gizi, progres aktual yang disederhanakan; tanpa data sensitif.

## 4. Keaslian dan arah pengembangan visual
- Setiap halaman harus punya satu *visual signature* yang dibenarkan oleh pekerjaannya, misalnya progress rail produksi, timeline serah terima, atau status card ETA yang aktual.
- Jangan mengisi ruang kosong dengan elemen berulang; gunakan lebih sedikit kelompok informasi, tetapi dengan kedalaman yang tepat.
- Judul bukan slogan perusahaan; tulis tindakan nyata: “Periksa 2 batch” lebih baik daripada “Your journey starts here”.
- Gunakan foto makanan asli **berizin** dan hasil yang benar-benar diunggah; kosongkan area bila tidak ada, bukan gambar stok seolah foto QC.
- Peta hanya di alur distribusi; jangan jadikan latar belakang dekoratif di semua dashboard.
- Ikon sebagai penguat teks; status tidak boleh bergantung warna atau ikon saja.

## 5. Presentasi tanggal, satuan, dan bukti
- Bahasa aplikasi Indonesia, angka dan tanggal lokal; contoh `340 porsi`, `±8 menit`, `Terakhir diperbarui 08.14 WIB`.
- `Target`, `Diproduksi`, `Dimuat`, `Diserahkan`, `Diterima`, `Dibagikan`, dan `Ompreng kembali` adalah variabel berbeda; jangan tampilkan semuanya dengan label umum “total”.
- Jika foto belum diverifikasi, tampilkan “Foto belum tersedia”; jangan tampilkan dummy dalam UI nyata.
- Prototype/dummy demo diberi penanda `DEMO / SIMULASI` yang jelas dan tidak menyaru laporan SPPG resmi.

## 6. Referensi eksekusi
- Sistem token: `UI-DESIGN-SYSTEM.md`.
- Prinsip prioritas mobile: `MOBILE-FIRST-GUIDELINES.md`.
- Motion, map, progress: `MOTION-GUIDELINES.md`.
- Pustaka komponen: `COMPONENT-SPEC.md`.
- Kriteria gerbang: `UI-QUALITY-CHECKLIST.md`.
- Dokumen per-halaman di `docs/pages/` memberi komposisi dan aksi yang lebih konkret daripada dokumen ini.

## 7. Review antidesain generik
Lulus review hanya jika seluruh pertanyaan berikut dijawab dengan bukti screenshot + tindakan yang benar: Apa informasi pertama pada layar 390px? Mengapa urutannya begitu? Apa aksi primer? Apa konten jika data kosong? Apa perubahan setelah submit? Jika warna/shadow dihapus, masihkah hirarki terbaca? Dapatkah screenshot keliru diartikan sebagai dashboard WordPress? Apakah aksesibilitas, performa, dan kejujuran data terjaga? Catat perbaikan di `UI-QUALITY-CHECKLIST.md`.


## V3 | Filter bukan design brief
Arah premium yang orisinal harus menghasilkan keputusan positif, bukan sekadar daftar larangan: gunakan focal point per role, journey-line sebagai motif yang terkait proses, ruang sebagai grouping, pine accent untuk action, dan editorial layout publik. Repo anti-slop menyatakan bahwa aturan itu filter, bukan style guide. Karena itu desain MBG-Track bersumber dari `DESIGN.md`, bukan warna bawaan upstream. 

**Quality check:** tampilkan enam halaman referensi K01/G03/A02/D03/P04/U02 dalam satu mood board screenshot nyata. Jika semua terlihat seperti dashboard card generik meskipun warnanya berbeda, revisi struktur. Jika semua terlalu polos tanpa identitas, tambah hirarki/motif bermakna, bukan glow/gradient. Contoh fisik seni foto makanan tidak boleh menjadi bukti operasi palsu.
