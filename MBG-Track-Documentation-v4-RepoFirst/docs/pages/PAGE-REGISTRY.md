# PAGE REGISTRY — Semua 34 Spesifikasi Halaman Unik

Sebelumnya teridentifikasi 53 **area fungsional** ketika setiap role dijabarkan satu demi satu. Setelah menghilangkan duplikasi (daftar+detail+form dalam satu workflow, shared cross-role), disepakati **34 file halaman unik**. Tidak ada 53 file Blade yang diwajibkan, dan halaman fisik boleh lebih/kurang sesuai implementasi sepanjang fungsi tercakup.

## Daftar lengkap
| ID | Halaman spec | Role | PBI terkait | Route usulan |
| --- | --- | --- | --- | --- |
| S01 | [Login internal](./shared/LOGIN.md) | Seluruh role login kecuali publik | prasyarat di luar PBI | `/login` |
| S02 | [Pusat notifikasi](./shared/NOTIFICATIONS.md) | Semua role login | PBI-02,PBI-09,PBI-10,PBI-11,PBI-12 | `/notifications` |
| S03 | [Akun saya](./shared/MY-ACCOUNT.md) | Semua internal | PBI-02,prasyarat | `/account` |
| K01 | [Dashboard operasional kepala](./kepala-sppg/DASHBOARD.md) | KEPALA_SPPG | PBI-04,PBI-05,PBI-06,PBI-08,PBI-09,PBI-10,PBI-11,PBI-12 | `/head/dashboard` |
| K02 | [Sekolah dan penanggung jawab](./kepala-sppg/SCHOOL-MANAGEMENT.md) | KEPALA_SPPG | PBI-01 | `/head/schools` |
| K03 | [Akun pegawai dan pembagian role](./kepala-sppg/EMPLOYEE-MANAGEMENT.md) | KEPALA_SPPG | PBI-02 | `/head/employees` |
| K04 | [Kebutuhan, menu, perencanaan dan batch](./kepala-sppg/PRODUCTION-PLANNING.md) | KEPALA_SPPG | PBI-03,PBI-04,PBI-05,PBI-06,PBI-07 | `/head/production` |
| K05 | [Kontrol distribusi, maps, sekolah dan wadah](./kepala-sppg/DISTRIBUTION-CONTROL.md) | KEPALA_SPPG | PBI-08,PBI-09,PBI-10,PBI-11,PBI-12 | `/head/distribution` |
| K06 | [Laporan, histori dan audit](./kepala-sppg/OPERATIONAL-REPORTS.md) | KEPALA_SPPG | PBI-11,PBI-12 | `/head/reports` |
| G01 | [Tugas pengawas gizi hari ini](./pengawas-gizi/NUTRITION-DASHBOARD.md) | PENGAWAS_GIZI | PBI-03,PBI-07,PBI-10,PBI-11 | `/nutrition/dashboard` |
| G02 | [Pengelolaan menu, komposisi dan versi](./pengawas-gizi/MENU-MANAGEMENT.md) | PENGAWAS_GIZI | PBI-03 | `/nutrition/menus` |
| G03 | [Pemeriksaan batch dan laporan kualitas sekolah](./pengawas-gizi/QUALITY-CONTROL.md) | PENGAWAS_GIZI | PBI-07,PBI-10,PBI-11 | `/nutrition/quality-control` |
| A01 | [Pekerjaan dan target produksi hari ini](./asisten-lapangan/WORK-TODAY.md) | ASISTEN_LAPANGAN | PBI-04,PBI-05,PBI-06 | `/production/today` |
| A02 | [Eksekusi produksi, batch dan label](./asisten-lapangan/PRODUCTION-EXECUTION.md) | ASISTEN_LAPANGAN | PBI-06,PBI-07 | `/production/plans/{plan}/execute` |
| A03 | [Pemuatan kendaraan dan penerimaan retur wadah](./asisten-lapangan/LOADING-CHECK.md) | ASISTEN_LAPANGAN | PBI-08,PBI-09 | `/production/logistics` |
| D01 | [Trip yang ditugaskan kepada saya](./driver/MY-TRIPS.md) | KURIR | PBI-08,PBI-09 | `/driver/trips` |
| D02 | [Persiapan perjalanan dan bukti muatan](./driver/TRIP-PREPARATION.md) | KURIR | PBI-08,PBI-09 | `/driver/trips/{trip}/prepare` |
| D03 | [Perjalanan aktif dan pergerakan GPS](./driver/ACTIVE-TRIP.md) | KURIR | PBI-09 | `/driver/trips/{trip}/active` |
| D04 | [Serah terima makanan kepada sekolah](./driver/SCHOOL-HANDOVER.md) | KURIR | PBI-09,PBI-10 | `/driver/trip-stops/{stop}/handover` |
| D05 | [Pengambilan dan retur ompreng](./driver/TRAY-PICKUP.md) | KURIR | PBI-09 | `/driver/pickups` |
| P01 | [Layanan MBG sekolah saya hari ini](./pic-sekolah/SCHOOL-TODAY.md) | PIC_SEKOLAH | PBI-03,PBI-04,PBI-09,PBI-10,PBI-12 | `/school/today` |
| P02 | [Konfirmasi kebutuhan porsi sekolah](./pic-sekolah/PORTION-CONFIRMATION.md) | PIC_SEKOLAH | PBI-04 | `/school/portions` |
| P03 | [Melacak pengiriman menuju sekolah sendiri](./pic-sekolah/DELIVERY-TRACKING.md) | PIC_SEKOLAH | PBI-09,PBI-12 | `/school/delivery-tracking` |
| P04 | [Penerimaan dan pemeriksaan makanan](./pic-sekolah/FOOD-RECEIPT.md) | PIC_SEKOLAH | PBI-10,PBI-11 | `/school/receipts` |
| P05 | [Pemeriksaan sebelum makan dan realisasi pembagian](./pic-sekolah/MEAL-DISTRIBUTION.md) | PIC_SEKOLAH | PBI-10 | `/school/meal-distribution` |
| P06 | [Ompreng siap dijemput dan kendala sekolah](./pic-sekolah/TRAYS-READY.md) | PIC_SEKOLAH | PBI-09,PBI-11 | `/school/trays` |
| U01 | [Cari sekolah untuk melihat MBG](./public/SCHOOL-SEARCH.md) | PUBLIK tanpa login | PBI-12 | `/mbg` |
| U02 | [Status MBG hari ini sekolah pilihan](./public/SCHOOL-MBG-TODAY.md) | PUBLIK tanpa login | PBI-03,PBI-10,PBI-12 | `/mbg/{school:public_slug}` |
| U03 | [Detail menu dan gizi MBG](./public/MENU-NUTRITION.md) | PUBLIK tanpa login | PBI-03,PBI-12 | `/mbg/{school:public_slug}/menu` |
| U04 | [Status pengiriman publik tanpa GPS presisi](./public/DELIVERY-STATUS.md) | PUBLIK tanpa login | PBI-09,PBI-12 | `/mbg/{school:public_slug}/delivery` |
| C01 | [Insiden, bukti dan tindak lanjut](./cross-role/INCIDENT-DETAIL.md) | KEPALA_SPPG; PENGAWAS_GIZI terbatas kualitas; ASISTEN_LAPANGAN; KURIR; PIC_SEKOLAH | PBI-06,PBI-09,PBI-10,PBI-11 | `/incidents/{incident}` |
| C02 | [Detail dan jejak batch](./cross-role/BATCH-DETAIL.md) | KEPALA_SPPG; PENGAWAS_GIZI; ASISTEN_LAPANGAN; KURIR terbatas assignment | PBI-06,PBI-07 | `/batches/{batch}` |
| C03 | [Trip, multi-stop dan bukti perjalanan](./cross-role/TRIP-DETAIL.md) | KEPALA_SPPG; KURIR owner; ASISTEN_LAPANGAN terkait; PIC_SEKOLAH projection terbatas | PBI-08,PBI-09,PBI-10 | `/trips/{trip}` |
| C04 | [Riwayat perubahan dan tanggung jawab](./cross-role/AUDIT-HISTORY.md) | KEPALA_SPPG; pengguna terbatas histori sendiri jika diizinkan | PBI-02,PBI-10,PBI-11,PBI-12 | `/head/audit` |

