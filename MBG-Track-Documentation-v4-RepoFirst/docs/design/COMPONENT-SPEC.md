# Component Specifications — Reusable, Role-Aware, Tidak Generik

## 1. Aturan dasar
Setiap komponen reusable memiliki **input API**, data source, visual state, keyboard/touch semantics, dan contoh use case. Komponen tidak boleh melakukan mutation HTTP diam-diam ketika hanya membaca. Component yang berulang harus dibuat satu kali; page memberikan konteks dan urutan, bukan copy-paste view untuk setiap role.

## 2. Daftar komponen primer
| Komponen | Props/input utama | State wajib | Digunakan pada |
|---|---|---|---|
| `AppShell` | user, role, activePage | loading, 403, session expired | semua internal |
| `ContextHeader` | title, entity, serviceDate, freshness | normal, stale | K01,A01,D03,P01 |
| `OperationalMetric` | label,value,unit,source,time | zero, unknown, delayed | K01/K06/G01 |
| `TaskActionPanel` | task,precondition,nextAction | ready, blocked, submitted | A02,D02,P04 |
| `StatusRail` | allowedStages,current, timestamps | waiting, active, failed | produksi/trip/publik |
| `QuantityReconciliation` | expected, actual, damage, delta | exact, shortage, surplus, unknown | produksi, receipt, trays |
| `EvidenceUploader` | type,subject,policy,limits | ready, uploading, rejected, saved | A02,D02,D04,P04,P06 |
| `GPSHealth` | lastFix, accuracy, permission | live, stale, denied, offline | D03,P03,K05 |
| `RouteStopList` | stops, sequence, activeStop | pending, en-route, arrived, done | K05/D03 |
| `QualityCheckForm` | stage, batch, stop, policy | draft, safe, unsafe, submitted | G03/P04/P05 |
| `IncidentTicket` | severity,type,owner,status | open, acknowledged, resolved | C01/K01 |
| `DataTableAdaptive` | columns,filters,sort,page | loading, empty, error | K02/K03/K06/G02 |
| `PrivateEvidenceViewer` | signedReference,caption,access | locked, failed, available | C01/C02/C03 |
| `PublicTimeline` | approved milestones | unknown, in-progress, done | U02/U04 |
| `NotificationFeed` | items,read state | empty, unread, error | S02 |

## 3. Prinsip kontrak konkret
**QuantityReconciliation:** angka bukan string dekor. `expected` dapat null (belum dikonfirmasi), `actual` hanya setelah user membuat tindakan. `delta` dihitung di domain sebagai `actual-expected`; urutan `received = usable + damaged` perlu didefinisikan eksplisit di receipt, bukan disamakan dengan ompreng. Format Indonesia. Surplus pun harus dicatat sebagai exception, bukan dihilangkan.

**StatusRail:** tahapan datang dari state machine (`STATUS-WORKFLOWS.md`) sehingga user tidak bisa klik bebas semua tahap. Komponen hanya memproyeksikan status dari backend; public memakai milestone projection yang disanitasi. Animasi tidak mengubah status.

**EvidenceUploader:** jenis bukti bukan nama file. Simpan referensi privat, validasi server, buat thumbnail aman; sebelum foto berhasil disimpan jangan menampilkan “terupload”. Wajah anak/identitas harus dicegah untuk publik; foto internal terbatas via Policy.

**GPSHealth:** `freshness` berdasarkan server `last_location_at`, bukan sekadar browser icon. Bila stale jangan interpolasi marker atau memperbarui ETA. CTA navigasi eksternal terpisah dari tombol konfirmasi penerimaan.

**QualityCheckForm:** label checkpoint berbeda (before dispatch, on arrival, before consumption); hasil `UNSAFE` tidak bisa dipaksa jadi safe oleh motion/hidden button. Scope sekolah/batch/stop harus divalidasi server.

## 4. Komposisi halaman per role
Kepala: briefing + exception + list/map drilldown. Gizi: queue + evidence + decision. Asisten: stage + count + evidence. Driver: active target + GPS + handover. PIC: count + QC + confirm. Publik: menu + status + transparansi terbatas.

## 5. Komponen harus menghindari anti-pattern
Tidak ada `UniversalCard` yang memaksakan markup sama untuk semua hal, tidak ada `GradientHero` otomatis, tidak ada `GlassContainer` untuk formulir pangan, tidak ada spinner tanpa konteks. Komponen `EmptyState` menjelaskan alasan dan aksi perbaikan; `ErrorState` membedakan server error, permission, offline dan data belum tersedia.

## 6. Kontrak testing frontend
Gunakan test browser/feature untuk props/state penting; visual regression 390px dan 1440px; keyboard navigation; reduced motion; notifikasi/error tidak hilang terlalu cepat; komponen shared tidak memunculkan aksi beda role. Lakukan screenshot pada D03, P04, A02, G03, K01, U02 sebelum merge.

**Terkait:** `ROLE-EXPERIENCE.md`, `UI-DESIGN-SYSTEM.md`, `UI-QUALITY-CHECKLIST.md`.


## V3 | Behavior-first component library
Semua komponen harus punya **source of truth, 3 states, satu izin/route**, bukan sekadar CSS class. Status pills memakai enum/transaksi nyata; panel count menyebut unit dan tahap; menu links hanya mengarah route yang benar-benar tersedia; confirmation dialog hanya pada aksi berisiko; toast setelah server ACK. 

Komponen yang perlu variasi menurut domain: manifest trip bukan sales card; sensory assessment bukan marketing score; public nutrition bukan dummy chart. Buat `app-shell` role-adaptive, bukan sidebar identik di semua role. Selalu uji keyboard, error, empty, loading dan reduced motion. Reuse bukan pengulangan visual tanpa fungsi.
