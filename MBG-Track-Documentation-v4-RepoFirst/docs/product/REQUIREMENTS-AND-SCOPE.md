# Requirements, Scope dan Definition of Done

## In scope dan PBI
PBI-01 sekolah/PIC; 02 akun/role; 03 menu/gizi; 04 kebutuhan porsi; 05 rencana produksi; 06 progres produksi; 07 batch/traceability; 08 trip/rute; 09 lokasi aktif + pengambilan ompreng; 10 penerimaan/pembagian; 11 insiden; 12 monitoring dan publik. Login dan logout adalah prasyarat tetapi **tidak dihitung** sesuai LMS.

## Non-scope yang harus dijaga
- Integrasi API SIPGN/BGN tanpa otorisasi, scraping sistem internal pemerintah, pengadaan & stok bahan baku penuh, keuangan, absensi siswa individual, data kesehatan anak, chat, rating makanan, chatbot AI, auto-dispatch AI, facial recognition.
- Push publik tanpa opt-in, notifikasi SMS/WhatsApp, tracking background terjamin dari web, ETA berjamin tepat menit tertentu, telemetri IoT suhu real-time.

## Minimal testable outputs
- Role login internal, orang tua/siswa tanpa login; sekolah/pegawai terpaut tenant yang benar.
- Trip dapat memiliki banyak stop; satu stop menerima campuran delivery_items dari beberapa batch; pengambilan ompreng bisa dalam trip lain.
- Menu diberi versi agar histori tidak berubah ketika ahli gizi mengedit resep.
- Foto evidence bisa dilacak ke transaksi, tersimpan privat; akses signed/policy.
- Setiap transisi status memiliki aktor, waktu, alasan bila koreksi, dan histori; status tidak naik sendiri karena gambar diunggah.
- Semua record data contoh dilabel **simulasi**.

## Batas waktu dan pembagian tim
Minimal 12–16 PBI; minimal 3 per mahasiswa jika 4 anggota = 12. Target satu PBI dapat dikembangkan ±6 hari part-time menurut LMS; ini target sizing, bukan jaminan. Rekomendasi pembagian tim dalam `SPRINT-PLAN.md`; beban GPS dan dashboard perlu dibantu bersama untuk menjaga keseimbangan.

## Acceptance untuk rancangan bisnis
Satu user story = satu tujuan bisnis. Jika halaman dashboard memuat banyak metrik, tujuan tetap *observabilitas layanan*; jika fitur pengembalian wadah tujuan berbeda, detailnya masuk lifecycle Fleet dan bukan digabung dengan insiden sebagai satu PBI.

## Perubahan scope
Ajukan perubahan dengan: masalah, referensi bukti, dampak ke PBI, tabel, halaman, effort sprint, keputusan pemilik proyek dan tests. Catat di `DECISION-LOG.md`, jangan ubah diam-diam.

## Scope matrix v2 — jangan disalahartikan sebagai janji production-ready
| Dalam MVP akademik | Tidak masuk MVP | Potensi fase lanjut (belum disetujui) |
|---|---|---|
| akun 5 role internal + publik tanpa akun | biodata/attendance per siswa | integrasi resmi dengan otorisasi |
| 1 SPPG melayani banyak sekolah | banyak organisasi pemerintah nasional | multi-SPPG dengan tenant isolation lebih luas |
| menu terversi & perhitungan nilai gizi input | AI menentukan gizi otomatis | pipeline verifikasi referensi gizi |
| aggregate request, alokasi & batch | pengadaan/pembayaran/vendor portal | modul procurement terpisah |
| QC manual, bukti & audit | lab test terintegrasi | inspeksi lanjutan |
| trip multi-stop, GPS foreground | tracking background terjamin | driver native app dengan izin sesuai kebijakan |
| PIC penerimaan, makan, tray lifecycle | sensor IoT atau identitas anak | sistem logistik besar |
| incident, dashboard, publik terbatas | push publik tanpa akun | subscription publik opt-in |

### Prioritas saat scope berbenturan dengan waktu kuliah
1. Kebenaran domain/role/security dan migration > visual embellishment.
2. Core journey request → batch → trip → receipt → served → tray selesai dan dapat diuji.
3. 12 PBI tetap satu tujuan bisnis per item; task teknis/komponen dapat lebih banyak tetapi bukan PBI baru.
4. Foto dan GPS boleh mempunyai fallback operasional yang terdokumentasi; jangan menyatakan sistem tidak bisa dipakai di lapangan hanya karena API pihak ketiga gagal.
5. Tiap perubahan requirement ditulis di `DECISION-LOG.md`, diperiksa dampaknya ke `DATA-DICTIONARY`, `API-CONTRACTS`, `STATUS-WORKFLOWS`, dan seluruh page terkait.

### Constraint proyek
Laravel monolith + MySQL sebagai baseline; tanpa membuat enam microservice hanya karena enam model AI terlibat. Pilihan frontend sebaiknya Blade+Tailwind+Alpine/Livewire bila telah dipilih dalam tim; jangan campur banyak framework tanpa kebutuhan nyata. **Versi Laravel dan dependensi harus dipin oleh proyek setelah verifikasi lingkungan lokal**, bukan semata mengikuti dokumentasi terbaru di internet.


## V3 | No scope creep from anti-slop
Anti-slop meningkatkan mutu pelaksanaan 12 PBI, **bukan PBI ke-13**. Jangan menambah testimonials, marketing pricing, AI assistant, map heatmap atau video hero agar tampak keren. Data pribadi siswa tetap tidak disimpan. Kalau aset fotografi belum approved, tampilkan placeholder. Uji semua actions yang ditampilkan; fitur belum ada tidak tampil sebagai clickable.
