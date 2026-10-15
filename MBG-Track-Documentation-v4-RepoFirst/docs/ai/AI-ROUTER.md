# AI Orchestration Router — Enam Keluarga Model

**Snapshot penelusuran publik:** 12 Oktober 2026. Status ketersediaan model di akun/API bisa berbeda menurut region, plan, provider, dan waktu. Model-specific `.md` di `models/` **tidak otomatis dibaca** kecuali coding client/agent diprompt menggunakannya. `CLAUDE.md` untuk Claude Code, `GEMINI.md` untuk Gemini CLI; `AGENTS.md` untuk client yang mendukung. Bukan enam AI yang otomatis ngobrol dengan menaruh enam file.

## Prinsip orkestrasi
1. **Owner manusia** memilih task/branch, menyetujui perubahan kritis dan memutuskan merge.
2. **Satu Source of Truth**: AGENTS/PBI/Role/Workflow/DB/API; model hanya memutuskan implementasi dalam batasnya.
3. **Satu writer per file pada satu waktu**. Reviewer beda model membaca diff dan menguji; jangan dua AI rewrite migration sama.
4. **Handoff eksplisit**: input task → rencana pendek → patch → test command dan hasil → diff → handoff log.
5. **Jangan membandingkan kualitas model dari klaim vendor sendiri saja.** Jalankan evaluation set yang sama (TC01–TC28), dokumentasikan komputasi, biaya/latensi, kesalahan, dan perubahan yang diperlukan.

## Routing rekomendasi, BUKAN ranking ilmiah
| Tipe tugas | Author usulan | Reviewer usulan | Gate wajib |
|---|---|---|---|
| Requirement dan konflik PBI | ChatGPT | Kimi | `kimi-k3` API; K2.x bila relevan; Agent/Swarm/Cluster adalah mode/produk berbeda | K3 `low/high/max` | Cek tool, kuota, endpoint; jangan asumsikan Cluster model ID |
| Blade/form role/halaman | Gemini | Claude | Policy + no wrong scope + tests |
| Domain action dan concurrency | Claude | DeepSeek | `deepseek-flash` = V4.1 Flash, `deepseek-v4-pro` masih tercantum; `deepseek-v4-flash` legacy redirect | thinking enabled/disabled, `low/high/max` (mapping bergantung endpoint) | Cek model & provider saat request |
| Migration/relasi/index | DeepSeek | `deepseek-flash` = V4.1 Flash, `deepseek-v4-pro` masih tercantum; `deepseek-v4-flash` legacy redirect | thinking enabled/disabled, `low/high/max` (mapping bergantung endpoint) | Cek model & provider saat request |
| Audit konsistensi 34 halaman dan 7 file desain | Kimi | `kimi-k3` API; K2.x bila relevan; Agent/Swarm/Cluster adalah mode/produk berbeda | K3 `low/high/max` | Cek tool, kuota, endpoint; jangan asumsikan Cluster model ID |
| Bug kecil terarah | GLM | Claude/Gemini | regression test/diff scope |
| Threat review/authorization | Claude atau ChatGPT | model lain yang berbeda | 401/403, leak test |

Pilihan ini adalah *pembagian kerja yang dapat diubah berdasarkan hasil pengujian internal*, bukan fakta bahwa salah satu model pasti unggul di kategori tertentu.

## Model/model UI/mode yang diverifikasi
| Keluarga | Snapshot model atau penawaran relevan | Mode/effort terverifikasi | Batas |
|---|---|---|---|
| OpenAI/ChatGPT | GPT-6 di ChatGPT; API `gpt-6-astra`/`gpt-6.1-sol`/`gpt-6-luna` | UI effort tergantung paket; API reasoning model-dependent | Bedakan ChatGPT UI, Codex, Work, API |
| Gemini | Gemini 3.8 Flash GA; 3.7 Flash, 3.6 Flash, 3.5 Flash-Lite; 3.1 Pro Preview; Live/TTS terpisah | Gemini 3.8 Flash `low/medium/high`, **tidak** `minimal` | API model list ≠ UI subscription |
| Claude | Fable 5.1, Opus 5.5, Sonnet 5.5, Haiku 5.5 | Effort `low/medium/high/xhigh/max` **bergantung model**, adaptive; Opus/Fable 5.1 tidak bisa disable thinking | Tidak semua model mendukung setiap opsi/parameter identik |
| Kimi | `kimi-k3` API; K2.x bila relevan; Agent/Swarm/Cluster adalah mode/produk berbeda | K3 `low/high/max` | Cek tool, kuota, endpoint; jangan asumsikan Cluster model ID |
| DeepSeek | `deepseek-flash` = V4.1 Flash, `deepseek-v4-pro` masih tercantum; `deepseek-v4-flash` legacy redirect | thinking enabled/disabled, `low/high/max` (mapping bergantung endpoint) | Cek model & provider saat request |
| Z.ai GLM | `glm-5.3` | thinking enabled wajib; `low/high/max` | parameter `disabled` tidak valid |

## Sumber primer (cek ulang saat task)
- OpenAI: https://help.openai.com/en/articles/6825453-chatgpt-release-notes, https://help-lb.openai.com/en/articles/20001354-gpt-6-and-other-models-in-chatgpt.
- Gemini: https://ai.google.dev/gemini-api/docs/models, https://ai.google.dev/gemini-api/docs/latest-model, https://ai.google.dev/gemini-api/docs/thinking.
- Anthropic: https://platform.claude.com/docs/en/models/overview, https://platform.claude.com/docs/en/build-with-claude/effort.
- Kimi: https://www.kimi.com/en/help/others/model-mode-selection, https://www.kimi.ai/id/ai-models/.
- DeepSeek: https://api-docs.deepseek.com/, https://api-docs.deepseek.com/guides/thinking_mode/, https://api-docs.deepseek.com/updates/.
- GLM: https://z.ai/blog/glm-5.3, https://docs.z.ai/guides/overview/quick-start.

