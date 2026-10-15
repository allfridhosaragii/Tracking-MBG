# U04 — Status pengiriman publik tanpa GPS presisi

| Atribut | Nilai |
|---|---|
| Page ID | `U04` |
| Role pengakses | PUBLIK tanpa login |
| PBI | PBI-09,PBI-12 |
| Route Laravel (usulan) | `/mbg/{school:public_slug}/delivery` |
| Jenis | Spesifikasi fungsional dan kontrak komposisi UI/UX mobile-first |
| Status | SPEC APPROVED FOR IMPLEMENTATION; kode dan tes belum dibuat |

## 1. Tujuan bisnis dan kapan digunakan
Menyediakan waktu perkiraan dan status kedatangan untuk satu sekolah secara aman. Halaman ini harus membantu pengguna menjalankan pekerjaan nyata pada fase yang benar, bukan sekadar memamerkan angka. Data harus terbaca dari transaksi sumber, dan aksi langsung memengaruhi satu domain yang jelas.

## 2. Identitas, aktor dan batas cakupan
- Role: PUBLIK tanpa login.
- Sumber izin: `users.role`, `users.sppg_id` untuk staf SPPG, `users.school_id` untuk PIC, assigned `trips.driver_id` untuk kurir. Publik hanya mengambil projection sekolah aktif.

- Scope diambil dari sesi server, tidak dari input frontend. Setiap SELECT dan mutation divalidasi Laravel Policy dengan objek target; read-only bukan izin mengakses seluruh tenant.
- Hak konkret harus konsisten dengan `docs/business/ROLE-PERMISSION-MATRIX.md`; jika role tidak ada di halaman ini, tolak akses atau tampilkan read-only projection khusus yang sah.


## 3. Data/tabel dan relasi yang dibaca
- `trip_stops`
- `trips`
- school_service_records (projection only)

- Query wajib menggunakan eager-load terukur serta filter tenant. Kolom waktu disimpan sebagai UTC kemudian ditampilkan dalam zona WIB untuk skenario uji kampus.
- Jika sumber transaksi belum ada, tampilkan “Belum tersedia” atau empty state, bukan menyimpan status simulasi sebagai fakta.

## 4. Komponen fungsional halaman
- status “belum berangkat/dalam perjalanan/tiba/diterima”
- perkiraan tiba ± atau unavailable
- last updated
- indikator info belum fresh
- timeline sederhana

Setiap komponen memerlukan definisi value, unit, sumber data, dan state. Angka porsi dari `production_allocations` dan realisasi dari sumber masing-masing harus dibedakan; jangan menggabungkan target dengan aktual. Komposisi konten ditentukan oleh kontrak prioritas mobile pada lampiran v2; gunakan token pusat untuk warna/ruang.

## 5. Semua aksi pengguna pada halaman ini
- refresh status
- kembali ke menu/sekolah
- salin status umum sekolah tanpa data internal

Untuk setiap aksi tulis satu Form Request, authorization action, precondition status, efek database dan respons. Aksi baca tidak boleh menjalankan perubahan status. Bila aksi hanya preview/fill form, jangan menulis ke DB sampai pengguna mengonfirmasi.

## 6. Urutan aktivitas dari awal hingga selesai
1. Backend membentuk projection dari stop sekolah
2. jika active dan ETA valid tampil perkiraan
3. ketika arrived tampil waktu
4. jika PIC belum confirm bedakan tiba vs diterima

Alur ini merupakan *happy path* operasional; event/asynchronous worker tidak boleh melakukan aksi fisik atas nama petugas, hanya memperbarui informasi/menjadwalkan notifikasi.

## 7. Aturan validasi dan larangan bisnis
- Tidak kirim raw location/route, identitas driver atau urutan sekolah lain
- jangan animasi mobil presisi fiktif

Aturan lintas-domain: konfirmasi penerimaan hanya setelah tiba, batch QC hold tidak bisa didistribusikan, dan insiden kritis keselamatan pangan tidak bisa di-bypass dengan menekan tombol dari role lain. Double-submit harus idempotent. Transaksi kritis dicatat audit.

## 8. Hubungan antar-role, notifikasi dan status
- Trip status berubah → page data diperbarui
- tidak ada notif push anonim

Rujukan: `docs/business/STATUS-WORKFLOWS.md` dan `docs/technical/NOTIFICATION-RULES.md`. Notifikasi ditujukan hanya role yang membutuhkan aksi, bukan seluruh akun. Semua perubahan yang berhasil harus dapat diperiksa pemilik transaksi dari halaman terkait tanpa input ulang.

