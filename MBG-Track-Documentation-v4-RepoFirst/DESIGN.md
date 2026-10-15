# DESIGN.md | MBG-Track art direction and experience contract

> MBG-Track v3.0 | Asal inspirasi: [anti-slop oleh Miqdad Badjuber](https://github.com/miqdadbadjuber/anti-slop/tree/9685ca7eac70283d083fc23baa9a88e7d3cd93c4) (MIT, snapshot 12-10-2026). Dokumen ini adalah **adaptasi orisinal khusus MBG-Track**, bukan salinan skill resmi ataupun hasil pengujian aplikasi.


## Design Read: identitas yang dapat langsung dipakai coding agent
"Membaca ini sebagai produk koordinasi pangan sekolah, dipakai petugas lapangan di luar ruang, operator dapur, kepala SPPG, dan keluarga. Arah: **calm premium operational editorial**; warna tidak ramai, struktur informasi berkarakter, tipografi presisi, animasi karena sebab nyata." Gunakan tiap page spec untuk membaca tingkat ENERGY/RHYTHM/MOTION. **Jangan menggandakan halaman dashboard untuk semua role.**

## Karakter unik, bukan merek pemerintah
- **Nama**: MBG-Track adalah prototype mahasiswa, tidak mengklaim merek resmi BGN/SIPGN. Jangan gunakan lambang pemerintah, logo sekolah sungguhan, atau foto MBG nyata tanpa izin.
- **Sifat**: manusiawi, tertib, transparan, teliti. Rasa premium datang dari ritme, ketelitian data dan tipografi, bukan dekorasi mahal.
- **Motif**: *journey line* / garis progres tipis bersegmen yang menghubungkan kebutuhan → dapur → QC → sekolah → ompreng. Dipakai pada timeline dan lokasi yang betul-betul merepresentasikan urutan. BUKAN latar garis grid di seluruh aplikasi.
- **Warna awal yang kompatibel dengan Sprint 0**: canvas `#F5F7F3`, ink `#172923`, pine `#1C6654`, line `#DCE5DD`; muted gold `#B78C56` hanya untuk satu momen editorial/non-status bila lolos rasio kontras. Palet ini mengikuti *laporan implementasi Gemini*, bukan hasil screenshot independen. Jangan langsung menimpa token aktual tanpa audit.
- **Tipografi**: Instrument Sans atau sistem fallback dengan alasan keterbacaan form angka dan akurasi peran; serif boleh dieksplor untuk editorial publik setelah proof, jangan mengacak font setiap role. `font-variant-numeric: tabular-nums` untuk perhitungan porsi dan waktu.
- **Permukaan**: mostly flat; divider fungsional dan tipografi memberi kedalaman, shadow hanya pada lapis yang terangkat secara semantik. Radius terbatas (input, panel, dialog dibedakan). Bukan 8 kartu seragam dengan icon bulat.

## Dials (adaptasi Liveliness Toolkit upstream)
| Keluarga halaman | ENERGY | RHYTHM | MOTION | Mengapa |
|---|---:|---:|---:|---|
| Kepala SPPG control room | 2 | 2 | 2 | Konsentrasi lintas sekolah; map/status boleh dinamis tetapi tidak berisik |
| Pengawas gizi | 1 | 2 | 1 | Keputusan aman/tidak aman perlu konsentrasi dan bukti |
| Asisten lapangan | 1 | 2 | 1 | Tangan sibuk; action satu per langkah, minim gerak |
| Driver aktif | 1 | 1 | 1 | Keselamatan; prioritas berhenti aman, tidak ada distraksi |
| PIC sekolah | 1 | 2 | 1 | Hitungan/pemeriksaan presisi di HP |
| Publik | 2 | 3 | 2 | Informasi mudah dicerna namun mempunyai identitas dan ritme editorial |
| Authentication/profile | 1 | 2 | 1 | Trust, cepat dan akurat |
Dials ini batas desain awal, bisa disesuaikan **per halaman** melalui `docs/pages/...`, dengan alasan tertulis; bukan izin memakai parallax berat pada perangkat kurir.

## Prinsip layout yang bisa diimplementasi
1. **First fold berisi keputusan, bukan sambutan**. Di HP, satu tindakan primer yang paling dibutuhkan saat ini, status jujur dengan sumber dan timestamp, lalu rincian.
2. **Konten menentukan komposisi**. K01 exception rail + timeline + map; G03 antrian QC + area pemeriksaan; D03 stop aktif + petunjuk aman + peta; P04 persandingan expected/received + pemeriksaan; U02 cerita menu + timeline publik.
3. **Desktop dapat lebih padat**, tetapi bukan mobile yang dijadikan dashboard 4 kolom. HP menggunakan urutan operasional, panel detail menjadi disclosure/drawer jika tepat.
4. **Satu fokus visual per layar**. Hindari hero, CTA dan badge yang semuanya bersaing. Gunakan kontras tekstual nyata, bukan efek cahaya.
5. **Teks mikro bahasa Indonesia**: "Konfirmasi 338 porsi", "Lokasi terakhir 08.13", "Menu belum disahkan", "Lihat laporan kendala"; bukan "Explore", "Seamless", "Next Gen". Jangan membuat KPI bisnis palsu.

## Larangan dengan pengecualian yang beralasan
- Tidak memakai template WordPress, bento acak, 6-card boilerplate, stock robot/3D blob, pseudo-testimonial, fake live GPS atau demo sebagai data nyata.
- `R-02` anti-slop: pada **copy baru yang dibuat agent untuk UI** hindari em dash; dokumen teknis historis dan kutipan sumber yang tidak dimaksud menjadi UI tidak perlu dihapus secara membabi buta.
- `R-21`: tema **light-first** beralasan untuk pekerja lapangan dengan sinar terang, pembacaan data dan identitas produk. Jangan tampilkan dark toggle hingga seluruh theme dapat diuji; apabila direncanakan dalam tugas tertentu, harus selesai dan diuji kedua mode.
- `R-23`: bila logo, ilustrasi, foto orang, foto makanan, visual resmi atau testimoni belum disetujui, pakai placeholder dengan label jelas **[ASET BELUM DISETUJUI]**. Tidak menciptakan bukti operasional palsu.
- Grafik hanya bila datanya sah dan lebih jelas dari teks/tabel. Motion tidak boleh menggambarkan sukses sebelum respons server sukses.

## Motion tokens awal, bukan janji performa
- Micro-feedback: 120–180ms; disclosure/transisi: 180–260ms; hanya untuk perubahan orientasi atau hasil nyata.
- Status update dari server: cross-fade ringan hanya pada nilai berubah, tanpa berkedip ketika polling.
- GPS marker memakai posisi terverifikasi + freshness; tidak menginterpolasi secara visual sampai menyesatkan keadaan lapangan; bila stale, hentikan interpolasi.
- CSS reduced-motion wajib. Animasi progress untuk batch dan stop mencerminkan state nyata, bukan animasi demonstrasi otomatis.
- Prioritas kontrol sentuh internal >=44×44px dan tidak tertutup keyboard/safe-area, pengujian nyata di 320/360/390/430/768/1440 dan zoom 200%.

## Standar validasi
Baca `docs/anti-slop/RULE-MATRIX.md`, `docs/anti-slop/DELIVERY-GATE.md` dan file page yang dikerjakan. Nilai pada `docs/design/UI-QUALITY-CHECKLIST.md` merupakan target internal, **tidak menggantikan gerbang mandatory**. Keberhasilan hanya dibuktikan lewat browser, klik, screenshot dan tes backend pada commit yang sama.

## V4 — Positive art direction used with ORIGINAL upstream antislop

**Do not replace this art direction with the upstream repo.** It supplies the creative answer to “what should this look and feel like,” while original upstream SKILL files answer “what is generic/unjustified/broken.” The two work together; both must be read before UI changes.

Role differentiation remains mandatory: K-head exception led control room; G-QC validation workspace; A-production stage runbook; D-driver next-stop one-hand interface; P-PIC quantity reconciliation; U-public editorial menu, honest delivery progress. One dominant decision per first mobile fold. Pale olive canvas + deep forest ink/pine primary retained unless a real screenshot audit warrants deliberate changes. Motion signals real state change, not decorative auto playback. More whitespace is not an excuse to strip information.

**External asset restriction:** a premium image, logo, badge, animation, or icon may only enter production with justified purpose, rights and user approval when required by upstream R-23. No unverified government branding, fake food photo evidence, reviews, or live GPS. Browser evidence and four-block original gate decide pass/fail.
