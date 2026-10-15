# Edge Cases dan Tindakan Pemulihan

Semua kondisi ini perlu respons sistem yang dapat diprediksi dan pesan ke role yang tepat. **Penanganan fisik pangan tetap mengikuti SOP operasional yang kompeten**, jangan diselesaikan otomatis oleh dashboard.

| Kasus | Perilaku sistem yang diharapkan |
| --- | --- |
| Sekolah belum konfirmasi | WAITING, reminder PIC, kepala lihat pending; jangan anggap 0 |
| Sekolah libur | PIC/Kepala batalkan layanan dengan alasan; bila batch sudah diproduksi buat incident |
| Menu berubah setelah final | versi baru, plan lama tetap referensi menu version |
| Produksi target berbeda aktual | simpan dua angka, incident jika selisih memengaruhi alokasi |
| Batch gagal QC | ON_HOLD, blokir loading/release; gizi/kepala diberi tahu |
| Driver/kendaraan bentrok | tolak assignment ganda aktif; kembali penjadwalan |
| Driver ganti saat perjalanan | kepala melakukan transfer assignment beralasan dengan audit, histori driver sebelumnya tetap |
| Kendaraan rusak di tengah | INTERRUPTED dan incident; manual eskalasi, tidak auto-complete |
| GPS ditolak | TRACKING_DISABLED; status manual tetap tersedia |
| GPS terputus | STALE/last-known, jangan animasi marker seolah live |
| Internet offline driver | queue lokal best effort, jika replay tandai waktu asli; manual fallback |
| Browser dibackground | tidak menjanjikan update; tampilkan stale pada pengamat |
| Route API limit/outage | fallback alamat/rute tersimpan, ETA unavailable dan hentikan notif berbasis ETA |
| ETA loncat-loncat | hysteresis/debounce; prearrival hanya sekali per stop |
| Driver salah tap tiba | konfirmasi tindakan, warning lokasi jauh, koreksi beralasan |
| PIC tidak konfirmasi | ARRIVED_AWAITING_CONFIRMATION, kepala follow-up |
| Sekolah terima porsi kurang | service record discrepancy + incident atomik |
| Makanan tidak layak konsumsi | HELD, tidak boleh lanjut distribution; eskalasi gizi/kepala |
| Ompreng kembali kurang | hitung retur aktual terpisah + incident |
| Foto upload gagal | checkpoint pending dan exception; proses fisik tidak diblokir diam-diam |
| Foto memuat wajah siswa | private, blur/hapus sesuai prosedur dan audit, jangan publikasikan |
| Double-click submit | unique/idempotency key, hanya satu receipt/incident/notif |
| Kepala dan PIC mengedit bersamaan | row lock + version check 409, jangan overwrite data terbaru |
| PIC ubah school_id pada URL | 403/404 tanpa kebocoran data |
| Public request raw GPS/evidence | tidak ada endpoint publik atau 403/404 |
| Server restart setelah commit sebelum notify | outbox/event retry dan dedup hingga notifikasi terkirim |

## Prinsip umum
1. Jangan memalsukan hasil: null/unknown bukan nol atau sukses.
2. Jangan menutupi error backend; tampilkan pesan aman + correlation/request ID.
3. Jika operasi bisnis penting gagal sebelum commit: rollback, tidak kirim notifikasi sukses.
4. Pekerjaan di dunia nyata boleh berlanjut dengan SOP manual saat web down, lalu reconciled melalui input tertunda beralasan.
5. Setiap recovery mempertahankan actor dan timestamp; audit tidak dihapus.

## Test lintas-role yang mesti ada
Kepala data SPPG lain, PIC sekolah lain, driver trip lain, publik GPS raw; seluruhnya gagal secara aman. Test restore koneksi sesudah offline tidak memicu dua kali side effects.

## Test case abnormal prioritas v2
| ID | Kejadian | UI wajib | Backend invariant |
|---|---|---|---|
| EX-01 | sekolah A belum konfirmasi sampai cutoff | status `Menunggu`, tombol sesuai izin | plan tak silently assume 0 |
| EX-02 | menu diubah setelah dipakai | versi baru terlihat | histori menu lama immutable |
| EX-03 | batch menghasilkan 518 dari target 520 | selisih -2, incident bila aturan | actual tidak overwrite planned |
| EX-04 | QC gagal | banner tahan `DITAHAN` | batch tidak release/load |
| EX-05 | 2 trip bersamaan memuat batch sama | loading conflict, retry | sum loaded <= actual |
| EX-06 | driver stop salah sekolah | konfirmasi scope/alamat | hanya active stop dapat tiba |
| EX-07 | GPS browser ditolak | stop tetap dapat diurus manual | no fake location, audit fallback |
| EX-08 | tab driver background dan stale | marker beku + last update | ETA notif tidak dipicu data stale |
| EX-09 | routing API down | alamat+stop list | delivery tetap berfungsi |
| EX-10 | PIC submit receipt dua kali | hasil sebelumnya ditampilkan | one receipt + one incident |
| EX-11 | received=338, damaged=2 dari sent=340 | label short=2, damaged=2 subset | no double count |
| EX-12 | uji sebelum makan unsafe | pembagian terkunci | service not DISTRIBUTED |
| EX-13 | foto evidence upload gagal | `Belum terunggah` + opsi ulang | no saved photo record |
| EX-14 | pickup 340, retur 338 | selisih -2 wadah | tray incident open |
| EX-15 | kendaraan diganti saat aktif | tampil histori pergantian | driver/vehicle conflict audit |
| EX-16 | publik menebak ID internal | generic unavailable/no data | raw DB internal blocked |
| EX-17 | role dinonaktifkan saat login aktif | akses ditolak/revoked | no stale valid permission |
| EX-18 | layar 320px + keyboard terbuka | input dan submit terbaca | no viewport overflow/data loss |

Setiap kasus harus punya test fixture, expected DB row count/status, screenshot bila UI, dan penanggung jawab sprint. Case belum diuji diberi `NOT TESTED` agar tidak disangka pass.


## V3 | Resilient states
Untuk setiap edge case operasional tentukan juga visual state, teks recovery, disabled vs enabled action, dan hasil persistensi: GPS denied, ETA stale, foto gagal, status berubah bersamaan, batch unsafe, ompreng selisih, PIC offline. Anti-slop R-27 menuntut empty/loading/error; ini tidak boleh berhenti pada skeleton design tanpa endpoint nyata.
