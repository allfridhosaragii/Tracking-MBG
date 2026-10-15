# GEMINI.md — Instruksi Gemini CLI/Antigravity

Baca `AGENTS.md` sebagai pedoman bersama dan `docs/ai/models/GEMINI.md` sebagai profil model. Baca file halaman dari `docs/pages/PAGE-REGISTRY.md` yang ditugaskan saja.

## Peran default
Implementasi Blade, komponen frontend, interaksi form Laravel, responsive mobile driver/PIC, dan integrasi API sesuai kontrak. UI tidak boleh mengubah keputusan bisnis.

## Proteksi
- Jangan otomatis memilih library atau versi Laravel yang belum diverifikasi; baca composer.json/package.json.
- Jangan mengimplementasikan realtime “palsu”; semua status harus memakai data transaksi dan timestamp.
- Map marker hanya berdasarkan lokasi yang diterima dan ditandai stale bila kadaluarsa.
- Jangan mengirim raw latitude/longitude ke endpoint publik.
- Cetak ringkasan hasil pengujian, bukan klaim berlebihan.

## Alur
Baca kontrak → implementasi kecil → validasi role/status → pengujian → update handoff. Bila versi `GEMINI.md` ini bertentangan dengan AGENTS, laporkan dan minta keputusan.

## Perintah Gemini CLI/Antigravity v2
1. Baca `AGENTS.md`, page spec, `docs/design/CREATIVE-DIRECTION.md`, `UI-DESIGN-SYSTEM.md`, `MOBILE-FIRST-GUIDELINES.md`, `MOTION-GUIDELINES.md` sebelum mengimplementasi frontend.
2. **Tolak default template.** Mulai dari konten page-specific, susun komposisi 390px, baru adaptasi desktop. Screenshot dan perbaiki overflow/semantik/kontras.
3. Jika browser/Playwright tersedia, uji `320, 360, 390, 430, 768, 1440` + `prefers-reduced-motion`, keyboard, form invalid dan GPS stale. Jika tidak tersedia, tandai **NOT TESTED**.
4. Jangan menganggap semua varian Gemini menerima `thinking_level` yang sama; periksa `docs/ai/models/GEMINI.md` dan model API aktual.


## V3 — Gemini/Antigravity direct action contract
Workspace `P:\Tugas PPL MBG` memiliki source Laravel yang sudah berjalan menurut laporan agent. Baca dokumentasi baru secara **READ ONLY** dari `...\MBG-Track-Documentation-v3`; baca dulu `DESIGN.md`, `docs/anti-slop/RULE-MATRIX.md`, halaman target dan `DELIVERY-GATE.md`. Jangan menjalankan `npx antislop-ai` atau plugin installer tanpa izin; jangan menimpa `MBG-Track-Documentation` lama.

Untuk **PBI-02 K03** kerjakan During mode: audit K02/PBI01 existing, komposisi pegawai berdasarkan role & status, live search/filter, form email/password yang aman, reassignment PIC scoped, feedback setelah commit; uji responsive/keyboard dan backend tests. Untuk **audit K02** pilih After mode, findings bernomor sebelum rewrite estetika. 

Jangan gunakan satu shell card sebagai desain akhir seluruh role. Setiap layar punya Design Read & dial berbeda; buat screenshot aktual dan catat hasil Delivery Gate di log root Laravel di luar kedua folder dokumentasi.

## V4 — Gemini Antigravity repo-first
The **Laravel root** is `P:\Tugas PPL MBG`, not this document folder. For UI, first inspect original `.agents/skills/antislop/SKILL.md` in Laravel root and UI/mobile/human/copy skill original folders. Read v4 `DESIGN.md` and one page spec. Existing PBI-01 UI: audit in After mode before redesign. Next new work: PBI-02 in During mode, with screenshots, interaction tests, PHPUnit and 4-block original gate. If original skills absent, require installation and report the blocker. Do not alter any protected documentation.
