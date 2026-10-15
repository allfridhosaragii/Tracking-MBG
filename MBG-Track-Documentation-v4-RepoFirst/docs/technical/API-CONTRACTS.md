# API Contracts — Route Responsibility dan Bentuk Payload

> Ini kontrak rancangan sebelum route Laravel benar-benar dibuat. `web.php` untuk halaman/sesi, JSON subendpoint dapat dipakai dengan session+CSRF atau API auth jika dipilih secara sadar. Jangan jadikan request eksternal tanpa Policy. Respons publik tidak memakai resource internal yang sama.

## Authentication/internal
- `GET /login`
- `POST /login`
- `POST /logout`
- `GET /me`
- `GET /notifications`
- `PATCH /notifications/{id}/read`

## Sekolah/pegawai
- `GET /head/schools`
- `POST /head/schools`
- `PATCH /head/schools/{school}`
- `PATCH /head/schools/{school}/deactivate`
- `GET /head/employees`
- `POST /head/employees`
- `PATCH /head/employees/{user}`

## Menu/gizi
- `GET /nutrition/menus`
- `POST /nutrition/menus`
- `POST /nutrition/menus/{menu}/versions`
- `POST /nutrition/menus/{menu}/activate`
- `POST /nutrition/batches/{batch}/checks`

## Kebutuhan/produksi
- `GET /school/portion-requests`
- `PUT /school/portion-requests/{date}`
- `POST /head/production-plans`
- `POST /head/production-plans/{plan}/finalize`
- `POST /production/batches`
- `POST /production/batches/{batch}/stages`

## Distribusi
- `POST /head/trips`
- `POST /head/trips/{trip}/assign`
- `POST /driver/trips/{trip}/start`
- `POST /driver/trips/{trip}/locations`
- `POST /driver/trip-stops/{stop}/arrive`
- `POST /driver/trip-stops/{stop}/handover-evidence`

## Sekolah/ompreng
- `POST /school/trip-stops/{stop}/receipt`
- `POST /school/service-records/{record}/before-meal-check`
- `POST /school/service-records/{record}/complete-meal`
- `POST /school/tray-returns/{return}/ready`
- `POST /driver/tray-returns/{return}/pickup`
- `POST /production/tray-returns/{return}/receive`

## Incident/evidence
- `POST /incidents`
- `PATCH /incidents/{incident}/status`
- `POST /evidence`
- `GET /evidence/{photo}/view`

## Publik
- `GET /public/schools?search=`
- `GET /public/schools/{slug}/today`
- `GET /public/schools/{slug}/menu`
- `GET /public/schools/{slug}/delivery-status`

## Contoh payload yang dikunci

### Konfirmasi porsi sekolah
`PUT /school/portion-requests/2026-10-07`: `{ "present_count": 340, "requested_portions": 340, "confirm": true, "reason": null }`. `school_id` dari sesi **bukan** body. 422 jika invalid; 409 bila cutoff tanpa alasan; 403 bila tidak PIC.

### Mulai trip
`POST /driver/trips/17/start`: `{ "vehicle_check_ack": true, "idempotency_key": "uuid" }`. Owner trip dari server. 409 jika state/vehicle salah. Respons: trip status+active stop, tanpa membocorkan data sekolah lain ke publik.

### GPS
`POST /driver/trips/17/locations`: `{ "latitude": -7.30, "longitude": 112.73, "accuracy_m": 16, "recorded_at": "2026-10-07T01:15:10Z" }`. Koordinat ini hanya **contoh sintaks**, bukan lokasi lapangan. Validasi batas bumi, umur timestamp, spoof/anomali, status trip aktif; 202 queued/ok, 403 trip bukan miliknya, 409 trip selesai. Rate-limit dan batch jika perlu.

### Receipt
`POST /school/trip-stops/41/receipt`: `{ "received_portions": 338, "damaged_portions": 2, "condition": "ACCEPTED_WITH_ISSUE", "notes": "2 porsi bermasalah", "idempotency_key": "uuid" }`. Sent expected 340 dibaca server; shortage derived = 2, damaged subset dari received jika relevan, bukan 2 tambahan otomatis. 409 jika belum ARRIVED atau sudah submit berbeda; 422 jika angka tidak masuk akal.

