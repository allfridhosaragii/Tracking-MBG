# Instal dan verifikasi original anti-slop, tanpa menyentuh docs

## Cara yang direkomendasikan
Jalankan script `../../scripts/Install-AntiSlop-Upstream.ps1` dari Windows PowerShell. Git wajib tersedia. Script mengambil source dari `https://github.com/miqdadbadjuber/anti-slop`, checkout commit `9685ca7eac70283d083fc23baa9a88e7d3cd93c4`, lalu copy **keenam folder original SKILL** ke `.agents/skills/` di project root.

**Kenapa bukan tempel adaptasi?** File SKILL tetap byte-identical dari source. Perubahan upstream harus melalui review versi baru dan hash, bukan disunting diam-diam.

## Pemeriksaan setelah install
1. `git -C "P:\Tugas PPL MBG\.anti-slop-upstream" rev-parse HEAD` harus sama persis dengan PIN.
2. `P:\Tugas PPL MBG\.agents\skills\antislop\SKILL.md` harus ada, dan lima lainnya.
3. `P:\Tugas PPL MBG\AGENTS.md` harus mengarahkan agent ke original SKILL dan `MBG-Track-Documentation-v4-RepoFirst\DESIGN.md`. Jika file AGENTS sudah ada, installer tidak menimpa: lakukan merge manual kemudian verifikasi.
4. Jalankan `../../scripts/Check-AntiSlop-Integration.ps1` dari docs v4. Ini memeriksa hash skill terhadap upstream source, bukan performa browser.
5. Buka Antigravity dengan workspace root Laravel; buka sesi agent baru; minta agent menyebut SKILL original yang benar-benar telah dibaca. Jangan hanya mengandalkan agent mengaku sudah membacanya tanpa path/bukti.
6. Ketika mengerjakan 1 halaman, pakai During/After sesuai fase dan tuntaskan 4 blok Delivery Gate.

## Jika gagal
- Git atau internet error: laporkan `UPSTREAM NOT INSTALLED`. Jangan membuat SKILL palsu untuk mengisi folder.
- Conflict pada `.agents/skills`: script berhenti tanpa overwrite. Bandingkan manual, jangan force copy.
- Docs v4 belum di-root yang benar: hentikan, jangan membuat subfolder baru di tempat lain.
- Model/IDE tidak otomatis membaca skill: sebutkan full path pada prompt dan lakukan sesi baru.
