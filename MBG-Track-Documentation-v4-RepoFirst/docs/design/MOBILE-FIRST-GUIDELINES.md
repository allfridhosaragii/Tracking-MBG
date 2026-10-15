# Mobile-First Guidelines — Semua Role, Prioritas Lapangan

## 1. Kesepakatan utama
Rancang dahulu interaksi pada lebar **390px**, uji 320px, 360px, 430px, 768px, 1024px, 1440px serta portrait/landscape. Beri bukti screenshot. Mobile bukan versi satu kolom dari tabel desktop. Jika tabel rumit, ubah menjadi *task row* dengan label dan nilai; jangan memaksa horizontal scroll seluruh halaman.

## 2. Kontrak navigasi
- PIC/kurir/asisten: top context kecil (sekolah/trip/shift), CTA terkait aksi aktif terlihat tanpa scroll panjang; bottom nav maks 3–5 destinasi penting jika diperlukan, sisanya ada menu sekunder.
- Kepala/Gizi: mobile menunjukkan briefing dan pengecualian teratas; advanced filters/laporan boleh tertutup di disclosure/bottom sheet; desktop dapat memakai split-pane.
- Publik: sekolah di header jelas; pilih sekolah tanpa registrasi; progress dan menu segera terlihat; link mendalam tidak memancing login.
- URL dan tombol Back menjaga query, filter, draft sejauh aman; tidak membuat pengguna mengisi ulang form setelah validasi server 422.

## 3. Tindakan satu tangan
- Target interaktif *internal proyek* minimal 44x44 CSS px untuk kontrol utama (lebih ketat dari kriteria umum WCAG 2.2 AA minimal 24px dengan pengecualian). Jarak antartombol yang berisiko salah tekan minimal 8px.
- Primary CTA tunggal per kondisi; jika ada 2 aksi penting, jelaskan prioritas dan konsekuensinya.
- Sticky action area tidak menutupi konten/keyboard; dukung `env(safe-area-inset-bottom)` untuk mobile dengan gesture bar.
- Saat perjalanan mobil, aplikasi memprioritaskan informasi glanceable; setiap input aktif hanya boleh dilakukan ketika kendaraan berhenti.

## 4. Form
- Satu section per tujuan, label selalu terlihat, satuan di dekat nilai; input angka gunakan `inputmode=numeric` dengan validasi server.
- Jumlah porsi `requested`, `loaded`, `received`, `damaged`, `served`, `leftover` memiliki label eksplisit; kalkulasi ditampilkan tetapi tidak disimpan dua kali tanpa alasan.
- Kamera: `accept=image/*`, capture jika didukung, fallback pilih file; kompresi client jika aman, validasi mime/size/dimensi server, progres upload; jangan memaksa foto kedua tanpa alasan.
- Tanggal/waktu ditampilkan WIB; server UTC; jelaskan waktu real vs estimasi.

## 5. Maps dan offline
- Map harus punya fallback alamat + urutan stop + aksi buka aplikasi navigasi; tidak bergantung satu tile service.
- Permission GPS ditolak: jelaskan konsekuensi, sediakan lanjut proses manual dengan audit jika diizinkan; tidak memaksa browser background tracking.
- Offline: simpan draft input non-sensitif sementara sesuai kebijakan; tampilkan `Belum terkirim`; jangan mengaku submit sukses tanpa server ACK.
- GPS stale diberi usia data; ETA hilang/diganti `Belum tersedia` daripada tetap tampil pasti.

## 6. Hierarki konten
- Bagian atas layar: **siapa/apa** (sekolah, trip, batch), **status aktual**, **satu tindakan berikutnya**.
- Detail lanjutan: history, evidence, pilihan filter, data teknis sekunder.
- Render density: tidak lebih dari 2–3 metrik primer pada tampilan pertama 390px kecuali ada alasan kuat.
- Panjang label dipastikan tidak terpotong pada bahasa Indonesia dan skenario angka panjang.

## 7. Gestur dan aksesibilitas
Semua aksi bisa keyboard & touch, bukan swipe-only; jangan sembunyikan aksi kritis di hover; fokus terlihat; state `disabled` menjelaskan sebab; zoom teks 200% tidak menutup CTA; dukung orientation dan reduced motion. Jaga keterbacaan di kondisi terang lapangan.

## 8. Mobile regression checklist
Uji manual dengan seeded realistic data: 1.026 porsi, tiga sekolah, 2 trip, 1 incident, 1 GPS stale, foto belum terunggah. Verifikasi 320px tanpa horizontal overflow, keyboard tidak menutup input, bottom nav tidak menutup toast/tombol, sticky status tidak tumpang-tindih, dialog bisa ditutup, semua action dapat dicapai tanpa desktop.

Referensi: https://www.w3.org/TR/WCAG22/ ; https://web.dev/articles/vitals


## V3 | Perilaku yang harus diuji
Setiap file page sekarang punya kontrak Section 15. Uji di 320/360/390/430/768/1440, keyboard menutup layar HP, teks besar 200%, viewport landscape dan panjang nama sekolah. Pada Kurir D03, tombol Tiba hanya saat berhenti aman; pada PIC P04, nilai expected/received tidak tertutup keyboard; pada Kepala K05, map kecil tidak menghalangi status prioritas. Button >=44px merupakan standar internal MBG-Track. Jangan mengklaim "AA passed" hanya dari ukuran tombol.

R-03 dan skill antislop-layoutmobile menjadikan overflow/clip kegagalan Hard Gate. Rekam screenshot ukuran kecil dan hasil ukuran `scrollWidth` pada app berjalan, bukan screenshot mockup.
