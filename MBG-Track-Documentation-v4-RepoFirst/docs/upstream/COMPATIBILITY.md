# Kompatibilitas multiagent dan larangan duplikasi skill

| Client | Folder skill project original upstream | Entry point yang perlu dibaca |
|---|---|---|
| Gemini Antigravity | `.agents/skills/` | `AGENTS.md` |
| Kimi Code | `.agents/skills/` | `AGENTS.md` |
| Claude Code | `.claude/skills/` (via instalasi resminya bila dipakai) | `CLAUDE.md` |
| Codex | `.codex/skills/` (juga dapat membaca `.agents/skills/` sesuai versi) | `AGENTS.md` |
| Gemini CLI (legacy) | `.gemini/skills/` | `GEMINI.md` |
| DeepSeek/GLM | Bergantung coding client yang membungkus model | Entry point client aktual, bukan sekadar nama model |

Installer paket v4 **hanya** memasang satu salinan di `.agents/skills` untuk Antigravity/Kimi. Bila menjalankan Claude Code atau client lain, ikuti `GUIDE.md` upstream dan lakukan pemeriksaan duplicate skill. Jangan sembarangan menyalin ke `.claude/skills` dan `.agents/skills` sekaligus jika agent membaca kedua lokasi dan precedence tidak jelas.

Tidak ada klaim model tertentu dapat membaca file Windows ketika dipanggil melalui ChatGPT web/API tanpa akses filesystem.
