# Profil Agent DEEPSEEK — MBG-Track

> **Riset terakhir:** 12 Oktober 2026; sumber primer terlampir. File ini adalah prosedur kerja yang dirancang untuk tim, **bukan** klaim bahwa model memiliki memori permanen atau kemampuan sempurna. Setiap versi, mode dan entitlement diperiksa ulang sebelum dipakai.

## A. Identitas dan bukti lineup DeepSeek
1. Model API resmi saat penelusuran: `deepseek-flash` (versi V4.1-Flash) dan `deepseek-v4-pro` (V4 Pro).
2. `deepseek-v4-flash` dan `deepseek-v4-flash-vision-exp` adalah alias lama yang dipetakan ke V4.1 Flash menurut changelog, tidak dipakai untuk integrasi baru.
3. Thinking dapat `enabled`/`disabled`; effort `none`, `low`, `high`, `max` pada Chat API/format yang didokumentasikan; `none` berarti non-thinking. Default thinking enabled/high, cek endpoint terbaru.
4. Format kompatibel OpenAI/Anthropic tidak berarti setiap parameter sama; versi interface SDK dapat memerlukan field berbeda.
5. V4.1 Flash native multimodal dan V4 Pro berbeda; tidak perlu mengirim foto bukti asli MBG ke model untuk proyek ini.

## B. Kelebihan yang ingin dimanfaatkan (hipotesis kerja)
Dapat diberi pekerjaan query/migration review, data integrity dan test generator yang terikat aturan jelas. Tidak ada dasar menyebut pasti lebih unggul dari model lain tanpa evaluasi.

**Tugas yang cocok untuk pilot internal:**
1. Review SQL schema dan unique constraints.
2. Generate Feature tests dengan contoh porsi 340/338.
3. Optimasi query dashboard N+1 dengan alat ukur.
4. Memeriksa atomic transaction side-effects.

## C. Mode, level berpikir, dan pemilihan rasional
| Mode/model atau tingkat effort | Kegunaan yang disarankan |
| --- | --- |
| Non-thinking / none | Transformasi sederhana, tidak boleh final security review |
| Thinking low | Review scoped SQL |
| Thinking high | Kasus relasi dan concurrency kompleks |
| Thinking max | Investigasi berat jika didukung serta diukur |

**Aturan:** Mode yang meningkatkan reasoning sering juga meningkatkan latensi/biaya dan **tidak menjamin ketepatan**. Gunakan level lebih tinggi hanya bila hasil tes memperlihatkan peningkatan yang relevan.

## D. Potensi kelemahan/risiko dan pagar pengaman
- Kesalahan numerik agregat batch/stop berdampak besar walau jawaban terlihat benar; verifikasi menggunakan fixtures SQL/tests.
- Model lama atau alias membuat mode berpikir tampak berbeda; cek API model list aktual.
- Pola kode Laravel generik bisa melupakan Policy per tenant; wajib test 403.
- Thinking response/tool call butuh handling sesuai docs; jangan serialisasi field tersembunyi sembarangan.

## E. Instruksi praktis khusus model
Minta simulasi fixture dan expected SQL/DB invariant. Setiap klaim “tidak ada race” perlu diuji concurrency, bukan cukup dibaca.

