# Delivery Gate: four mandatory blocks for MBG-Track

> MBG-Track v3.0 | Asal inspirasi: [anti-slop oleh Miqdad Badjuber](https://github.com/miqdadbadjuber/anti-slop/tree/9685ca7eac70283d083fc23baa9a88e7d3cd93c4) (MIT, snapshot 12-10-2026). Dokumen ini adalah **adaptasi orisinal khusus MBG-Track**, bukan salinan skill resmi ataupun hasil pengujian aplikasi.

## Aturan operasional
Empat blok ini diadaptasi dari [upstream](https://github.com/miqdadbadjuber/anti-slop/blob/main/skills/antislop/SKILL.md). Jalankan ketika suatu halaman **diklaim siap diserahkan**. Jangan sekadar copy checklist dengan semua PASS; setiap PASS butuh perintah, output, screenshot atau pathname bukti dan commit. Bila bukti tidak ada, tulis **NOT VERIFIED**, bukan PASS. FAIL pada hard safety/behavior berarti tidak boleh `DONE`.

### Block 1: HARD GATE
- [ ] R-02 copy UI tidak berisi em dash yang dilarang, kecuali kutipan/persetujuan tercatat.
- [ ] R-03 layar 320–1440 tidak overflow, CTA tidak tertutup keyboard/safe-area.
- [ ] R-17,18,36,38 tidak ada angka/foto/testimoni/status/claim palsu. Porsi demo ditandai jelas.
- [ ] R-23 aset baru disetujui atau placeholder; logo pemerintah tidak dipakai palsu.
- [ ] R-24 tiap nav menuju route nyata dan authorized.
- [ ] R-25 rasio AA teks diuji, bukan diasumsikan.
- [ ] R-26 tombol, form, dropdown, filter/sort benar-benar bekerja.
- [ ] R-27 empty/loading/error tersedia untuk tiap data-bearing view.
- [ ] R-28 tidak ada FAQ template.
- [ ] R-32 keyboard, focus, Escape dapat diuji.
- [ ] R-33 fitur UI tidak ditambahkan dengan patch file string replacement.
- [ ] R-34 setiap mode/theme yang dikirim berfungsi.
- [ ] R-35 aplikasi di-build/dijalankan dan semua action diklik.
- [ ] R-37 DESIGN.md/Design Read + dials dipakai sebelum implementasi.

### Block 2: PURPOSE GATE
Tuliskan alasan ringkas setiap penggunaan R-01 warna/gradien, R-04 icon, R-06 tipografi, R-07 tekstur, R-08 panah, R-09 badge, R-10 blur, R-12 shadow, R-13 glow, R-14 kartu, R-19 motion, R-22 ilustrasi. Bila teknik tak dipakai, `N/A`. Teknik tanpa alasan = FAIL.

### Block 3: LIVELINESS
- [ ] ENERGY/RHYTHM/MOTION terdefinisi dan hasil visual sesuai.
- [ ] Focal point satu per viewport; whitespace memisahkan unit bermakna.
- [ ] Aksen satu yang disengaja; motif produk muncul **hanya** di area yang relevan.
- [ ] Kesan berkarakter, bukan output steril putih + border.

### Block 4: CRAFTSMANSHIP + QUALITY LOCKS
- [ ] C1–C5: keputusan disengaja, interaksi lengkap, komposisi mengikuti isi, tangguh pada semua state, klaim berbukti.
- [ ] R-05 layout bukan template generik; R-11 radius punya sistem; R-15 CTA spesifik; R-16 microcopy alami.
- [ ] R-20 identitas MBG-Track terukur; R-21 alasan tema jelas; R-29 palet terkendali; R-30 bukan tiruan produk; R-31 alasan tiap pilihan penting dicatat.
- [ ] PBI, Policy, status workflow, GPS/privacy tidak rusak oleh perubahan visual.

## Evidence table wajib saat serah terima
| Area | Status | Bukti konkret | Gap |
|---|---|---|---|
| PHP/Laravel regression | PASS/FAIL/NOT VERIFIED | `php artisan test` + output | ... |
| Vite build | PASS/FAIL/NOT VERIFIED | `npm run build` + output | ... |
| R-03 mobile | PASS/FAIL/NOT VERIFIED | screenshots 320/390/430 | ... |
| R-25 contrast | PASS/FAIL/NOT VERIFIED | rasio + elemen | ... |
| R-26 controls | PASS/FAIL/NOT VERIFIED | click-through action map | ... |
| R-27 states | PASS/FAIL/NOT VERIFIED | screenshots fixtures | ... |
| R-32 keyboard | PASS/FAIL/NOT VERIFIED | tab order & Escape | ... |
| R-35 real browser | PASS/FAIL/NOT VERIFIED | browser log & commit | ... |
| Privacy/tenant | PASS/FAIL/NOT VERIFIED | 403/404 tests | ... |
| Identity & dials | PASS/FAIL/NOT VERIFIED | Design Read & rationale | ... |

**Gate hanya untuk implementasi**. ZIP dokumentasi v3 tidak dapat disebut PASS UI karena belum memuat halaman live.
