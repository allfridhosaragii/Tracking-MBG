# AGENTS.md — Kontrak kerja bersama enam AI

## Misi
Bangun prototype MBG-Track dengan Laravel, bukan pengganti SIPGN. Patuh pada 12 PBI, kebutuhan kampus, privasi sekolah/kurir, dan fakta BGN yang telah diverifikasi.

## Urutan sumber kebenaran
1. `docs/product/REQUIREMENTS-AND-SCOPE.md`: cakupan/pengecualian.
2. `docs/product/PBI-BACKLOG.md`: hasil bisnis dan acceptance criteria.
3. `docs/business/ROLE-PERMISSION-MATRIX.md`: cakupan akses per tindakan.
4. `docs/business/STATUS-WORKFLOWS.md`: satu aturan transisi domain.
5. `docs/technical/DATA-DICTIONARY.md`: field, constraint, transaksi.
6. `docs/technical/API-CONTRACTS.md`: kontrak request/response.
7. `docs/pages/PAGE-REGISTRY.md` + spesifikasi halaman masing-masing.
8. `docs/project/DECISION-LOG.md`: alasan dan perubahan yang disetujui.

## Wajib sebelum edit
- Baca file target, PBI terkait, role, status, schema dan tes; jangan membaca seluruh 76 file jika tidak relevan.
- Buat ringkasan perubahan dan dampaknya, lalu sentuh file sekecil mungkin.
- Jika spesifikasi saling bertentangan: **hentikan keputusan yang irreversible**, usulkan perbaikan dan catat di Decision Log, bukan memilih diam-diam.
- Tidak boleh mengarang prosedur pemerintah, jam kedatangan, kandungan gizi, nilai benchmark AI atau ketersediaan API.

## Dilarang
- Mengubah 12 PBI, role, izin, relasi penting, atau data publik tanpa persetujuan manusia.
- Menghapus histori transaksi, membagikan foto bukti/GPS presisi ke publik, menyimpan identitas siswa individual tanpa alasan.
- Membuat fitur yang memerlukan akses Internet seolah-olah pasti selalu tersedia; mengklaim browser pasti tracking saat background.
- Menambahkan model API atau credential palsu; API key hanya dalam `.env`, jangan commit.
- Mengedit file pekerjaan agent lain tanpa koordinasi dan melihat `HANDOFF-LOG.md`.
- Mengatakan “tested” jika belum menjalankan pengujian.

## Cara implementasi
1. Tetapkan task, PBI, halaman, dependensi dan acceptance criteria.
2. Baca kode terbaru. Tidak mengasumsikan direktori/migration yang belum ada.
3. Ubah migration/model/form request/policy/service/controller/view secukupnya.
4. Buat Feature/Unit tests termasuk 403 lintas sekolah/SPPG dan kasus status ilegal.
5. Jalankan perintah tersedia, laporkan exit code serta kasus yang gagal.
6. Perbarui spesifikasi bila ada perubahan kontrak yang disetujui.
7. Isi `docs/project/HANDOFF-LOG.md` dengan ringkasan, file, tes dan isu.

## Standar acceptance untuk semua halaman
Authorized; scope benar; invalid input ditolak; duplicate submit idempotent; empty/loading/error jelas; perubahan audit; notifikasi hanya role yang relevan; tidak membocorkan data internal; tes mencakup happy path dan failure.

## Status agent
`PROPOSED` → `APPROVED` → `IMPLEMENTING` → `REVIEW` → `TESTED` → `MERGED`. Tidak ada klaim merged tanpa commit/hasil git aktual.

