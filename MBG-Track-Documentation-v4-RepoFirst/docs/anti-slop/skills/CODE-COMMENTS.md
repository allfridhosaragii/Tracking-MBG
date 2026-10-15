# MBG-Track anti-slop skill lens | CODE-COMMENTS

> MBG-Track v3.0 | Asal inspirasi: [anti-slop oleh Miqdad Badjuber](https://github.com/miqdadbadjuber/anti-slop/tree/9685ca7eac70283d083fc23baa9a88e7d3cd93c4) (MIT, snapshot 12-10-2026). Dokumen ini adalah **adaptasi orisinal khusus MBG-Track**, bukan salinan skill resmi ataupun hasil pengujian aplikasi.

## Lingkup aman: komentar kode saja
Adaptasi upstream `antislop-code` bersifat sangat ketat: review komentar yang tidak memberi nilai. Jika task **comment cleanup**, jangan ubah executable PHP/Blade/JS, identifier, imports, routes, model, status, atau whitespace yang menimbulkan perubahan runtime. Jangan memperlebar tugas menjadi refactor.

Hapus/pendekkan bila aman:
- Divider banner `// ========= IMPORTANT =========`, komentar emoji, komentar "Step 1 ... Step 2 ..." yang hanya menarasikan alur.
- `// Initialize variable` sebelum variabel yang jelas.
- `// User Model` sebelum `class User`.
- TODO kabur tanpa tindakan, misal `TODO improve this`.
- PHPDoc yang mengulang signature tanpa constraints berarti.

Pertahankan:
- Kenapa `DB::transaction()` melindungi konfirmasi porsi atomik.
- Kenapa GPS dihentikan saat trip tidak aktif; kebijakan retensi/akses.
- Kenapa `UNSAFE` menahan distribusi sampai keputusan petugas.
- Caveat routing ETA provider tidak memiliki live traffic.
- Kontrak API, security invariants, race condition, konsekuensi yang tidak tampak dari kode.

## Bukti
Git diff hanya komentar di file code saat task komentar; jalankan test bila perubahan berpotensi menyesatkan. Bila menemukan bug logic, laporkan issue terpisah, jangan sambil diam-diam memperbaikinya.
