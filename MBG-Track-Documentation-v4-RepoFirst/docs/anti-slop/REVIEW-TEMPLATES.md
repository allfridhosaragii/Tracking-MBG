# Page review templates and evidence schema

> MBG-Track v3.0 | Asal inspirasi: [anti-slop oleh Miqdad Badjuber](https://github.com/miqdadbadjuber/anti-slop/tree/9685ca7eac70283d083fc23baa9a88e7d3cd93c4) (MIT, snapshot 12-10-2026). Dokumen ini adalah **adaptasi orisinal khusus MBG-Track**, bukan salinan skill resmi ataupun hasil pengujian aplikasi.

## Design Read record
```
Page: <ID> / Role: <ROLE> / PBI: <PBI>
Current implementation: <commit or NOT IMPLEMENTED>
Reading as: <job and user>
ENERGY: <1..3>, RHYTHM: <1..3>, MOTION: <1..3>
First-fold focal point: <entity + status + next action>
Source of truth: <table/DTO>
Identity motif used: <where + why / N/A>
Visual tradeoff: <one sentence>
```

## Component contract
| Component | Source field | State | Authorized action | 320px behavior | Evidence |
|---|---|---|---|---|---|
| Primary action | named server endpoint | enabled/disabled/loading/error | Policy | first fold, 44px min | route+test |
| Count comparison | planned/actual fields | 0/partial/conflict | read scoped | stacked text not squeezed row | fixture screenshot |
| Status | event enum/timestamp | fresh/stale/unknown | read scoped | text+icon | test |

## Evidence naming
`<PAGEID>-<viewport>-<state>-<commit>.png` for screens; e.g. `P04-390-discrepancy-abc123.png`. Browser logs must include tested URL and timestamp. If images cannot be produced use `NOT VISUALLY VERIFIED` and keep task at IMPLEMENTED, not DONE.

## One-line rationale format
`Keputusan: [posisi/status/motif/warna]. Alasan: [prioritas domain]. Alternatif ditolak: [mengapa]. Bukti: [path/test].`

## Delivery record
Copy four blocks from `DELIVERY-GATE.md`, fill PASS/FAIL/NOT VERIFIED per block and link each to evidence. For P0 safety/privacy, FAIL blocks release. A section with no real test may not be converted to PASS by language model confidence.
