# Pemeriksaan seluruh komponen upstream

Snapshot repository yang ditinjau (Git tree) berisi **59 berkas**, di luar direktori kosong. Clone lokal dari Git mempertahankan seluruh repo pada commit terpin. Paket dokumentasi v4 tidak mengklaim setiap berkas diperlukan oleh Laravel.

| Kelompok asli | Fungsi | Kebijakan MBG |
|---|---|---|
| `skills/antislop/` | Core rulebook 38 aturan + During/After + Delivery Gate | **WAJIB dipasang dan dibaca** |
| `skills/antislop-ui/` | Diagnosis komposisi/color/layout/motion | WAJIB untuk desain halaman |
| `skills/antislop-copywriting/` | Tone, data dan klaim tanpa filler | WAJIB untuk UI copy |
| `skills/antislop-human/` | Kontras, fokus, keyboard, states | WAJIB untuk UI interaktif |
| `skills/antislop-layoutmobile/` | Reflow dan constraints responsif | WAJIB untuk halaman mobile |
| `skills/antislop-code/` | Review komentar kode AI | Hanya tugas komentar; dilarang mengubah executable memakai skill ini |
| `rules/` dan `antislop.md` | Pointer pendek dan aturan single-file | Referensi; sumber utama tetap original SKILL |
| `GUIDE.md`, `README.md`, `ROADMAP.md` | Cara instalasi/versioning/semantik repo | Dibaca saat perlu; bukan kontrak UI MBG |
| `cli/`, `scripts/` | Installer, smoke, checker internal | Tidak perlu dieksekusi di aplikasi Laravel untuk memakai skill |
| `.claude-plugin`, `.codex-plugin`, `.kimi-plugin`, `.cursor-plugin`, `.cline-plugin`, `.omp-plugin`, `.agents/plugins` | Distribution/plugin manifests per client | Bukan file yang perlu dipindahkan ke app Laravel kecuali melakukan instalasi native resmi |
| `.github`, `CONTRIBUTING.md`, `CODE_OF_CONDUCT.md`, `SECURITY.md` | CI/komunitas/security repo | Bukan requirement MBG |
| `assets/` | Demo dan before-after repo | Tidak dijadikan gambar atau branding MBG |
| `LICENSE` | MIT license | Pertahankan seluruh license dalam source clone |

**Jangan mengklaim 59/59 berkas dipakai oleh runtime MBG.** Clone tersedia 59/59; enam SKILL asli aktif pada Antigravity setelah pemasangan dan verifikasi. Ini dua ukuran berbeda.
