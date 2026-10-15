# 12 Product Backlog Items — baseline terkunci

> Estimasi hari adalah perkiraan per PBI part-time, bukan ukuran kinerja terukur atau janji selesai. LMS: 12–16 PBI, ≥3 per anggota, sekitar 6 hari part-time per PBI, satu tujuan bisnis per story.

| ID | PBI | Tujuan bisnis tunggal | Estimasi |
| --- | --- | --- | --- |
| PBI-01 | Sekolah dan PIC | Identitas tujuan layanan dan penanggung jawab valid | 4–5 hari |
| PBI-02 | Pegawai dan hak akses | Aksi hanya dilakukan aktor yang diberi kewenangan | 4–5 hari |
| PBI-03 | Menu dan kandungan gizi | Menu terverifikasi dan histori nutrisi tidak berubah sembarangan | 4–5 hari |
| PBI-04 | Kebutuhan porsi sekolah | SPPG memiliki angka porsi aktual terkonfirmasi per sekolah/hari | 4–5 hari |
| PBI-05 | Rencana produksi dan alokasi | Permintaan terkonfirmasi menjadi perencanaan produksi dan target sekolah | 4–5 hari |
| PBI-06 | Monitoring produksi dan kemasan | Jumlah aktual dan progres setiap tahap dapat dipertanggungjawabkan | 5–6 hari |
| PBI-07 | Batch dan traceability | Setiap porsi terlacak ke rencana/menu/QC serta sekolah penerima | 4–5 hari |
| PBI-08 | Trip, kendaraan dan rute | Pengiriman multi-sekolah mempunyai penugasan dan urutan stop jelas | 5–6 hari |
| PBI-09 | GPS distribusi dan pengembalian ompreng | Layanan tahu progres perjalanan aktif serta retur wadah tanpa pemantauan berlebihan | 6 hari |
| PBI-10 | Penerimaan dan pembagian di sekolah | Perpindahan tanggung jawab dan realisasi penerimaan tercatat sekolah | 5–6 hari |
| PBI-11 | Insiden/kendala | Setiap masalah memiliki status tanggung jawab dan penyelesaian terpantau | 4–5 hari |
| PBI-12 | Monitoring internal dan transparansi publik | Setiap pengguna mendapat visibilitas status MBG sesuai kebutuhan/izin | 5–6 hari |

## Detail formal

### PBI-01 — Sekolah dan PIC
**Tujuan bisnis:** Identitas tujuan layanan dan penanggung jawab valid.

**Aktor:** Kepala SPPG; PIC sekolah.

**User story:** Sebagai aktor yang berwenang, saya ingin menjalankan fitur sekolah dan pic agar identitas tujuan layanan dan penanggung jawab valid.

**Alur:** Kepala memilih SPPG sendiri → menambah sekolah dengan kode/alamat/koordinat/penerima → mengaitkan PIC → PIC hanya bisa mengakses sekolahnya.

**Input dan sumber:** schools; users.

**Output/efek ke role lain:** Profil sekolah, PIC aktif, school slug publik.

**Aturan penting:** Kode sekolah unik; perubahan tenant dan PIC tercatat; sekolah berhistori dinonaktifkan, bukan hard-delete.

**Acceptance criteria yang diuji:** CRUD sekolah aktif; validasi duplikat; PIC hanya sekolahnya; 403 lintas sekolah; riwayat tetap setelah nonaktif.

**Estimasi awal:** 4–5 hari part-time.

**Tests wajib:** happy path, validasi salah, 403 batas role/tenant, transaksi ganda, status ilegal, dan histori audit; jalur tampilan ada di `docs/pages/PAGE-REGISTRY.md`.

### PBI-02 — Pegawai dan hak akses
**Tujuan bisnis:** Aksi hanya dilakukan aktor yang diberi kewenangan.

**Aktor:** Kepala SPPG; pegawai.

**User story:** Sebagai aktor yang berwenang, saya ingin menjalankan fitur pegawai dan hak akses agar aksi hanya dilakukan aktor yang diberi kewenangan.

**Alur:** Kepala membuat akun internal → menetapkan role+SPPG → akun aktif → login → backend Policy membatasi setiap resource → nonaktif saat keluar.

**Input dan sumber:** users.

**Output/efek ke role lain:** Akun, status, dan hak akses.

**Aturan penting:** Role tidak dipilih sendiri; reset aman; account inactive ditolak; perubahan role punya audit.

