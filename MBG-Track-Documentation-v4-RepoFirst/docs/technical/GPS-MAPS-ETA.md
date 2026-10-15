# GPS, Maps, ETA, dan Notifikasi Pra-Kedatangan

## Sumber dan batasan faktual
Browser Geolocation `watchPosition()` menyediakan perubahan posisi saat izin diberikan pada konteks aman HTTPS. Browser dapat membatasi update ketika tab tidak terlihat/perangkat hemat baterai. Ini **bukan** bukti GPS background konsisten, dan bukan pelacakan native. [MDN](https://developer.mozilla.org/en-US/docs/Web/API/Geolocation/watchPosition). Peta dan geocoding/routing pihak ketiga tunduk kuota/terms [ORS](https://openrouteservice.org/restrictions/) dan [OSM tiles](https://operations.osmfoundation.org/policies/tiles/).

## Pilihan desain awal, status TENTATIF sampai uji provider
- Library renderer: MapLibre GL JS (opsi open source) dengan provider tiles OSM-derived yang mengizinkan traffic prototype (mis. OpenFreeMap setelah cek policy terkini).
- Route/ETA: openrouteservice public API atau server alternatif sesuai kuota; key rahasia di server, cache hasil; perhatikan bukan ETA lalu lintas live.
- Source koordinat: izin driver per trip dari `navigator.geolocation.watchPosition`, tidak perlu Google Maps berbayar.
- Interval target update **desain uji** 10–15 detik, tapi event OS/browser dapat lebih lambat; backend hanya menyimpan posisi valid menurut rate limiter.
- Refresh ETA kira-kira 60 detik atau pergerakan bermakna. Hitung sisa rute menuju **active_stop**, bukan semua sekolah sekaligus.
- Status `LIVE` untuk fresh, `STALE` bila >60 detik sejak update valid, `DISABLED` jika izin ditolak, `UNAVAILABLE` jika provider bermasalah. Angka detik adalah threshold prototype yang bisa dikonfigurasi, bukan aturan BGN.

## Trigger notifikasi pilihan
Jika ETA ke stop saat ini tersedia dan <=10 menit → satu `PRE_ARRIVAL` ke PIC tujuan, dedup `trip_stop.pre_arrival_notified_at`. Jika route API gagal tetapi *jarak rute tersisa* valid <=1 km, gunakan fallback; garis lurus Haversine bukan estimasi rute dan tidak boleh diperlakukan setara. Jika data GPS stale, **jangan** kirim ETA seolah-olah akurat. Saat driver klik arrived, notifikasi arrival berbeda dikirim ke PIC yang sama.

## Multi-stop
Trip 1: SD A -> SD B -> SMP C. Sebelum SD A dikonfirmasi selesai, hanya SD A menerima pra-kedatangan. Setelah driver menuju SD B secara valid, `active_stop` pindah ke SD B. ETA dan marker hanya untuk stop aktif; sekolah berikutnya boleh melihat status menunggu tetapi tidak menerima notif pra-kedatangan dulu.

## GPS mati/offline
- Driver tidak dipaksa membatalkan pengiriman fisik. Tampilkan last known + timestamp dan tombol coba ulang; status dilaporkan manual.
- Offline queue opsional dan harus dibatasi; jangan memutar kembali posisi offline seolah-olah live ketika sinkron. Tandai source timestamp asli dan sync timestamp.
- Maps provider down: tampilkan alamat sekolah, trip stop, nomor kontak PIC untuk internal secara sesuai izin; operasional jalan.
- Public API dilarang mengembalikan lat/lon presisi maupun ID driver; hanya status + ETA perkiraan/last updated.

## Privasi, penyimpanan, pengujian
- Driver memberikan izin lokasi per perjalanan; tracking berhenti saat selesai dan listener dilepas ketika logout/tab ditutup.
- Retensi raw GPS contoh usulan 30 hari, setelah itu hapus/aggregasi sesuai persetujuan. Tidak menyimpan GPS 24/7.
- Test dengan GPS simulasi rute, permission deny, accuracy >100m, stale, stop berpindah, duplicate notification, browser background, public leakage.
- Ukur biaya/kuota API di lingkungan demo sebelum memutuskan production provider.

## Implementasi Laravel
`GpsIngestionService`, `EtaService`, `TrackingStatus`, `TripPolicy`, cache `trip:last_location`, job `RecalculateEta`, scheduler cleanup, notifications `PreArrival` dedup di DB transaction; realtime via polling awal, broadcast private channel jika benar-benar dibutuhkan.

## State telemetry v2 — truthfulness sebelum animasi
| State | Kondisi | Dashboard/Map | ETA | Tindakan |
|---|---|---|---|---|
| `LIVE` | trip aktif, fresh fix dalam ambang produk | marker aktual + waktu | perkiraan jika routing tersedia | pantau stop aktif |
| `STALE` | tidak ada fix baru melampaui ambang | freeze marker + `lokasi terakhir HH:mm` | kosong / not available | kurir cek izin/koneksi |
| `DENIED` | izin browser ditolak | alamat/route teks | tidak dihitung otomatis | kurir berikan update manual beralasan |
| `INACCURATE` | accuracy kurang baik | berikan ikon peringatan; jangan pakai untuk trigger ETA | tunda ETA | tunggu fix lebih baik |
| `OFFLINE` | request gagal | UI “belum terkirim”, hindari spoof server | kosong | retry terkontrol |
| `COMPLETED` | tracking ended | tidak update marker | tidak ada | tidak mengirim GPS baru |

### Segmentasi stop & notifikasi
Driver menuju stop 1/2/3; hanya stop saat ini mendapat peringatan ETA. Jika ETA fresh ≤10 menit, atomic unique event per `(trip_stop_id, PRE_ARRIVAL)`; tidak memicu sekolah berikutnya. Jarak ≤1 km sebagai **fallback desain** ketika routing gagal, bukan threshold resmi BGN. Tidak kirim pre-arrival berbasis lastFix stale.

### Geolocation client
`watchPosition()` hanya dipakai pada halaman trip yang aktif dengan HTTPS dan izin sadar. Batalkan watch saat stop/unmount/logout/trip complete. Jangan janji background tracking browser. Server memvalidasi ownership, UTC timestamp wajar, geospatial boundary, status trip, jumlah update/rate, dan kualitas data. Paket UI memakai MapLibre + provider tiles yang mengikuti ToS serta openrouteservice atau alternatif terverifikasi; OSM data bukan izin otomatis memakai server tiles publik gratis tanpa batas.

### ETA disclaimer
Routing API berbasis jaringan jalan, mungkin tanpa kemacetan real-time. Label `Perkiraan ±X menit` dan `terakhir dihitung HH:mm`. Jika rute invalid, tidak bisa menghitung, atau GPS terlambat, sembunyikan angka alih-alih mempertahankan prediksi usang. Simpan update relevan pada trip_stop dengan last_eta_timestamp.

### Privasi & retensi
GPS presisi hanya untuk Kepala SPPG, driver owner, dan PIC relevan selama fase yang diizinkan (PIC bisa diberikan ETA/status tanpa titik presisi agar lebih minim data). Publik tak boleh menerima koordinat. Raw GPS retention 30 hari hanyalah placeholder keputusan; harus disepakati tim, dengan mekanisme purge teruji, tanpa menghapus event audit operasional yang sah.


## V3 | Geolocation visual truth
Marker tidak boleh bergerak ketika last fix stale, ETA tidak boleh menjanjikan live traffic jika routing provider tidak menyediakannya. Driver foreground only; status `location last updated` wajib. Publik menerima status/ETA yang diperkasar, tidak lat/lng mentah. Animation R-19 hanya memberi orientasi; risiko terutama visualisasi yang kelihatan realtime padahal data berhenti.
