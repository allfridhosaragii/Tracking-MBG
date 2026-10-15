# Arsitektur Laravel — Monolith Modular, Bukan Microservice

## Keputusan
Laravel (versi yang cocok dengan `composer.json` aktual; dokumentasi rujukan 13.x bila terinstal), MySQL, Blade + JavaScript komponen minimum, Vite/Tailwind hanya bila sudah ditetapkan, queue database, cache database/file untuk MVP, notifications database, broadcasting opsional. Tidak perlu Next.js/Express tambahan.

## Lapisan
```text
Web browser / HP (HTTPS)
  -> Laravel web routes + session/csrf
     -> Controllers (orchestrasi request)
     -> FormRequest (validasi)
     -> Policies (scope/action)
     -> Services/Actions (state machine, DB transaction, side effects)
     -> Eloquent Models/Relations
     -> MySQL
  -> Private Storage (evidence)
  -> Jobs + Scheduler (notification, GPS cleanup, stale check)
  -> JSON endpoints untuk status / GPS / ETA
```

## Struktur folder rekomendasi kode (belum dibuat)
```text
app/
  Enums/ {UserRole,ProductionStatus,BatchStatus,TripStatus,IncidentStatus}.php
  Models/ {School,Menu,FoodBatch,Trip,TripStop,SchoolServiceRecord,...}.php
  Http/Controllers/{Head,Nutrition,Production,Driver,School,Public}/
  Http/Requests/{CreateSchoolRequest,ConfirmPortionsRequest,...}/
  Policies/ {SchoolPolicy,TripPolicy,SchoolServicePolicy,...}.php
  Actions/ {FinalizePlan,ReleaseBatch,StartTrip,ConfirmReceipt,PickupTrays,...}.php
  Services/ {GpsIngestionService,EtaService,NotificationDedupService}.php
  Notifications/ {TripAssigned,PreArrival,IncidentOpened,...}.php
  Jobs/ {RecalculateEta,ExpireGps,PruneGps}.php
  Events/ {TripStarted,StopArrived,ReceiptConfirmed,...}.php
resources/views/ {shared,head,nutrition,production,driver,school,public}/
tests/Feature/{Authorization,Production,Distribution,Public}/
```

## Konsistensi transaksi
- Semua mutasi kuantitas memanggil satu domain action, misalnya `ConfirmReceipt` mem-lock stop dan service, validasi role, update jumlah, buat incident bila selisih, activity log, dispatch job notification **after commit**.
- Double-submit: gunakan unique constraints/idempotency key per event sensitif, kembalikan record yang sudah ada untuk request sama; bukan duplicate side effects.
- Endpoint read-side pakai eager loading dan scoped query agar dashboard cepat dan aman.

## Rencana GPS tanpa janji palsu
HP driver Browser Geolocation (`watchPosition`) → endpoint terautentikasi → validasi trip+accuracy+timestamp → insert terkontrol → cache last-known → internal map polling/safe broadcasting. Sifatnya near real-time selama browser aktif; aplikasi tidak mengklaim background GPS 24/7.

## Keamanan
Policies + middleware + signed/private evidence download; password Laravel hash; CSRF session; throttle login/GPS; anti IDOR; logs redact data personal; deployment HTTPS. Raw GPS tidak dijadikan field JSON publik.

## Referensi resmi
- [Laravel Authorization](https://laravel.com/framework/docs/13.x/authorization)
- [Laravel Notifications](https://laravel.com/framework/docs/13.x/notifications)
- [Laravel Database](https://laravel.com/framework/docs/13.x/database)
- [Laravel Filesystem](https://laravel.com/framework/docs/13.x/filesystem)
- [Laravel Validation](https://laravel.com/framework/docs/13.x/validation)

## Implementasi Laravel v2 — Struktur modular monolith
Contoh susunan, bukan generator mandatory:
```text
app/
  Actions/Portions/ConfirmPortionRequest.php
  Actions/Production/FinalizeProductionPlan.php
  Actions/Production/ReleaseFoodBatch.php
  Actions/Trips/StartTrip.php
  Actions/Trips/ArriveAtStop.php
  Actions/School/ConfirmReceipt.php
  Actions/School/CompleteMeal.php
  Actions/Trays/RecordTrayPickup.php
  Policies/{School,Trip,FoodBatch,Incident}Policy.php
  Http/{Controllers,Requests,Resources}/...
  Enums/{TripStatus,StopStatus,BatchStatus,IncidentStatus}.php
  Events/...  Notifications/...  Models/...
resources/views/{components,head,nutrition,production,driver,school,public}/...
```

### Request flow yang direkomendasikan
`Route middleware auth/verified?` → `Controller` (route binding + `authorize`) → `FormRequest` → `Action/Service` (transaction + row lock + invariant) → `model` update → `activity_logs` → `event/notification` after commit → response redirect/JSON. Jangan menaruh seluruh logika status di Blade/Alpine.

### Frontend stack yang paling realistis
Blade + Tailwind + Alpine atau Livewire **pilih satu pola dominan sesuai tim**. Untuk GPS/MapLibre, tulis modul JS terisolasi yang dapat di-test; Livewire mungkin sesuai form CRUD, tetapi jangan menaruh geolocation loop ke component re-render tanpa cleanup. Semua CSS berdasarkan semantic design tokens `docs/design/UI-DESIGN-SYSTEM.md`.

### Authorization dan scope
- Query internal dibatasi oleh SPPG/school/driver assignment melalui scoping teruji dan Policy resource; hidden tombol **bukan** authorization.
- Public menggunakan Query Service + Resource DTO whitelist per school slug/tanggal; jangan render shared internal model JSON.
- Handover shared evidence memiliki pemilik dan viewer berbeda; authorize read & upload secara terpisah.

### Operational correctness
- DB transactions untuk `finalize`, `release`, `load`, `handover`, `receipt`, `meal complete`, dan `tray return`.
- Guna `afterCommit()` untuk dispatch notification; idempotency key atau unique event key untuk handover/pre-arrival.
- Queue retry tidak boleh mengulang state transition, scheduler mengecek stale GPS dan overdue status dengan rate limit.
- Browser geolocation hanya aktif pada trip relevant foreground dan harus di-cleanup saat route leave/logout/complete.

### Pengujian
`php artisan test` meliputi HTTP Feature Policy, DB FK, state machine, transaction rollback, public projection, double submit; browser testing untuk mobile view, focus, upload, map fallback, dan reduced motion. **Tidak ada klaim passed** sebelum benar-benar menjalankan command di repository.


## V3 | Behavior-first frontend architecture
Blade components berbagi token & control behavior, namun role views punya susunan konten berbeda. Controllers tidak mengoperasikan fake frontend transitions; service/transaction commit lebih dulu, baru event/notification. R-33 melarang skrip ad-hoc mengganti Blade/CSS dengan regex untuk membuat fitur; edit source secara eksplisit, diff review. Design Read dan Delivery Gate harus menjadi acceptance sebelum status DONE.
