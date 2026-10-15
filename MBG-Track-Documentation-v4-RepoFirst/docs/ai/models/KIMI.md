# Profil Agent KIMI — MBG-Track

> **Riset terakhir:** 12 Oktober 2026; sumber primer terlampir. File ini adalah prosedur kerja yang dirancang untuk tim, **bukan** klaim bahwa model memiliki memori permanen atau kemampuan sempurna. Setiap versi, mode dan entitlement diperiksa ulang sebelum dipakai.

## A. Identitas dan bukti lineup Moonshot AI
1. Kimi web menawarkan K2.6 (Standard/Advanced), K3 (Standard/Advanced/Extreme), dan K3 Cluster (Standard/Advanced/Extreme).
2. Kimi web mengintegrasikan pengalaman percakapan dan Agent; Agent Cluster diposisikan untuk task besar/parallel. K3 Cluster bukan jaminan lebih baik untuk satu bug kecil.
3. Moonshot juga mencantumkan K2.7 Code sebagai opsi coding terpisah di laman model, namun identitas/entitlement CLI harus diverifikasi sebelum dijadikan konfigurasi.
4. K2.5 tercantum sebagai generasi terdahulu/open-source multimodal; jangan menganggap K2.5 sama dengan K3 web.
5. Model API Kimi perlu dicek dokumentasi developer; model web dan API tidak selalu memakai string ID sama.

## B. Kelebihan yang ingin dimanfaatkan (hipotesis kerja)
Rekomendasi penugasan: audit konsistensi banyak dokumen atau batch review bila tool Agent/Cluster aktif; kekuatan komparatif tetap harus diuji di repo.

**Tugas yang cocok untuk pilot internal:**
1. Audit link 76 file dan page registry.
2. Bandingkan 12 PBI terhadap 34 page dan role matrix.
3. Menemukan bagian doc yang duplikasi/kontradiktif.
4. Meringkas hasil review banyak file menjadi issue list, bukan rewrite global.

## C. Mode, level berpikir, dan pemilihan rasional
| Mode/model atau tingkat effort | Kegunaan yang disarankan |
| --- | --- |
| K2.6 Standard | Ringkasan dan FAQ cepat |
| K2.6 Advanced | Audit terbatas lebih dalam |
| K3 Standard | Umum/coding scope kecil |
| K3 Advanced | Analisis banyak file |
| K3 Extreme | Audit paling rumit bila tersedia |
| K3 Cluster Standard/Advanced/Extreme | Pekerjaan batch/parallel; satu writer per file |

**Aturan:** Mode yang meningkatkan reasoning sering juga meningkatkan latensi/biaya dan **tidak menjamin ketepatan**. Gunakan level lebih tinggi hanya bila hasil tes memperlihatkan peningkatan yang relevan.

## D. Potensi kelemahan/risiko dan pagar pengaman
- Parallel agent dapat mengubah banyak file dan saling tumpang tindih; gunakan ownership per file.
- Context besar tidak menjamin bebas drift; gunakan manifest + checklist PBI.
- Klaim hasil agents atau penelitian tanpa sumber berisiko; cross-check bukti primer.
- Extreme effort bisa boros untuk tugas sederhana, tidak berarti selalu lebih benar.

## E. Instruksi praktis khusus model
Berikan file manifest/coverage matrix. Output berupa daftar konflik dengan path+kutipan ringkas, impact, severity dan usulan perubahan. Agent cluster hanya reviewer paralel, bukan penulis 20 file sekaligus tanpa kontrol.

