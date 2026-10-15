# Anti-slop acceptance matrix, severity and proof

> MBG-Track v3.0 | Asal inspirasi: [anti-slop oleh Miqdad Badjuber](https://github.com/miqdadbadjuber/anti-slop/tree/9685ca7eac70283d083fc23baa9a88e7d3cd93c4) (MIT, snapshot 12-10-2026). Dokumen ini adalah **adaptasi orisinal khusus MBG-Track**, bukan salinan skill resmi ataupun hasil pengujian aplikasi.

## Gate vs score
Skor 100 pada `UI-QUALITY-CHECKLIST.md` adalah rubrik internal. **Hard Gate** dan keamanan PBI tidak dapat ditawar dengan skor. Fungsionalitas salah meski cantik = NOT DONE; visual buruk meski test lewat = BELUM VISUAL_REVIEWED.

| Domain | Minimum evidence | Hard failure |
|---|---|---|
| Auth/tenant | auth+policy 403 test dan route list | orang melihat data tenant lain |
| QC/safety | status transitions + test hold | UI bisa `serve` saat `UNSAFE` |
| Numeric integrity | data fixture calculated backend | porsi target ditampilkan sebagai aktual tanpa label |
| Navigation | click all visible links | ghost link/dummy button |
| Loading/empty/error | browser fixtures | halaman kosong tidak menjelaskan status |
| Contrast/focus | computed contrast & tab walkthrough | teks tak terbaca / keyboard tak bisa masuk |
| Mobile | 320/360/390/430/768/1440 + zoom 200% | page overflow / CTA terhalang |
| GPS/privacy | stale simulation, public DTO test | posisi kurir presisi publik |
| Motion | toggle reduced motion & state event | fake success atau autoplay status |
| Asset truth | provenance registry | foto/merek/testimoni tidak disetujui dipresentasi sebagai nyata |
| Visual identity | page fingerprints and visual rationale | clone template & universal card-grid |

## Laporan status
`SPEC_READY` = dokumen, belum UI; `IMPLEMENTED` = kode ada tapi mungkin belum dites; `TESTED` = fungsional/secure pada scope; `VISUAL_REVIEWED` = audit browser dan motion; `DONE` = semua, evidence dan commit. Sertakan `NOT VERIFIED` jika uji tidak tersedia.

## Regression acceptance untuk PBI-02
K03 tambah pegawai dan mutasi PIC harus menjaga PBI-01: old PIC kehilangan akses, user lintas SPPG ditolak, nonaktif logout, password tidak dibocorkan; form mobile dan destructive confirmation diuji.
