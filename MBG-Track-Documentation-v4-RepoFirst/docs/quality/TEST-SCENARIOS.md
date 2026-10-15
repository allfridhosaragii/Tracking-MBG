# Test Plan — Laravel Unit, Feature, Integration dan UAT

Belum ada hasil eksekusi; semua test status awal **PLANNED**. Gunakan Pest atau PHPUnit sesuai dependensi repo, `RefreshDatabase` pada database TEST saja; `migrate:fresh` jangan pernah dijalankan pada produksi.

| ID | Kasus | Langkah ringkas | Expected |
| --- | --- | --- | --- |
| TC01 | Happy path 3 sekolah | PIC requests 340/276/410 → plan → released batches → 3 stops → receipt → return | Semua status per sekolah; total 1026; audit |
| TC02 | Isolation PIC | PIC SD A akses ID service SD B | 403/404; tanpa data B |
| TC03 | Isolation kepala | Kepala SPPG A akses trip B | 403/404 |
| TC04 | Public projection | GET public today/menu/status | Tidak ada raw GPS, akun, bukti internal, PII |
| TC05 | Versi menu | Ubah gizi menu yang sudah dipakai | Buat versi baru; histori lama tetap |
| TC06 | Duplikat porsi | PIC input tanggal sama dua kali | Unique/idempotent; alasan revisi |
| TC07 | QC gagal | QC batch UNSAFE | ON_HOLD, tak bisa loaded |
| TC08 | Wrong status | Coba completed sebelum arrived | 409, tidak berubah |
| TC09 | Multi-batch stop | Stop SD A memakai 2 batch | Trace dua batch, jumlah tepat |
| TC10 | Konflik driver | Driver ditugaskan dua trip overlap | Penugasan ditolak |
| TC11 | GPS deny | Driver menolak permission | Trip fisik tetap dapat dilaporkan, tracking disabled |
| TC12 | GPS stale | Tidak ada update > threshold | Waktu terakhir jelas, tidak live |
| TC13 | GPS spoof ownership | Driver 1 submit koordinat trip Driver 2 | 403 |
| TC14 | ETA prearrival | ETA <=10 dua kali | Notif PIC tepat satu |
| TC15 | API maps down | Provider routing 503 | ETA unavailable, status/manual tetap |
| TC16 | Arrival proof | Driver foto lalu PIC belum konfirmasi | Tetap menunggu PIC |
| TC17 | Shortage | 340 dikirim, 338 diterima | Shortage=2 + incident atomik |
| TC18 | Before-meal QC fail | QC kedua gagal | Tidak bisa served; alert gizi |
| TC19 | Wadah kurang | 340 pickup, 338 return | Incident tray discrepancy |
| TC20 | Evidence security | PIC SD B download bukti SD A | 403; file private |
| TC21 | Foto upload mati | Upload sebelum checkpoint gagal | Pendapatan status pending; retry tanpa duplikat |
| TC22 | Double submit | Submit receipt sama 2× | Satu service record + satu incident |
| TC23 | Concurrent update | 2 user edit record status lama | 409/conflict atau serial transaction |
| TC24 | Audit privacy | Edit role/receipt | Old/new ada, password dan token tidak terekam |
| TC25 | Notification scope | Driver menuju SD A | Hanya PIC SD A pra-tiba, bukan semua role |
| TC26 | Offline resume | Location buffered lama replay | Tidak digambar sebagai live; timestamp asli |
| TC27 | Deleted school with history | Nonaktifkan sekolah terlayani | Histori tidak hilang |
| TC28 | Public unknown school | Slug tidak ada | 404/empty aman, tidak stack trace |

## Testing pyramid
- Unit: status transition map, gizi agregat desimal, ETA trigger dedup, quantity invariants.
- Feature: auth 401/403, scoped route binding, FormRequest, controller response, private evidence.
- Integration: DB transactions receipt+incident+audit+notification after commit, queue retry, GPS/mocks routing API.
- E2E UAT: empat akun internal + PIC + viewer publik, alur 3 sekolah, pengembalian ompreng.

## Fixture demo (SEMUA SIMULASI)
SPPG Demo, SD A 340 porsi, SD B 276, SMP C 410; 1 batch atau beberapa batch, dua kendaraan opsional, dua driver, kepala, pengawas gizi, asisten, tiga PIC. Nilai gizi/foto tidak diset sebagai klaim gizi nyata. Seed harus memakai domain `.test` dan identitas fiktif, bukan data nyata siswa/guru.

## Bukti selesai
Catat per test tanggal, branch/commit, command, exit code, screenshot/log redacted, bug ID. Tidak boleh menandai PASS hanya dari penilaian model AI tanpa runtime.

## E2E suite minimum v2 — enam aktor dan publik
**Seed reproducible:** `SPPG A`, tiga sekolah `340/276/410` kebutuhan; 1 menu versi 1; 2 batch jika perlu; satu trip 3 stop delivery dan pickup terpisah; lima role internal; publik anonymous; satu sumber data yang memunculkan mismatch/unsafe/stale. Jangan memakai data anak atau GPS asli.

| Test ID | Alur | Assertion utama |
|---|---|---|
| E2E-01 | PIC A request → Kepala finalize → Asisten produksi | alokasi total 1.026, no manual duplicate |
| E2E-02 | Gizi QC SAFE → Asisten loading | released batch only, photo evidence saved private |
| E2E-03 | Gizi QC UNSAFE → driver try load | rejected 409/422, incident created, no loaded row |
| E2E-04 | driver 3 stops → PIC A receipt 338/340, damaged 2 | sent/received/damaged/shortage correct and audited |
| E2E-05 | PIC second QC unsafe → click serve | denied and service remains HELD |
| E2E-06 | PIC ready 340 ompreng → pickup 340 → returned 338 | count unit trays, discrepancy 2, incident |
| E2E-07 | GPS fresh → pre-arrival threshold twice | exactly one notification to current PIC stop |
| E2E-08 | GPS stale → public/PIC/Kepala query | freshness rendered, no fake ETA movement |
| E2E-09 | PIC school A → school B endpoint | 403/404 and no data exposure |
| E2E-10 | driver A → driver B trip endpoint | 403/404; no GPS exposure |
| E2E-11 | anonymous public school page | menu/status only, no internal incident/photo/coords |
| E2E-12 | offline retry after user submit | one transaction, no duplicate job/evidence |

### UI suite yang juga wajib
- Screenshot D03, P04, A02, G03, K01, U02 pada 390px dan 1440px; tambah semua page saat implementasi.
- 320px/no overflow, keyboard focus, zoom 200%, reduced-motion, upload flow, empty/error, denied GPS, slow connections.
- E2E simulation time `WIB`; backend stores UTC and labels estimasi.

### Test report format
`ID`, commit hash, model/author, OS/browser + viewport, command yang benar-benar dijalankan, exit code, hasil, screenshot path, regresi, unresolved. `NOT RUN` **bukan** `PASS`.


## V3 | UI evidence is not PHPUnit count
Regresi Laravel penting tetapi belum cukup untuk menyatakan premium/mobile. Tambahkan browser click-through seluruh controls, screenshot 320/390/1440 normal+error, reduced-motion, tab/keyboard, contrast, zoom 200%, network offline dan stale. Jangan mengklaim R-35 PASS bila belum membuka aplikasi berjalan. Gabungkan acceptance matrix `docs/quality/ANTISLOP-ACCEPTANCE-MATRIX.md`.