**Acceptance criteria yang diuji:** Hak berbeda; API 403; tidak bisa self-promote; nonaktif login ditolak; employee SPPG A tidak melihat B.

**Estimasi awal:** 4–5 hari part-time.

**Tests wajib:** happy path, validasi salah, 403 batas role/tenant, transaksi ganda, status ilegal, dan histori audit; jalur tampilan ada di `docs/pages/PAGE-REGISTRY.md`.

### PBI-03 — Menu dan kandungan gizi
**Tujuan bisnis:** Menu terverifikasi dan histori nutrisi tidak berubah sembarangan.

**Aktor:** Pengawas Gizi.

**User story:** Sebagai aktor yang berwenang, saya ingin menjalankan fitur menu dan kandungan gizi agar menu terverifikasi dan histori nutrisi tidak berubah sembarangan.

**Alur:** Ahli gizi membuat draft menu/item+gramasi→ input nilai gizi bersumber → cek totals → aktifkan versi → rencana produksi menunjuk versi tersebut.

**Input dan sumber:** menus; menu_items.

**Output/efek ke role lain:** Menu aktif berversi dengan informasi nutrisi.

**Aturan penting:** Tidak tampil angka gizi default rekaan; menu aktif tak diedit in-place; perlu versi baru.

**Acceptance criteria yang diuji:** Draft bisa diedit; nilai wajib lengkap; version immutability; agregat nutrisi konsisten; publik hanya melihat menu terjadwal.

**Estimasi awal:** 4–5 hari part-time.

**Tests wajib:** happy path, validasi salah, 403 batas role/tenant, transaksi ganda, status ilegal, dan histori audit; jalur tampilan ada di `docs/pages/PAGE-REGISTRY.md`.

### PBI-04 — Kebutuhan porsi sekolah
**Tujuan bisnis:** SPPG memiliki angka porsi aktual terkonfirmasi per sekolah/hari.

**Aktor:** PIC Sekolah.

**User story:** Sebagai aktor yang berwenang, saya ingin menjalankan fitur kebutuhan porsi sekolah agar sppg memiliki angka porsi aktual terkonfirmasi per sekolah/hari.

**Alur:** PIC melihat tanggal layanan → mengisi jumlah penerima dan kebutuhan → konfirmasi → SPPG melihat agregat; perubahan setelah cutoff lewat revisi beralasan.

**Input dan sumber:** schools; daily_portion_requests.

**Output/efek ke role lain:** Permintaan terkonfirmasi, daftar sekolah belum konfirmasi.

**Aturan penting:** Unique school+date; angka nonnegatif; belum konfirmasi tidak diasumsikan nol; periode cutoff configurable.

**Acceptance criteria yang diuji:** PIC hanya sekolahnya; total otomatis; duplikat ditolak; revisi diaudit; reminder sekolah belum input.

**Estimasi awal:** 4–5 hari part-time.

**Tests wajib:** happy path, validasi salah, 403 batas role/tenant, transaksi ganda, status ilegal, dan histori audit; jalur tampilan ada di `docs/pages/PAGE-REGISTRY.md`.

### PBI-05 — Rencana produksi dan alokasi
**Tujuan bisnis:** Permintaan terkonfirmasi menjadi perencanaan produksi dan target sekolah.

**Aktor:** Kepala SPPG; Asisten Lapangan.

**User story:** Sebagai aktor yang berwenang, saya ingin menjalankan fitur rencana produksi dan alokasi agar permintaan terkonfirmasi menjadi perencanaan produksi dan target sekolah.

**Alur:** Kepala memilih tanggal/menu aktif → sistem tarik kebutuhan sekolah → alokasikan ke rencana → cek total/kapasitas → finalisasi → tugas produksi muncul.

**Input dan sumber:** daily_portion_requests; production_plans; production_allocations.

**Output/efek ke role lain:** Rencana, target, alokasi, versi menu.

**Aturan penting:** Target≠aktual tetap berbeda; jangan final jika permintaan belum valid tanpa alasan/override; transaksi saat finalisasi.

**Acceptance criteria yang diuji:** Total benar; alokasi tenant benar; tidak dobel alokasi; rencana finalized terbaca oleh produksi.

**Estimasi awal:** 4–5 hari part-time.

**Tests wajib:** happy path, validasi salah, 403 batas role/tenant, transaksi ganda, status ilegal, dan histori audit; jalur tampilan ada di `docs/pages/PAGE-REGISTRY.md`.

