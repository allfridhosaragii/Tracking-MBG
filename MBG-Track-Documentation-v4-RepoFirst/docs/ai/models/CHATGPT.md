# Profil Agent CHATGPT — MBG-Track

> **Riset terakhir:** 12 Oktober 2026; sumber primer terlampir. File ini adalah prosedur kerja yang dirancang untuk tim, **bukan** klaim bahwa model memiliki memori permanen atau kemampuan sempurna. Setiap versi, mode dan entitlement diperiksa ulang sebelum dipakai.

## A. Identitas dan bukti lineup OpenAI
1. GPT-6 di ChatGPT (rilis 7 Oktober 2026); GPT-6 Sol bagi sejumlah plan berbayar dan Luna bagi Free/Go menurut catatan produk; Pro menggunakan jalur GPT-6 Astra. Plan dan workspace menentukan menu yang benar-benar muncul.
2. Tingkat ChatGPT terverifikasi: Instant, Medium, High, Extra High; Pro menjadi pilihan lain pada entitlement tertentu. Free/Go bisa melihat Think; kemampuan ini tidak menyamakan semua model backend.
3. GPT-5.6 dan GPT-5.5 dapat tetap tersedia sebagai pilihan legacy pada beberapa akun; jangan otomatis mengganti tanpa memastikan label di akun.
4. “GPT-6” pada UI ChatGPT bukan bukti bahwa sebuah endpoint API tertentu bernama `gpt-6` tersedia untuk developer. Pisahkan produk ChatGPT, OpenAI API, Codex dan Work.

## B. Kelebihan yang ingin dimanfaatkan (hipotesis kerja)
Gunakan untuk meninjau requirement lintas banyak role, mengidentifikasi konflik tujuan bisnis, membuat review/checklist dan mengorganisasi keputusan desain; ini rekomendasi penugasan, bukan bukti model lebih baik dari pesaing.

**Tugas yang cocok untuk pilot internal:**
1. Review integritas lintas PBI 01–12 dan page registry.
2. Audit non-functional, privacy, otorisasi antar-tenant dan negative tests.
3. Mengidentifikasi asumsi lapangan BGN yang tak disokong sumber.
4. Merangkum perubahan arsitektur ke ADR/handoff tanpa mengarang hasil.

## C. Mode, level berpikir, dan pemilihan rasional
| Mode/model atau tingkat effort | Kegunaan yang disarankan |
| --- | --- |
| Instant | Cek cepat dokumen/FAQ; bukan penentu keamanan final |
| Medium | Penelusuran dependensi dan review default |
| High | Konflik arsitektur / audit multi-file |
| Extra High | Keputusan kompleks setelah tests atau banyak batasan |
| Pro / Astra | Tugas sangat berat jika opsi tersedia di plan; validasi terhadap keluaran karena tidak kebal salah |

**Aturan:** Mode yang meningkatkan reasoning sering juga meningkatkan latensi/biaya dan **tidak menjamin ketepatan**. Gunakan level lebih tinggi hanya bila hasil tes memperlihatkan peningkatan yang relevan.

## D. Potensi kelemahan/risiko dan pagar pengaman
- Bisa memberi jawaban yakin walau akses filesystem/web belum diverifikasi; gunakan git diff, migration dan tests nyata.
- Alur panjang memicu kehilangan batasan awal; baca sumber kebenaran dan sebut ulang invariants.
- Perbedaan ChatGPT app, Codex CLI dan OpenAI API sering membingungkan versi/model/mode; cek produk yang benar.
- Jawaban yang indah bukan bukti status proyek LOCKED/TESTED tanpa verifikasi.

## E. Instruksi praktis khusus model
Untuk tugas perancangan, minta matriks konflik + alternatif + alasan + berkas terdampak. Untuk review kode, minta mengaitkan klaim dengan file/baris dan test yang dijalankan; jika tool tidak tersedia, tulis BELUM DIVERIFIKASI.