## F. Kesalahan yang harus dicegah
Jangan menyatakan beberapa ratus agent otomatis bekerja hanya karena K3 Cluster tersedia; fitur memerlukan produk/plan/tool yang benar-benar terhubung.

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
Kamu bertugas pada MBG-Track sebagai KIMI dengan scoped assignment.
Baca AGENTS.md, docs/ai/models/KIMI.md, docs/pages/<PAGE>.md,
PBI terkait, ROLE-PERMISSION-MATRIX, STATUS-WORKFLOWS,
DATA-DICTIONARY, API-CONTRACTS.
Implementasikan task sekecil mungkin sesuai scope yang ditugaskan.
Jangan mengubah PBI/role/schema tanpa approval.
Verifikasi dengan tests nyata, laporkan exit status dan file berubah.
Jika informasi belum ada atau bertentangan, tandai BLOCKED bukan mengarang.
```

## I. Sumber resmi primer dan update
- [https://www.kimi.com/en/help/others/model-mode-selection](https://www.kimi.com/en/help/others/model-mode-selection)
- [https://www.kimi.ai/id/ai-models/](https://www.kimi.ai/id/ai-models/)

> **Kejujuran riset:** Ini snapshot fitur yang relevan untuk coding per tanggal di atas, bukan inventaris semua model audio/video/embedding dunia. Klaim perbandingan yang memerlukan benchmark independen diberi status **belum tervalidasi untuk repo MBG-Track**; lihat evaluasi internal di `AI-ROUTER.md`.

## J. Snapshot terverifikasi ulang 12 Oktober 2026 (pembaruan v2)

**Katalog/ID yang relevan, perbedaan produk:** Kimi K3 (`kimi-k3`) diumumkan Moonshot Juli 2026; K2.6 dan K2.7 dapat muncul di produk/CLI lain tergantung availability. K3 Cluster/Swarm merupakan **mode/produk orkestrasi**, bukan otomatis model API ID baru.

**Modes/effort dan batas dukungan:** Kimi K3 API `reasoning_effort = low | high | max` menurut pengumuman resmi Moonshot; label Chat/Agent/Swarm/Cluster di UI tidak boleh dianggap identik dengan effort API. Ketersediaan paket/region/kuota perlu diperiksa saat berjalan.

**Kelebihan yang akan dimanfaatkan sebagai hipotesis kerja:** Peran uji: audit codebase panjang, gap lintas 34 halaman, traceability requirements dan reconciliation. Gunakan context window besar secara selektif, bukan menjejalkan semua file ke prompt.

**Failure mode & guardrail konkret:** Risiko resmi: K3 proaktif bisa melebar dari tugas; risiko operasional dari laporan pengguna: konsumsi kuota dan durasi agent tinggi, jangan asumsikan semua orang mengalaminya. Pengaman: scope file, budget dan max iterations, streaming, verify every diff.

**Sumber primer/penelusuran tanggal ini:**
- https://forum.moonshot.ai/t/kimi-k3-is-here-our-most-capable-model/480
- https://www.moonshot.ai
- https://platform.moonshot.ai/docs/guide/prompt-best-practice

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
**Scope pilihan tim:** audit konsistensi banyak dokumen dan page. Ini bukan bukti benchmark bahwa model lebih unggul; ketersediaan versi/mode tetap mengikuti verifikasi client aktual.

**Mandatory reading (task scoped):** `DESIGN.md` → `docs/anti-slop/RULE-MATRIX.md` → `docs/anti-slop/DURING-WORKFLOW.md` atau `AFTER-AUDIT.md` → satu page spec → `DELIVERY-GATE.md`. Jangan menghafal larangan tanpa memahami Design Read.

**Instruksi task:** Audit 34 page Section 15 terhadap 12 PBI dan lima role, temukan konflik design read/status, buat numbered findings tanpa rewrite massal. Pilih mode After untuk review source lama, minta approval nomor temuan sebelum perbaikan visual.

**Guard kelemahan:** Risiko: parallel agent melakukan edit bertabrakan; satu pemilik per file, catat commit, jangan mengubah folder dokumentasi hanya karena menemukan perbedaan.

**Definisi selesai:** file nyata + pass/fail test commands + link screenshot nyata mobile/desktop + event/route actions bekerja + empat blok Delivery Gate dengan bukti. Jika tidak punya tools browser, laporkan `NOT VISUALLY VERIFIED`. Jangan menandai 100% hanya karena dokumen atau source tampak benar.

**Protected workspace:** Semua dokumen lama `P:\Tugas PPL MBG\MBG-Track-Documentation` dan v3 setelah ekstrak **READ ONLY**. Log implementasi ditulis ke project root Laravel di luar kedua folder dokumentasi.

## v4.0 mandatory original antislop rule for KIMI
The repo `https://github.com/miqdadbadjuber/anti-slop` original skill source (pinned `9685ca7eac70283d083fc23baa9a88e7d3cd93c4`) supersedes any adapted v3 checklist for evaluating UI/copy/mobile accessibility. When this model is operated through a code client, **verify which skill directory that client actually loads**; merely reading `docs/ai/models/KIMI.md` does not install antislop. For Antigravity/Kimi use Laravel-root `.agents/skills`; for other clients follow original upstream `GUIDE.md`. Before UI tasks load original core plus relevant skill, v4 `DESIGN.md` and page contract; then produce 4-block Delivery Gate with evidence. Do not claim model-specific guaranteed performance or “zero slop”.
