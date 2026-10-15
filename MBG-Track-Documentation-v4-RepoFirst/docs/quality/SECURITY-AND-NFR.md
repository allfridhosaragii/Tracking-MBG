# Nonfunctional Requirements — Measurable Design Targets

> Angka target di bawah adalah **target pengujian prototype** dengan data demo, bukan SLA nasional maupun ketentuan BGN.

## Keamanan
- HTTPS untuk semua internal dan GPS; Laravel session authentication, CSRF, password hash modern bawaan, rate limit login/endpoint GPS.
- Policy setiap model dan object-level authorization; `sppg_id`, `school_id`, ownership assignment; query database terfilter **sebelum** pagination.
- Foto evidence pada private storage, URL terautentikasi, MIME valid, file size limit; tidak ada public directory raw evidence.
- Semua error tidak membocorkan SQL trace, stack exception, nama file internal; `.env` rahasia; permission paling rendah.

## Privacy
- Tidak ada akun siswa atau data individual anak; halaman publik school-level saja.
- Lokasi presisi hanya selama tugas driver aktif; PIC dapat memantau trip menuju sekolahnya saat perlu, publik hanya ETA/status.
- Retensi GPS 30 hari adalah rekomendasi awal yang perlu persetujuan, bukan hukum; catatan foto/audit memiliki retensi tersendiri dan kebutuhan kepatuhan harus diverifikasi.
- Bukti foto tidak mengambil wajah siswa; jika ada secara tidak sengaja, batasi atau buramkan sebelum publikasi (pada MVP bukti tidak publik).

## Kinerja dan beban demo
- P95 halaman CRUD internal <2 detik dan dashboard <3 detik pada dataset uji 1 SPPG/3 sekolah dan jaringan stabil, diukur secara nyata; jangan klaim sudah tercapai.
- GPS maksimal satu update valid per ±10–15 detik (target, browser best effort), stale >60 detik configurable.
- Sistem dapat tetap dipakai bila maps/route API gagal; alamat serta status manual tersedia.
- Paginasi semua tabel histori; indexing date, school, tenant, status, trip; queue notif.

## Keandalan/integritas
- DB transaction untuk receipt/quantity/incident/audit; idempotency untuk tombol ganda.
- Cross-tenant dan status ilegal ditolak server; perubahan tidak menghapus histori.
- Backup database setidaknya sebelum demo/deploy; pengujian restore; catat hasilnya.
- Date/time disimpan UTC, ditampilkan dengan zona WIB (Asia/Jakarta) untuk demo.

## Aksesibilitas/operasional
- Web bisa dipakai HP untuk driver/PIC; bukan jaminan GPS aktif di background.
- Pesan error menjelaskan langkah aman; konfirmasi operasi irreversible; empty state dan stale indicator.
- Tidak ada status “sudah dimakan semua siswa” tanpa bukti agregat PIC; gunakan “kegiatan pembagian selesai”.

## Pembuktian
Masing-masing NFR punya test/observasi di `TEST-SCENARIOS.md`; tugas dinyatakan completed setelah hasil benar-benar dijalankan, bukan hanya tertulis.

## Threat model praktis v2
| Ancaman | Contoh | Kontrol | Bukti test |
|---|---|---|---|
| IDOR | PIC A GET `/school/receipts?id=B` | Policy resource + scoped query | 403/404 B, no leaked fields |
| cross-SPPG | Kepala X edit trip Y | SPPG ownership in service | 403 & DB unchanged |
| privilege escalation | request role `KEPALA_SPPG` | role field not mass assign | 422/403 and audit |
| race condition | dua user load batch sama | transaction + row lock + invariant | no overbooking batch |
| duplicate mutation | submit receipt berkali-kali | idempotency key / constraint | one record/event |
| sensitive file | guessed private evidence URL | private storage + Policy + short-lived signed link | no unauthorized retrieval |
| GPS leakage | public JSON includes lat/lng | explicit DTO projection | serialized response test |
| fake live/ETA | last update 5 min old | server-based freshness | stale UI and no notif |
| XSS/upload | notes + malicious image | escape Blade, MIME validation, malware considerations | escaping/file rejection |
| CSRF/session | POST forged cross-site | session CSRF and secure cookies | CSRF test |

### Quality attributes dan target usulan
- WCAG 2.2 AA sebagai sasaran aksesibilitas; touch controls utama 44px **internal best practice**, bukan klaim minimal universal WCAG.
- Mobile viewport 320px masih dapat reflow; test di 360/390/430px, 768px, 1440px.
- Performa target awal halaman operasional di jaringan kampus/Wi-Fi/lokasi lapangan harus diukur dengan benchmark, jangan mencatat target sebagai hasil terukur.
- Backend memastikan audit immutability, input validation, queue retries, tenancy, no false status.
- Informasi privasi: tidak ada daftar nama siswa atau wajah anak dari bukti; GPS presisi hanya trip aktif dan akses terkait.
- Secrets hanya `.env`/secret manager, tidak dimasukkan ke Markdown/commit/tool prompt.

### Deployment & storage
Aktifkan HTTPS, `APP_DEBUG=false`, CORS/CSRF sesuai model auth, backup DB, cron scheduler, queue worker untuk notifikasi, object storage private bila tersedia. Purge dan retensi ditentukan tertulis; jangan hapus audit yang dibutuhkan tanpa kebijakan.


## V3 | Claims need evidence
Jangan menulis "WCAG AA compliant", "secure", "fast", "100% live" tanpa laporan pengujian dan ukuran. Asset privat/GPS scoped dan auth dibuktikan oleh tests; contrast dan 200% zoom dibuktikan browser. `DESIGN.md` bukan hasil audit. Usulan performa ms hanya target internal, bukan SLA resmi. R-25/R-32/R-35 adalah must-fix.
