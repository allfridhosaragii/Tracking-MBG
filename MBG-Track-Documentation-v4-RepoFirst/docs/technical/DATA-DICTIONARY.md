# Data Dictionary dan Kontrak Migration MySQL/Laravel

**Status:** skema desain terperinci, belum migration yang dieksekusi. Default tiap tabel: `id` = unsigned BIGINT PK auto-increment, `created_at/updated_at` timestamps kecuali catatan pengecualian, semua FK menggunakan `restrictOnDelete` untuk transaksi inti dan `nullOnDelete` hanya bila sengaja. Gunakan Laravel `foreignId()->constrained()` sesuai kesepakatan. Seluruh angka porsi unsigned integer dan tidak boleh negatif. Simpan waktu UTC di database, tampilkan WIB untuk demo Indonesia.

## Koleksi tabel dan kolom
### `sppgs`
| Kolom | Tipe/constraint desain |
| --- | --- |
| code | varchar(32) unique not null |
| name | varchar(180) not null |
| address | text not null |
| latitude | decimal(10,7) nullable |
| longitude | decimal(10,7) nullable |
| max_daily_portions | unsignedInteger nullable |
| is_active | boolean default true |

### `schools`
| Kolom | Tipe/constraint desain |
| --- | --- |
| sppg_id | FK -> sppgs.id not null |
| school_code | varchar(40) unique not null |
| name | varchar(180) not null |
| education_level | varchar(30) not null |
| address | text not null |
| latitude | decimal(10,7) nullable |
| longitude | decimal(10,7) nullable |
| registered_beneficiaries | unsignedInteger default 0 |
| public_slug | varchar(160) unique not null |
| is_active | boolean default true |

### `users`
| Kolom | Tipe/constraint desain |
| --- | --- |
| sppg_id | FK -> sppgs.id nullable (PIC via school) |
| school_id | FK -> schools.id nullable (PIC only) |
| name | varchar(180) not null |
| email | varchar(190) unique not null |
| phone | varchar(30) nullable |
| password | varchar(255) not null, hash |
| role | varchar(40) not null |
| is_active | boolean default true |
| last_login_at | datetime nullable |
| remember_token | varchar(100) nullable |

### `menus`
| Kolom | Tipe/constraint desain |
| --- | --- |
| sppg_id | FK -> sppgs.id not null |
| menu_code | varchar(40) not null |
| name | varchar(180) not null |
| version | unsignedInteger not null |
| status | varchar(20) not null |
| image_path | varchar(400) nullable |
| effective_from | date nullable |
| created_by | FK -> users.id not null |
| approved_by | FK -> users.id nullable |
| approved_at | datetime nullable |

### `menu_items`
| Kolom | Tipe/constraint desain |
| --- | --- |
| menu_id | FK -> menus.id not null |
| item_name | varchar(180) not null |
| serving_weight_g | decimal(9,2) not null |
| energy_kcal | decimal(10,2) nullable |
| protein_g | decimal(10,2) nullable |
| carbohydrate_g | decimal(10,2) nullable |
| fat_g | decimal(10,2) nullable |
| fiber_g | decimal(10,2) nullable |
| nutrition_source | varchar(255) nullable |
| notes | text nullable |

### `daily_portion_requests`
| Kolom | Tipe/constraint desain |
| --- | --- |
| school_id | FK -> schools.id not null |
| service_date | date not null |
| registered_count | unsignedInteger not null |
| present_count | unsignedInteger nullable |
| requested_portions | unsignedInteger nullable until confirm |
| status | varchar(25) not null |
| confirmed_by | FK -> users.id nullable |
| confirmed_at | datetime nullable |
| locked_at | datetime nullable |
| revision_reason | text nullable |

### `production_plans`
| Kolom | Tipe/constraint desain |
| --- | --- |
| sppg_id | FK -> sppgs.id not null |
| menu_id | FK -> menus.id not null |
| service_date | date not null |
| planned_start_at | datetime nullable |
| status | varchar(35) not null |
| finalized_by | FK -> users.id nullable |
| finalized_at | datetime nullable |
| created_by | FK -> users.id not null |

