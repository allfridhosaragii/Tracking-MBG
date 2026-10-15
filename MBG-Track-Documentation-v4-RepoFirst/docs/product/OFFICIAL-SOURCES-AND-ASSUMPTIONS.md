# Register Bukti, Asumsi dan Keputusan Desain

**Tanggal penelusuran:** 12 Oktober 2026. Referensi daring dapat berubah. Tautan adalah rujukan untuk pembacaan/verifikasi ulang, bukan jaminan akses tanpa internet.

## A. Sumber primer MBG
| Sumber | Hal yang didukung | Bukan bukti untuk |
|---|---|---|
| [Juknis MBG TA 2026](https://cdn-web.bgn.go.id/juknis/01KFZQGR85YKY1HRMNDK4RRM1P.pdf) | SPPG menyediakan dan mendistribusikan makanan, pengawasan gizi/sanitasi, penyerahan dan pelaksanaan program | Tidak menentukan desain tabel MySQL atau ritme GPS 15 detik |
| [Portal SIPGN](https://portal-doc-sipgn.bgn.go.id/panduan-sipgn) | Ada ekosistem digital dengan modul terpisah | Bukan akses API publik bagi tugas kami |
| [Menu Planner](https://portal-doc-sipgn.bgn.go.id/panduan-sipgn/persiapan-produksi/menu-planner) | Komposisi/gizi dan review menu | Bukan validasi otomatis gizi hasil input mahasiswa |
| [POP](https://portal-doc-sipgn.bgn.go.id/panduan-sipgn/produksi/pop) | Monitoring memasak, pemorsian, batch dan kegiatan produksi | Tidak berarti sistem prototype terintegrasi dengan POP |
| [Fleet](https://portal-doc-sipgn.bgn.go.id/panduan-sipgn/distribusi/fleet) | Referensi alur distribusi dan serah-terima; akses URL kadang gagal, verifikasi ulang saat presentasi | Tidak berarti aplikasi harus meniru seluruh fitur Fleet |
| [Penerima Manfaat](https://portal-doc-sipgn.bgn.go.id/panduan-sipgn/penerima-manfaat/penerima-manfaat) | Referensi konfirmasi penerimaan PIC; akses URL kadang gagal | Tidak ada alasan menyimpan biodata siswa |

## B. Dokumen teknologi primer
- [Laravel 13 authorization](https://laravel.com/framework/docs/13.x/authorization): middleware, Gates, Policies.
- [Laravel notifications](https://laravel.com/framework/docs/13.x/notifications): database notifications.
- [MDN browser Geolocation](https://developer.mozilla.org/en-US/docs/Web/API/Geolocation/watchPosition): memerlukan HTTPS, permission; bukan background tracker yang dijamin terus jalan.
- [Kebijakan tile OSM](https://operations.osmfoundation.org/policies/tiles/): data OSM bebas, server tile publik terbatas, tanpa SLA; provider tiles harus dipilih berdasarkan syarat.
- [Batas public openrouteservice](https://openrouteservice.org/restrictions/): kuota/batas penggunaan; ETA estimasi jalan tanpa jaminan traffic real-time.

## C. Keputusan DESAIN aplikasi, bukan aturan BGN
| Kode | Keputusan | Alasan | Batas verifikasi |
|---|---|---|---|
| D01 | Prototype menguji 1 SPPG → 3 sekolah | Skenario sederhana membuktikan multi-tenant dan multi-stop | Jangan sebut statistik rata-rata lapangan |
| D02 | Orang tua/siswa publik tanpa akun | Minimum data pribadi dan friction kecil | Publik tidak memverifikasi siswa benar-benar makan |
| D03 | PIC konfirmasi agregat porsi, bukan identitas siswa | Menekan risiko data anak | Tidak berarti presensi resmi memakai agregat saja |
| D04 | ETA ≤10 menit sebagai event 1× per stop | Persiapan penerimaan tanpa spam | Tuning lewat uji simulasi; bukan SLA |
| D05 | GPS saat trip aktif/halaman driver terbuka | Batas browser dan privasi pekerja | Tidak menjamin background tracking |
| D06 | Evidence photo checkpoint, hindari foto wajah anak | Bukti perpindahan tanggung jawab | Foto sendiri tidak menjamin isi pangan aman |
| D07 | Uji organoleptik berupa pencatatan manusia | Sistem digital tidak menilai keamanan pangan otomatis | Protokol substantif harus mengikuti ahli/pedoman |
| D08 | Retensi raw GPS terbatas contoh 30 hari | Minimalkan data lokasi | Angka wajib dikonsultasikan tim/kebijakan, belum resmi |
| D09 | Dokumentasi 12 PBI dan 34 page unik | Memenuhi skala LMS tanpa 53 implementasi terpisah | Dokumen bukan bukti fitur telah dikodekan |

## D. Aturan mengutip ke proposal
Tuliskan: “Berdasarkan Juknis/portal BGN..., prototype kami mengusulkan...” Alih-alih: “BGN mewajibkan update GPS 15 detik”. Beri tanggal dokumen dan halaman/pasal saat dikutip. Bila tautan tidak dapat diakses, tandai *belum terverifikasi ulang* dan jangan mengarang kutipan.

## E. Sumber model AI (riset hingga tanggal dokumen)
- OpenAI GPT-6 ChatGPT: https://help.openai.com/en/articles/6825453-chatgpt-release-notes; model picker: https://help-lb.openai.com/en/articles/20001354-gpt-6-and-other-models-in-chatgpt.
- Google Gemini 3.8 / model list / thinking: https://ai.google.dev/gemini-api/docs/latest-model ; https://ai.google.dev/gemini-api/docs/models; https://ai.google.dev/gemini-api/docs/thinking.
- Anthropic model lineup / effort: https://platform.claude.com/docs/en/models/overview; https://platform.claude.com/docs/en/build-with-claude/effort.
- Moonshot Kimi model/mode: https://www.kimi.com/en/help/others/model-mode-selection; https://www.kimi.ai/id/ai-models/.
- DeepSeek API dan thinking: https://api-docs.deepseek.com/; https://api-docs.deepseek.com/guides/thinking_mode/.
- Z.ai GLM-5.3 dan API: https://z.ai/blog/glm-5.3; https://docs.z.ai/guides/overview/quick-start.
Perbedaan kemampuan antar-model tidak disimpulkan dari benchmark vendor saja. Tes ulang di repo dengan task, budget dan harness sama sebelum menetapkan pilihan permanen.

## Verifikasi ulang dan tingkat kepastian (snapshot 12 Oktober 2026)
| Klaim | Kategori | Perlakuan di proposal |
|---|---|---|
| BGN memiliki proses SPPG/produksi/distribusi/pemeriksaan | rujukan proses resmi yang perlu dikutip per dokumen yang tepat | tautkan pedoman/portal, jangan mengaku terintegrasi |
| SIPGN memiliki modul Menu Planner, POP, Fleet, Penerima Manfaat | dokumentasi publik SIPGN | gunakan sebagai inspirasi pemodelan, bukan izin API |
| Koordinat kurir dikirim tiap 10–15 detik | **asumsi desain prototype** | beri label tunable, bukan Juknis |
| ETA ≤10 menit memicu notifikasi satu kali | **keputusan desain prototype** | tes kegunaan; bukan standar nasional |
| Detail UI premium dan token warna | **keputusan kreatif tim** | tidak berasal dari BGN |
| Kewajiban menyimpan identitas siswa individu | **bukan kebutuhan MVP** | hindari klaim atau pengumpulan data |
| Model AI tertentu terbaik di semua tugas | **belum dibuktikan untuk repo** | gunakan benchmark internal hasil nyata |

### Sumber UI/teknologi yang digunakan v2
- WCAG 2.2: https://www.w3.org/TR/WCAG22/ . Touch target minimal 24x24px dengan pengecualian (AA); **standar internal proyek 44px untuk aksi primer**, jangan mencampur keduanya.
- MDN `prefers-reduced-motion`: https://developer.mozilla.org/en-US/docs/Web/CSS/@media/prefers-reduced-motion .
- Laravel 13 Authorization/Policies: https://laravel.com/framework/docs/13.x/authorization . Pilihan versi proyek diverifikasi saat `composer.json` dibuat.
- OpenAI API GPT-6: https://developers.openai.com/api/docs/guides/latest-model .
- Gemini 3.8 Flash: https://ai.google.dev/gemini-api/docs/latest-model .
- Claude model overview: https://platform.claude.com/docs/en/models/overview .
- Kimi K3 announcement: https://forum.moonshot.ai/t/kimi-k3-is-here-our-most-capable-model/480 .
- DeepSeek thinking + model updates: https://api-docs.deepseek.com/guides/thinking_mode/ ; https://api-docs.deepseek.com/updates/ .
- Z.ai GLM-5.3: https://z.ai/blog/glm-5.3 .

### Cara audit data sumber
Sebelum menerapkan aturan operasional yang mungkin berubah, buka PDF/halaman resmi paling baru, cari tanggal versi, pasal/halaman, periksa bukti visual jika PDF, dan catat pada Decision Log. Jika belum bisa dibuka, beri label BELUM DIVERIFIKASI—jangan menyalin dari ingatan atau membuat angka pengganti.


## V3 | Provenance and dated claims
Tambahkan tag saat mengutip: `[BGN-SOURCE]`, `[PROJECT-DECISION]`, `[DEMO]`, `[UNVERIFIED]`. Upstream anti-slop R-17/36/38 melarang statistic/authority palsu, konsisten dengan keharusan membedakan juknis aktual dengan asumsi produk. Tidak ada label `real time verified` tanpa waktu sumber atau pengukuran update status.
