# Profil Agent GLM — MBG-Track

> **Riset terakhir:** 12 Oktober 2026; sumber primer terlampir. File ini adalah prosedur kerja yang dirancang untuk tim, **bukan** klaim bahwa model memiliki memori permanen atau kemampuan sempurna. Setiap versi, mode dan entitlement diperiksa ulang sebelum dipakai.

## A. Identitas dan bukti lineup Z.ai/Zhipu
1. Sumber resmi Z.ai dan AWS menunjukkan ketersediaan GLM-5.3 pada 2026 (AWS Bedrock mencatat 5 Oktober 2026) sebagai peningkatan GLM-5.2 sebagai peningkatan coding/agentic dari GLM-5.2, dengan model ID `glm-5.3` pada contoh resmi.
2. GLM-5.3 hanya mendukung thinking `enabled` dengan `reasoning_effort` `low`, `high`, `max` dan default `max`. Mengirim `thinking.disabled` menyebabkan error.
3. GLM-5.2 generasi sebelumnya mendukung long-context dan menurut rilisnya effort High/Max pada Coding Plan. Quick Start Z.ai bisa tertinggal menyebut 5.2; gunakan blog 5.3 untuk parameter baru.
4. GLM-5V-Turbo adalah model visual coding yang berbeda; tugas utama Laravel tidak membutuhkan model vision bila tidak ada input gambar.
5. Ketersediaan lewat ZCode, Coding Plan, API, Claude Code compatibility berbeda; periksa model yang benar-benar tersedia di tool.

## B. Kelebihan yang ingin dimanfaatkan (hipotesis kerja)
Rekomendasi implementasi bug terarah/agentic coding dan review edge-case; klaim kinerja vendor harus dibandingkan lewat test MBG-Track.

**Tugas yang cocok untuk pilot internal:**
1. Memperbaiki spesifik 409 status transition.
2. Implementasi validasi upload evidence di Laravel.
3. Review scheduler GPS stale dan idempotency notification.
4. Menguji code regression untuk perubahan kecil.

## C. Mode, level berpikir, dan pemilihan rasional
| Mode/model atau tingkat effort | Kegunaan yang disarankan |
| --- | --- |
| GLM-5.3 low | Bug kecil dengan ruang lingkup eksplisit |
| GLM-5.3 high | Implementasi beberapa class dan tests |
| GLM-5.3 max | Tugas agentic kompleks; default API menurut rilis |
| GLM-5.3 thinking disabled | TIDAK DIDUKUNG |
| GLM-5.2 High/Max | Generasi sebelumnya menurut blog; periksa model/current API |

**Aturan:** Mode yang meningkatkan reasoning sering juga meningkatkan latensi/biaya dan **tidak menjamin ketepatan**. Gunakan level lebih tinggi hanya bila hasil tes memperlihatkan peningkatan yang relevan.

## D. Potensi kelemahan/risiko dan pagar pengaman
- Legacy client mungkin masih memilih GLM-5.2 dan tidak menerima parameter 5.3; inspect config.
- High/Max thinking memperlama dan memperbesar perubahan; batasi scope.
- Model dapat menyentuh API berbeda saat dipakai melalui tool “Claude Code” kompatibel; jangan menganggap otomatis Anthropic native.
- Benchmark release berasal vendor; keberhasilan repo hanya dibuktikan test.

## E. Instruksi praktis khusus model
Validasi model di config lebih dulu. Gunakan patch kecil, tests, perbaiki berdasarkan error nyata. Jangan menaikkan effort demi “jaminan sempurna”.

## F. Kesalahan yang harus dicegah
Jangan menyalin instruksi “thinking disabled” dari panduan GLM lama ke GLM-5.3.