### PBI-06 — Monitoring produksi dan kemasan
**Tujuan bisnis:** Jumlah aktual dan progres setiap tahap dapat dipertanggungjawabkan.

**Aktor:** Asisten Lapangan; Pengawas Gizi.

**User story:** Sebagai aktor yang berwenang, saya ingin menjalankan fitur monitoring produksi dan kemasan agar jumlah aktual dan progres setiap tahap dapat dipertanggungjawabkan.

**Alur:** Asisten membuka pekerjaan hari ini → mulai persiapan/masak/pemorsian/packing berurutan → input hasil aktual → unggah bukti makanan siap → kirim ke QC.

**Input dan sumber:** production_plans; food_batches; production_stage_logs; evidence_photos.

**Output/efek ke role lain:** Timeline produksi, target-vs-aktual, permintaan QC.

**Aturan penting:** Tidak bisa lompat status; waktu server; selisih tidak ditimpa; foto wajib pada checkpoint akhir produksi.

**Acceptance criteria yang diuji:** Urutan valid; jumlah valid; foto terkait batch; waktu tersimpan; selisih menciptakan alert/kendala.

**Estimasi awal:** 5–6 hari part-time.

**Tests wajib:** happy path, validasi salah, 403 batas role/tenant, transaksi ganda, status ilegal, dan histori audit; jalur tampilan ada di `docs/pages/PAGE-REGISTRY.md`.

### PBI-07 — Batch dan traceability
**Tujuan bisnis:** Setiap porsi terlacak ke rencana/menu/QC serta sekolah penerima.

**Aktor:** Asisten Lapangan; Pengawas Gizi.

**User story:** Sebagai aktor yang berwenang, saya ingin menjalankan fitur batch dan traceability agar setiap porsi terlacak ke rencana/menu/qc serta sekolah penerima.

**Alur:** Batch dibuat dari rencana → kode unik & target/aktual → QC batch dicatat → release atau hold → batch dibagi lewat delivery_items pada stop.

**Input dan sumber:** food_batches; quality_checks; delivery_items; evidence_photos.

**Output/efek ke role lain:** Batch released/held, asal menu, tujuan sekolah.

**Aturan penting:** Batch tidak dikirim sebelum released; catatan QC tidak auto-menilai aman; photo dan kode versi terkunci sesudah rilis.

**Acceptance criteria yang diuji:** Batch unik; versi menu konsisten; batch ON_HOLD tidak dapat dipilih; telusur sekolah+batch dua arah.

**Estimasi awal:** 4–5 hari part-time.

**Tests wajib:** happy path, validasi salah, 403 batas role/tenant, transaksi ganda, status ilegal, dan histori audit; jalur tampilan ada di `docs/pages/PAGE-REGISTRY.md`.

### PBI-08 — Trip, kendaraan dan rute
**Tujuan bisnis:** Pengiriman multi-sekolah mempunyai penugasan dan urutan stop jelas.

**Aktor:** Kepala SPPG; Asisten Lapangan; Driver.

**User story:** Sebagai aktor yang berwenang, saya ingin menjalankan fitur trip, kendaraan dan rute agar pengiriman multi-sekolah mempunyai penugasan dan urutan stop jelas.

**Alur:** Ambil alokasi+batch ready → pilih driver/vehicle → susun trip stop → cek kapasitas/checklist → assign → driver menerima tugas.

**Input dan sumber:** trips; trip_stops; vehicles; delivery_items.

**Output/efek ke role lain:** Trip dengan stop yang dapat diselesaikan sendiri-sendiri.

**Aturan penting:** Driver/vehicle tidak overlap; tujuan harus terkait SPPG; pengiriman batch released; jalur pickup bisa berbeda.

**Acceptance criteria yang diuji:** Trip 3 sekolah; urutan tampil; conflict ditolak; driver hanya tugas sendiri; daftar muatan valid.

**Estimasi awal:** 5–6 hari part-time.

**Tests wajib:** happy path, validasi salah, 403 batas role/tenant, transaksi ganda, status ilegal, dan histori audit; jalur tampilan ada di `docs/pages/PAGE-REGISTRY.md`.

### PBI-09 — GPS distribusi dan pengembalian ompreng
**Tujuan bisnis:** Layanan tahu progres perjalanan aktif serta retur wadah tanpa pemantauan berlebihan.

**Aktor:** Driver; PIC; Kepala SPPG.

