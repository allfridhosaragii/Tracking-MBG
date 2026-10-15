# Decision Log — Keputusan dan Alasan

Keputusan disepakati dalam percakapan desain hingga 12 Oktober 2026. Label LOCKED adalah baseline proyek, bukan hukum eksternal.

| ID | Keputusan LOCKED | Alasan / risiko yang dijaga |
| --- | --- | --- |
| ADR-001 | Laravel monolith + MySQL | Kewajiban dosen; kurangi kompleksitas microservice |
| ADR-002 | SDG17 + MBG-Track prototype independen | Koordinasi lintas mitra; tidak mengklaim resmi BGN |
| ADR-003 | 12 PBI, login di luar | Sesuai LMS; perlu jaga satu tujuan bisnis tiap PBI |
| ADR-004 | 1 SPPG → banyak sekolah | Status per stop, bukan status global |
| ADR-005 | 5 login internal role + publik tanpa login | Least privilege; tanpa akun siswa individual |
| ADR-006 | Porsi kebutuhan dikonfirmasi PIC | Sekolah pemilik angka; tidak input ulang |
| ADR-007 | Menu berversi dan quality gate | Riwayat gizi/keamanan tak berubah diam-diam |
| ADR-008 | Trip delivery/pickup bisa berbeda | Ompreng perlu lifecycle tersendiri |
| ADR-009 | Checkpoint foto bukan tiap klik | Kurangi beban; bukti perpindahan tanggung jawab |
| ADR-010 | GPS browser saat perjalanan aktif | Jaga privasi dan batasan platform; fallback manual |
| ADR-011 | ETA ≤10 menit + fallback <=1km rute | Notifikasi persiapan yang berguna; sekali per stop |
| ADR-012 | Publik tidak mendapat koordinat presisi | Lindungi petugas/lokasi aktif |
| ADR-013 | Audit append-only; no hard delete transaksi | Jejak koreksi dapat dipertanggungjawabkan |
| ADR-014 | 34 specs page unik untuk 53 area fungsional | Satu kontrak halaman per workflow, hindari duplikasi role |
| ADR-015 | Multi-AI berbagi satu AGENTS/PBI/DB | Hindari penulisan kode saling kontradiksi |
| ADR-016 | Perubahan ERD v1.0 → v1.1 | Tambah stop_type, return ready_count, many-to-many delivery items; perlu review sebelum migration |

## Cara menambah keputusan
Gunakan format `ADR-0XX — Context → Options → Decision → Consequences → Affected Docs → Approved by → Date`. Jangan mengubah status LOCKED tanpa persetujuan manusia; bila ditemukan celah keamanan atau konflik, tandai `REVIEW REQUIRED` sampai disepakati.

## Decision log tambahan — v2, 12 Oktober 2026
| ID | Keputusan | Alasan | Status |
|---|---|---|---|
| ADR-UI-01 | clean luxury, bukan WordPress/AI admin template | kualitas produk dan orisinalitas | LOCKED sebagai arah |
| ADR-UI-02 | mobile-first 390px; wajib test 320–1440px | role lapangan memakai HP | LOCKED sebagai quality gate |
| ADR-UI-03 | transisi relevan dan reduced-motion | fokus, aksesibilitas, performa | LOCKED sebagai pedoman |
| ADR-UI-04 | seven design docs + appendix di 34 halaman | mencegah tiap agent desain berbeda | LOCKED dokumen |
| ADR-TECH-01 | Blade/Tailwind/Alpine atau Livewire: pilih satu pola dominan | menghindari overhead frontend tidak perlu | OPEN: final stack saat bootstrapping |
| ADR-DATA-01 | stop DELIVERY vs PICKUP, ompreng unit terpisah | akurasi fisik | LOCKED konseptual |
| ADR-OPS-01 | foto checkpoint dengan exception bila tak tersedia | lapangan tetap bisa beroperasi secara aman | OPEN: approval SOP exception |
| ADR-SEC-01 | public projection tanpa raw GPS/foto internal | privasi kurir/sekolah/siswa | LOCKED |

Setiap keputusan OPEN tidak boleh disebut kebijakan pemerintah atau implementasi final. Perubahan ke schema dan fitur harus menyebut PBI/page/test impacted. Catat yang menyetujui dan commit/issue saat pengembangan mulai.


## V3 | Protected documentation logging
File ini adalah **template asal yang dilindungi** sesudah ZIP diekstrak. Untuk keputusan source Laravel aktual tulis log di `P:\Tugas PPL MBG\DECISION-LOG.md` di luar kedua folder dokumentasi. Tambahkan alasan warna/layout/font/motion sesuai R-31 di log kerja, sertakan commit dan screenshot; jangan mengubah dokumen snapshot secara diam-diam.