## Design contract v2.0 — wajib untuk setiap agent
- **Mobile first 390px**, uji 320/360/430/768/1440; PIC/driver/asisten/publik dibangun dari task prioritas, bukan sidebar desktop yang dipersempit.
- **Original premium:** visual tenang, kontras jelas, ruang bermakna; larang AI slop, WordPress/admin starter look, neon/gradasi berlebihan, card identik di semua role. Lihat `docs/design/CREATIVE-DIRECTION.md`.
- Baca `docs/design/UI-DESIGN-SYSTEM.md` dan `COMPONENT-SPEC.md` untuk tokens/variants; jangan hardcode desain acak per Blade view.
- Motion hanya memberi feedback benar, menghormati reduced motion, dan tidak menyatakan sukses sebelum backend commit. Lihat `MOTION-GUIDELINES.md`.
- Kode UI tidak selesai tanpa screenshot 390px+1440px, pemeriksaan 320px dan gate `docs/design/UI-QUALITY-CHECKLIST.md`. Jika screenshot/tool tidak tersedia, tulis **NOT VISUALLY VERIFIED**.
- Jangan memodifikasi 12 PBI atau menambah entitas baru tanpa keputusan tertulis. Stop jika data kontrak saling bertentangan; jelaskan konflik dan usulan.
- Larangan penting: foto internal/GPS driver tidak tampil di publik; tidak ada profil siswa; label `DEMO/SIMULASI` untuk data uji.
- Satu AI menulis satu file/fitur pada satu waktu; semua model wajib handoff/commit, bukan mengandalkan percakapan yang tidak dibagikan.


## V3 — Protected workspace override (MENGGANTIKAN instruksi v2 yang memperbolehkan edit docs)
**Lokasi source Laravel sebenarnya:** `P:\Tugas PPL MBG`. Dokumen asli `P:\Tugas PPL MBG\MBG-Track-Documentation` serta v3 `P:\Tugas PPL MBG\MBG-Track-Documentation-v3` tidak boleh diedit/ditambah/dihapus/di-rename/digunakan sebagai installer target. Ketika teks v2 di atas meminta update `docs/project/HANDOFF-LOG.md` atau `DECISION-LOG.md`, **JANGAN lakukan di dalam folder dokumentasi**; tulis ke `P:\Tugas PPL MBG\HANDOFF-LOG.md` dan `DECISION-LOG.md` setelah memeriksa file existing.

**Aturan komposisi wajib:** mulai `DESIGN.md` v3 dan Section 15 pada satu page spec. Mode During untuk fitur baru dan After untuk review existing. Selalu cek `docs/anti-slop/RULE-MATRIX.md` dan `docs/anti-slop/DELIVERY-GATE.md`; setiap keputusan visual penting satu alasan, setiap claim PASS satu bukti.

**Scope & status nyata:** Sprint 0 dan PBI-01 telah **dilaporkan** dikerjakan Gemini; jangan bootstrap ulang Laravel atau membangun K02 dari nol. Verifikasi Git, tests dan screenshot sebelum menganggap selesai. PBI-02 K03 adalah next task. Jangan mengubah jumlah 12 PBI, 34 halaman unik atau data dictionary karena anti-slop.

**Konflik instruksi:** aturan workspace read-only v3 ini lebih baru dan lebih ketat daripada arah logging di isi v2. Domain policy dan keselamatan pangan tetap menang atas gaya visual; antislop adalah quality filter, bukan pengganti kontrak bisnis.

## V4 — upstream repo is the PRIMARY quality authority (replaces v3 adaptation as the primary antislop source)
- The actual upstream **original** must be present under Laravel-root `.agents/skills/antislop/SKILL.md`; read it before any UI/copy design. Also read original `antislop-ui`, `antislop-human`, `antislop-layoutmobile`, `antislop-copywriting` as applicable. Missing original = `UPSTREAM NOT INSTALLED`, not approved.
- Existing `docs/anti-slop/RULE-MATRIX.md` and tailored lenses remain **secondary notes only**, not a replacement for primary original skills. Read `UPSTREAM-FIRST.md`, `docs/upstream/SOURCE-OF-TRUTH.md`, `docs/upstream/DELIVERY-EVIDENCE.md`.
- `DESIGN.md` owns MBG visual identity. Original antislop rejects generic decisions; it does not replace a positive design brief. A page can be clean but still too sterile.
- Laravel source root `P:\Tugas PPL MBG`; ALL `MBG-Track-Documentation*` folders are READ-ONLY. User's protected original documentation must not be modified. Never run installer in documentation.
- Do not claim original skill integration until `scripts/Check-AntiSlop-Integration.ps1` validates copy hashes. This script is shipped in v4 docs, runs on user's Windows only.
- For existing UI audit choose upstream **After** mode; don't auto-apply findings until user approves specific items. New PBI pages use **During**. Always show original four-block Delivery Gate with actual evidence.