### `production_allocations`
| Kolom | Tipe/constraint desain |
| --- | --- |
| production_plan_id | FK -> production_plans.id not null |
| portion_request_id | FK -> daily_portion_requests.id not null |
| school_id | FK -> schools.id not null |
| allocated_portions | unsignedInteger not null |

### `food_batches`
| Kolom | Tipe/constraint desain |
| --- | --- |
| production_plan_id | FK -> production_plans.id not null |
| menu_id | FK -> menus.id not null |
| batch_code | varchar(60) unique not null |
| portion_category | varchar(35) nullable |
| planned_portions | unsignedInteger not null |
| actual_portions | unsignedInteger nullable |
| status | varchar(30) not null |
| released_by | FK -> users.id nullable |
| released_at | datetime nullable |

### `production_stage_logs`
| Kolom | Tipe/constraint desain |
| --- | --- |
| food_batch_id | FK -> food_batches.id not null |
| stage | varchar(40) not null |
| started_at | datetime not null |
| completed_at | datetime nullable |
| updated_by | FK -> users.id not null |
| notes | text nullable |

### `quality_checks`
| Kolom | Tipe/constraint desain |
| --- | --- |
| food_batch_id | FK -> food_batches.id not null |
| trip_stop_id | FK -> trip_stops.id nullable |
| check_stage | varchar(45) not null |
| checked_by | FK -> users.id not null |
| appearance_ok | boolean nullable |
| aroma_ok | boolean nullable |
| texture_ok | boolean nullable |
| taste_ok | boolean nullable |
| conclusion | varchar(20) not null |
| notes | text nullable |
| checked_at | datetime not null |

### `vehicles`
| Kolom | Tipe/constraint desain |
| --- | --- |
| sppg_id | FK -> sppgs.id not null |
| plate_number | varchar(24) unique not null |
| vehicle_name | varchar(80) nullable |
| vehicle_type | varchar(40) not null |
| capacity_note | varchar(160) nullable |
| is_active | boolean default true |

### `trips`
| Kolom | Tipe/constraint desain |
| --- | --- |
| sppg_id | FK -> sppgs.id not null |
| driver_id | FK -> users.id not null |
| vehicle_id | FK -> vehicles.id not null |
| trip_code | varchar(60) unique not null |
| trip_type | varchar(20) not null DELIVERY/PICKUP/MIXED |
| service_date | date not null |
| planned_departure_at | datetime nullable |
| started_at | datetime nullable |
| completed_at | datetime nullable |
| vehicle_check_passed | boolean nullable |
| vehicle_checked_at | datetime nullable |
| vehicle_check_notes | text nullable |
| tracking_status | varchar(25) not null |
| tracking_started_at | datetime nullable |
| tracking_ended_at | datetime nullable |
| last_location_at | datetime nullable |
| status | varchar(30) not null |
| created_by | FK -> users.id not null |

### `trip_stops`
| Kolom | Tipe/constraint desain |
| --- | --- |
| trip_id | FK -> trips.id not null |
| school_id | FK -> schools.id not null |
| stop_type | varchar(20) not null DELIVERY/PICKUP |
| sequence_number | unsignedSmallInteger not null |
| planned_arrival_at | datetime nullable |
| estimated_arrival_at | datetime nullable |
| eta_updated_at | datetime nullable |
| remaining_distance_m | unsignedInteger nullable |
| arrived_at | datetime nullable |
| handed_over_at | datetime nullable |
| pre_arrival_notified_at | datetime nullable |
| status | varchar(35) not null |

### `delivery_items`
| Kolom | Tipe/constraint desain |
| --- | --- |
| trip_stop_id | FK -> trip_stops.id not null |
| food_batch_id | FK -> food_batches.id not null |
| production_allocation_id | FK -> production_allocations.id not null |
| planned_portions | unsignedInteger not null |
| loaded_portions | unsignedInteger nullable |
| delivered_portions | unsignedInteger nullable |

