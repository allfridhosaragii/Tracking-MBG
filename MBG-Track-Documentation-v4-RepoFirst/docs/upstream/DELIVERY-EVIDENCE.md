# Evidence-based 4-Block Delivery Gate, upstream-first

**Sumber aturan asli:** baca file `.agents/skills/antislop/SKILL.md`, khususnya `Delivery Gate (Mandatory)`. File ini hanya menentukan **bukti khusus MBG** di samping gate original, bukan menggantinya. Jangan merekayasa PASS.

## Block 1 — Hard Gate
Catat hasil nyata untuk R-02, R-03, R-17, R-18, R-23 s.d. R-28, R-32 s.d. R-38. Bukti minimal: hasil HTTP, screenshot, keyboard navigation, state kosong/error, pengecekan link, source data untuk angka. Fitur tidak jadi PASS bila hanya static markup.

## Block 2 — Purpose-Gate
Sebut satu alasan spesifik untuk pilihan warna, icon, grid, shadow, radius, glass, motion. Hindari alasan melingkar seperti “agar modern”. Contoh: status batch ditandai dengan label+warna karena petugas bisa memindai daftar dengan cepat, namun label teks wajib tetap ada.

## Block 3 — Liveliness
Tuliskan `Design Read` dan dials ENERGY/RHYTHM/MOTION sesuai page spec, identifikasi satu focal point, satu accent bermakna, dan motif journey-line *hanya untuk alur sebenarnya*. Lulus antislop tidak sama dengan kosong/steril.

## Block 4 — Craftsmanship
Bukti fungsi, struktur konten sesuai kerja role, resilience pada 320px sampai 1440px, tidak meniru template, tidak ada claim palsu, tidak ada placeholder yang berpura-pura final. Sertakan nilai build/test dan browser console. Audit backend Policy dan data isolation terpisah: ini wajib proyek MBG meski di luar scope asli antislop.

## Format catatan
```
Commit/branch:
Page ID, role, PBI:
Source original skill version / commit:
Design Read:
Dials:
Viewport/screenshots:
Forms and clickable controls tested:
Contrast/zoom/keyboard:
PHP tests, npm build:
Block 1 Hard Gate: PASS/FAIL/NOT VERIFIED (evidence)
Block 2 Purpose Gate: PASS/FAIL/NOT VERIFIED (reason)
Block 3 Liveliness: PASS/FAIL/NOT VERIFIED (evidence)
Block 4 Craftsmanship: PASS/FAIL/NOT VERIFIED (evidence)
Ship status: BLOCKED unless all four pass with evidence
```
