# Status Workflow — Mesin Keadaan Terpisah per Domain

**Jangan menyatukan seluruh MBG menjadi satu `status_mbg`.** Status produksi, trip, per sekolah, konsumsi, ompreng dan incident memiliki siklus berbeda. Enum berbentuk PHP backed enum/string db; format snake_case dalam storage boleh, nama dalam dokumen UPPER_CASE.

## portion_requests
- Jalur normal: `DRAFT -> CONFIRMED -> LOCKED`.
- Jalur pengecualian: `LOCKED -> REVISED -> LOCKED (alasan) | CANCELLED (layanan batal)`.
- Pemilik transisi: PIC; kepala untuk revisi terotorisasi.
- Test wajib: urutan legal, urutan ilegal ditolak 409, duplicate request idempotent, status lama tidak tertimpa tanpa audit.

## production_plans
- Jalur normal: `PLANNED -> FINALIZED -> IN_PRODUCTION -> READY_FOR_DISTRIBUTION -> COMPLETED`.
- Jalur pengecualian: `CANCELLED sebelum produksi | CORRECTION_LOG, bukan overwrite`.
- Pemilik transisi: Kepala finalisasi; Asisten menjalankan.
- Test wajib: urutan legal, urutan ilegal ditolak 409, duplicate request idempotent, status lama tidak tertimpa tanpa audit.

## food_batches
- Jalur normal: `PREPARING -> COOKING -> PORTIONING -> PACKED -> QC_CHECK -> RELEASED`.
- Jalur pengecualian: `QC_CHECK -> ON_HOLD; hanya release setelah QC baru lulus`.
- Pemilik transisi: Asisten update stage; Pengawas QC.
- Test wajib: urutan legal, urutan ilegal ditolak 409, duplicate request idempotent, status lama tidak tertimpa tanpa audit.

## delivery_trips
- Jalur normal: `DRAFT -> ASSIGNED -> VEHICLE_CHECKED -> READY -> IN_PROGRESS -> COMPLETED`.
- Jalur pengecualian: `INTERRUPTED -> IN_PROGRESS (recovery), CANCELLED sebelum perjalanan`.
- Pemilik transisi: Kepala assign; driver start/complete.
- Test wajib: urutan legal, urutan ilegal ditolak 409, duplicate request idempotent, status lama tidak tertimpa tanpa audit.

## delivery_stops
- Jalur normal: `PENDING -> EN_ROUTE -> ARRIVED -> HANDED_OVER -> COMPLETED`.
- Jalur pengecualian: `ARRIVED_AWAITING_CONFIRMATION | EXCEPTION | SKIPPED dengan alasan`.
- Pemilik transisi: Driver menandai arrived; PIC konfirmasi handover.
- Test wajib: urutan legal, urutan ilegal ditolak 409, duplicate request idempotent, status lama tidak tertimpa tanpa audit.

## school_receipts
- Jalur normal: `WAITING -> RECEIVED -> CLEARED_TO_SERVE -> DISTRIBUTED -> MEAL_COMPLETED`.
- Jalur pengecualian: `RECEIVED_WITH_DISCREPANCY | HELD (QC gagal), tindakan beralasan`.
- Pemilik transisi: PIC sekolah dan pengawas untuk safety escalation.
- Test wajib: urutan legal, urutan ilegal ditolak 409, duplicate request idempotent, status lama tidak tertimpa tanpa audit.

## pickup_trips
- Jalur normal: `DRAFT -> ASSIGNED -> READY -> IN_PROGRESS -> COMPLETED`.
- Jalur pengecualian: `INTERRUPTED | CANCELLED`.
- Pemilik transisi: Kepala + driver.
- Test wajib: urutan legal, urutan ilegal ditolak 409, duplicate request idempotent, status lama tidak tertimpa tanpa audit.

## container_returns
- Jalur normal: `IN_USE -> READY_FOR_PICKUP -> PICKUP_ASSIGNED -> PICKED_UP -> RETURNED_TO_SPPG`.
- Jalur pengecualian: `DISCREPANCY saat pickup/return`.
- Pemilik transisi: PIC siap; driver ambil; asisten terima balik.
- Test wajib: urutan legal, urutan ilegal ditolak 409, duplicate request idempotent, status lama tidak tertimpa tanpa audit.

## incidents
- Jalur normal: `OPEN -> ACKNOWLEDGED -> IN_PROGRESS -> RESOLVED -> CLOSED`.
- Jalur pengecualian: `REOPENED -> IN_PROGRESS jika bukti baru`.
- Pemilik transisi: Kepala; gizi untuk kualitas.
- Test wajib: urutan legal, urutan ilegal ditolak 409, duplicate request idempotent, status lama tidak tertimpa tanpa audit.

