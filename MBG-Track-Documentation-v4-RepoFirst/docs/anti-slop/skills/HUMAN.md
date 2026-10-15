# MBG-Track anti-slop skill lens | HUMAN

> MBG-Track v3.0 | Asal inspirasi: [anti-slop oleh Miqdad Badjuber](https://github.com/miqdadbadjuber/anti-slop/tree/9685ca7eac70283d083fc23baa9a88e7d3cd93c4) (MIT, snapshot 12-10-2026). Dokumen ini adalah **adaptasi orisinal khusus MBG-Track**, bukan salinan skill resmi ataupun hasil pengujian aplikasi.

## Aksesibilitas sebagai persyaratan nyata
- Verifikasi WCAG 2.2 AA secara elementwise (normal 4.5:1 dan besar 3:1); jangan mengklaim semua WCAG AA lulus hanya karena memakai palet Calm Utility.
- Fokus keyboard kontras >=3:1 dengan area sekitarnya bila relevan, Tab/Shift+Tab masuk akal, dialog ditutup Escape, fokus balik ke pemicu. Jangan menonaktifkan outline tanpa mengganti.
- Gunakan semantic HTML: `<button>` untuk aksi, `<a>` untuk navigasi, `label for` untuk input, `aria-live` secukupnya untuk feedback; ikon tidak pernah satu-satunya indikator.
- Status batch gagal, koneksi GPS stale dan discrepancy 2 porsi ditulis dengan teks+simbol, bukan hanya merah/kuning.
- Form P04/P02 tetap dapat digunakan dengan pembesaran 200%; keyboard mobile tidak menutupi tombol lanjut/simpan. Error per field dibacakan dan tidak membuang nilai yang diisi.
- Tabel desktop yang penting (K06/C04) punya ringkasan data alternatif di 320–430, bukan tabel mengecil sampai tak terbaca.
- Tombol sentuh minimum internal 44px dan jarak area berbahaya (hapus/nonaktifkan) aman; standar internal ini tidak boleh diklaim sebagai minimum universal WCAG tanpa penjelasan.
- Batasi animasi dan terapkan `prefers-reduced-motion`; teks proses tetap terlihat saat motion dimatikan.
- Jadikan bahasa `lang="id"`, waktu disajikan jelas `WIB`, loading punya teks, empty/error mempunyai jalan lanjut.

## Verifikasi manual
Tab seluruh halaman, nyalakan zoom 200%, simulasikan 320px/keyboard, cek kontras tiap pasangan warna dan status, serta lihat screen reader smoke bila tersedia. Lampirkan bukti konkret; penilaian otomatis saja tidak cukup.
