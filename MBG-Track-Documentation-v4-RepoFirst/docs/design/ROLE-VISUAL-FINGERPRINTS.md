# Distinct role visual fingerprints

> MBG-Track v3.0 | Asal inspirasi: [anti-slop oleh Miqdad Badjuber](https://github.com/miqdadbadjuber/anti-slop/tree/9685ca7eac70283d083fc23baa9a88e7d3cd93c4) (MIT, snapshot 12-10-2026). Dokumen ini adalah **adaptasi orisinal khusus MBG-Track**, bukan salinan skill resmi ataupun hasil pengujian aplikasi.

## Kriteria visual yang tidak boleh digenerikkan
| Role | Komposisi first fold | Elemen pembeda | Hindari |
|---|---|---|---|
| Kepala SPPG | status pengecualian paling penting → total per sekolah → operasi aktif | operational ledger, route line + audit trail | empat KPI identik dipaksa mengisi fold |
| Pengawas gizi | antrian batch belum QC → satu keputusan aman/hold → bukti | pemeriksaan terstruktur, tabel bahan/gizi presisi | warna hijau otomatis menyatakan makanan aman |
| Asisten lapangan | langkah produksi aktif → porsi actual → bukti foto | timeline tahapan seperti work order | animasi berlapis pada tugas yang berulang |
| Kurir | tujuan saat ini → estimasi + keandalan GPS → rute → aksi saat berhenti | full-width stop leg dan GPS freshness | tombol besar yang mengundang tap sambil berkendara |
| PIC Sekolah | kebutuhan/penerimaan harus dikerjakan → selisih → QC sebelum makan | angka dan form dua tahap dengan status tersimpan | mengubah jumlah porsi jadi jumlah siswa individual |
| Orang tua/siswa | sekolah yang dipilih → menu disetujui → status sederhana | editorial menu story dan timeline publik | raw GPS, foto bukti internal, nama staf, reputasi palsu |

## Konsekuensi desain
- Reuse typography tokens dan controls; **jangan reuse persis satu template shell dengan susunan KPI seragam** bila tugas role berbeda.
- Setiap halaman tetap punya komponen fungsional dalam spesifikasi v2; fingerprint menambahkan komposisi, bukan mengganti hak akses.
- Sebelum memperluas ke semua layar, uji enam reference K01, G03, A02, D03, P04, U02; bandingkan apakah tiap layar terbaca sebagai role-nya tanpa judul.
