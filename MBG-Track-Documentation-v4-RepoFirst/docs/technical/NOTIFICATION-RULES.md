# Notification Rules — Event-Driven, Bukan Semua Role Diberi Spam

## Prinsip
Kirim notifikasi hanya ketika penerima harus menindaklanjuti atau perlu bersiap; data rutin cukup dashboard. Simpan database notification Laravel; tanpa Whatsapp/SMS/push publik pada MVP. Dedup satu event sesuai business key (`event_type + subject_id + recipient_id + occurrence`). Broadcast opsional hanya untuk akun berizin.

## Matriks pemicu
| Event | Dari | Ke | Kondisi | Prioritas |
|---|---|---|---|---|
| `PORTION_PENDING` | Scheduler | PIC sekolah terkait | Menjelang cutoff masih belum konfirmasi | Tugas |
| `PORTIONS_CONFIRMED` | PIC | Kepala/Asisten | Kebutuhan telah dikonfirmasi | Informasi |
| `PLAN_FINALIZED` | Kepala | Asisten + Gizi | Rencana sudah final | Tugas |
| `BATCH_QC_REQUIRED` | Asisten | Pengawas Gizi | Stage packing selesai | Tugas |
| `BATCH_ON_HOLD` | Gizi | Kepala + Asisten | Quality gate gagal | Kritis |
| `TRIP_ASSIGNED` | Kepala | Driver | Ada penugasan baru | Tugas |
| `TRIP_STARTED` | Driver | PIC stop terkait | Dalam perjalanan; PIC tetap bisa lihat per stop | Info terbatas |
| `PRE_ARRIVAL` | ETA service | PIC stop aktif | ETA <=10 menit, satu kali; fallback rute <=1 km | Persiapan |
| `STOP_ARRIVED` | Driver | PIC stop aktif | Sudah tiba dan menunggu serah terima | Tugas |
| `RECEIPT_DISCREPANCY` | PIC | Kepala + Asisten | Jumlah berbeda | Penting |
| `QUALITY_PROBLEM` | PIC/Gizi | Kepala + Gizi | Pemeriksaan gagal | Kritis |
| `TRAYS_READY` | PIC | Driver yang ditugaskan pickup | Wadah siap | Tugas |
| `TRAYS_MISSING` | Asisten/Driver | Kepala | Hitungan kembali kurang | Penting |
| `GPS_STALE` | Sistem | Driver + Kepala | Tidak fresh > ambang saat aktif | Peringatan |

## Aturan pengiriman
- Notification dibuat **setelah transaksi berhasil commit**, bukan sebelum. Gagal queue di-retry idempotent.
- Tidak mengirim ke semua kepala di sistem nasional; hanya tenant SPPG pemilik.
- Jangan broadcast ID PIC/email/phone ke publik; publik hanya membaca status ringkas tanpa push.
- ETA fluktuatif tidak mengirim ulang `PRE_ARRIVAL` stop yang sama; bila perjalanan dialihkan ke stop baru, stop punya key baru.
- GPS stale dapat diberi cool-down, jangan notif tiap 15 detik.
- Incident keamanan pangan perlu tampil jelas walau email tidak tersedia; follow-up secara operasional tetap di luar sistem jika urgent.

## Acceptance
Notif terhubung ke URL yang memang dapat diakses penerima, tidak membuat 403 saat klik. Unread/read bekerja; duplicate retry tidak membuat duplikat; perubahan satu role terlihat role tujuan tanpa input ulang.

## Matrix event & deduplikasi v2
| Event | Target | Kondisi sah | Unik/periode | Tindakan |
|---|---|---|---|---|
| PORTION_PENDING | PIC sekolah | request belum confirmed mendekati cutoff | school+date+cutoff reminder | buka P02 |
| BATCH_QC_REQUIRED | Gizi | batch PACKED → QC_CHECK | batch+QC cycle | buka G03 |
| BATCH_ON_HOLD | Kepala, Asisten terkait | QC UNSAFE/hold sah | batch+hold version | buka C02/C01 |
| TRIP_ASSIGNED | Driver owner | trip ASSIGNED | trip+assignment version | buka D02 |
| PRE_ARRIVAL | PIC stop aktif | ETA fresh ≤10 min atau fallback jarak sah | trip_stop+event | buka P03 |
| STOP_ARRIVED | PIC stop | driver arrived sah | trip_stop+event | buka P04 |
| RECEIPT_ISSUE | Kepala dan gizi bila kualitas | short/damaged/unsafe | receipt id+category | buka C01 |
| TRAYS_READY | Kurir pickup assigned | PIC ready count sah | tray_return+ready version | buka D05 |
| GPS_STALE | Kepala/driver terkait | trip aktif dan stale lebih ambang | trip+stale episode | cek tracking |

### Pengiriman dan UX
Gunakan Laravel Database Notification sebagai delivery layer dan unique notification event key per domain (bisa implement dengan kolom/source event table bila hasil migration mendukung). Dispatch setelah DB commit. Worker retry aman; jangan salah mengirim reminder untuk stop lain pada trip multi-stop. Notifikasi publik tanpa akun **tidak** menggunakan browser push di MVP; publik cukup refresh/proyeksi status.

### Prinsip severity dan beban perhatian
Incident pangan kritis menghasilkan alert yang tahan terlihat; ETA 10 menit berupa pemberitahuan sekali, bukan spam. Waktu selalu WIB UI, dan notifikasi menyebut **entitas dan aksi**, bukan teks abstrak. Ketika user membuka item, Policy diterapkan ulang—dulu punya akses bukan berarti selamanya punya akses setelah reassignment.


## V3 | Functional notification rather than decoration
Badge dan toast hanya untuk kejadian nyata yang perlu aksi dan dikirim ke penerima benar. Pre-arrival PIC active stop satu kali per trip_stop; jangan tampilkan toast dummy tiap refresh, jangan gunakan icon bell dengan count palsu. Empty/error/notified states jelas; deep link harus ada dan authorized (R-24/26/27).