## 9. State dan error yang wajib ditangani
- GPS stale
- ETA provider gagal
- sekolah belum mendapat trip
- PIC tidak confirm

Selain kasus di atas: LOADING → READY/EMPTY/ERROR; 401 bila sesi habis; 403/404 resource bukan scope; 409 status berubah oleh pengguna lain; 422 input salah; 503 dependency tidak tersedia. Pesan kesalahan aman bagi pengguna dan tidak menghapus draft input bila kegagalan terjadi sebelum submit sukses.

## 10. Acceptance criteria khusus halaman
1. No GPS leak via JSON
2. timestamp jelas
3. bukan label “live” saat stale
4. status arrived berbeda received

Acceptance umum: data tenant lain tidak muncul; semua aksi memiliki test positive/negative; state kosong dan error jujur; audit perubahan; resource private tak bocor; tidak ada klik yang tampak sukses tanpa commit. Daftar test tambahan di `docs/quality/TEST-SCENARIOS.md`.

## 11. Halaman dan kontrak yang terhubung
- Navigasi/logika terkait: `U02,P03` (ID di `PAGE-REGISTRY.md`; tampilkan hanya yang relevan dengan izin user).
- Kontrak API: `docs/technical/API-CONTRACTS.md`.
- Database dan relasi: `docs/technical/DATA-DICTIONARY.md`.
- Peraturan desain bukti/ETA (jika relevan): `docs/technical/PHOTO-EVIDENCE.md`, `docs/technical/GPS-MAPS-ETA.md`.

## 12. Implementasi Laravel yang disarankan
1. Mulai dari route yang memiliki nama stabil; `auth` middleware untuk internal dan Policy model setiap resource. Public memakai controller projection tersendiri.
2. Controller hanya memvalidasi/otorisasi lalu mendelegasikan aksi ke service/domain action yang dapat diuji.
3. Service melakukan status transition/DB transaction dan audit, lalu dispatch event/notification setelah commit.
4. Buat Blade+JS secukupnya untuk interaksi halaman ini; tidak menambah fitur visual yang mengubah bisnis tanpa persetujuan.
5. Buat Feature tests: sukses, invalid 422, status ilegal 409, scope salah 403, duplikat/idempotency, serta empty/error bila sesuai.

## 13. Asumsi & pembatas implementasi
- Route adalah **rancangan**, belum terbukti dibuat di project Laravel.
- Jika backend/data masih dummy untuk demo pertama, tampilkan label *SIMULASI* jelas dan jangan mengklaim realtime sungguhan.
- Perilaku, prioritas mobile, dan standar desain ditetapkan pada lampiran v2 serta `docs/design/`; detail piksel final ditentukan lewat prototipe dan uji visual.

## 14. UI/UX Premium — kontrak khusus U04 (v2.0)

**Arah desain:** *clean luxury, task-first, tidak templated, mobile-first*. Halaman tidak boleh terlihat seperti admin WordPress/AI starter. Gunakan `docs/design/CREATIVE-DIRECTION.md`, `UI-DESIGN-SYSTEM.md`, `MOTION-GUIDELINES.md`, `MOBILE-FIRST-GUIDELINES.md`, dan `COMPONENT-SPEC.md`. Identitas visual bukan alasan menambah data atau aksi bisnis.

### 14.1 Urutan informasi mobile (390px sebagai acuan)
Sekolah+status perkiraan → ETA kalau layak → timestamp → milestones → tautan ke menu.

**Aturan fold pertama:** user harus bisa mengidentifikasi entitas, status sebenarnya, dan langkah berikutnya. Jangan pasang welcome hero/ilustrasi robot, chart dekoratif, atau card yang menggeser tugas kritis ke bawah layar. Jika bagian atas memiliki lebih dari tiga metrik, jelaskan prioritas dan pindahkan sisanya ke disclosure.

### 14.2 Perluasan tablet/desktop tanpa mengubah bisnis
Progress timeline with minimal map or no map depending on privacy; do not imitate delivery tracking consumer apps with live driver pin.

Pada >=1024px informasi detail boleh disusun berdampingan, namun urutan pembacaan screen reader dan navigasi keyboard harus tetap logis. Gunakan komposisi yang berbeda sesuai peran, bukan tiga kolom kartu yang identik pada semua halaman. Tidak ada duplikasi HTTP mutation karena layout berbeda.

### 14.3 Aksi primer, pengamanan dan umpan balik
**Aksi primer:** Refresh status/read-only; tidak ada pinpoint driver.

