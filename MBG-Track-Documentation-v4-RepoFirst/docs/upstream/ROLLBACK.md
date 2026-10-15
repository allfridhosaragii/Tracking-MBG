# Rollback tanpa menghapus dokumentasi

Pemasang tidak memindahkan atau menghapus file di `MBG-Track-Documentation*`. Source clone berada di `.anti-slop-upstream` dan skill di `.agents/skills/antislop*`; root `AGENTS.md` dan `GEMINI.md` hanya dibuat jika belum ada.

Jika ingin berhenti menggunakan antislop, **jangan menjalankan penghapusan recursive otomatis**. Pertama periksa `git status`, backup root `AGENTS.md` jika ada, lalu secara manual hapus hanya original folder yang dibuat dan telah diverifikasi sebagai hasil install, sesuai kebutuhan. Hapus pointer antislop dari root `AGENTS.md` secara manual agar agent tidak diarahkan ke file yang hilang. Jangan menghapus folder `.agents` yang mungkin berisi skill lain.

Menyimpan original source clone untuk audit adalah opsi paling aman. Update hanya dengan versi pin baru setelah audit perbedaan. Hasil kode Laravel yang sudah ditulis tidak dibatalkan oleh menghapus skill.