## Gate wajib lintas domain
1. `FoodBatch::RELEASED` hanya jika check BEFORE_DISPATCH lulus dan foto kesiapan tersedia.
2. Driver start trip hanya bila ASSIGNED, checklist kendaraan lulus, muatan sesuai dan ada stop aktif.
3. `trip_stop.HANDED_OVER` menunggu konfirmasi PIC; foto driver sendiri tidak menggantikan penerimaan PIC.
4. `SchoolService.DISTRIBUTED` hanya setelah pemeriksaan kedatangan dan sebelum konsumsi lulus; bila `HELD`, blokir distribusi sampai penyelesaian resmi, bukan override buta.
5. `Trip.COMPLETED` setelah semua delivery stop selesai/ditutup eksplisit. Pickup ompreng boleh jadi trip terpisah dan tidak boleh diam-diam diasumsikan selesai.
6. Perubahan status dicatat `activity_logs` + event yang menghasilkan database notifications sesuai aturan; mencegah notif ganda dengan notification event key.

## Kesetaraan label publik
`PLANNED/COOKING/PACKED` → Sedang disiapkan; `READY/ASSIGNED` → Siap dikirim; `EN_ROUTE` → Dalam perjalanan; `ARRIVED/HANDED_OVER` → Sudah tiba / diterima setelah konfirmasi PIC; `DISTRIBUTED/MEAL_COMPLETED` → Sudah dibagikan / kegiatan selesai. Hindari menyatakan “anak Anda makan” karena tidak ada data siswa individual.

## Penentuan transisi
Implementasi dengan service domain, `DB::transaction()`, optimistic/pessimistic locking pada event sensitif, status transition map pusat, dan audit. Jangan memberi update status langsung dari controller tanpa pemeriksaan.

## Spesifikasi transisi tambahan v2 dan audit
| Domain | Trigger | Guard minimal | Output/event |
|---|---|---|---|
| Portion request | PIC confirm | tanggal cocok sekolah, sebelum cutoff / exception sah, count valid | CONFIRMED, audit, kepala tahu pending berkurang |
| Plan finalize | Kepala approve | menu approved, request locked sesuai aturan, jumlah allocation masuk akal | FINALIZED, jadwal produksi |
| Batch release | Pengawas QC SAFE | foto/checkpoints memenuhi policy, jumlah actual tercatat | RELEASED, batch boleh loading |
| Trip start | Driver | assigned sendiri, checklist kendaraan lulus, manifest load sah | IN_PROGRESS, tracking authorized |
| Stop arrive | Driver | stop aktif benar, trip berjalan | ARRIVED + notification PIC terkait |
| Receipt submit | PIC | ARRIVED, stop sekolah sendiri, check arrival terisi | RECEIVED / DISCREPANCY / HELD |
| Meal serve | PIC | dua checkpoint kualitas lulus, received valid | DISTRIBUTED / MEAL_COMPLETED |
| Trays ready | PIC | meal selesai, hitung actual wadah | READY_FOR_PICKUP |
| Pickup/return | Driver/Asisten | stop pickup benar, jumlah nonnegatif | PICKED_UP / RETURNED atau discrepancy |

### Prinsip safety & recovery
1. `UNSAFE` adalah sinyal blokir; tidak ada bypass tersembunyi. Perubahan hasil pemeriksaan harus menjadi record baru, bukan overwrite keputusan historis.
2. Kesalahan tap dapat dikoreksi melalui alur koreksi beralasan dan audit, bukan tombol `undo` yang menghapus semua jejak.
3. `TRIP COMPLETED` bukan berarti ompreng kembali bila pickup trip terpisah; status food, trip, dan tray berdiri sendiri.
4. Notifikasi menggunakan event key stabil; pengiriman ulang queue tidak menghasilkan dua peringatan stop yang sama.
5. Sistem menerima **ketidakhadiran GPS** sebagai keterbatasan telemetri, bukan memalsukan keberadaan kurir.

### Kontrak state machine minimal untuk agent
Setiap command harus memiliki source status, target status, actor, precondition, invariant, idempotency strategy, audit, and rollback on failure. Jangan set status dari request payload bebas. Unit test state transition dijalankan untuk setiap edge legal dan ilegal.


## V3 | Status typography and animation tied to truth
Tahapan `UNSAFE`, `ON_HOLD`, `RELEASED`, `STALE`, `RECEIVED_WITH_DISCREPANCY` dibaca dari enum/event di server. Warna tidak boleh menjadi satu-satunya pembeda. Jangan membuat progress penuh dari timer CSS atau simulasi. Animasi status hanya setelah backend commit; jika transisi 409/concurrency gagal, pesan tetap menjelaskan kondisi saat ini.