**User story:** Sebagai aktor yang berwenang, saya ingin menjalankan fitur gps distribusi dan pengembalian ompreng agar layanan tahu progres perjalanan aktif serta retur wadah tanpa pemantauan berlebihan.

**Alur:** Driver mulai trip → minta izin GPS → lokasi masuk dan status per stop berubah; saat PIC siap pickup, dibuat penugasan pickup → driver mengambil ompreng → asisten konfirmasi return.

**Input dan sumber:** trips; gps_locations; trip_stops; tray_returns; evidence_photos.

**Output/efek ke role lain:** Status lokasi+ETA opsional; riwayat delivery/pickup; bukti dan selisih tray.

**Aturan penting:** Tidak menjanjikan tracking background browser; fallback GPS mati; stale berbeda live; publik tanpa raw GPS; return hitung berdasarkan fisik.

**Acceptance criteria yang diuji:** Aktif saat trip; lokasi bergerak dari titik aktual; stale jelas; notif pra-tiba hanya 1×; pickup tercatat; retur selisih jadi incident.

**Estimasi awal:** 6 hari part-time.

**Tests wajib:** happy path, validasi salah, 403 batas role/tenant, transaksi ganda, status ilegal, dan histori audit; jalur tampilan ada di `docs/pages/PAGE-REGISTRY.md`.

### PBI-10 — Penerimaan dan pembagian di sekolah
**Tujuan bisnis:** Perpindahan tanggung jawab dan realisasi penerimaan tercatat sekolah.

**Aktor:** PIC Sekolah; Driver.

**User story:** Sebagai aktor yang berwenang, saya ingin menjalankan fitur penerimaan dan pembagian di sekolah agar perpindahan tanggung jawab dan realisasi penerimaan tercatat sekolah.

**Alur:** Driver tiba+foto handover → PIC verifikasi porsi/kondisi & organoleptik kedatangan → konfirmasi → uji lagi sebelum konsumsi → bagi makanan → catat served/leftover, selesai.

**Input dan sumber:** trip_stops; school_service_records; quality_checks; evidence_photos.

**Output/efek ke role lain:** Serah-terima, dua checkpoint QC sekolah, realisasi pembagian.

**Aturan penting:** PIC saja menilai diterima; gagal QC menahan pembagian; expected/delivered/received/served berbeda dan terlacak.

**Acceptance criteria yang diuji:** Konfirmasi per sekolah; selisih menghasilkan incident; uji tidak boleh dilewati; pembagian tak bisa sebelum penerimaan.

**Estimasi awal:** 5–6 hari part-time.

**Tests wajib:** happy path, validasi salah, 403 batas role/tenant, transaksi ganda, status ilegal, dan histori audit; jalur tampilan ada di `docs/pages/PAGE-REGISTRY.md`.

### PBI-11 — Insiden/kendala
**Tujuan bisnis:** Setiap masalah memiliki status tanggung jawab dan penyelesaian terpantau.

**Aktor:** Kepala; Gizi; Driver; Asisten; PIC.

**User story:** Sebagai aktor yang berwenang, saya ingin menjalankan fitur insiden/kendala agar setiap masalah memiliki status tanggung jawab dan penyelesaian terpantau.

**Alur:** Pengguna membuat insiden pada trip/batch/sekolah → notifikasi relevan → kepala tindak lanjuti → catat resolusi → tutup, tanpa hapus riwayat.

**Input dan sumber:** incidents; evidence_photos; activity_logs.

**Output/efek ke role lain:** Tiket incident dan rekam tindak lanjut.

**Aturan penting:** Food safety blokir pembagian; hanya scope relevan; resolusi wajib catatan; tidak hard-delete.

**Acceptance criteria yang diuji:** Buat/lacak incident; bukti; filter; penerima notifikasi sesuai peran; resolved/closed audit.

**Estimasi awal:** 4–5 hari part-time.

**Tests wajib:** happy path, validasi salah, 403 batas role/tenant, transaksi ganda, status ilegal, dan histori audit; jalur tampilan ada di `docs/pages/PAGE-REGISTRY.md`.

### PBI-12 — Monitoring internal dan transparansi publik
**Tujuan bisnis:** Setiap pengguna mendapat visibilitas status MBG sesuai kebutuhan/izin.

**Aktor:** Semua role; publik read-only.

**User story:** Sebagai aktor yang berwenang, saya ingin menjalankan fitur monitoring internal dan transparansi publik agar setiap pengguna mendapat visibilitas status mbg sesuai kebutuhan/izin.

