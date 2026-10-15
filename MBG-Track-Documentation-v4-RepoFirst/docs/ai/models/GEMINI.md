# Profil Agent GEMINI — MBG-Track

> **Riset terakhir:** 12 Oktober 2026; sumber primer terlampir. File ini adalah prosedur kerja yang dirancang untuk tim, **bukan** klaim bahwa model memiliki memori permanen atau kemampuan sempurna. Setiap versi, mode dan entitlement diperiksa ulang sebelum dipakai.

## A. Identitas dan bukti lineup Google
1. Gemini 3.8 Flash GA, ID API `gemini-3.8-flash`, dirancang Google untuk engineering multi-langkah; context hingga 1M menurut dokumen resmi. Model API bukan otomatis opsi persis di Gemini app.
2. Thinking Gemini 3.8 Flash: `low`, `medium` (default), `high`. `minimal` tidak didukung dan menghasilkan error.
3. Rujukan daftar model API juga memuat generasi lain seperti Gemini 3.7 Flash, 3.6 Flash, 3.5 Flash-Lite, 3.1 Pro Preview; Live 3.8, Live Extended Thinking, Flash TTS/Flash-Lite TTS adalah kategori produk/modalitas berbeda, tidak relevan wajib untuk coding Laravel.
4. Gemini App dapat memiliki model picker, Deep Think, dan batasan langganan sendiri; periksa UI produk, jangan menyamakan dengan API `thinking_level`.
5. Nama model/pilihan di Gemini CLI, Antigravity dan API bergantung versi client serta API; validasi konfigurasi lokal sebelum pemanggilan.

## B. Kelebihan yang ingin dimanfaatkan (hipotesis kerja)
Dapat ditugaskan menyusun halaman Laravel/Blade dan interaksi form yang terhubung kontrak, termasuk audit struktur file luas jika context/tool mendukung. Klaim vendor tentang benchmark bukan jaminan hasil.

**Tugas yang cocok untuk pilot internal:**
1. Menerjemahkan ACTIVE-TRIP.md menjadi Blade/JS yang responsif HP.
2. Membuat form PIC dengan invalid/loading/offline states.
3. Membuat komponen dashboard internal dari read model, bukan hardcoded angka.
4. Meninjau aksesibilitas dan responsive, lalu screenshot/test browser.

## C. Mode, level berpikir, dan pemilihan rasional
| Mode/model atau tingkat effort | Kegunaan yang disarankan |
| --- | --- |
| 3.8 Flash low | Tugas kecil dan pertanyaan sederhana |
| 3.8 Flash medium | Implementasi komponen multi-file biasa |
| 3.8 Flash high | Refactor rumit, koordinasi tool yang sulit |
| 3.8 Flash minimal | TIDAK DIDUKUNG |
| 3.1 Pro preview low/medium/high | Opsi API jika benar tersedia; validasi versi |
| Live/TTS | Khusus audio/voice; tidak perlu untuk MBG-Track |

**Aturan:** Mode yang meningkatkan reasoning sering juga meningkatkan latensi/biaya dan **tidak menjamin ketepatan**. Gunakan level lebih tinggi hanya bila hasil tes memperlihatkan peningkatan yang relevan.

## D. Potensi kelemahan/risiko dan pagar pengaman
- Konteks 1M tidak menjamin semua detail diingat; gunakan dokumen per page dan checklist.
- Model bisa mengarang konfigurasi SDK/parameter generasi lama; cek docs aktual.
- Refactor frontend dapat mengubah endpoint atau role tanpa sadar; verifikasi kontrak dan tests.
- Peta interaktif bisa tampak berjalan padahal data marker mock, wajib tes koordinat server.

## E. Instruksi praktis khusus model
Setiap tugas harus memuat spesifikasi halaman dan API kontrak. Minta implementasi dengan perubahan file eksplisit, built assets/test dan screenshot mobile; jangan menerima UI dummy dengan fake success.