## Benchmark internal MBG-Track (wajib untuk klaim kelebihan)
- Buat 5 task representatif: multi-tenant policy, batch count constraint, double submit receipt, realtime GPS stale, revisi halaman PIC.
- Input sama, codebase commit sama, batas waktu/token tercatat, tools sama jika tersedia.
- Ukur: tes PASS, regresi, kebocoran data, jumlah file tak relevan yang disentuh, durasi, rework.
- Jangan menilai “paling pintar” berdasarkan prompt panjang atau jawaban meyakinkan tanpa eksekusi tes.

## Prompt handoff standar
```
Task: PBI-XX / PAGE-ID / issue
Read: AGENTS.md, <specific PBI>, <specific page>, STATUS-WORKFLOWS,
      ROLE-PERMISSION-MATRIX, DATA-DICTIONARY, API-CONTRACTS.
Repo commit/branch: <actual>
Expected change: <files/behavior>
Forbidden change: no PBI/role/schema outside scope
Tests: <exact commands>
Deliver: diff + tests + unresolved risks + HANDOFF-LOG update
```

## Pengendali kegagalan agent yang umum
- Halusinasi versi: cek docs bertanggal; jangan gunakan model ID dari ingatan.
- Context overflow: baca scope lokal; tidak masukkan seluruh 76 file ke setiap prompt.
- Tool output tidak dipercaya: periksa file nyata dan exit code.
- Overengineering: Laravel monolith, bukan 6 microservice buatan enam AI.
- Konflik Git: satu writer; reviewer membuka PR/diff, bukan rewrite penuh.
- Data pribadi: jangan mengirim foto atau koordinat pengguna nyata ke banyak AI tanpa izin.

## Orkestrasi v2: Design quality gate lintas enam AI
**Satu writer per halaman/komponen**, satu reviewer lain, satu pemilik merge. Sebelum frontend dibuat, reviewer harus membaca tujuh file `docs/design/` dan halaman terkait; task yang hanya berbunyi “buat UI premium” ditolak tanpa komposisi konten, role, state, dan screenshot target.

### Urutan kolaborasi yang dipilih
1. ChatGPT (atau reviewer requirement) menulis ticket satu domain `PBI-ID/PAGE-ID`, data, kontrak states, deskripsi mobile awal.
2. DeepSeek/Claude memverifikasi constraint/migration/Policy dan menyiapkan test minimal, bukan menulis ulang tampilan.
3. Gemini/GLM/Kimi atau coding agent yang tersedia mengimplementasikan *satu* halaman sesuai design system; hasil UI screenshot 390px & 1440px.
4. Reviewer berbeda mengaudit permission 401/403, edge case, no AI slop, mobile-first, actual source of state, reduced motion.
5. User/tim menyetujui merge dan mencatat hasil di `HANDOFF-LOG.md` serta `DECISION-LOG.md` bila ada perubahan desain produk.

### Tugas model bukan mode produk
- Model yang kuat pada coding **tidak otomatis** punya browser, file edit, screenshot, git, terminal, internet, atau kemampuan menghubungi agent lain. Semua kemampuan itu ditentukan **client/tool yang tersambung**.
- `Kimi Agent/Swarm`, `Claude Code`, `Gemini CLI/Antigravity`, dan `Codex` adalah konteks produk/orkestrasi, bukan sinonim dari label model API.
- Jangan memanggil enam model untuk tiap tugas jika satu model + reviewer sudah cukup; lebih banyak model bisa meningkatkan konflik dan biaya.

### Prioritas audit
P0: privasi, keselamatan pangan, status palsu atau izin lintas sekolah; P1: double-submit, angka tidak konsisten, kartu mobile menutup form; P2: motion/spacing/visual consistency. Wajib perbaiki P0 sebelum komposisi visual lain.


## V3 | Router anti-slop yang dieksekusi per tugas
- **Before coding:** ChatGPT (review scope), Gemini (implementer if available), Claude (domain reviewer), DeepSeek (database check), Kimi (doc consistency), GLM (targeted test/fix). Ini pembagian tugas, **bukan** agent yang otomatis berkomunikasi.
- **During mode** untuk PBI-02/halaman baru: implementer membaca satu page Section 15 dan deklarasi dials, lalu code+test.
- **After mode** untuk PBI-01 K02 existing: auditor membuat findings R-XX bernomor, jangan mengedit sampai temuan dalam cakupan disetujui.
- **Single writer** per file; reviewer tidak mengubah file milik implementer diam-diam. Handoff adalah artefak Git/tes/screenshot, bukan klaim percakapan.
- **Bukti akhir:** empat blok `docs/anti-slop/DELIVERY-GATE.md` di log pekerjaan Laravel root. Jangan mengubah log di documentation root yang dilindungi.
- **Pemeriksa konflik:** Brand `DESIGN.md` lebih spesifik untuk look, 12 PBI dan Policy menang untuk behavior; rule anti-slop menghalangi slop tanpa mengubah transaksi/domain. Keputusan yang bertentangan dicatat, bukan diselesaikan dengan prompt berulang.

## V4 — anti-slop original skill routing
For design/copy tasks **original upstream skill is the required first quality filter**. Route Gemini Antigravity/Kimi to Laravel-root `.agents/skills/`; other coding clients must use their installed original path per `docs/upstream/COMPATIBILITY.md`. The docs v3 adapted rules remain useful index only. PBI/domain instructions always preserve data authority; DESlGN.md governs identity. The actual original SKILL load and 4-block gate cannot be replaced by model-specific advice.