### `gps_locations`
| Kolom | Tipe/constraint desain |
| --- | --- |
| trip_id | FK -> trips.id not null |
| driver_id | FK -> users.id not null |
| latitude | decimal(10,7) not null |
| longitude | decimal(10,7) not null |
| accuracy_m | decimal(8,2) nullable |
| speed_mps | decimal(8,2) nullable |
| heading_deg | decimal(6,2) nullable |
| recorded_at | datetime not null |

### `school_service_records`
| Kolom | Tipe/constraint desain |
| --- | --- |
| trip_stop_id | FK -> trip_stops.id unique not null |
| received_by | FK -> users.id nullable |
| received_at | datetime nullable |
| expected_portions | unsignedInteger not null |
| received_portions | unsignedInteger nullable |
| damaged_portions | unsignedInteger default 0 |
| shortage_portions | unsignedInteger default 0 |
| distribution_started_at | datetime nullable |
| served_portions | unsignedInteger nullable |
| leftover_portions | unsignedInteger nullable |
| meal_completed_at | datetime nullable |
| status | varchar(35) not null |
| notes | text nullable |

### `tray_returns`
| Kolom | Tipe/constraint desain |
| --- | --- |
| school_service_record_id | FK -> school_service_records.id unique not null |
| pickup_trip_stop_id | FK -> trip_stops.id nullable |
| expected_trays | unsignedInteger not null |
| ready_count | unsignedInteger nullable |
| ready_at | datetime nullable |
| picked_up_count | unsignedInteger nullable |
| picked_up_at | datetime nullable |
| returned_count | unsignedInteger nullable |
| returned_at | datetime nullable |
| status | varchar(35) not null |
| discrepancy_reason | text nullable |

### `evidence_photos`
| Kolom | Tipe/constraint desain |
| --- | --- |
| evidenceable_type | varchar(100) not null morph type |
| evidenceable_id | unsignedBigInteger not null |
| evidence_type | varchar(50) not null |
| uploaded_by | FK -> users.id not null |
| file_path | varchar(500) not null private disk |
| mime_type | varchar(70) not null |
| file_size | unsignedInteger not null |
| captured_at | datetime nullable; untrusted client |
| latitude | decimal(10,7) nullable |
| longitude | decimal(10,7) nullable |
| notes | text nullable |

### `incidents`
| Kolom | Tipe/constraint desain |
| --- | --- |
| sppg_id | FK -> sppgs.id not null |
| school_id | FK -> schools.id nullable |
| subject_type | varchar(100) not null morph |
| subject_id | unsignedBigInteger not null |
| reported_by | FK -> users.id not null |
| category | varchar(40) not null |
| severity | varchar(20) not null |
| status | varchar(25) not null |
| description | text not null |
| resolved_by | FK -> users.id nullable |
| resolved_at | datetime nullable |
| resolution_notes | text nullable |

### `activity_logs`
| Kolom | Tipe/constraint desain |
| --- | --- |
| actor_id | FK -> users.id nullable for system |
| auditable_type | varchar(100) not null |
| auditable_id | unsignedBigInteger not null |
| event | varchar(60) not null |
| old_values | json nullable |
| new_values | json nullable |
| metadata | json nullable |
| ip_address | varchar(45) nullable |
| user_agent | varchar(500) nullable |

### `notifications`
| Kolom | Tipe/constraint desain |
| --- | --- |
| id | uuid PK; NOT bigint |
| type | varchar(255) not null |
| notifiable_type | varchar(255) not null |
| notifiable_id | unsignedBigInteger not null |
| data | json not null |
| read_at | datetime nullable |