## Pemetaan 53 area awal → 34 dokumen unik
- Shared area awal 3: login/notifikasi/akun → S01/S02/S03.
- Kepala 15 area: dashboard, kebutuhan porsi, menu, produksi, batch, rencana, trip, live maps, sekolah, ompreng, incident, histori, audit → K01–K06, ditambah C01/C02/C03/C04 untuk detail bersama.
- Pengawas Gizi 8 area: dashboard, daftar+detail menu, penggunaan menu, QC queue/check, organoleptik, incident kualitas → G01–G03 + C01/C02.
- Asisten Lapangan 7 area: pekerjaan, plan, eksekusi, batch, label, loading, retur → A01–A03 + C02.
- Driver 7 area: daftar+riwayat trip, persiapan, aktif GPS, serah-terima, daftar+aktif pickup → D01–D05 + C03.
- PIC Sekolah 9 area: hari ini, kebutuhan, menu, tracking, receipt, pemeriksaan pembagian, wadah, incident, riwayat → P01–P06 + C01.
- Publik 4 area: cari sekolah, hari ini, menu+gizi, tracking ringkas → U01–U04.

## Status implementasi
Semua `SPEC READY` dan `CODE NOT STARTED` ketika paket dibuat. Jangan mengubah menjadi DONE tanpa artefak kode dan hasil test. Jika page spec diganti nama, perbarui registry, cross-link ID di setiap halaman, API routing dan sprint.