## F. Kesalahan yang harus dicegah
Jangan meminta ChatGPT sekadar "ingat semua file". Tambahkan path presisi dan commit; ChatGPT percakapan tidak otomatis membaca AGENTS.md di folder lokal.

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
Kamu bertugas pada MBG-Track sebagai CHATGPT dengan scoped assignment.
Baca AGENTS.md, docs/ai/models/CHATGPT.md, docs/pages/<PAGE>.md,
PBI terkait, ROLE-PERMISSION-MATRIX, STATUS-WORKFLOWS,
DATA-DICTIONARY, API-CONTRACTS.
Implementasikan task sekecil mungkin sesuai scope yang ditugaskan.
Jangan mengubah PBI/role/schema tanpa approval.
Verifikasi dengan tests nyata, laporkan exit status dan file berubah.
Jika informasi belum ada atau bertentangan, tandai BLOCKED bukan mengarang.
```

## I. Sumber resmi primer dan update
- [https://help.openai.com/en/articles/6825453-chatgpt-release-notes](https://help.openai.com/en/articles/6825453-chatgpt-release-notes)
- [https://help-lb.openai.com/en/articles/20001354-gpt-6-and-other-models-in-chatgpt](https://help-lb.openai.com/en/articles/20001354-gpt-6-and-other-models-in-chatgpt)
- [https://platform.openai.com/docs/models](https://platform.openai.com/docs/models)

> **Kejujuran riset:** Ini snapshot fitur yang relevan untuk coding per tanggal di atas, bukan inventaris semua model audio/video/embedding dunia. Klaim perbandingan yang memerlukan benchmark independen diberi status **belum tervalidasi untuk repo MBG-Track**; lihat evaluasi internal di `AI-ROUTER.md`.

## J. Snapshot terverifikasi ulang 12 Oktober 2026 (pembaruan v2)

**Katalog/ID yang relevan, perbedaan produk:** GPT-6 Astra (`gpt-6-astra`), GPT-6.1 Sol (`gpt-6.1-sol`), GPT-6 Luna (`gpt-6-luna`) pada API; label ChatGPT/Codex/Work bisa memetakan snapshot berbeda. Model ChatGPT GPT-6 tersedia tergantung plan; pilihan Instant/Thinking UI tidak identik dengan `reasoning.effort` API.

**Modes/effort dan batas dukungan:** ChatGPT UI: Instant dan pilihan Medium/High/Extra High bila paket mendukung; Think untuk Free/Go. API reasoning effort bersifat **model-dependent**, kemungkinan `none/minimal/low/medium/high/xhigh/max`; `gpt-6-astra` dan `gpt-6.1-sol` tidak mendukung `none`. API tool calling yang kompleks utamakan Responses API.

**Kelebihan yang akan dimanfaatkan sebagai hipotesis kerja:** Peran yang layak diuji: arsitektur, konsistensi banyak PBI, review tradeoff, audit kontrak lintas komponen. Ini hipotesis penugasan, bukan klaim benchmark independen.

**Failure mode & guardrail konkret:** Risiko: menulis rencana panjang tanpa kode, menganggap docs telah dibaca padahal tidak, mencampur label ChatGPT dan ID API, mengisi gap kebijakan BGN dengan asumsi. Pengaman: buka file nyata, catat path/commit, lakukan test.

**Sumber primer/penelusuran tanggal ini:**
- https://developers.openai.com/api/docs/guides/latest-model
- https://developers.openai.com/api/docs/guides/reasoning
- https://developers.openai.com/api/docs/changelog

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
**Scope pilihan tim:** review lintas PBI, desain, keamanan. Ini bukan bukti benchmark bahwa model lebih unggul; ketersediaan versi/mode tetap mengikuti verifikasi client aktual.

**Mandatory reading (task scoped):** `DESIGN.md` → `docs/anti-slop/RULE-MATRIX.md` → `docs/anti-slop/DURING-WORKFLOW.md` atau `AFTER-AUDIT.md` → satu page spec → `DELIVERY-GATE.md`. Jangan menghafal larangan tanpa memahami Design Read.

**Instruksi task:** Untuk setiap permintaan, petakan PBI→page→role→data fields dan tandai apakah bukti berasal dari dokumen atau aplikasi live. Jalankan After audit bila hanya diminta mereview screenshot/code existing; jangan memberi nilai 100% atau mengklaim browser diuji tanpa log.

**Guard kelemahan:** Risiko: jawaban generik tentang desain bagus padahal screenshot belum ada; arahkan reviewer ke `DESIGN.md` dan per-role fingerprints, mintalah proof R-35. Hindari menciptakan fitur/halaman baru hanya untuk terlihat lengkap.

**Definisi selesai:** file nyata + pass/fail test commands + link screenshot nyata mobile/desktop + event/route actions bekerja + empat blok Delivery Gate dengan bukti. Jika tidak punya tools browser, laporkan `NOT VISUALLY VERIFIED`. Jangan menandai 100% hanya karena dokumen atau source tampak benar.

**Protected workspace:** Semua dokumen lama `P:\Tugas PPL MBG\MBG-Track-Documentation` dan v3 setelah ekstrak **READ ONLY**. Log implementasi ditulis ke project root Laravel di luar kedua folder dokumentasi.

## v4.0 mandatory original antislop rule for CHATGPT
The repo `https://github.com/miqdadbadjuber/anti-slop` original skill source (pinned `9685ca7eac70283d083fc23baa9a88e7d3cd93c4`) supersedes any adapted v3 checklist for evaluating UI/copy/mobile accessibility. When this model is operated through a code client, **verify which skill directory that client actually loads**; merely reading `docs/ai/models/CHATGPT.md` does not install antislop. For Antigravity/Kimi use Laravel-root `.agents/skills`; for other clients follow original upstream `GUIDE.md`. Before UI tasks load original core plus relevant skill, v4 `DESIGN.md` and page contract; then produce 4-block Delivery Gate with evidence. Do not claim model-specific guaranteed performance or “zero slop”.
