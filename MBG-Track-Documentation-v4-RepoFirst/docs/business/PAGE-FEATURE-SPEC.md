# Kontrak Fitur Semua Halaman

**Halaman ≠ PBI.** Sistem memetakan 53 area fungsional hasil brainstorm ke 34 **spesifikasi halaman unik**. Rincian ada di `../pages/PAGE-REGISTRY.md` beserta link setiap halaman. Shared views tidak berarti izin semua role sama.

## Kontrak universal halaman
1. **Context**: identitas pengguna dan scope data (`sppg_id`/`school_id`/`trip_id`) dari server.
2. **Summary**: read-model yang relevan saja, tidak memuat kolom privat tanpa kebutuhan.
3. **Komponen**: field, filter, tabel, timeline, form, tautan silang, map hanya pada halaman terkait.
4. **Action**: setiap aksi memakai service domain/policy, bukan mutasi status frontend lokal saja.
5. **Validation**: field wajib, jenis input, tanggal, cakupan jumlah, duplicate submit.
6. **State**: loading, empty, unavailable, stale data, permission denied, pending confirmation, success.
7. **Event**: perubahan status memicu dashboard/notification yang dipilih.
8. **Audit**: actor, timestamp, before/after dan reason saat koreksi.
9. **Proof**: checklist acceptance dan test link PBI.

## Garis besar halaman role
- Shared internal: Login, Notification Center, My Account (3).
- Kepala SPPG: Dashboard, Sekolah, Pegawai, Rencana Produksi, Distribution Control, Laporan (6).
- Gizi: Gizi Hari Ini, Menu Management, Quality Control (3).
- Asisten: Work Today, Production Execution, Loading Check (3).
- Driver: My Trips, Trip Preparation, Active Trip, Handover, Tray Pickup (5).
- PIC: School Today, Portion Confirmation, Delivery Tracking, Food Receipt, Meal Distribution, Trays Ready (6).
- Publik: School Search, School MBG Today, Menu Nutrition, Delivery Status (4).
- Cross-role shared: Incident, Batch, Trip, Audit (4).

## Kebijakan page gabungan
Satu dokumen dapat mencakup daftar + detail + formulir bila itu satu workflow yang sama. Jika nanti dipisah menjadi file Blade berbeda, dokumentasi sumber tetap satu dan subroute didaftar dalam halaman tersebut. Hanya tambahkan `.md` baru jika muncul bounded business capability berbeda, bukan karena ada modal/tabs.

## Feature traceability
Setiap page wajib menyebut PBI terkait, aktor, model/tabel, event lintas role, dan 3+ acceptance criteria. `PAGE-REGISTRY.md` memverifikasi tidak ada PBI/role tanpa page.

## Functional page information architecture v2
Lihat `docs/pages/PAGE-REGISTRY.md`: 53 **area konseptual** terdahulu direduksi menjadi **34 file halaman fungsional unik** karena beberapa subhalaman adalah drilldown/komponen bersama. Angka 34 adalah **jumlah dokumen page spec**, bukan perintah membuat tepat 34 Blade/route; implementasi nyata dapat memiliki form terpisah tetapi harus menjaga cakupan.

### Empat kategori pekerjaan layar
1. **Overview/queue:** dashboard Kepala, dashboard Gizi, pekerjaan Asisten, hari ini PIC. Harus menunjukkan anomali dan aksi berikutnya, bukan empat chart stok.
2. **Transaction workflow:** request porsi, production stage, QC, loading, trip, receipt, meal, tray. Semua aksi menghasilkan transisi server yang diaudit.
3. **Record/traceability:** batch/trip/incident/audit detail. Banyak role boleh melihat proyeksi berbeda melalui Policy, bukan menduplikasi halaman.
4. **Public projection:** sekolah, menu, status. Sumber sama dengan transaksi, tetapi serialisasi terpisah demi privasi.

### Interaksi global wajib
Loading, kosong, offline, 401/403/404, validation 422, conflict 409, stored success, retry aman, timestamp data; task prioritas mobile; tangani session expired dan browser Back; jangan pakai loading yang berpura-pura berhasil.

### Visual handoff
Setiap page spec v2 menyimpan section 14 mobile order, desktop composition, motion, role risks dan tests. Tim frontend wajib membaca hanya page yang dikerjakan serta tujuh dokumen `docs/design/`; tidak perlu membuka semua 34 page tiap kali.


## V3 | Area-to-page design decomposition
53 area fungsional awal direpresentasikan 34 spec. Tiap spec kini memiliki Section 15 yang menjelaskan komposisi spesifik, focal point, dials, copy CTA, risk data dan proof. Jangan memaksakan template seragam pada shared page. Jika Laravel memisahkan create/edit/show menjadi route berbeda, semuanya tetap tunduk pada page contract dan Delivery Gate per state.
