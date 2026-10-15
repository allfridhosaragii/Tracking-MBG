# Photo Evidence — Bukti Peristiwa, Bukan Pengganti Pemeriksaan

## Aturan desain
Foto menjadi atribut suatu transaksi/checkpoint; tidak sekadar lampiran tidak bernama. Foto tidak membuktikan nilai gizi, keamanan makanan, suhu, atau bahwa setiap siswa mengonsumsi makanan. Hindari wajah siswa, plat nomor pribadi yang tidak diperlukan, EXIF/lokasi tanpa tujuan.

## Checkpoint dan kepemilikan
| Evidence code | Pemilik aksi | Linked model | Kapan | Wajib? |
|---|---|---|---|---|
| `FOOD_READY` | Asisten | FoodBatch | Akhir packing sebelum QC | Wajib prototype |
| `VEHICLE_LOADED` | Asisten/Driver | Trip | Setelah muat dan sebelum start | Wajib |
| `SCHOOL_HANDOVER` | Driver | TripStop | Saat tiba menyerahkan | Wajib 1 foto, PIC mengonfirmasi transaksi yang sama |
| `RECEIPT_PROBLEM` | PIC | SchoolServiceRecord/Incident | Jika jumlah/kondisi berbeda | Wajib bila foto memungkinkan; fallback catatan saat kamera rusak |
| `TRAYS_READY` | PIC | TrayReturn | Siap diambil | Wajib prototype |
| `TRAYS_PICKED_UP` | Driver | TrayReturn | Fisik diambil | Wajib |
| `TRAYS_RETURN_PROBLEM` | Asisten | TrayReturn/Incident | Selisih saat balik | Wajib bila masalah |
| `INCIDENT` | Pelapor terkait | Incident | Laporan masalah | Opsional kecuali kategori membutuhkan bukti |

## Data evidence
Morfologis `evidenceable_type/id`, kategori, uploader, server_received_at, captured_at deklaratif (tidak trusted), file path privat, mime, size, hash opsional, catatan. File size maksimum desain 5MB, JPEG/PNG/WebP, kompresi di browser sesuai perangkat, upload retry tanpa membuat duplikat transaksi.

## Akses
Private storage Laravel, file hanya lewat Policy controller, tidak taruh dalam URL publik. Kepala melihat seluruh bukti SPPG; gizi hanya bukti kualitas relevan; PIC hanya sekolahnya; driver hanya assignment; publik tidak melihat bukti internal. Foto menu publik berbeda dan berasal dari `menus.image_path` yang sudah disetujui.

## Validasi penting
Validasi ekstensi tidak cukup: MIME dan magic bytes; file tidak boleh SVG executable; rename dengan UUID, bukan nama user; strip EXIF bila tidak diperlukan; rate limit; scan bila layanan tersedia; jangan mempercayai koordinat metadata kamera.

## Perilaku saat kamera/internet gagal
Simpan status checkpoint `EVIDENCE_PENDING` dan catatan alasan; kepala dapat melihat risiko. Untuk foto yang dinyatakan wajib, pembuatan status terkait tidak boleh diloloskan diam-diam; workflow pengecualian beralasan diperlukan dan harus diaudit. Penyerahan makanan fisik tidak diblokir oleh kegagalan upload.

## Acceptance
Satu handover memiliki evidence driver dan konfirmasi PIC; tidak ada dua transaksional handover karena click ganda. Download bukti dari tenant lain 403. File tersimpan dan hanya tersedia untuk owner yang sah. Uji ukuran, mime palsu, upload terputus dan audit.

## Bukti visual v2 — checkpoint, akses, validasi
| Checkpoint | Actor upload | Entitas | Siapa membaca | Saat normal wajib? |
|---|---|---|---|---|
| FOOD_READY | Asisten | batch | Kepala/Gizi/Asisten terkait | Ya sesuai desain, fallback beralasan |
| VEHICLE_LOADED | Asisten/Kurir sesuai pembagian tugas | trip | Kepala/Asisten/Kurir assigned | Ya sesuai desain, satu bukti bersama |
| SCHOOL_HANDOVER | Driver | delivery trip_stop | PIC sekolah sendiri, Kepala | Ya sesuai desain, PIC **tidak** upload duplikat normal |
| RECEIPT_PROBLEM | PIC | service record | Kepala/Gizi terkait | Jika ada selisih/kualitas |
| TRAYS_READY | PIC | tray return | Kurir assigned, Kepala | Ya sesuai desain, fallback beralasan |
| TRAYS_PICKED_UP | Kurir | tray return | Kepala/Asisten/PIC relevan | Ya sesuai desain, satu foto pickup |
| TRAYS_RETURN_PROBLEM | Asisten/Kurir | tray return | Kepala, pihak relevan | Bila selisih/rusak |
| INCIDENT | pelapor berwenang | incident | pemilik/penangan scope | Opsional sesuai kategori |

### Teknis
Gunakan private disk/signed URL dengan TTL pendek dan otorisasi per request; jangan buat public storage symlink untuk evidence internal. Validasi bytes/MIME nyata server, bukan hanya ekstensi. Batasi file misalnya JPEG/PNG/WebP dan 5MB sebagai **nilai desain yang dapat diuji**; normalisasi orientasi, ukuran dan EXIF. Waktu foto dari device bukan bukti UTC otoritatif; `created_at` server adalah peristiwa upload, `captured_at` metadata bersifat untrusted.

### Fallback kegagalan kamera/network
Jika checkpoint foto normally required tapi kamera tidak bisa dipakai di lapangan, jangan membuat petugas menunda penanganan pangan berisiko: sediakan exception beralasan + supervisor review + flag `EVIDENCE_MISSING`, sesuai persetujuan operasional; jangan diam-diam menandai bukti lengkap. Saat upload gagal jangan blokir browser dengan modal permanen; foto lokal belum dikirim harus ditandai belum tersimpan.

### Privasi
Hindari wajah siswa, plat kendaraan jika tak diperlukan untuk publik, identitas personal, dan koordinat EXIF. Staff scoped dapat melihat foto sesuai keperluan, publik hanya foto menu publik yang memang secara terpisah disetujui—bukan isi `evidence_photos` otomatis.


## V3 | Asset ethics & evidence distinction
Foto menu publik perlu izin/persetujuan; foto serah-terima/tray/incident adalah evidence privat, bukan dekorasi marketing. Untuk placeholder UI gunakan label eksplisit `[FOTO BELUM ADA]`, jangan stock dapur sekolah sebagai bukti asli. Image loading/error/failed upload/retake workflow diuji. Asset approval dan bukti asal di `docs/design/CONTENT-TRUTH-PROTOCOL.md`.
