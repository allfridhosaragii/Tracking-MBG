# Prompt pertama untuk Gemini Antigravity: enforce anti-slop upstream asli

Kamu bekerja di Laravel EXISTING root `P:\Tugas PPL MBG`. **JANGAN bootstrap ulang, hapus database, atau edit folder MBG-Track-Documentation, MBG-Track-Documentation-v3, maupun MBG-Track-Documentation-v4-RepoFirst.**

1. Periksa `.agents/skills/antislop/SKILL.md` dan original `antislop-ui`, `antislop-layoutmobile`, `antislop-human`, `antislop-copywriting`, `antislop-code`. Jika belum ada, tulis `UPSTREAM NOT INSTALLED` dan hentikan pekerjaan UI sampai instalasi selesai. Jangan buat versi tiruan.
2. Baca `AGENTS.md` dari root Laravel, v4 `UPSTREAM-FIRST.md`, v4 `DESIGN.md`, `docs/product/PBI-BACKLOG.md` dan spesifikasi halaman target.
3. Konfirmasikan mode **After** bila sedang memeriksa K02/K03 existing; buat daftar temuan R-XX + bukti screenshot dan tunggu approval sebelum memperbaiki existing UI. Untuk PBI-02 baru gunakan **During** dan lakukan quality gate sepanjang kerja.
4. Berikan Design Read dan ENERGY/RHYTHM/MOTION yang sesuai role. Jangan bikin layout generik atau desain steril; komposisi harus diturunkan dari pekerjaan dan data nyata.
5. Semua perubahan Laravel harus mendapat authorization dan validasi. Jangan mengarang status live, angka, foto bukti atau review.
6. Jalankan PHPUnit/Pest bila tersedia, npm build, lalu browser audit 320/360/390/430/768/1440, 200% zoom, keyboard, semua kontrol, empty/loading/error, contrast, screenshot, console. Laporkan yang gagal tanpa mengarang.
7. Lampirkan **Delivery Gate 4 blok asli** (Hard, Purpose, Liveliness, Craftsmanship) dengan evidence per hasil. Jangan menulis PASS tanpa bukti.
8. Catat log hanya di root Laravel, bukan dokumentasi read-only. Mulai dengan audit repo dan verifikasi skill, bukan langsung mengganti CSS.
