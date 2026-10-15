# Quick Start Windows — pemasangan upstream asli untuk Antigravity

1. Ekstrak ZIP ke `P:\Tugas PPL MBG\MBG-Track-Documentation-v4-RepoFirst` **tanpa mengubah folder dokumentasi lama**. Folder Laravel existing tetap di `P:\Tugas PPL MBG`.
2. Pastikan Git terpasang: jalankan `git --version`. Tutup/commit perubahan aplikasi yang sedang berlangsung agar mudah dipulihkan jika ada masalah.
3. Baca `scripts/Install-AntiSlop-Upstream.ps1` sebelum menjalankannya. Script hanya dimaksudkan menulis ke root Laravel di luar folder dokumentasi. **Tidak berjalan otomatis.**
4. Jalankan dari PowerShell Windows:

   ```powershell
   & "P:\Tugas PPL MBG\MBG-Track-Documentation-v4-RepoFirst\scripts\Install-AntiSlop-Upstream.ps1"
   ```

5. Verifikasi:

   ```powershell
   & "P:\Tugas PPL MBG\MBG-Track-Documentation-v4-RepoFirst\scripts\Check-AntiSlop-Integration.ps1"
   ```

6. Di Antigravity, buka **workspace root** `P:\Tugas PPL MBG`, **mulai sesi agent baru**, lalu minta agent membaca `AGENTS.md`, `DESIGN.md` v4, file page target, serta original upstream `antislop/SKILL.md` dan skill relevan. Untuk audit K02 existing pilih mode **After**; untuk PBI-02 baru gunakan **During**.
7. Dapatkan laporan 4 blok **Delivery Gate** dengan screenshot 320/360/390/430/768/1440, interaksi nyata, fokus/keyboard, zoom, dan unit/feature test. Bila tidak diuji, agent wajib menulis `NOT VERIFIED` dan tidak menyatakan halaman selesai.

## Jika ada nama file yang sudah ada
Script **tidak menimpa** `AGENTS.md`, `GEMINI.md` maupun original skill yang berbeda. Ia melaporkan konflik agar kamu menggabungkan instruksi secara sadar. Tidak ada auto-reset, tidak ada penghapusan massal, dan tidak menjalankan Composer/migration.

## Alternatif resmi dari pemilik repo
README upstream mencantumkan `agy plugin install https://github.com/miqdadbadjuber/anti-slop` atau installer `npx antislop-ai`. Kedua cara dapat mengubah konfigurasi agent. Paket ini memakai installer lokal yang **menyalin skill asli dari clone SHA terpin**, agar lokasi tulisan terkendali. Tidak berarti plugin Antigravity resmi sudah terinstal.