## Unique/index yang wajib
- `schools.school_code`, `schools.public_slug`, `users.email`, `vehicles.plate_number`, `trips.trip_code`, `food_batches.batch_code` unique.
- `menus (sppg_id,menu_code,version)` unique.
- `daily_portion_requests (school_id,service_date)` unique.
- `production_allocations (production_plan_id,school_id)` unique **hanya bila satu menu/permintaan per sekolah di satu plan**.
- `trip_stops (trip_id,sequence_number)` unique.
- `delivery_items (trip_stop_id,food_batch_id,production_allocation_id)` unique untuk idempotency pemuatan item.
- `school_service_records.trip_stop_id` dan `tray_returns.school_service_record_id` unique.
- `gps_locations (trip_id,recorded_at)` index; `trip_stops (trip_id,status)` index; `incidents (sppg_id,status,created_at)` index; `activity_logs (auditable_type,auditable_id,created_at)` index; foto `(evidenceable_type,evidenceable_id)` index.

## Invariant wajib dan pengujiannya
1. PIC harus memiliki `school_id`, internal SPPG harus memiliki `sppg_id`; jangan `school_id` + `sppg_id` kombinasi liar yang saling berbeda.
2. School/plan/trip/vehicle/driver harus semuanya tenant SPPG sama; **FK sendiri belum menjamin kesesuaian tenant** → cek service+Policy+test.
3. `requested_portions <= registered_count` sebagai peringatan/aturan normal; pengecualian dengan alasan dan approval, bukan mutlak melarang kebutuhan sah.
4. `loaded_portions <= batch.actual_portions` secara agregat lintas `delivery_items`; gunakan DB transaction dan row lock saat loading.
5. `received + shortage` harus dijelaskan terhadap sent/delivered; `damaged` bagian dari yang diterima dan **tidak** ditambahkan dua kali ke total selisih. Simpan terminologi di UI.
6. `served_portions + leftover_portions` tidak boleh lebih dari porsi yang layak dibagikan; uji quality gate wajib.
7. `gps_locations.driver_id` cocok dengan assigned trip driver dan status tracking aktif; reject koordinat dengan timestamp tidak masuk akal, accuracy lemah ditandai bukan dibuang diam-diam.
8. Foto private disk: mime image/jpeg/png/webp; ukuran maksimum **desain 5 MB**; stripping metadata EXIF lokasi jika tidak diperlukan; metadata unggah server tidak sama dengan timestamp kamera yang terpercaya.
9. `notifications.id` UUID bawaan Laravel; jangan buat bigint kedua.
10. Audit transaction append-only, jangan simpan password/token sensitif pada `old_values/new_values`.

## Uji migrasi sebelum coding fitur
`php artisan migrate:fresh --seed` **hanya database lokal/dev**, jangan di production; referential tests, unique tests, foreign key tests, nullable validation, 403 multi-tenancy dan migrasi rollback di lingkungan testing. Tidak ada klaim sudah dijalankan dalam dokumen ini.

## Addendum v2 — kontrak lintas kolom dan keputusan yang harus diuji

### A. Perhitungan jumlah: lima tahap berbeda
1. `request.requested_portions`: kebutuhan yang diakui PIC.
2. `allocation.allocated_portions`: komitmen rencana SPPG untuk request+menu tertentu.
3. `batch.actual_portions`: unit porsi secara fisik dihasilkan.
4. `delivery_item.loaded_portions` dan `delivered_portions`: satuan porsi dialokasikan ke satu stop.
5. `service.received_portions`, `damaged_portions`, `served_portions`, `leftover_portions`: rekonsiliasi lapangan. `damaged` adalah *subset* dari received menurut aturan MVP; `usable = max(0, received - damaged)`. Jangan menyimpan `shortage` dari sisi klien sebagai sumber tunggal; backend menghitung `max(0, sum(delivered) - received)` dan mencatat surplus jika ada.

**Penting:** `expected_trays`, `ready_count`, `picked_up_count`, `returned_count` **unit ompreng**, bukan porsi. Hubungan numeriknya harus diuji terhadap kebijakan penggunaan wadah setempat; untuk demo jumlah bisa sama tetapi jangan paksa secara schema. Catat selisih pada incident.