### Publik
`GET /public/schools/{slug}/today`: JSON projection berisi school display name, menu publik dan nutrient values **hanya terisi bila terverifikasi**, status sederhana, ETA sebagai perkiraan (atau null), last_updated_at. Jangan mengirim `driver_id`, raw lat/lon, evidence private, user email/phone, school receiver counts terperinci.

## Response standar
`{ "data": ..., "meta": { "timestamp": "ISO-8601", "request_id": "..." } }`. Error: `{ "error": {"code":"STATE_CONFLICT", "message":"...", "details": {} } }`. Kategori: 401 login, 403 tak berhak, 404 resource tak diketahui, 409 transisi konflik, 422 validasi, 429 rate limit, 503 provider tidak tersedia.

## Perintah pengembangan sebelum final
Setiap endpoint punya Form Request, Policy, atomic action, event dan Feature Test. Jangan langsung implementasi endpoint hanya dari contoh; sinkronkan nama model/migration aktual dengan `DATA-DICTIONARY.md`.

## Kontrak endpoint v2 — klasifikasi keamanan dan dokumentasi contoh
| Kelompok | Authentication | Periksa Policy | Mutasi penting | Status HTTP tambahan |
|---|---|---|---|---|
| `head/*` | session user Kepala | SPPG + resource | plan/trip/employee | 409 concurrent plan, 422 count |
| `nutrition/*` | Gizi | SPPG + batch/menu | menu/QC | 409 immutable version / already checked |
| `production/*` | Asisten | SPPG + assigned plan | stages/loading/return | 409 batch HOLD |
| `driver/*` | Kurir | trip owner + stop in trip | GPS/arrival/handover | 409 trip inactive/stop order |
| `school/*` | PIC | school_id from session | request/receipt/meal/trays | 409 expired/held/duplicate |
| `public/*` | none | public flag & slug | **none** | 404 invalid/private school, 429 abuse |

### Consistency contract JSON
Responses selalu punya resource ID, status resmi server, dan `updated_at`/timestamp relevan. Yang dihitung sebagai derived misalnya `shortage = max(0, sum(delivered) - received)` ditandai jelas. Jangan menerima `sppg_id`/`school_id` dari input sebagai bukti authority. Gunakan route binding scoped; mass-assignment raw status dilarang.

### Contoh transaksi berseri
1. `POST /driver/trip-stops/41/handover-evidence` menyimpan foto internal terotorisasi. Beri `idempotency_key` unik.
2. `POST /school/trip-stops/41/receipt` hanya dari PIC stop sekolah 41; server memverifikasi ARRIVED dan mengambil sent quantities dari `delivery_items`.
3. `POST /school/service-records/record/complete-meal` hanya setelah quality gate `BEFORE_CONSUMPTION SAFE` dan validasi jumlah.
4. `POST /school/tray-returns/return/ready` dengan jumlah **wadah**, bukan salinan nilai `served` tanpa inspeksi.
5. Public `GET /public/schools/{slug}/delivery-status` hanya status sederhana + ETA boleh null + last_updated, **tanpa** raw route/GPS/driver data.

### Konflik dan retry
Jika double submit dengan idempotency key yang sama dan payload sama, kembalikan hasil transaksi yang sama (tanpa duplikat evidence/incident); payload berbeda dengan key sama ditolak sesuai desain. Jika conflict version, kembalikan 409 yang bisa ditindaklanjuti. Browser retry saat jaringan pulih tidak boleh menjalankan event fisik dua kali. Queue outbox bila butuh reliability lebih kuat dapat ditambahkan sebagai keputusan teknis, bukan fitur bisnis baru.


## V3 | No fake controls, no false success
Setiap komponen actionable punya endpoint nyata, FormRequest, Policy, precondition, idempotent/409 behavior dan response feedback. UI R-26/R-35 hanya PASS bila click-through benar. Public endpoint memakai projection yang menolak field internal termasuk driver lat/lng, foto privat, nomor kontak. Error 401/403/404/409/422/503 punya teks recovery, bukan spinner tanpa akhir.