- Terapkan `focus-visible`, label tekstual, area tap utama >=44x44px (standar internal), indikator loading setelah pengguna mengirim perintah, dan hasil **hanya setelah server ACK**.
- Untuk mutation: komponen wajib mengetahui Policy, precondition status, validasi FormRequest, transaksi backend, audit, dan event/notifikasi. Jika status berpindah oleh pengguna lain, tangani 409 secara informatif dan refresh, jangan overwrite diam-diam.
- Untuk read-only: tetap tampilkan freshness dan empty state. Tidak boleh render tombol yang terlihat bisa dipakai tetapi secara diam-diam gagal.
- **Risiko khusus halaman:** Peta publik granular dilarang; ETA tanpa traffic bukan janji kedatangan.

### 14.4 State yang harus punya rancangan terlihat
**Skenario spesifik:** GPS stale; ETA not available; trip belum dimulai; arrived but PIC belum confirmed.

Selain itu wajib ada: LOADING terstruktur (bukan spinner full page yang terus berputar), EMPTY dengan alasan serta next action, OFFLINE tanpa sukses palsu, 401/403/404 sesuai respons aman, 422 error dekat field, 409 concurrent change, dependency unavailable, dan SUCCESS setelah commit. Tampilkan tanggal/jam WIB jika relevan, bedakan real vs estimasi; status jangan hanya warna.

### 14.5 Motion, aksesibilitas dan feedback
- Transisi masuk/keluar halaman tidak boleh menghalangi tombol kritis. Gunakan prinsip 120–260ms untuk transisi pendek **hanya bila mendukung orientasi**; uji pada perangkat menengah.
- Indikator perubahan angka/status terjadi karena data yang telah tersimpan, tidak karena animasi client atau timer demo.
- `prefers-reduced-motion` menghapus motion non-esensial; focus order konsisten; tab, Enter, Escape dan browser Back dapat dipakai.
- Semua informasi tetap terbaca pada 320px dan zoom 200%; sticky CTA tidak menutupi field saat keyboard HP muncul.

### 14.6 Test case nyata khusus halaman
Tes projection `EN_ROUTE`/`ARRIVED`, no lat/lng in response HTML/API, stale handling.

Tambahkan minimal satu test happy-path, satu invalid, satu unauthorized/cross-tenant (jika ada sumber privat), satu stale/offline (jika relevant), dan satu visual screenshot 390px + 1440px. Test dibuat dari perilaku yang benar-benar diimplementasikan; jangan tanda PASS jika belum dijalankan.

### 14.7 Handoff implementasi tanpa kehilangan konteks
- Baca file ini bersama PBI, role, status, data dictionary, API contract; jangan membaca seluruh 76 dokumen sekaligus.
- Hasilkan view/komponen yang mematuhi token `docs/design/UI-DESIGN-SYSTEM.md`, bukan salin template pihak lain.
- Laporkan nama route nyata, controller/action, Policy, query & data source, Feature tests, screenshot mobile/desktop, status reduced-motion, dan perubahan schema (jika ada) di `docs/project/HANDOFF-LOG.md`.
- Kriteria visual final dirinci dalam `docs/design/UI-QUALITY-CHECKLIST.md`. **File spec bukan bukti page sudah tampil, lolos tes, atau berkualitas premium sampai diuji.**


## 15. Anti-Slop v3.0: page-specific composition and evidence contract

> **Interpretasi untuk PBI/role ini, bukan template umum.** Baca `DESIGN.md` (root dokumentasi v3) + `docs/anti-slop/RULE-MATRIX.md`, dan periksa kode yang sudah ada terlebih dahulu. Rujukan rule adalah adaptasi repo `miqdadbadjuber/anti-slop`; file ini bukan hasil audit browser.

### 15.1 Design Read dan dial wajib
**Reading as:** `U04` (`PUBLIK tanpa login`; PBI-09,PBI-12) adalah layar untuk **status kedatangan tanpa pelacakan presisi**, dengan bahasa visual *MBG-Track Calm Operational Editorial*, **ENERGY 2 / RHYTHM 3 / MOTION 2**.

**Focal point first fold:** Status kedatangan tanpa pelacakan presisi. Hindari welcome hero/beranda marketing. Bila data belum ada, tampilkan status yang jujur di tempat focal point tersebut, tidak mengisinya dengan angka simulasi seolah transaksi hidup.

### 15.2 Komposisi visual yang membedakan halaman ini
Simple stepper produksi → dikirim → diterima, teks perkiraan dan updated-at, tidak ada map jalan yang membocorkan kurir. Prinsip R-05/R-14: bentuk layout harus mengikuti semantik data. **Jangan** membuat sederetan card identik atau menyalin struktur role lain. Tetapkan satu alasan konkret untuk tiap perubahan jarak, ukuran, aksen, dan elevasi (R-31).

