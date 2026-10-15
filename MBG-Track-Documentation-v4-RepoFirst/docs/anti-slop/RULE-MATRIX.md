# Anti-slop rule matrix | R-01..R-38 with MBG-Track controls

> MBG-Track v3.0 | Asal inspirasi: [anti-slop oleh Miqdad Badjuber](https://github.com/miqdadbadjuber/anti-slop/tree/9685ca7eac70283d083fc23baa9a88e7d3cd93c4) (MIT, snapshot 12-10-2026). Dokumen ini adalah **adaptasi orisinal khusus MBG-Track**, bukan salinan skill resmi ataupun hasil pengujian aplikasi.

## Aturan interpretasi
Referensi R-XX mengikuti nama/semangat [upstream core](https://github.com/miqdadbadjuber/anti-slop/blob/main/skills/antislop/SKILL.md). Ini **parafrasa operasional**, bukan salinan teks lengkap; jika perlu detail normatif, buka versi resmi commit yang dipatok di `UPSTREAM-RESEARCH.md`. Hard = tidak boleh diloloskan; Purpose = boleh hanya dengan alasan; Quality = penilaian konsistensi. Aturan core tidak menggantikan Policy, DB dan aturan keselamatan pangan MBG.


| Rule | Tier | Fokus | Penerapan konkret MBG-Track | Bukti acceptance |
|---|---|---|---|---|
| R-01 | Purpose | Color & gradients | Palet pine/alabaster dipakai untuk orientasi; gradasi tidak otomatis menjadi background | Screenshot warna/hierarki dan satu kalimat alasan per penggunaan |
| R-02 | Hard | Natural writing / em dash | Copy UI baru tidak memakai em dash; pertahankan kutipan pengguna sampai ada keputusan | Audit string UI baru dan review microcopy |
| R-03 | Hard | Mobile responsiveness | Setiap halaman diuji di 320/360/390/430/768/1440, no overflow, CTA tak tertutup | Screenshot, viewport test, zoom 200% |
| R-04 | Purpose | Icons | Icon menandai aksi/domain asli (batch, suhu, porsi, rute), bukan sparkle default | Daftar makna icon & uji label sr-only |
| R-05 | Quality | Page structure | Susunan mengikuti task nyata; tidak pakai hero + enam card default | Audit flow dan variasi ritme yang beralasan |
| R-06 | Purpose | Typography | Instrument Sans dipilih untuk keterbacaan operasional; hierarki dan angka tabular | Type scale dan keterbacaan pada 320px |
| R-07 | Purpose | Background texture | Tanpa graph paper/grid; motif garis hanya untuk timeline yang benar | Screenshot latar dan alasan pemakaian motif |
| R-08 | Purpose | Button arrows | Panah hanya untuk tindakan navigasi yang jelas, bukan seluruh tombol | Audit CTA / action map |
| R-09 | Purpose | Badges | Badge hanya mewakili status benar (QC, GPS stale, trip), bukan hiasan | Status dicocokkan dengan enum backend |
| R-10 | Purpose | Glassmorphism | Hindari blur pada sistem data; pengecualian maksimal 1–2 lapisan bermakna | CSS search & penilaian kontras |
| R-11 | Quality | Radius consistency | Input, panel, dialog memakai skala radius; semua pill dilarang | Token audit |
| R-12 | Purpose | Shadows | Shadow hanya untuk overlay elevasi nyata; tabel dan panel biasa flat | CSS screenshot + alasan elevation |
| R-13 | Purpose | Glows | Tidak ada glow generik; aksen fokus hanya bila fungsi jelas | Screenshot scan |
| R-14 | Purpose | Feature cards | Daftar tugas vs data QC vs map mempunyai komposisi berbeda, bukan kartu seragam | Visual comparison 3 role |
| R-15 | Quality | CTA specificity | Label tombol menggunakan domain: "Konfirmasi porsi", "Tandai tiba", "Tahan batch" | Audit semua tombol dapat dioperasikan |
| R-16 | Quality | No buzzwords | Bahasa Indonesia operasional sederhana; tanpa klaim next-gen/seamless | Copy audit + actual source |
| R-17 | Hard | Data & stats | Angka 1.026 hanya contoh simulasi, bukan jumlah produksi live; angka nyata dari DB | Audit sumber angka dan label DEMO |
| R-18 | Hard | No fake testimonials | Tanpa nama/foto ulasan fiktif | Konten publik review |
| R-19 | Purpose | Animations | Motion karena response/status/disclosure; kurir tidak pakai scroll gimmick | Reduced-motion screenshot, state transition recording |
| R-20 | Quality | Identity | Motif jalur layanan, palet terukur dan peran berbeda; bukan clone dashboard | Brand swap test & rationale |
| R-21 | Quality | Theme choice | Light-first karena outdoor/readability; tema kedua hanya jika seluruh komponen lulus | Contrast both themes if shipped |
| R-22 | Purpose | Illustrations | Gunakan bukti foto yang diizinkan atau placeholder jujur; bukan stock blob | Content provenance/permission |
| R-23 | Hard | Visual assets & consent | Aset logo/foto/testimoni wajib disetujui atau placeholder terang-terangan | Asset registry + approval record |
| R-24 | Hard | Navigation destination | Link menu hanya menuju route implemented & authorized, atau jelas coming soon | php artisan route:list + click path |
| R-25 | Hard | Contrast AA | Teks normal 4.5:1, besar 3:1; jangan mengira palet otomatis lulus | Contrast calculation & focus states |
| R-26 | Hard | Functional controls | Seluruh tombol/sort/filter/search/form bekerja atau dihapus | Manual keyboard/click test + endpoint |
| R-27 | Hard | UI states | Setiap data view punya loading/empty/error yang sesuai transaksi | Browser fixture 0 data + fail network |
| R-28 | Hard | Real FAQs only | Jangan buat FAQ generic untuk aplikasi operasional; hanya bila kebutuhan nyata | Review kebijakan konten |
| R-29 | Quality | Constrained palette | 2–3 warna utama + satu aksen, netral tidak dihitung; semantic status terbatas | Token + screenshot audit |
| R-30 | Quality | No copying popular apps | Tidak meniru keseluruhan layout Linear/Notion/Vercel secara otomatis | Original composition rationale |
| R-31 | Quality | Write reasons | Setiap keputusan besar warna/layout/typography/spacing punya alasan satu kalimat | Decision ledger dengan owner dan screenshot |
| R-32 | Hard | Keyboard accessibility | Tab order, Enter/Space, Escape pada dialog, focus visible | Keyboard-only walkthrough |
| R-33 | Hard | No external UI patch scripts | Jangan memodifikasi HTML/CSS executable via skrip string replacement sebagai implementasi fitur | Git diff & review cara edit kode |
| R-34 | Hard | Every shipped theme works | Tidak menampilkan theme toggle palsu/dark mode setengah jadi | Mode-by-mode test |
| R-35 | Hard | Verify before deliver | Build, klik tiap kontrol, screenshot, console logs; status belum diuji tidak boleh PASS | Evidence log commit & test commands |
| R-36 | Hard | No unverified compliance claims | Jangan klaim WCAG AA/compliant, aman atau real-time tanpa hasil ukur | Report evidence and qualification |
| R-37 | Hard | Design direction first | Load DESIGN.md, tentukan ENERGY/RHYTHM/MOTION, catat Design Read | Handoff design read + rationale |
| R-38 | Hard | True content or label placeholder | Foto/data pengguna/kalori/status hanya dari sumber sah, atau [DEMO]/[BELUM ADA] | Content source map and empty state |

## Prioritas gabungan pada review per halaman
1. **P0**: privacy leak, false success, count mismatch, batch hold bypass, driver distraction, R-17/R-24/R-25/R-26/R-27/R-32/R-35/R-38.
2. **P1**: layout overflow, missing source, broken motion/accessibility, color-only status, unapproved asset, unreviewed page direction.
3. **P2**: monotonous cards, typography rhythm, inconsistent visual motif, unnecessary glow, comment noise.

## Aturan benturan
- `R-33` melarang patch skrip string-replacement **untuk mengubah fitur UI/source**; tidak melarang generator dokumentasi, test runner, formatter resmi, atau migration Laravel selama aman dan di-review.
- `R-02` khusus copy baru dalam produk; jangan mengubah kutipan regulasi, nama perusahaan, file referensi read-only untuk mengejar statistik karakter.
- `R-21` mengizinkan tema tetap jika alasannya kuat (di MBG-Track: penggunaan di lapangan/siang hari). Jangan klaim dark-mode compliant tanpa implementasi.
- `R-23`: untuk aset tanpa persetujuan gunakan placeholder jujur; jangan memakai logo resmi BGN sebagai identitas app.
- Jika design rule bertabrakan dengan keselamatan/ketersediaan makanan, **keselamatan dan aksesibilitas menang**; tulis deviasi dan bukti.