## G. Checklist sebelum mengaku selesai
1. Baca `AGENTS.md` dan spesifikasi task halaman/PBI yang benar; jangan mengarang aturan bisnis dari folder lama.
2. Verifikasi model ID, mode, runtime/CLI, provider dan tanggal dari sumber resmi; laporkan jika menu aktual berbeda.
3. Scope file dan kontrak API tidak melebar tanpa persetujuan.
4. Test `401/403` tenant, state machine, idempotency, error boundary, dan public privacy pada perubahan relevan.
5. Pastikan docs/file/command benar-benar dilihat, bukan dihalusinasikan.
6. Serahkan diff, perintah tes dan hasil, risiko, serta isi `HANDOFF-LOG.md`; jangan mengaku sudah test bila tidak.
7. Jangan menaruh secrets ataupun data personal sekolah/kurir dalam prompt publik atau log.

## H. Prompt siap pakai
```text
Kamu bertugas pada MBG-Track sebagai GLM dengan scoped assignment.
Baca AGENTS.md, docs/ai/models/GLM.md, docs/pages/<PAGE>.md,
PBI terkait, ROLE-PERMISSION-MATRIX, STATUS-WORKFLOWS,
DATA-DICTIONARY, API-CONTRACTS.
Implementasikan task sekecil mungkin sesuai scope yang ditugaskan.
Jangan mengubah PBI/role/schema tanpa approval.
Verifikasi dengan tests nyata, laporkan exit status dan file berubah.
Jika informasi belum ada atau bertentangan, tandai BLOCKED bukan mengarang.
```

