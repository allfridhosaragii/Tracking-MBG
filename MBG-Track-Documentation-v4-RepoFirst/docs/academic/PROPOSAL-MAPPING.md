# Mapping Dokumentasi ke Template Proposal PPL Dosen

Template sumber adalah `Template Proposal PPL_V01.docx` pada link LMS yang diberikan. Struktur berikut dibaca langsung dari isinya pada percakapan; jangan mengganti format/susunan tanpa izin dosen.

| Bagian template | Sumber `.md` | Output tambahan untuk proposal |
|---|---|---|
| Sampul, tim, tanggal | README + SPRINT-PLAN | Nama/NIM sebenarnya; jangan karang anggota |
| Pendahuluan / Latar Belakang | PRODUCT-BRIEF + OFFICIAL-SOURCES | Masalah aktual dengan sitasi yang tepat; jangan klaim belum ada SIPGN |
| Tujuan dan Output | PRODUCT-BRIEF + REQUIREMENTS-AND-SCOPE | Batas prototype dan hasil terukur |
| Usulan Solusi / Deskripsi Produk | PRODUCT-BRIEF + BUSINESS-FLOWS | Ringkasan manfaat per role |
| Proses Bisnis Existing | BUSINESS-FLOWS | BPMN existing sebagai baseline analisis, perlu validasi lapangan |
| Proses Bisnis Usulan | BUSINESS-FLOWS + STATUS-WORKFLOWS | BPMN usulan lengkap swimlane per role |
| Kebutuhan Fungsional | PBI-BACKLOG + API-CONTRACTS | Tabel ID, kebutuhan, deskripsi, kategori pengguna |
| Karakteristik Pengguna | ROLE-PERMISSION-MATRIX | Tabel kategori, deskripsi, hak akses |
| Non Fungsional | SECURITY-AND-NFR | Kebutuhan terukur dan cara pengujian |
| Teknis | LARAVEL-ARCHITECTURE + GPS-MAPS-ETA | Alasan stack Laravel/MySQL/Maps |
| Use Case & scenario | PAGE-FEATURE-SPEC + PAGE-REGISTRY | Gambar use case + scenario per aksi inti |
| Arsitektur Sistem | LARAVEL-ARCHITECTURE | Diagram arsitektur, literatur terbaru |
| ERD | DATABASE-ERD + DATA-DICTIONARY | Diagram kardinalitas, penjelasan FK |
| Class Diagram | DATA-DICTIONARY + API-CONTRACTS | Diagram kelas model/domain **dibuat tersendiri**, bukan duplikat ERD mentah |
| Mockup | docs/pages/* | Visual mockup Figma per role, setelah spesifikasi fungsional |
| Metode Pengembangan | SPRINT-PLAN | Jelaskan Scrum dan peran aktual |
| Jadwal Pengembangan | SPRINT-PLAN | Tabel sprint, per tanggal nyata |
| Tim Pengembang | SPRINT-PLAN | Anggota, peran, PBI, tanggung jawab aktual |

## Peringatan keakuratan
Angka porsi demo dan nilai gizi contoh tidak boleh disebut data SPPG nyata. Kebutuhan 12 PBI harus dibahas sebagai rencana, bukan fitur yang sudah selesai bila belum coding. Semua referensi Juknis dicantumkan tautan dan tahun. Diagram/mocks masih wajib dibuat dan diperiksa sendiri meski struktur `.md` sudah lengkap.

## Pemetaan bukti dan demonstrasi ke proposal dosen v2
| Bagian proposal | Dokumen sumber | Artefak yang perlu dibuat |
|---|---|---|
| Latar belakang & SDG 17 | PRODUCT-BRIEF, OFFICIAL-SOURCES | masalah berbasis sumber, sitasi bertanggal |
| Tujuan, output, batasan | REQUIREMENTS-AND-SCOPE | jelas bahwa prototype bukan sistem resmi |
| Existing & usulan proses bisnis | BUSINESS-FLOWS, STATUS-WORKFLOWS | BPMN asli/diusulkan yang digambar dan diverifikasi |
| Kebutuhan fungsional | PBI-BACKLOG, PAGE-REGISTRY | 12 PBI satu objective per item |
| Karakteristik pengguna | ROLE-PERMISSION-MATRIX, ROLE-EXPERIENCE | tabel role permission, bukan role siswa |
| Non-fungsional & teknis | SECURITY-AND-NFR, LARAVEL-ARCHITECTURE, UI guides | target terukur dan stack Laravel |
| Use case & skenario | PBI, PAGE-FEATURE-SPEC | diagram dan scenario per actor penting |
| Arsitektur | LARAVEL-ARCHITECTURE, API-CONTRACTS | Laravel monolith, browser, DB, maps, jobs |
| ERD & class diagram | DATABASE-ERD, DATA-DICTIONARY | gambar relasi + penjelasan source-of-truth |
| Mockup | 34 page specs + 7 design docs | mockup mobile awal untuk enam role & publik |
| Metode & jadwal | SPRINT-PLAN, HANDOFF-LOG | sprints, dependensi, tanggung jawab 4 anggota |

**Catatan akademik:** berkas markdown adalah rancangan proyek; diagram BPMN/use-case/class diagram dan mockup **belum otomatis menjadi artefak final** hanya karena namanya disebut. Dosen dapat meminta screenshot implementasi nyata dan bukti pengujian. Kutip BGN dengan tautan + tanggal dan cek detail pada sumber primer, bukan menyalin asumsi aplikasi sebagai aturan nasional.


## V3 | Dokumentasi tidak menggantikan laporan akademik
Rancangan v3 meningkatkan acceptance UI, tetapi jangan menambah scope proposal dosen. Jika proposal mengklaim usability/accessibility, harus ada skenario uji dan hasil nyata; sebutkan fitur GPS browser sebagai prototype foreground dan data sekolah sebagai DEMO. Tidak boleh mengarang sitasi BGN atau testimony agar tugas terlihat kredibel.
