# Role Permission Matrix — Prinsip Least Privilege

Tidak ada tombol tersembunyi yang dianggap sebagai pengamanan. Backend Laravel Policies, scoped queries, Form Request dan route binding menegakkan izin. `sppg_id` pada akun internal, `school_id` pada PIC, dan ownership trip pada driver menjadi dasar pemeriksaan.

| Modul | KEPALA SPPG | PENGAWAS GIZI | ASISTEN LAPANGAN | KURIR | PIC SEKOLAH | PUBLIK |
| --- | --- | --- | --- | --- | --- | --- |
| Sekolah/PIC | Kelola SPPG | Lihat SPPG | Lihat tujuan | Hanya alamat stop | Lihat sekolah sendiri | Info publik saja |
| Akun/pegawai | Kelola SPPG | Akun sendiri | Akun sendiri | Akun sendiri | Akun sendiri | Tidak |
| Menu dan gizi | Monitor | Buat/versi/aktivasi | Lihat | Ringkas tugas | Lihat menu sekolah | Lihat menu terjadwal |
| Porsi harian | Lihat/revisi terotorisasi | Lihat | Lihat | Tidak | Input/konfirmasi sendiri | Tidak |
| Produksi | Finalisasi plan/lihat | QC terkait | Update tahapan | Lihat muatan | Status sekolah | Status sederhana |
| Batch QC | Monitor | Uji/release/hold | Buat/serahkan QC | Lihat yang ditugaskan | Hanya status | Tidak internal |
| Trip/stop | Jadwalkan/override | Lihat relevan | Loading | Jalankan trip sendiri | Pantau ke sekolahnya | Status saja |
| GPS presisi | Trip SPPG | Tidak default | Tidak default | Kirim/lihat sendiri | Trip tujuan aktif saja | Tidak |
| Serah terima | Monitor | Baca kondisi | Lihat | Bukti kirim | Konfirmasi terima | Status saja |
| Pembagian | Monitor | Lihat QC | Tidak | Tidak | Uji+catat realisasi | Status ringkas |
| Ompreng | Monitor | Tidak | Terima balik | Pickup sesuai tugas | Siap diambil | Tidak |
| Incident | Tindak lanjut | Kualitas saja | Buat/lacak terkait | Buat/lacak sendiri | Buat/lacak sekolah | Tidak |
| Foto bukti | Lihat terkait | Lihat kualitas | Upload produksi/loading | Upload delivery/pickup | Upload receipt/tray | Tidak |
| Audit | SPPG | Perubahan terkait | Aktivitas sendiri | Aktivitas sendiri | Sekolah sendiri terbatas | Tidak |

## Invariant lintas tenant
1. Kepala SPPG A tidak bisa query sekolah/batch/trip SPPG B.
2. PIC SD A tidak bisa query layanan atau foto SD B walau mengganti ID di URL.
3. Driver tidak bisa mengirim GPS ke trip driver lain; server pakai assigned driver_id, jangan percaya body JSON.
4. File evidence hanya boleh di-stream via controller yang mengecek policy; jangan taruh di public disk tanpa pembatasan.
5. Halaman publik mengambil projection yang disanitasi, bukan serialisasi model Eloquent mentah.
6. Audit perubahan role, data master, status, kuantitas, dan koreksi; audit bukan izin mengubah data.

## Aturan koreksi
Kepala dapat menjalankan override tertentu tetapi tetap harus memenuhi larangan keamanan pangan dan audit. Tidak ada `superadmin` tersembunyi. Override memerlukan alasan, actor, before/after dan waktu.

## Perancangan permission implementasi
`SchoolPolicy`, `UserPolicy`, `MenuPolicy`, `PortionRequestPolicy`, `ProductionPlanPolicy`, `FoodBatchPolicy`, `TripPolicy`, `TripStopPolicy`, `SchoolServicePolicy`, `TrayReturnPolicy`, `IncidentPolicy`, `EvidencePolicy`.

## Negative tests
Tiap model diuji 401 unauthenticated, 403 role salah, 403 tenant lain, 404 atau 403 data tak terjangkau sesuai kebijakan tidak bocor eksistensi; serta test akses URL storage evidence.

## Kebijakan server sebagai sumber keputusan, bukan tombol frontend
| Aksi | Kepala | Gizi | Asisten | Kurir | PIC | Publik |
|---|---|---|---|---|---|---|
| Edit sekolah dan assign PIC | C | — | — | — | — | — |
| Ubah hak pegawai | C | — | — | — | — | — |
| Versi menu & approve | R | C | R sesuai tugas | — | R scoped | menu publik |
| Konfirmasi porsi harian | R | — | R plan | — | C scoped | — |
| Finalisasi produksi | C | R | R | — | — | — |
| Update tahap & jumlah produksi | R | R | C | — | — | — |
| QC release batch | R | C | R | — | — | — |
| Assign trip kendaraan | C | — | R manifest | R assigned | R status sekolah | — |
| Kirim GPS trip | R monitor | — | — | C assigned | R relevant stop | **tidak** |
| Bukti serah driver | R | R jika kualitas | R sesuai tugas | C assigned | R sekolah sendiri | — |
| Konfirmasi received/meal | R | R kualitas | — | R confirmed status | C scoped | proyeksi status |
| Tray ready/pickup/return | R | — | C return | C pickup | C ready | status ringkas jika disetujui |
| Incident | C | C khusus kualitas | Create assigned | Create assigned | Create scoped | — |

`C` hanya bisa membuat/mengubah **dengan Policy & state yang sah**; `R` adalah read yang discope, tidak berarti semua record tenant/semua kolom. Matriks diringkas, action detail di page spec. **Tidak ada role publik sebagai entitas user atau login.**

### Test ownership lintas school/SPPG
Akun PIC A mencoba URL PIC B → 403/404 tanpa bocoran. Driver 1 mencoba GET trip driver 2 → 403/404. Kepala SPPG X mencoba menu/trip SPPG Y → 403/404. Staf nonaktif mencoba session lama → invalidasi/akses ditolak sesuai implementasi. Header `school_id`/`sppg_id` di request harus diabaikan sebagai sumber scope dan diambil dari server/session.


## V3 | Visible UI ≠ authorization
R-24/R-26 mengharuskan setiap tombol dan link bekerja, tetapi hanya bila user berhak. Button tersembunyi belum cukup: setiap route/resource memakai backend Policy, PIC scoped school, driver assigned trip, publik no GPS private. Perbedaan tampilan antarrole merupakan task composition, bukan celah permission. Test klik unauthorized harus 403/404 sesuai policy.
