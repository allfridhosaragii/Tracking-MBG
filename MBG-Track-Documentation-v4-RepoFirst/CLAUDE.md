# CLAUDE.md — Instruksi Claude Code untuk MBG-Track

Sumber utama adalah `AGENTS.md`; aturan di sini hanya penyesuaian untuk tool yang membaca `CLAUDE.md`.

## Tugas default
Periksa service layer Laravel, transaksi database, Eloquent race condition, Form Request, Policies, dan efek lintas-role. Jangan menulis ulang spesifikasi ketika tidak diperlukan.

## Sebelum edit
- Baca `docs/ai/models/CLAUDE.md`, PBI dan halaman target, `STATUS-WORKFLOWS.md`, `DATA-DICTIONARY.md`.
- Tetapkan scope perubahan dan test. Jika migration belum ada, jangan asumsikan sudah diuji.

## Risiko yang harus dikendalikan
- Konteks panjang bisa menyembunyikan detail awal → buat checklist invariant kecil dari sumber dokumen.
- Patch besar berisiko menyentuh halaman lain → commit/patch bertahap; verifikasi diff.
- Model cakap menjelaskan kode tetapi bukan bukti kebenaran → jalankan test dan review output.
- Thinking/effort mengikuti model dan versi aktual; jangan kirim parameter thinking yang tidak kompatibel.

## Output tugas
Ringkas keputusan, file berubah, test dijalankan (command + status), risiko tersisa, dan handoff. Jangan menyertakan reasoning internal atau kredensial.

## Perintah Claude Code v2 (baca otomatis oleh client Claude Code jika didukung)
1. Terapkan aturan bersama `AGENTS.md`; sebelum perubahan UI baca `docs/design/CREATIVE-DIRECTION.md`, `MOBILE-FIRST-GUIDELINES.md`, `COMPONENT-SPEC.md` dan page spec.
2. Untuk perubahan business logic, selalu tunjukkan status sebelum/sesudah, precondition server, Policy resource dan transaksi atomic. Jangan refactor semua modul ketika tiketnya satu halaman.
3. Untuk UI, jangan izinkan desain generik dari snippet atau component library tanpa alasan; screenshot 390/1440, fokus keyboard, reduced-motion, no overflow.
4. Jangan berasumsi model/API effort dari berkas ini dipilih oleh client; cek aktual di sesi dan ikuti `docs/ai/models/CLAUDE.md`.


## V3 — Claude Code additional context
Read root `DESIGN.md` and `docs/anti-slop/skills/CORE.md` in new versioned doc folder in addition to core business/technical docs. Treat protected documentation root as READ ONLY; code/log mutations only in Laravel app root. Do not rely on agent/plugin auto-loading the `docs/ai/models/CLAUDE.md` profile. At PBI-02 prioritize role escalation test and PIC reassignment before visual polish; do not alter executable code during comment-only antislop cleanup. Record R-XX findings and real build/test evidence before delivery.

## V4 — Claude multi-client caution
Use official upstream antislop skills in a directory Claude Code actually reads and verify loading; an Antigravity `.agents/skills` install alone does NOT prove Claude native loading. Original antislop gate takes priority over v3 summary. Read `DESIGN.md` and preserve protected documentation. No source code changes from comment-only skill.
