# Sprint Plan dan Pembagian PBI

> Contoh 4 anggota. Nama/NIM dan kalender resmi kelas belum diberikan; gunakan placeholder sampai tim mengisi. Pembagian bergantung kompleksitas, bukan sekadar angka PBI.

## Baseline tim
| Anggota | PBI utama | Tanggung jawab lintas-modul |
|---|---|---|
| A — [Nama/NIM] | 01,02,03 | School/PIC, users, menu/gizi; review security |
| B — [Nama/NIM] | 04,05,06 | Permintaan, alokasi, produksi; fixture numerik |
| C — [Nama/NIM] | 07,08,09 | Batch, trip, GPS/ETA & ompreng; integration test |
| D — [Nama/NIM] | 10,11,12 | Receipt, incident, dashboard/public; UAT |

## Sprint usulan
- Sprint 0 (fondasi teknis): setup Laravel/MySQL, migration, role login, seed mock, testing harness; bukan PBI login yang dihitung.
- Sprint 1: PBI 01–03; gate: data sekolah dan menu valid, 403 lintas tenant.
- Sprint 2: PBI 04–07; gate: validasi target/aktual, batch HOLD/RELEASED.
- Sprint 3: PBI 08–10; gate: driver tiga stop, PIC receipt, evidence, fallback maps.
- Sprint 4: PBI 11–12; gate: incident dan monitoring, privacy publik, E2E, laporan dosen.

Setiap sprint boleh sekitar 2–3 minggu sesuai arahan LMS; durasi proyek asli bergantung jadwal kelas/anggota. Jangan mengklaim seluruh PBI selesai dalam enam hari kalender masing-masing bila belum dievaluasi kapasitas.

## Definition of Ready
Tujuan bisnis, aktor, input/output, aturan status, data dictionary, page spec, acceptance dan test ada; dependensi tersedia.

## Definition of Done
Kode dipush, migration aman, Form Request, Policy, service, feature tests pass, tidak bocor data, update docs/handoff, demo 1 use-case lulus. Ketua tim menyetujui merge.

## Pengendalian scope
GPS real-time, maps dan publik harus tetap fallback/offline-friendly; jangan menambah sistem chat/pengadaan ke sprint tengah hanya karena ide baru.

## Strategi pengerjaan realistis, hindari 34 layar sekaligus
- **Fondasi:** DB migration, seed, auth+policies, shared AppShell, token UI; login/logout bukan PBI.
- **Sprint 1:** PBI 01/02/03 dan halaman master K02/K03/G02, kedisiplinan version menu.
- **Sprint 2:** PBI 04/05/06/07, P02/K04/A01/A02/G03/C02; demonstrasikan satu plan dan QC.
- **Sprint 3:** PBI 08/09, K05/D01-D05/P03/C03, GPS foreground, ETA dedupe, ompreng.
- **Sprint 4:** PBI 10/11/12, P04/P05/P06/C01/K01/U01-U04 + laporan/audit.
- **Stabilisasi:** uji multi-school/tenant, P0 privacy/food-safety, mobile visual quality; deploy/demo dan proposal.

### Kontrak pekerjaan tiap anggota
Satu ticket mempunyai satu tujuan bisnis/PBI, pembaca schema/API yang bertanggung jawab, author, reviewer, page ID, expected tests dan screenshot. Jangan memulai PBI 09 sebelum batch + manifest memiliki test; dashboard awal boleh memakai seeded demo berlabel agar visual system diuji, tetapi jangan deklarasi live.

### Penyesuaian estimasi
Instruksi dosen menyebut PBI menengah kira-kira enam hari part-time, tetapi beberapa PBI kompleks (GPS+ompreng) bisa lebih berat. Pecah menjadi subtask implementasi tanpa mengubah total 12 PBI, dan dokumentasikan risiko/mitigasi dalam sprint planning.


## V3 | Gate dalam setiap sprint
Untuk tiap PBI yang diimplementasikan: audit current code → Design Read Section15 per page → implementation → Laravel regression tests → browser audit → Delivery Gate. Sprint 0 & PBI-01 dilaporkan selesai oleh Gemini; lakukan After audit sebelum rewrite, PBI-02 selanjutnya During mode. Jangan mengubah jadwal/estimasi kuliah dengan alasan tambahan dekorasi.
