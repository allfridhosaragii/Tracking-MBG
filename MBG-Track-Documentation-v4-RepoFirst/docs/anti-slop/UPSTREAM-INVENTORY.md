# Upstream source tree and reading map

> MBG-Track v3.0 | Asal inspirasi: [anti-slop oleh Miqdad Badjuber](https://github.com/miqdadbadjuber/anti-slop/tree/9685ca7eac70283d083fc23baa9a88e7d3cd93c4) (MIT, snapshot 12-10-2026). Dokumen ini adalah **adaptasi orisinal khusus MBG-Track**, bukan salinan skill resmi ataupun hasil pengujian aplikasi.

## Inventory kategoris dari Git tree per 12-10-2026
- Core: `README.md`, `GUIDE.md`, `antislop.md`, `ROADMAP.md`, `LICENSE`, `SECURITY.md`, `CONTRIBUTING.md`.
- Skills: `skills/antislop/SKILL.md`, `antislop-ui/SKILL.md`, `antislop-copywriting/SKILL.md`, `antislop-human/SKILL.md`, `antislop-layoutmobile/SKILL.md`, `antislop-code/SKILL.md` (core memiliki `VERSION`).
- Accessibility tooling: `skills/antislop-human/contrast-check.py`, `contrast-mcp.py` pada upstream. Untuk Laravel kita hanya merujuk prinsip dan menghitung kontras lewat alat tepercaya setelah implementasi.
- Config pointers: `rules/antislop.md`, `rules/antislop.mdc`.
- Instalasi: `cli/index.mjs`, `cli/lib/install.mjs`, `settings.mjs`, `cli/package.json`, script sync/smoke.
- Plugin doors yang tampak: `.agents`, `.claude-plugin`, `.codex-plugin`, `.cursor-plugin`, `.kimi-plugin`, `.cline-plugin`, `.omp-plugin`, plus `plugin.json`.
- CI & governance: `.github/workflows/ci.yml`, issue templates, PR template, scripts/check-repo.mjs.
- Assets upstream: contoh visual UI/text/code before/after dan banner. **Tidak diperbanyak** dalam dokumen ini.

## Cara membaca tanpa konteks membengkak
- Implementasi page baru: `DESIGN.md` + core `RULE-MATRIX.md` + `skills/UI.md` + `skills/LAYOUT-MOBILE.md` + spesifikasi halaman.
- Perbaikan copy: `skills/COPYWRITING.md` + R-17/18/36/38.
- Audit aksesibilitas: `skills/HUMAN.md` + WCAG 2.2 + tests.
- Audit komentar kode: `skills/CODE-COMMENTS.md`, gunakan perubahan komentar saja; jangan patch logic.
- Pasca implementasi: `AFTER-AUDIT.md` sebelum revisi desain yang belum disetujui.