**Pada layar HP:** mulailah 390px, lalu validasi 320/360/430, pastikan komponen pusat dan aksi primer terlihat tanpa horizontal page scrolling. Konten numerik/identitas yang panjang harus wrap atau beralih ke format stacked; pada 768/1440 tambah detail tanpa membuat pengguna membaca informasi yang berbeda semantik. Pada zoom 200% dan keyboard terbuka, aksi utama tidak boleh tertutup.

### 15.3 Action dan honesty contract
**Label tindakan primer yang disarankan:** `Kembali ke MBG hari ini`. Label akhir dapat menyesuaikan status aktual, tetapi tidak menjadi "Get Started" / "Explore" / "Submit" generik (R-15). Jika aksi belum tersedia atau route belum diimplementasikan, **jangan menampilkan tombol aktif palsu** (R-24/R-26/R-35). Hubungkan tombol ke authorization, Form Request, service, transaksi serta feedback setelah server ACK.

**Larangan khusus:** Raw lat/lng, foto bukti dan identitas driver tidak boleh ada di public JSON. Larangan ini harus dipastikan lewat output response/HTML, bukan sekadar disembunyikan pada CSS. Jika komponen membutuhkan aset dan belum disetujui, tampilkan `[ASET BELUM DISETUJUI]` (R-23/38); jangan generate gambar seolah bukti operasional asli.

### 15.4 Data states dan motion nyata
**Skenario khusus halaman:** tracking belum dimulai, ETA stale, koneksi offline. Untuk masing-masing siapkan text state, disabled/available actions, recovery next step dan konsekuensi audit. Loading/Empty/Error minimal (R-27), ditambah 401/403/409/422/offline bila relevan. Status dan error memakai teks, bukan warna saja.

**Motion policy:** energy 2, motion 2. Gunakan transisi pendek karena state benar-benar berubah: disclosure, feedback server, atau perubahan indikator timeline. Jangan memicu fake success, floating decoration, autoplay data atau fake LIVE; terapkan `prefers-reduced-motion`. R-19 boleh meluluskan animasi hanya jika tujuannya dapat ditulis dalam satu kalimat.

### 15.5 R-XX review yang paling berisiko untuk halaman ini
- **R-03**: screenshot mobile nyata 320/390/430, test overflow dan input focus.
- **R-17/R-38**: nilai/label berasal dari tabel dan status PBI ini; fixture SIMULASI selalu diberi label.
- **R-24/R-26/R-35**: setiap kontrol yang terlihat memiliki aksi/route nyata dan diverifikasi secara click-through.
- **R-25/R-32**: kontras teks AA, focus terlihat, keyboard dan screen-reader basic.
- **R-27**: empty/loading/error yang sesuai dengan skenario di atas.
- **R-31/R-37**: satu alasan layout + dials tercatat sebelum implementasi.
- **C-2/C-4/C-5**: perilaku lengkap, resilient saat error, dan klaim berbukti.

**Bukti khusus yang wajib dibuat oleh implementer:** Uji no GPS columns on payload, copy ETA tidak pasti. Lampirkan nama route asli, `php artisan test` filter kasus terkait, `npm run build`, screenshot `<PAGEID>-390-normal`, `<PAGEID>-390-error`, `<PAGEID>-1440-normal` beserta commit; jika belum diuji beri `NOT VERIFIED`. Perubahan dokumentasi/log dilakukan di folder kerja Laravel **di luar** seluruh folder dokumentasi terlindungi.

## 16. Upstream-original anti-slop quality contract (v4, Page U04)

**Primary authority:** read original Laravel-root `.agents/skills/antislop/SKILL.md` before generating this page, then original `antislop-ui`, `antislop-human`, `antislop-layoutmobile`, `antislop-copywriting` as relevant. **Do not substitute** `docs/anti-slop/RULE-MATRIX.md` for missing native SKILL. Design direction remains `../../../DESIGN.md` relative to this file; functional contract remains Sections 1–15.

**Non-generic intent specific to U04:** Menu/gizi/timeline publik yang informatif; tidak ada GPS, foto privat, testimonial dan statistik fabricated.

**Acceptance proof:** state one specific focal point for this role/page, the ENERGY/RHYTHM/MOTION dials and visual reason; test actual control actions, empty/loading/error states, 320/360/390/430/768/1440 and 200% zoom, accessible keyboard/focus/contrast, reduced-motion, server authorization and source data; attach screenshot/test evidence. Before claiming completion run the **original upstream four-block Delivery Gate**. No evidence => `NOT VERIFIED`, not PASS. Existing UI audit first uses **After** and waits for approved findings; new UI uses **During**.
