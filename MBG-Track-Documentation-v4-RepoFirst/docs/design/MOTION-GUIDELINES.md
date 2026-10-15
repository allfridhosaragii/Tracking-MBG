# Motion Guidelines — Transisi Bermakna, Bukan Hiasan

## 1. Prinsip
Animasi mengkomunikasikan hubungan spasial atau perubahan status, tidak memperindah dashboard dengan noise. Semua motion harus dapat dimatikan lewat `prefers-reduced-motion: reduce`; tanpa motion sistem tetap sepenuhnya dapat digunakan. Tidak ada autoplay loop dekoratif pada UI operasional.

## 2. Motion token usulan (bukan kewajiban BGN)
| Jenis | Durasi awal | Kapan | Larangan |
|---|---:|---|---|
| Hover/tap feedback | 90–150ms | button/select clickable | gerakan besar tidak relevan |
| Section disclosure | 160–220ms | panel detail, filter | height animation yang memicu layout shift besar |
| Navigation context | 180–260ms | route internal setelah data siap | whole-page blur/fade lama |
| Dialog/sheet | 180–280ms | confirmation | modal menghalangi aksi darurat |
| Data value swap | 120–220ms | update jumlah setelah commit | count-up yang mengubah kenyataan saat belum commit |
| Status progress | 180–260ms | `EN_ROUTE→ARRIVED` | melompati status nyata |
| GPS marker | interpolasi antar titik valid | tracking dengan timestamp | memprediksi/mengarang posisi ketika stale |

Gunakan easing yang cepat saat masuk dan mudah diikuti saat berhenti; validasi terhadap perangkat kelas menengah.

## 3. Komponen dan aturannya
- **Submission:** tombol memuat + teks `Menyimpan…` → setelah respons sukses terkonfirmasi `Tersimpan` + timestamp. Saat gagal, restore form dan tampilkan perbaikan; jangan memutar checkmark sukses sebelum respons server.
- **Timeline produksi:** titik progres berubah hanya setelah server mengesahkan status; animate emphasis pada item berubah, bukan seluruh halaman.
- **GPS:** simbol posisi hanya diinterpolasi bila ada koordinat baru valid berurutan; kecepatan/update timestamp konsisten. Jika > ambang stale yang disepakati, berhenti interpolasi, beri `Lokasi terakhir…` dan fallback.
- **Notification:** toast non-kritis hilang otomatis dengan kesempatan membaca; notifikasi risiko pangan tidak boleh hilang begitu saja dan harus bisa diakses ulang.
- **Receipt/QC:** aksi destruktif/hold menunjukkan modal ringkas berisi konsekuensi; tidak boleh terlewat hanya karena animasi.
- **Charts:** animasi maksimal satu kali saat masuk viewport; matikan animasi saat data sering berubah.

## 4. Reduced motion
```css
@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after { scroll-behavior: auto !important; }
  .motion-enhanced { animation: none !important; transition: none !important; }
}
```
Gunakan aturan scoped yang tidak merusak indikator loading atau elemen aksesibel; di implementasi nyata pastikan indikator tetap punya representasi teks.

## 5. Accessibility dan keselamatan penggunaan lapangan
- Jangan mewajibkan gestur geser/drag saja untuk reordering stop; sediakan tombol urutan naik/turun.
- Toast dan panel terbuka harus menjaga urutan focus; setelah dialog ditutup fokus kembali ke pemicu.
- Hindari gesture tersembunyi yang memaksa driver melihat layar lama.
- Jangan menampilkan blinking kritis berulang; pakai severity + teks.

## 6. Audit
Uji pada 360, 390, 430px dan desktop; no motion pada reduced-motion; tidak ada layout shift/scroll jumping; buka-tutup modal dengan keyboard; status dipicu perubahan backend bukan setTimeout UI. Catat perbaikan bersama rekaman singkat.

Rujukan: https://developer.mozilla.org/en-US/docs/Web/CSS/@media/prefers-reduced-motion ; https://www.w3.org/TR/WCAG22/


## V3 | Motion attached to state events
Gunakan `docs/design/MOTION-STATES-CATALOG.md` sebagai kontrak: hanya animasikan status yang telah sah secara backend. Status QC sukses setelah commit; marker GPS hanya bergerak pada fix valid; stale tidak boleh interpolated; pre-arrival toast dedupe tidak boleh muncul tiap polling. Transisi pendek saat disclosure dan akibat interaksi; energy/motion per role dari `DESIGN.md` dan Section 15 tiap page. 

R-19 tidak otomatis melarang animasi; ia melarang motion tanpa alasan. Saat no-motion preference, informasi status tetap sama. Jangan menampilkan parallax atau efek scroll pada tugas kurir, pemeriksaan pangan, atau form kritis PIC.
