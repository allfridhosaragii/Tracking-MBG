# Source of truth — anti-slop upstream first

Repo: https://github.com/miqdadbadjuber/anti-slop
Pinned HEAD: `9685ca7eac70283d083fc23baa9a88e7d3cd93c4`. License: MIT, copyright Miqdad Badjuber (2026).

## Sumber primer file yang wajib digunakan agent

| Concern | Berkas upstream asli (pasca-install) | Pemakaian |
|---|---|---|
| Core | `.agents/skills/antislop/SKILL.md` | Seluruh tugas UI/copy termasuk mekanisme During/After dan 4-block gate |
| UI | `.agents/skills/antislop-ui/SKILL.md` | Komposisi, warna, spacing, hierarchy, motion |
| Copywriting | `.agents/skills/antislop-copywriting/SKILL.md` | Copy Indonesia, status jujur, CTA khusus pekerjaan |
| Human | `.agents/skills/antislop-human/SKILL.md` | WCAG, fokus, states, penggunaan keyboard |
| Mobile | `.agents/skills/antislop-layoutmobile/SKILL.md` | Reflow HP, form dan keyboard, overflow |
| Code | `.agents/skills/antislop-code/SKILL.md` | Kualitas komentar, bukan perubahan logic |

Skill asli **tidak ada di ZIP sebelum installer dijalankan**; ZIP menyediakan spesifikasi adaptasi, skrip pemasang pin, dan entrypoint. Setelah script dijalankan pada mesin user, semua skill asli berada di root Laravel di `.agents/skills` dan semua 59 file upstream berada di `.anti-slop-upstream` pada commit yang sama.

## Kewenangan domain vs kualitas visual
- Produk: `../product/PBI-BACKLOG.md`, `../business/ROLE-PERMISSION-MATRIX.md`, `../technical/DATA-DICTIONARY.md`.
- Visual direction: `../../DESIGN.md` (tetap original MBG).
- Filter: **file upstream original**, bukan `../anti-slop/RULE-MATRIX.md`. Matriks adaptasi v3 hanya catatan sekunder.
- Bukti eksekusi: browser/PHP/Vite dan empat blok Delivery Gate yang dihasilkan setelah pengujian.

## Contoh konflik dan resolusi
- Upstream R-02 membatasi em dash **copy yang agent buat**, bukan label batch/input kode/dokumen historis yang tidak ditampilkan.
- Upstream R-21 mengharuskan alasan tema; MBG light-first untuk keterbacaan lapangan adalah arah spesifik; jangan membuat dark toggle setengah jadi.
- Upstream R-23 melarang aset final diada-adakan; user sudah menentukan art direction, **belum** memberikan lisensi foto/logo pemerintah. Gunakan placeholder yang jujur.
- Upstream R-33 melarang rewriting CSS via patch scripts; installer ini hanya menyalin skill, bukan melakukan patch code UI.
- Data simulasi 1.026 selalu diberi label simulasi; angka itu bukan realisasi harian.