## F. Kesalahan yang harus dicegah
Jangan menggunakan `thinking_budget` usang untuk Gemini 3.8 bila API mengharapkan `thinking_level`. Jangan mengklaim semua modalitas live/voice dapat dipakai untuk coding web.

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
Kamu bertugas pada MBG-Track sebagai GEMINI dengan scoped assignment.
Baca AGENTS.md, docs/ai/models/GEMINI.md, docs/pages/<PAGE>.md,
PBI terkait, ROLE-PERMISSION-MATRIX, STATUS-WORKFLOWS,
DATA-DICTIONARY, API-CONTRACTS.
Implementasikan task sekecil mungkin sesuai scope yang ditugaskan.
Jangan mengubah PBI/role/schema tanpa approval.
Verifikasi dengan tests nyata, laporkan exit status dan file berubah.
Jika informasi belum ada atau bertentangan, tandai BLOCKED bukan mengarang.
```

## I. Sumber resmi primer dan update
- [https://ai.google.dev/gemini-api/docs/models](https://ai.google.dev/gemini-api/docs/models)
- [https://ai.google.dev/gemini-api/docs/latest-model](https://ai.google.dev/gemini-api/docs/latest-model)
- [https://ai.google.dev/gemini-api/docs/thinking](https://ai.google.dev/gemini-api/docs/thinking)
- [https://ai.google.dev/gemini-api/docs/generate-content/latest-model](https://ai.google.dev/gemini-api/docs/generate-content/latest-model)

> **Kejujuran riset:** Ini snapshot fitur yang relevan untuk coding per tanggal di atas, bukan inventaris semua model audio/video/embedding dunia. Klaim perbandingan yang memerlukan benchmark independen diberi status **belum tervalidasi untuk repo MBG-Track**; lihat evaluasi internal di `AI-ROUTER.md`.

## J. Snapshot terverifikasi ulang 12 Oktober 2026 (pembaruan v2)

**Katalog/ID yang relevan, perbedaan produk:** Gemini 3.8 Flash GA (`gemini-3.8-flash`) menurut dokumentasi; model lain pada katalog berbeda untuk use case dan tanggal. Pilihan Gemini CLI/Antigravity/AI Studio tidak selalu sama dengan Gemini API.

**Modes/effort dan batas dukungan:** Gemini 3.8 Flash `thinking_level = low | medium | high`, default `medium`; **tidak mendukung `minimal`**. Model Gemini lain bisa memiliki daftar tingkat berbeda. Model inference terpisah dari mode agent/tool dan produk Gemini CLI.

**Kelebihan yang akan dimanfaatkan sebagai hipotesis kerja:** Peran uji: iterasi multi-file, implementasi Blade/JS frontend, responsive probes, navigasi role dan refactor; verifikasi dengan screenshot nyata dan tests.

**Failure mode & guardrail konkret:** Risiko: UI generik meski prompt “modern”; mengubah komponen di luar scope; asumsi dukungan parameter lama `thinking_budget`; mengorbankan a11y saat animasi. Pengaman: token design, screenshot 390/1440, testing, review diff.

**Sumber primer/penelusuran tanggal ini:**
- https://ai.google.dev/gemini-api/docs/latest-model
- https://ai.google.dev/gemini-api/docs/thinking
- https://ai.google.dev/gemini-api/docs/models

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
**Scope pilihan tim:** implementasi Blade/Tailwind/JS mobile. Ini bukan bukti benchmark bahwa model lebih unggul; ketersediaan versi/mode tetap mengikuti verifikasi client aktual.

**Mandatory reading (task scoped):** `DESIGN.md` → `docs/anti-slop/RULE-MATRIX.md` → `docs/anti-slop/DURING-WORKFLOW.md` atau `AFTER-AUDIT.md` → satu page spec → `DELIVERY-GATE.md`. Jangan menghafal larangan tanpa memahami Design Read.

**Instruksi task:** Pada PBI-02 berikutnya: baca K03, existing K02 source, role policy; nyatakan Design Read K03; bangun hanya komponen visual yang memiliki route/mutation benar, tes keyboard, mobile viewport 320–1440. Lakukan During gate saat implementasi, lalu screenshot sebenarnya.

**Guard kelemahan:** Risiko: mengekstrapolasi satu app-shell/card pattern ke seluruh role. Jangan menjalankan installer upstream pada root tanpa review, jangan generate image logo/foto nyata yang tidak disetujui, jangan patch CSS via script string replacement.

**Definisi selesai:** file nyata + pass/fail test commands + link screenshot nyata mobile/desktop + event/route actions bekerja + empat blok Delivery Gate dengan bukti. Jika tidak punya tools browser, laporkan `NOT VISUALLY VERIFIED`. Jangan menandai 100% hanya karena dokumen atau source tampak benar.

**Protected workspace:** Semua dokumen lama `P:\Tugas PPL MBG\MBG-Track-Documentation` dan v3 setelah ekstrak **READ ONLY**. Log implementasi ditulis ke project root Laravel di luar kedua folder dokumentasi.

## v4.0 mandatory original antislop rule for GEMINI
The repo `https://github.com/miqdadbadjuber/anti-slop` original skill source (pinned `9685ca7eac70283d083fc23baa9a88e7d3cd93c4`) supersedes any adapted v3 checklist for evaluating UI/copy/mobile accessibility. When this model is operated through a code client, **verify which skill directory that client actually loads**; merely reading `docs/ai/models/GEMINI.md` does not install antislop. For Antigravity/Kimi use Laravel-root `.agents/skills`; for other clients follow original upstream `GUIDE.md`. Before UI tasks load original core plus relevant skill, v4 `DESIGN.md` and page contract; then produce 4-block Delivery Gate with evidence. Do not claim model-specific guaranteed performance or “zero slop”.