## Audit cakupan PBI
| PBI | Halaman utama yang mengerjakan |
| --- | --- |
| 01 | K02, P01 |
| 02 | K03, S01, S03 |
| 03 | G02, U03 |
| 04 | P02, K04 |
| 05 | K04, A01 |
| 06 | A02, C02 |
| 07 | G03, C02 |
| 08 | K05, D01, D02 |
| 09 | D03, D05, P03, U04 |
| 10 | D04, P04, P05 |
| 11 | C01, P06 |
| 12 | K01, K06, U01, U02, U03, U04 |

## Aturan membaca halaman
Ambil page spec sesuai tugas, lalu rujuk PBI, role, workflow, dictionary, API. Jangan memuat semua dokumen ke semua AI sehingga konteks menjadi kabur. Halaman yang dipakai beberapa role menerapkan Policy, bukan copy code tiga kali.

## UI/UX implementation matrix v2
| Pengalaman berbeda | Primary first-fold mobile | Dokumen visual wajib |
|---|---|---|
| Kepala K01–K06 | exception/status SPPG → next decision → drilldown | Creative Direction + UI System + Mobile + QA |
| Gizi G01–G03 | QC queue/menu version → evidence → safe/unsafe decision | Role Experience + Component + Motion |
| Asisten A01–A03 | stage produksi / quantity / one action | Mobile + Component + reduced motion |
| Kurir D01–D05 | current stop / status GPS / safe handover/pickup | Mobile + Motion + GPS docs |
| PIC P01–P06 | kebutuhan/receipt/QC/meal/tray task terbaru | Mobile + Status Workflow + Photo Evidence |
| Publik U01–U04 | sekolah/menu/status factual & privacy | UI System + Role Experience + Security |
| Shared/Cross-role | izin + konteks resource → aksi yang sah | Role Matrix + Policy + Component Spec |

**Acceptance UX:** tiap halaman harus punya struktur 390px yang spesifik di Section 14 masing-masing, screenshot 390px & 1440px, test 320px, keyboard, error, status benar, dan tidak memakai admin template generik. `PAGE-REGISTRY` mengatur cakupan, sementara status `DONE` baru berlaku sesudah tes kode dan quality gate.


## Addendum v3.0 | anti-slop page contract
Seluruh 34 file page di atas memiliki **Section 15 Anti-Slop v3**, masing-masing berbeda sesuai pekerjaan pengguna, bukan copy identik. Gunakan registry untuk memilih satu target. Untuk satu halaman muat `DESIGN.md` + `RULE-MATRIX.md` + `LIVELINESS-DIALS.md` + file page, kemudian akses detail skill hanya jika relevan.

**Current source code caution:** laporan agent yang diberikan pengguna menyatakan K02 PBI-01 sudah dibangun, sementara registry v2 dibuat lebih dahulu dan masih menandai `CODE NOT STARTED`. Jangan menafsirkan status registry v2 sebagai audit kode saat ini. Verifikasi langsung Git source; lihat `docs/project/IMPLEMENTATION-BASELINE-2026-10-12.md`.

**Handoff location:** ketika documentation root read-only, jangan mengedit registry ini dari agent; tulis status aktual pada `P:\Tugas PPL MBG\HANDOFF-LOG.md` di luar folder terlindungi. Perubahan spesifikasi harus melalui paket versi baru yang disetujui.