**Alur:** Halaman role membaca satu transaksi → status/trip/ETA ditampilkan sesuai scope → publik pilih sekolah/QR tanpa login → statistik berasal dari data nyata, tidak diinput manual.

**Input dan sumber:** school_service_records; production_plans; trips; menus; notifications.

**Output/efek ke role lain:** Dashboard internal dan status publik terpisah izin.

**Aturan penting:** No raw GPS/PII publik; staleness jelas; empty state jika sekolah belum ada jadwal; dashboard tidak mengubah transaksi.

**Acceptance criteria yang diuji:** PIC A tak melihat B; publik read-only; metrik konsisten; waktu update jelas; filter sekolah/tanggal benar.

**Estimasi awal:** 5–6 hari part-time.

**Tests wajib:** happy path, validasi salah, 403 batas role/tenant, transaksi ganda, status ilegal, dan histori audit; jalur tampilan ada di `docs/pages/PAGE-REGISTRY.md`.

## Dependensi yang tidak bisa ditukar sembarangan
01/02 untuk user dan school → 03/04 untuk menu dan kebutuhan → 05 rencana → 06/07 produksi + batch QC → 08 trip dan stop → 09/10 driver+PIC → 11 insiden lintas proses → 12 visibilitas. Sebagian bisa dikerjakan paralel setelah kontrak model/API disepakati.

## Uji acceptance terpadu
Demo 1 SPPG, SD A 340, SD B 276, SMP C 410 (angka **simulasi**); batch released; driver multi-stop; SD A menerima 338 dari 340; incident otomatis; PIC memeriksa tahap sebelum konsumsi; baki dikembalikan dengan hitungan terpisah; publik hanya melihat status sekolah pilihan.

## Quality gate tambahan untuk 12 PBI v2
1. Satu PBI harus punya satu hasil bisnis; implementasi dapat memiliki banyak route, file page, komponen dan test tanpa menciptakan item backlog baru.
2. Acceptance criteria tidak boleh hanya “halaman tampil”; wajib membuktikan perubahan data benar dan dapat dilihat role berikutnya.
3. Setiap PBI yang mengubah status mempunyai: Policy server, precondition, audit, idempotency, UI loading/error, satu minimal test silang-role.
4. PBI yang menyentuh GPS harus membedakan live/stale/denied; yang menyentuh bukti foto harus punya private storage dan fallback upload.
5. PBI yang memengaruhi publik hanya mengekspos safe projection; tidak ada public read langsung ke Eloquent internal berskala luas.
6. Definisi DONE: acceptance verified, tests true pass, peer review, screenshots mobile/desktop pada page terkait, docs/update, dan tidak ada P0 privasi/food safety.

### Dependensi operasional (jangan salah sprint)
`01/02` master akses → `03/04` menu/porsi → `05` plan/alokasi → `06/07` produksi/QC release → `08` trip/driver/vehicle → `09` delivery/GPS/trays → `10` sekolah → `11` incident → `12` dashboard/projection. PBI 11 cross-cutting sudah dapat dimulai bersama QC agar kasus gagal punya penanganan. PBI 12 dapat dibuat skeleton lebih awal, tetapi bukan sebagai mock status tanpa data.

### Contoh acceptance E2E yang bisa direproduksi
Seorang PIC sekolah A mengonfirmasi 340 porsi (PBI04). Kepala memfinalisasi alokasi yang memuat 340 untuk A, 276 B dan 410 C (PBI05). Dua batch dapat membagi satu sekolah jika aturan load terpenuhi (PBI07/09). Driver Trip-001 menyerahkan 340 ke sekolah A; PIC mengakui 338 dengan 2 rusak (PBI10/11). Sistem memisahkan selisih kurang fisik 2 dan kerusakan subset dari 338. Pelayanan tidak boleh otomatis dinyatakan selesai jika pemeriksaan keamanan belum lulus. Periksa seluruh role setelah refresh browser, bukan hanya state frontend.


## V3 | Acceptance overlay untuk seluruh 12 PBI
Pada setiap PBI, selain test bisnis, halaman terkait wajib menulis Design Read, live UI state, no ghost link, verified source untuk angka dan asset, serta 4 blok Delivery Gate. Implementasi **tidak mengubah satu tujuan bisnis per PBI**. Screenshot premium tidak menutupi 403/409/422 bugs. Lihat `docs/pages/PAGE-REGISTRY.md` untuk page mapping.
