# Agent integrations: Gemini, Claude, ChatGPT/Codex, Kimi, DeepSeek, GLM

> MBG-Track v3.0 | Asal inspirasi: [anti-slop oleh Miqdad Badjuber](https://github.com/miqdadbadjuber/anti-slop/tree/9685ca7eac70283d083fc23baa9a88e7d3cd93c4) (MIT, snapshot 12-10-2026). Dokumen ini adalah **adaptasi orisinal khusus MBG-Track**, bukan salinan skill resmi ataupun hasil pengujian aplikasi.

## Penting: dua pendekatan berbeda
**A. Dokumentasi v3**: dapat dibaca langsung oleh semua model tanpa plugin tambahan. `AGENTS.md`, `GEMINI.md`, `CLAUDE.md` dan profil `docs/ai/models/` adalah entry point.
**B. Upstream installer/plugin resmi**: opsional. Perintah `npx antislop-ai` dan `npx skills add miqdadbadjuber/anti-slop` mengubah file di lokasi pemasangan; **jangan jalankan otomatis** pada folder dokumentasi `MBG-Track-Documentation` atau `...-v3` yang dilindungi. Baca dahulu [upstream GUIDE](https://github.com/miqdadbadjuber/anti-slop/blob/main/GUIDE.md).

## Mode per client
| Client yang sebenarnya dijalankan | Entry instruksi | Cara aman membawa konteks anti-slop |
|---|---|---|
| Gemini CLI / Antigravity | `GEMINI.md` bila didukung; sistem agent dapat memiliki file proyek sendiri | Berikan path `P:\Tugas PPL MBG\MBG-Track-Documentation-v3\DESIGN.md` dan `docs/anti-slop/DURING-WORKFLOW.md` secara eksplisit; verify tool membaca file |
| Claude Code | `CLAUDE.md` atau AGENTS bila versi mendukung | Import referensi dokumen dari folder v3 read-only, test izin filesystem |
| Codex | `AGENTS.md` | Tambahkan perintah baca `DESIGN.md` dan rules; tidak otomatis memuat file profil CHATGPT.md |
| ChatGPT chat | File harus diunggah/dikoneksikan secara eksplisit | Jangan mengklaim bisa membaca P: lokal tanpa tool/attachment |
| Kimi Code | `AGENTS.md` / plugin sesuai client | Profil KIMI.md sebagai checklist; hindari multiagent write conflict |
| DeepSeek / GLM via API atau coding client | Mengikuti instruksi coding client yang memanggil model | Masukkan sumber aturan ke prompt/context yang nyata; file bernama model sendiri tidak otomatis terbaca |

## Install upstream opsional (contoh dari README asli)
- Installer interaktif: `npx antislop-ai`
- Skills directory: `npx skills add miqdadbadjuber/anti-slop`
- Plugin Antigravity: `agy plugin install https://github.com/miqdadbadjuber/anti-slop`
- Claude plugin: `/plugin marketplace add https://github.com/miqdadbadjuber/anti-slop` lalu `/plugin install antislop@anti-slop`
- Codex plugin: `codex plugin marketplace add miqdadbadjuber/anti-slop` lalu `codex plugin add antislop@anti-slop`
- Kimi plugin: `/plugins install https://github.com/miqdadbadjuber/anti-slop`

Ketersediaan CLI/subcommand tergantung versi client dan lingkungan Windows. **Ini dokumentasi, belum dijalankan/diuji di mesin pengguna.** Hindari menjalankan script instalasi eksternal yang belum diperiksa di project root, terutama bila menulis `AGENTS.md`, `GEMINI.md` atau direktori sistem.

## Lokasi yang harus aman
```
P:\Tugas PPL MBG\              # project Laravel existing; boleh berubah terkontrol
  MBG-Track-Documentation\       # V2 original, READ ONLY, tidak boleh disentuh
  MBG-Track-Documentation-v3\    # dokumen V3 baru, READ ONLY setelah ekstrak
  app\ resources\ routes\ ...   # source Laravel existing
```
Jangan mengekstrak ZIP v3 dengan overwrite ke folder `MBG-Track-Documentation` existing. **Nama root di ZIP berbeda sengaja**.

## Mode During / After
Mode upstream `--mode during|after|ask` jika fitur tersedia menyimpan preferensi user global, bukan memaksa AI agent lain pada repo ini. Dalam konteks MBG-Track default tugas implementasi = During; audit atas page existing = After. Jangan mengubah setting global tanpa izin pengguna.