## F. Kesalahan yang harus dicegah
Jangan memakai `deepseek-chat`/`deepseek-reasoner` sebagai identitas permanen model baru; menurut changelog API legacy 2026 telah dihentikan/dirutekan sementara.

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
Kamu bertugas pada MBG-Track sebagai DEEPSEEK dengan scoped assignment.
Baca AGENTS.md, docs/ai/models/DEEPSEEK.md, docs/pages/<PAGE>.md,
PBI terkait, ROLE-PERMISSION-MATRIX, STATUS-WORKFLOWS,
DATA-DICTIONARY, API-CONTRACTS.
Implementasikan task sekecil mungkin sesuai scope yang ditugaskan.
Jangan mengubah PBI/role/schema tanpa approval.
Verifikasi dengan tests nyata, laporkan exit status dan file berubah.
Jika informasi belum ada atau bertentangan, tandai BLOCKED bukan mengarang.
```

## I. Sumber resmi primer dan update
- [https://api-docs.deepseek.com/](https://api-docs.deepseek.com/)
- [https://api-docs.deepseek.com/guides/thinking_mode/](https://api-docs.deepseek.com/guides/thinking_mode/)
- [https://api-docs.deepseek.com/updates/](https://api-docs.deepseek.com/updates/)
- [https://www.deepseek.com/en/news/deepseek-v4-1-flash/](https://www.deepseek.com/en/news/deepseek-v4-1-flash/)

> **Kejujuran riset:** Ini snapshot fitur yang relevan untuk coding per tanggal di atas, bukan inventaris semua model audio/video/embedding dunia. Klaim perbandingan yang memerlukan benchmark independen diberi status **belum tervalidasi untuk repo MBG-Track**; lihat evaluasi internal di `AI-ROUTER.md`.

## J. Snapshot terverifikasi ulang 12 Oktober 2026 (pembaruan v2)

**Katalog/ID yang relevan, perbedaan produk:** Per 12 Oktober 2026 API DeepSeek menyarankan `deepseek-flash` untuk **V4.1 Flash** (rilis 10 September 2026) dan masih mencantumkan `deepseek-v4-pro`. Nama lama `deepseek-v4-flash`/`deepseek-v4-flash-vision-exp` diterima sebagai alias kompatibilitas tetapi model sebelumnya retired dan diteruskan ke V4.1 Flash; `deepseek-chat`/`deepseek-reasoner` telah diumumkan retired sejak 24 Juli 2026. Perubahan routing Pro perlu dicek karena halaman resmi berbeda saat perubahan layanan.

**Modes/effort dan batas dukungan:** Thinking enabled/disabled. Default thinking enabled dengan effort high (per docs yang diperiksa); kontrol API OpenAI style `thinking.type`, `reasoning_effort` `low/high/max`. Mapping V4 Pro/Flash berbeda; parameter sampling seperti temperature/top_p tidak berlaku pada thinking mode.

**Kelebihan yang akan dimanfaatkan sebagai hipotesis kerja:** Peran uji: review query/FK, migration, deterministic data constraints, alternative implementation plans; verifikasi di MySQL dan Feature tests, bukan hanya pseudocode.

**Failure mode & guardrail konkret:** Risiko: alias retired/parameter tidak didukung, query nampak sah tetapi join menghasilkan porsi ganda, instruksi tool reasoning-content multi-turn terabaikan. Pengaman: periksa docs API, jalankan migration, SQL EXPLAIN dan seed tests.

**Sumber primer/penelusuran tanggal ini:**
- https://api-docs.deepseek.com/updates/
- https://api-docs.deepseek.com/guides/thinking_mode/
- https://api-docs.deepseek.com/quick_start/pricing/

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
**Scope pilihan tim:** database/query/test review. Ini bukan bukti benchmark bahwa model lebih unggul; ketersediaan versi/mode tetap mengikuti verifikasi client aktual.

**Mandatory reading (task scoped):** `DESIGN.md` → `docs/anti-slop/RULE-MATRIX.md` → `docs/anti-slop/DURING-WORKFLOW.md` atau `AFTER-AUDIT.md` → satu page spec → `DELIVERY-GATE.md`. Jangan menghafal larangan tanpa memahami Design Read.

**Instruksi task:** Uji data integrity kartu dashboard: `registered_beneficiaries` vs `requested` vs `received`; test many batch/stop, unique/tenant, QC hold. Audit copy angka R-17 dan source DTO; bukan sekadar SQL sintaks.

**Guard kelemahan:** Risiko: review schema tanpa UI context menghasilkan KPI salah; konfirmasikan rumus dan unit pada page spec, tampilkan NOT VERIFIED bila MySQL test belum dijalankan.

**Definisi selesai:** file nyata + pass/fail test commands + link screenshot nyata mobile/desktop + event/route actions bekerja + empat blok Delivery Gate dengan bukti. Jika tidak punya tools browser, laporkan `NOT VISUALLY VERIFIED`. Jangan menandai 100% hanya karena dokumen atau source tampak benar.

**Protected workspace:** Semua dokumen lama `P:\Tugas PPL MBG\MBG-Track-Documentation` dan v3 setelah ekstrak **READ ONLY**. Log implementasi ditulis ke project root Laravel di luar kedua folder dokumentasi.

## v4.0 mandatory original antislop rule for DEEPSEEK
The repo `https://github.com/miqdadbadjuber/anti-slop` original skill source (pinned `9685ca7eac70283d083fc23baa9a88e7d3cd93c4`) supersedes any adapted v3 checklist for evaluating UI/copy/mobile accessibility. When this model is operated through a code client, **verify which skill directory that client actually loads**; merely reading `docs/ai/models/DEEPSEEK.md` does not install antislop. For Antigravity/Kimi use Laravel-root `.agents/skills`; for other clients follow original upstream `GUIDE.md`. Before UI tasks load original core plus relevant skill, v4 `DESIGN.md` and page contract; then produce 4-block Delivery Gate with evidence. Do not claim model-specific guaranteed performance or “zero slop”.