### B. Constraint yang tidak cukup hanya satu FK
| Invariant | Enforcement | Tes |
|---|---|---|
| Trip driver+vehicle SPPG sama | service & Policy pada assign + DB transaction | assign cross-SPPG harus 403/422 |
| Stops sekolah bagian SPPG | service pada create/modify trip | school lain ditolak |
| Item batch berasal plan/menu yang sah | service pada build manifest | batch HOLD/different plan ditolak |
| Jumlah loaded <= actual batch total | lock row batch & sum delivery_items | dua trip parallel tidak overbook |
| Receipt hanya DELIVERY stop sendiri | Policy dan guard stop_type | pickup stop cannot receive food |
| Quality stage/check keys valid | FormRequest dan service | ON_ARRIVAL but no stop rejected |
| Mutasi foto polymorphic aman | `morphMap`, owner/tenant policy | path/file ID lintas sekolah 403 |
| Public data projection | DTO/resource whitelist | raw coords/phone never serialized |

### C. Nullability & nilai nol
- `nullable` berarti **belum diketahui/tidak terjadi**; `0` berarti hasil pemeriksaan menunjukkan nol. Jangan normalisasi null ke 0 di UI agar tidak tampak bahwa sekolah sudah diperiksa.
- `received_at`, `started_at`, `released_at`, `completed_at` hanya muncul dari transisi valid dan memakai waktu server sebagai fakta; nilai timestamp kamera hanya metadata tidak tepercaya.
- Pengisian `lat/lng` ditolak bila di luar range bumi; akurasi buruk diberi status dan mungkin ditolak untuk ETA, namun aktivitas fisik tetap bisa berlanjut dengan exception.

### D. Constraints MySQL/Laravel yang belum seluruhnya direpresentasikan migration
- Pertimbangkan unique key `menus(sppg_id,menu_code,version)`; `trip_stops(trip_id,sequence_number)`; `delivery_items(trip_stop_id,food_batch_id,production_allocation_id)`. Uji dengan database MySQL engine aktual, bukan SQLite saja.
- `quality_checks` perlu check stage: `BEFORE_DISPATCH` menaut batch; `ON_ARRIVAL/BEFORE_CONSUMPTION` perlu stop valid. Bila schema saat ini nullable, enforce di service serta test.
- `production_plans` tidak otomatis unique per sekolah+tanggal; adanya beberapa menu/siklus perlu dialokasikan tanpa menghitung dobel satu permintaan.
- `activity_logs` dan `evidence_photos` polymorphic membatasi integritas FK; larang class name arbitrary, tetapkan `Relation::enforceMorphMap` bila digunakan, dan batasi orphan cleanup tanpa menghapus audit.
- `notifications.id` UUID; migration urutan dependensi: sppgs→schools→users→menus→menu_items→requests→plans→allocations→batches→stage logs→vehicles→trips→stops→delivery_items→GPS→QC→service records→trays→evidence/incidents/activity logs/notifications. Urutan QC setelah trip_stops menghindari FK gagal jika didefinisikan dalam satu migration.

### E. Migrasi dan rollback
Migration adalah perubahan versioned. Buat schema dari file ini, lalu gunakan `php artisan migrate` untuk pengembangan, `php artisan migrate:fresh --seed` **hanya di lingkungan lokal/test**; jangan `migrate:fresh` di basis data produksi. Untuk perubahan besar, buat migration baru dan data transform/backfill, uji down migration di DB uji dan peer review.

### F. Catatan desain yang masih perlu persetujuan implementasi
Retensi GPS dan foto, ketentuan cutoff per sekolah, jumlah pickup parsial lebih dari sekali, lokasi file storage/cloud, foto bukti sebagai hard gate saat tidak ada jaringan, dan formula ETA fallback adalah keputusan produk/operasional; jangan klaim aturan BGN. Jika harus berubah, update PBI/page/API/ERD sekaligus.


## V3 | Claim-to-column checks
Sebelum merender angka: tulis `[UI label] -> [table.field / computed query] -> [unit] -> [scope] -> [freshness]`; test dengan fixture 340 planned vs 338 received. Nilai QC dan geolocation tidak boleh disulap menjadi label SAFE/LIVE bila input manusia/akurasi belum memadai. Aturan database tetap diuji nyata di MySQL; format status UI tidak boleh menciptakan field tanpa migration yang disetujui.
