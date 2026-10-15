# MBG-Track Documentation v4.0 — upstream anti-slop FIRST

**Original repo is the authority for anti-AI-slop quality.** Repository: [https://github.com/miqdadbadjuber/anti-slop](https://github.com/miqdadbadjuber/anti-slop), pinned source commit `9685ca7eac70283d083fc23baa9a88e7d3cd93c4`. The **real six original SKILL.md files** must be installed and loaded in Antigravity; summary/rule matrix files are not a substitute. Positive style direction remains `DESIGN.md`; requirements and data authority remain MBG-Track docs. This ZIP preserves v3 content and extends it; user original folders are never modified.

## Start here
1. `UPSTREAM-FIRST.md` — rule hierarchy and protected Windows paths.
2. `QUICK-START-WINDOWS.md` — safe one-time installation via `scripts/Install-AntiSlop-Upstream.ps1` at project root, and verify via `scripts/Check-AntiSlop-Integration.ps1`.
3. `docs/upstream/SOURCE-OF-TRUTH.md` and `docs/upstream/CONSUMPTION-PLAYBOOK.md`.
4. `DESIGN.md` and the relevant one of 34 page specs.
5. `prompts/ANTIGRAVITY-REPO-FIRST.md` after successful install, for Gemini agent work.

**Repo source not bundled in this archive:** the installer clones the *unchanged original Git repository* to `.anti-slop-upstream`, verifies its commit hash, and copies its original skills into `.agents/skills/`. We cannot assert it is installed on your Windows machine until your local script runs successfully. Original upstream license is retained by the clone.

## Current project
- Laravel existing: `P:\Tugas PPL MBG` (root, NOT a new subfolder).
- Original `MBG-Track-Documentation` and v3 docs: protected read-only.
- v4 docs: `MBG-Track-Documentation-v4-RepoFirst` in separate folder.
- Sprint 0/PBI-01: reported by Gemini earlier; verify current local repository and tests before proceeding.
- 12 PBI, 5 internal roles + public, 34 page specs, 22 baseline entities remain intact.

## Difference from v3
- v3: adapted 38 rules plus custom playbook; original upstream installer optional.
- v4: original upstream SKILL files become **mandatory quality foundation**, with automated source/pin/copy validation and explicit requirement to read/execute upstream's full Delivery Gate.
- Original anti-slop is a **filter, not a UI design template**; do not confuse “clean” with “sterile.”

## v3 documentation preserved below (some references to v3 are historical; v4 rules above supersede them)

---

# MBG-Track | Documentation v3.0 + Anti-Slop Engineering Playbook

> Snapshot: 12 Oktober 2026. **100 file Markdown**, termasuk 34 page specs unik, 12 PBI, 6 profil AI, 22 entitas baseline dan lapisan anti-slop yang diadaptasi untuk Laravel. Ini **paket dokumentasi**, bukan hasil build/deployment atau bukti UI sudah lolos audit.

## Apa yang berubah dari v2.0?

**v2.0** membahas bisnis MBG, role, database, Laravel, 34 halaman, clean luxury dan mobile-first. **v3.0** menambahkan cara *mencegah dan mengaudit AI slop* pada source code yang dibuat agent, berdasarkan [anti-slop](https://github.com/miqdadbadjuber/anti-slop) karya Miqdad Badjuber (MIT), snapshot commit [`9685ca7`](https://github.com/miqdadbadjuber/anti-slop/tree/9685ca7eac70283d083fc23baa9a88e7d3cd93c4).

Temuan utama dari upstream: **anti-slop adalah filter, bukan style guide**. Oleh karena itu MBG-Track v3 memakai **dua instrumen bersama**: `DESIGN.md` untuk arah visual positif dan `docs/anti-slop/RULE-MATRIX.md` untuk menyaring pola buruk. Menambah larangan tanpa direction justru dapat membuat desain steril.

## Cara membaca yang benar

1. Mulai `AGENTS.md` dan **`DESIGN.md`**, lalu scope dari `docs/product/REQUIREMENTS-AND-SCOPE.md` dan `docs/product/PBI-BACKLOG.md`.
2. Pilih pekerjaan nyata di `docs/pages/PAGE-REGISTRY.md` dan baca **hanya satu spesifikasi halaman**; setiap 34 page spec sekarang memiliki `Section 15: Anti-Slop v3` yang berisi focal point, komposisi unik, dials, copy, risk dan tes.
3. Cocokkan transaksi, role dan schema melalui `docs/business/ROLE-PERMISSION-MATRIX.md`, `STATUS-WORKFLOWS.md`, `docs/technical/DATA-DICTIONARY.md`, dan API contracts.
4. Mode membangun fitur baru: `docs/anti-slop/DURING-WORKFLOW.md`. Mode mereview UI existing: `docs/anti-slop/AFTER-AUDIT.md`.
5. Setiap perubahan UI wajib melewati `docs/anti-slop/DELIVERY-GATE.md` empat blok dengan bukti nyata, bukan deklarasi semata. Untuk fokus tertentu baca `docs/anti-slop/skills/{UI,COPYWRITING,HUMAN,LAYOUT-MOBILE,CODE-COMMENTS}.md`.
6. Bagi multiagent, baca `docs/ai/AI-ROUTER.md` dan profil di `docs/ai/models/`; file khusus model **tidak otomatis dibaca semua tools**.

## Jumlah file

| Kelompok | File .md | Contoh |
|---|---:|---|
| Root project docs | 5 | README, AGENTS, CLAUDE, GEMINI, DESIGN |
| Produk | 4 | 12 PBI, scope, official sources |
| Proses bisnis | 4 | role policy, workflows, 53 area functional |
| Teknis Laravel | 7 | ERD, data dictionary, API, GPS, evidence |
| Kualitas | 4 | security, tests, acceptance matrix |
| Manajemen proyek | 6 | sprint, decision, handoff, implementation baseline, changelog |
| Akademik | 1 | pemetaan proposal PPL |
| AI | 7 | AI Router + ChatGPT, Gemini, Claude, Kimi, DeepSeek, GLM |
| Pages | 35 | Registry + 34 spesifikasi halaman |
| Design | 10 | UI system, mobile, motion, visual fingerprints, content truth |
| Anti-slop | 17 | riset upstream, 38 aturan, enam skill lenses, modes, gate, workspace |
| **Total** | **100** | **Semua satu folder ZIP** |

## Filosofi visual MBG-Track (wajib)

- **Calm premium operational editorial**: typographic hierarchy, informasi tepercaya, ruang yang bermakna, bukan mewah karena glow/gradient.
- **Mobile-first yang benar**: desain awal di 390px, diverifikasi 320/360/430/768/1440 serta zoom 200%, keyboard dan reduced-motion.
- **Tidak templated**: dashboard Kepala berbasis exception, Gizi berbasis QC, Asisten berbasis tahap, Driver berbasis stop, PIC berbasis konfirmasi, Publik berbasis cerita menu.
- **Liveliness bukan animasi sembarangan**: `ENERGY/RHYTHM/MOTION` per halaman, motif journey-line hanya untuk jalur operasional yang benar.
- **No fake behavior**: angka target ≠ aktual; menu ≠ bukti pemorsian; marker GPS stale ≠ live; foto stock ≠ bukti lapangan; user tidak boleh melihat data tenant lain.
- **Clean bukan polos**: hindari design system yang cuma seluruhnya putih+border tipis. Bangun identitas asli lewat komposisi dan interaksi nyata.

## Penggunaan pada proyek lokal yang SUDAH memiliki source code

**Jangan menimpa v2.0.** Ekstrak ZIP baru ini menjadi:

```text
P:\Tugas PPL MBG\
├── app\, database\, resources\, routes\, ...    (Laravel existing)
├── MBG-Track-Documentation\                      (dokumentasi lama, READ ONLY)
└── MBG-Track-Documentation-v3\                   (ZIP BARU, READ ONLY sesudah ekstrak)
```

Lihat `docs/anti-slop/WORKSPACE-ADOPTION.md` sebelum meminta Gemini/Claude/Codex bekerja. Source code Laravel tetap **langsung pada** `P:\Tugas PPL MBG`, tidak pada subfolder `MBG-Track`. Semua file dokumentasi lama dan baru hanya dibaca oleh agent. Laporan progres di `P:\Tugas PPL MBG\HANDOFF-LOG.md` dan `DECISION-LOG.md` **di luar** folder dokumentasi.

Menurut **laporan agent yang diberikan user**, Sprint 0 dan PBI-01 telah dikembangkan (39 test/303 assertions). Paket v3 **belum memeriksa source Windows secara independen**. Perubahan selanjutnya: audit visual *After* K02, lalu PBI-02 K03 dalam mode *During*, tanpa re-bootstrap Laravel atau rewrite massal.

## Bacaan penting menurut tugas

| Tugas | Dokumen minimum |
|---|---|
| Mulai desain satu halaman | `DESIGN.md`, `RULE-MATRIX.md`, page Section 15, `DURING-WORKFLOW.md` |
| Audit halaman existing | `AFTER-AUDIT.md`, `DELIVERY-GATE.md`, screenshot app nyata |
| Form/CRUD Laravel | PBI, Role Matrix, Data Dictionary, page spec, `skills/HUMAN.md` |
| Halaman Driver GPS | D03/D04, GPS-MAPS-ETA, `skills/LAYOUT-MOBILE.md`, `MOTION-STATES-CATALOG.md` |
| Publik | U01–U04, `CONTENT-TRUTH-PROTOCOL.md`, Security/NFR |
| Komentar source code | `skills/CODE-COMMENTS.md` (komentar saja, tidak memodifikasi executable) |
| Mengatur AI agent | `AI-ROUTER.md`, profil model, `INSTALLATION-AND-AGENTS.md` |
| Menguji selesai | `DELIVERY-GATE.md`, `ANTISLOP-ACCEPTANCE-MATRIX.md`, test/screenshots |

## Batas sumber dan klaim

- Sumber repo anti-slop dibaca pada GitHub branch `main` per 12-10-2026. Version README menyebut v3.2.20 saat pemeriksaan; perilaku installer/paket bergantung versi client dan belum dijalankan di komputer user.
- Ini adaptasi **prinsip**, bukan plugin upstream yang terpasang. Referensi asli/lisensi MIT dicatat di `docs/anti-slop/UPSTREAM-RESEARCH.md` dan `UPSTREAM-INVENTORY.md`.
- Desain dan source lama dipertahankan agar tidak ada perubahan schema/role di luar persetujuan. Status "premium", "responsive", "secure", "WCAG AA", "DONE" membutuhkan bukti dari app nyata, bukan hanya ZIP spesifikasi.
- PBI tetap **12**, role tetap **5 internal + publik**, 34 halaman adalah **dokumen fungsional unik**, bukan jumlah pasti route Blade yang akan dibuat.
- 1.026 porsi pada contoh simulasi bukan angka operasional resmi. Sistem bukan milik BGN atau SIPGN.
