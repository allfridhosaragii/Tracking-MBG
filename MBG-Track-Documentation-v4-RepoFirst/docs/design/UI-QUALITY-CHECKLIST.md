# UI Quality Gate — Premium, Mobile-first, No AI Slop

> Checklist wajib saat review tiap page. **Jangan beri status PASS berdasarkan file `.md` saja**. Butuh aplikasi berjalan, screenshot dan hasil tes. Tetapkan reviewer berbeda dari author saat memungkinkan.

## Rubrik skor 100 (rubrik internal tim, bukan standar objektif universal)
| Dimensi | Bobot | Bukti |
|---|---:|---|
| Mobile hierarchy, 320–430px | 20 | screenshot mobile, CTA primer, no overflow |
| Keaslian dan kualitas visual | 15 | audit anti-template, design rationale per page |
| Kejelasan data dan status | 15 | screenshot target vs real vs stale, timestamp |
| Keamanan/peran dan privasi | 15 | tests 401/403, public projection |
| Kinerja dan robustness | 10 | devtools/performance, offline/error |
| Aksesibilitas | 10 | keyboard, zoom, contrast, touch, screen reader smoke |
| Microinteraction dan motion | 5 | reduced-motion, action feedback, no fake success |
| Konsistensi design system | 5 | tokens, typography, component reuse |
| Data realistis dan edge cases | 5 | screenshots incomplete/shortage/QC hold |
| **Total** | **100** | **per halaman** |

Ambang usulan internal: tidak boleh merge bila ada P0 keselamatan/privasi, gagal critical action, overflow serius, atau false LIVE; skor >=85 target kualitas, **tetapi skor tidak menggantikan gate wajib**.

## Anti-AI-slop audit
- [ ] Bukan hero gradient generik + 6 metric cards yang diulang di semua role.
- [ ] Komposisi halaman mengikuti pekerjaan aktual; ada alasan mengapa bagian pertama ditempatkan paling atas.
- [ ] Tidak ada pseudo-data seperti “12.5% growth” tanpa basis transaksi.
- [ ] Tidak ada ilustrasi stok seolah foto makanan produksi/serah terima nyata.
- [ ] Bentuk/radius/shadow konsisten, tidak berlebihan, spacing bermakna.
- [ ] Tidak ada microcopy bahasa AI seperti “Unlock seamless excellence”; gunakan bahasa tindakan nyata.

## Checklist mobile wajib
- [ ] 320px tanpa page-level horizontal scrolling (kecuali komponen tabel dengan alternatif responsif).
- [ ] 360, 390, 430px: H1/status/tugas terpenting terlihat dan terbaca.
- [ ] Target kontrol utama >=44px (standar internal), jarak antar aksi destruktif aman.
- [ ] Safe-area bottom, sticky CTA dan keyboard tidak saling menimpa.
- [ ] Teks panjang, angka besar, tanggal dan pembesaran 200% diuji.
- [ ] Perubahan orientasi tidak merusak form; tidak ada hover-only action.
- [ ] Reduced motion menghormati sistem dan tidak menghilangkan status loading.
- [ ] Operasi kurir tidak memaksa interaksi ketika berkendara.

## Checklist kejujuran data dan kerja sistem
- [ ] `target`, `aktual`, `delivered`, `received`, `served` tidak disamakan.
- [ ] Timestamp aktivitas dan GPS berasal dari server/record yang benar.
- [ ] ETA memakai “±” atau kata “perkiraan” dan hilang jika data stale.
- [ ] Foto privat tidak tampil di view publik atau pengguna tak berhak.
- [ ] Submit baru dinyatakan berhasil setelah backend sukses; double submit dicegah.
- [ ] Setiap error state memberi alasan aman dan next action.
- [ ] Data simulasi diberi label dan tidak mengaku informasi resmi.

## Pengujian per page — bukti minimal
| Bukti | Naming/format |
|---|---|
| Screenshot mobile normal | `PAGEID-390-normal.png` |
| Screenshot mobile error/empty | `PAGEID-390-error.png` |
| Screenshot desktop | `PAGEID-1440-normal.png` |
| Accessibility notes | focus order, contrast matrix, zoom |
| Screenshot timing state | only if GPS/ETA/notifikasi relevant |
| Feature/browser tests | command, passed/failed, date, commit |

## Status dan interpretasi
- `SPEC_READY`: dokumen lengkap, tidak ada kode.
- `IMPLEMENTED`: fitur tersambung backend, belum seluruh pengujian.
- `TESTED`: semua gate fungsional/keamanan lolos sesuai lingkup.
- `VISUAL_REVIEWED`: screenshot & rubrik disetujui.
- `DONE`: TESTED + VISUAL_REVIEWED + docs & git handoff.

## Gate uji pertama
D03 Active Trip, P04 Food Receipt, A02 Production Execution, G03 Quality Control, K01 Dashboard, U02 Public Today. Enam ini mewakili pengalaman paling berbeda; setelah desainnya bagus baru diekstrapolasi ke 28 halaman lain.

Rujukan normatif: https://www.w3.org/TR/WCAG22/ ; rekomendasi internal 44px **lebih ketat** dari minimum WCAG 2.2 AA 24px dalam sebagian kondisi.


## V3 | Mandatory anti-slop gate on top of internal score
Rubrik angka di atas tetap sebagai alat review internal. **Tidak ada skor yang meloloskan Hard Gate R-XX**. Tambahkan bukti dari `docs/anti-slop/DELIVERY-GATE.md`: empat blok Hard/Purpose/Liveliness/Craftsmanship, PASS/FAIL per item dengan bukti. R-17 angka palsu, R-24 dead link, R-25 contrast, R-26 controls palsu, R-27 missing states, R-32 keyboard, R-35 belum dibuka di browser = release blocker, meski rubrik visual >85.

Masukkan `Design Read` + ENERGY/RHYTHM/MOTION sesuai halaman, satu focal, satu motif, dan design rationale. Jika desain tampak steril setelah menghapus slop, perbaiki lewat komposisi dan konten bernilai, bukan kembali ke efek standar AI. Screenshot review wajib dari source Laravel aktual, bukan komposit AI.
