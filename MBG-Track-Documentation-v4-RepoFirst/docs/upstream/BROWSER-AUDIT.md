# Audit browser untuk halaman Laravel MBG (bukan sekadar screenshot cantik)

1. Catat branch/commit dan route nyata. Status `REPORTED` jika hanya mengandalkan ringkasan Gemini; `VERIFIED` baru setelah mengeksekusi test sendiri.
2. Gunakan 320, 360, 390, 430, 768, 1440px; audit zoom 200% dan portrait/landscape relevan. Periksa `document.documentElement.scrollWidth` vs viewport; jangan hanya menilai screenshot.
3. Uji tombol, link, form, dropdown, back, Escape, keyboard focus, error, loading, empty, offline/dependency error. Semua interaksi harus punya perilaku backend yang benar.
4. Periksa kontras teks dan status, fokus terlihat, label input, live-region/feedback bila relevan, teks tidak terpotong oleh keyboard HP/safe-area.
5. Uji navigasi role lain harus 403 dan tenant lain tidak terlihat. Publik tidak mendapat GPS dan foto evidence private.
6. Periksa console dan network 404/500; jangan buat tombol mirip navigasi yang tidak mempunyai route.
7. Bandingkan image/UX dengan DESIGN.md serta page spec. Tulis minimal satu hal khas role yang tidak dapat dijelaskan hanya oleh template admin.
8. Simpan screenshot/trace di root Laravel `tests/artifacts/` atau area temporer terkontrol, bukan dalam folder dokumentasi read-only.
9. Jalankan gate upstream penuh. Satu screenshot lulus tidak membuktikan semua viewport/state lulus.
