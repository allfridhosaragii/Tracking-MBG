# MBG-Track anti-slop skill lens | LAYOUT-MOBILE

> MBG-Track v3.0 | Asal inspirasi: [anti-slop oleh Miqdad Badjuber](https://github.com/miqdadbadjuber/anti-slop/tree/9685ca7eac70283d083fc23baa9a88e7d3cd93c4) (MIT, snapshot 12-10-2026). Dokumen ini adalah **adaptasi orisinal khusus MBG-Track**, bukan salinan skill resmi ataupun hasil pengujian aplikasi.

## Mobile-first tingkat operasi, bukan CSS breakpoint kosmetik
- Mulai dari urutan informasi 320–390px: identitas sekolah/trip/batch; status jujur; satu aksi yang dibutuhkan; detail sekunder; histori sebagai disclosure.
- Layar driver D03/D04: informasi tujuan dalam posisi baca cepat; tombol tindakan saat kendaraan berhenti aman; bukan halaman dengan menu melayang dan animasi map penuh.
- Layar PIC P02/P04/P05: input numerik besar yang membedakan expected/actual, kamera evidence, konfirmasi; tampilan aman saat keyboard HP muncul.
- Layar asisten A02/A03: tap dengan sarung tangan mungkin diperlukan tetapi jangan mengklaim riset lapangan nyata; tombol besar dan pesan status selalu terlihat.
- Layar Kepala K01/K05: mobile berprioritas exception satu per satu; desktop mengizinkan table/map lebih lebar. Jangan horizontal scroll di **level halaman**; horizontal scroll tabel terkendali hanya jika ada alternatif yang jelas.
- Portal publik U02/U03: editorial mobile dengan heading/food photo disetujui, ringkasan gizi dan timeline, bukan grid 4 kolom yang dipaksa ke HP.
- Breakpoint uji: 320, 360, 390, 430, 768, 1440. Pastikan real device atau emulated keyboard, portrait/landscape, data panjang, 200% zoom.
- `position: sticky` tidak boleh menutupi input, toast, atau CTA. Gunakan safe-area env var jika relevan, dan beri ruang untuk gesture bar mobile.
- Tap targets>=44px (kebijakan internal), hover bukan syarat action. Error/offline/slow network tetap menampilkan cara melanjutkan.

## Review teknis
Ukur viewport `scrollWidth <= clientWidth`, screenshot dan flow action. Jika satu halaman hanya lulus 390 tetapi gagal 320, status responsif tetap **FAIL**. Semua dimensi harus diuji, jangan cuma menulis CSS @media.
