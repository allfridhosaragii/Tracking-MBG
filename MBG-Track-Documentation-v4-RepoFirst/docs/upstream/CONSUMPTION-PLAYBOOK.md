# Cara agent benar-benar menggunakan upstream original

## Aturan baca sebelum UI berubah
```
1. Baca <root>/.agents/skills/antislop/SKILL.md
2. Jika UI: baca antislop-ui/SKILL.md dan antislop-layoutmobile/SKILL.md
3. Jika form/accessible: baca antislop-human/SKILL.md
4. Jika teks UI: baca antislop-copywriting/SKILL.md
5. Jika comments only: antislop-code/SKILL.md
6. Baca v4/DESIGN.md dan satu page spec relevan
7. Tentukan mode During (baru) atau After (existing) dan dials
8. Implementasi terbatas + tes + empat blok Delivery Gate dengan bukti
```

**Tidak ada silent fallback:** bila original SKILL tidak ditemukan, hentikan klaim upstream-backed; install dulu atau laporkan keterbatasan. Jangan menggantikan skill asli dengan `RULE-MATRIX.md` adaptasi tanpa memberi label jelas.

## Per halaman wajib spesifik
- Screen priority, actual data origin, main action and fallback/empty/error state.
- Yang mengubah backend harus melewati Policy dan FormRequest; tidak cukup terlihat aktif di Blade.
- Penggunaan animasi ditulis alasan; data tidak boleh bergerak seakan live ketika stale.
- Simpan pemetaan aturan original yang diterapkan (R-XX) dan bukti tes/screenshot.

## Agent audit lama
Mode After mengikuti upstream: identifikasi temuan bernomor, **minta persetujuan untuk fix** sebelum edit existing UI secara massal. Mode During membangun PBI baru setelah spec/permission dibaca.