## I. Sumber resmi primer dan update
- [https://z.ai/blog/glm-5.3](https://z.ai/blog/glm-5.3)
- [https://docs.z.ai/guides/overview/quick-start](https://docs.z.ai/guides/overview/quick-start)
- [https://z.ai/blog/glm-5.2](https://z.ai/blog/glm-5.2)

> **Kejujuran riset:** Ini snapshot fitur yang relevan untuk coding per tanggal di atas, bukan inventaris semua model audio/video/embedding dunia. Klaim perbandingan yang memerlukan benchmark independen diberi status **belum tervalidasi untuk repo MBG-Track**; lihat evaluasi internal di `AI-ROUTER.md`.

## J. Snapshot terverifikasi ulang 12 Oktober 2026 (pembaruan v2)

**Katalog/ID yang relevan, perbedaan produk:** GLM-5.3 (`glm-5.3`) tercantum di blog Z.ai dan AWS Bedrock dengan tanggal rilis 5 Oktober 2026; GLM-5.x versi sebelumnya tersedia bergantung endpoint. Nama model di Bedrock punya prefix/provider ID lain.

**Modes/effort dan batas dukungan:** GLM-5.3: `thinking.type=enabled` **wajib**; effort `low | high | max`, default `max` menurut Z.ai. `disabled` tidak valid. Periksa dukungan parameter dan akses kode untuk ZCode/Claude Code/OpenCode masing-masing.

**Kelebihan yang akan dimanfaatkan sebagai hipotesis kerja:** Peran uji: implementasi task terarah, debugging, regression, atau review agentic coding dalam scope sempit dengan acceptance criteria.

**Failure mode & guardrail konkret:** Risiko: mengirim `thinking.disabled` warisan model lama, menganggap model lintas-provider identik, atau memperluas refactor. Pengaman: pin ID endpoint, read tool status, run tests, handoff ketat.

**Sumber primer/penelusuran tanggal ini:**
- https://z.ai/blog/glm-5.3
- https://docs.aws.amazon.com/bedrock/latest/userguide/model-card-zai-glm-5-3.html
- https://docs.z.ai/guides/overview/quick-start

### J.1 Protokol tugas wajib untuk model ini
1. Ambil **satu** ticket `PBI-xx/PAGE-ID`, bukan perintah “perbaiki seluruh aplikasi”. Tulis file yang akan diubah dan yang dilarang diubah sebelum menyentuh repository.
2. Baca `AGENTS.md`, PBI, page spec, role matrix, state machine, data dictionary, API contract, dan ketujuh standar visual bila menyentuh frontend; kerjakan hanya bagian yang berhubungan.
3. Catat model ID/mode, provider, kemampuan tool, batas token/kuota pada sesi aktual. **Jangan mengaku** mode yang tidak tersedia di client itu bisa dipakai.
4. Kode wajib mematuhi batas tenant, Laravel Policy, transaksi dan audit. UI harus lolos desain pada 390px, reduced motion dan no template generik.
5. Jalankan tes yang relevan, laporkan command, exit code, output ringkas, screenshot (jika UI), file berubah, serta kegagalan yang **belum** diselesaikan.
6. Reviewer kedua harus mampu mereproduksi test di commit yang sama. Jangan mengubah status halaman menjadi `DONE` karena laporan agent semata.

### J.2 Matrik pilihan usaha penalaran (aturan internal, bukan parameter universal)
| Ukuran pekerjaan | Pilih cara kerja | Bukti output |
|---|---|---|
| Kecil: typo, label, menambah validasi tunggal | opsi cepat/default yang memang didukung model | diff pendek + smoke test |
| Sedang: form, resource controller, satu halaman | opsi default/medium atau effort setara **yang didukung** | test success/error/403 + screenshot jika UI |
| Berat: relasi multi-batch, transaksi idempotent, kasus GPS/quality hold | effort lebih tinggi jika didukung; pecah subtask | daftar asumsi + tests concurrency/security |
| Kritis: redesign schema atau keputusan keselamatan | **review manusia terlebih dahulu**, tidak otomatis jalan | ADR, migration plan, rollback, uji dampak 12 PBI |

### J.3 Kontrak hasil yang akan ditolak reviewer
- Mengganti data `UNSAFE` menjadi `SAFE` agar test hijau, atau membypass Policy dengan hide button semata.
- Berbohong bahwa migration/test sudah dijalankan tanpa tool output.
- Mengarang API endpoint BGN atau menambahkan pengumpulan data siswa/kurir di luar scope.
- Membuat frontend generik, desktop-only, animasi berlebihan, atau map “live” tanpa timestamp GPS yang benar.
- Mengubah lebih dari satu domain atau tabel inti tanpa rationale dan `DECISION-LOG`.


## V3 | Anti-slop operating playbook for this model
**Scope pilihan tim:** fix terarah, regression dan edge case. Ini bukan bukti benchmark bahwa model lebih unggul; ketersediaan versi/mode tetap mengikuti verifikasi client aktual.

**Mandatory reading (task scoped):** `DESIGN.md` → `docs/anti-slop/RULE-MATRIX.md` → `docs/anti-slop/DURING-WORKFLOW.md` atau `AFTER-AUDIT.md` → satu page spec → `DELIVERY-GATE.md`. Jangan menghafal larangan tanpa memahami Design Read.

**Instruksi task:** Gunakan error reproduce berbasis route + state. Saat memperbaiki frontend, jangan patch via scripted string replace (R-33). Setelah fix jalankan test subset dan full suite, `npm run build`, screenshot affected viewport.

**Guard kelemahan:** Risiko: bugfix cepat sering mengubah style global tanpa alasan dan menambahkan fallback palsu; gunakan diff sekecil mungkin dan bukti Delivery Gate.

**Definisi selesai:** file nyata + pass/fail test commands + link screenshot nyata mobile/desktop + event/route actions bekerja + empat blok Delivery Gate dengan bukti. Jika tidak punya tools browser, laporkan `NOT VISUALLY VERIFIED`. Jangan menandai 100% hanya karena dokumen atau source tampak benar.

**Protected workspace:** Semua dokumen lama `P:\Tugas PPL MBG\MBG-Track-Documentation` dan v3 setelah ekstrak **READ ONLY**. Log implementasi ditulis ke project root Laravel di luar kedua folder dokumentasi.

## v4.0 mandatory original antislop rule for GLM
The repo `https://github.com/miqdadbadjuber/anti-slop` original skill source (pinned `9685ca7eac70283d083fc23baa9a88e7d3cd93c4`) supersedes any adapted v3 checklist for evaluating UI/copy/mobile accessibility. When this model is operated through a code client, **verify which skill directory that client actually loads**; merely reading `docs/ai/models/GLM.md` does not install antislop. For Antigravity/Kimi use Laravel-root `.agents/skills`; for other clients follow original upstream `GUIDE.md`. Before UI tasks load original core plus relevant skill, v4 `DESIGN.md` and page contract; then produce 4-block Delivery Gate with evidence. Do not claim model-specific guaranteed performance or “zero slop”.
