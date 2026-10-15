# Upstream anti-slop research | provenance and scope

> MBG-Track v3.0 | Asal inspirasi: [anti-slop oleh Miqdad Badjuber](https://github.com/miqdadbadjuber/anti-slop/tree/9685ca7eac70283d083fc23baa9a88e7d3cd93c4) (MIT, snapshot 12-10-2026). Dokumen ini adalah **adaptasi orisinal khusus MBG-Track**, bukan salinan skill resmi ataupun hasil pengujian aplikasi.


## Identitas sumber yang benar-benar diperiksa
- Repo resmi: https://github.com/miqdadbadjuber/anti-slop
- Commit `main` diperiksa: [`9685ca7eac70283d083fc23baa9a88e7d3cd93c4`](https://github.com/miqdadbadjuber/anti-slop/tree/9685ca7eac70283d083fc23baa9a88e7d3cd93c4); tanggal commit berdasarkan GitHub: 12 Oktober 2026.
- README menyebut versi **v3.2.20** pada saat diperiksa. Versi plugin/model dapat berubah dan tidak boleh dianggap permanen.
- Lisensi: [MIT, copyright (c) 2026 Miqdad Badjuber](https://github.com/miqdadbadjuber/anti-slop/blob/9685ca7eac70283d083fc23baa9a88e7d3cd93c4/LICENSE).
- Sumber yang dibaca: `README.md`, `GUIDE.md`, `skills/antislop/SKILL.md`, `skills/antislop-ui/SKILL.md`, `skills/antislop-layoutmobile/SKILL.md`, `skills/antislop-human/SKILL.md`, `skills/antislop-copywriting/SKILL.md`, `skills/antislop-code/SKILL.md`, serta struktur Git tree `main`.

## Hasil audit struktur repo
| Area upstream | Apa yang tersedia | Adaptasi MBG-Track |
|---|---|---|
| Core (`antislop.md`, `skills/antislop/SKILL.md`) | 38 aturan R-01..R-38 dengan tiga tier, craftsmanship C1..C5 | `RULE-MATRIX.md`, `DELIVERY-GATE.md` dan rujukan dalam setiap halaman |
| Six skills | core, UI, copywriting, human/a11y, layoutmobile, code comments | enam lensa terpisah di folder `skills/`; tidak semua harus dipakai tiap perubahan |
| During / After | prevention selama implementasi vs audit pasca-implementasi dengan approval temuan | `DURING-WORKFLOW.md`, `AFTER-AUDIT.md` |
| Liveliness | dials ENERGY/RHYTHM/MOTION, Design Read, focal/whitespace/accent/motif | `DESIGN.md`, `LIVELINESS-DIALS.md`, profil per halaman |
| Delivery Gate | 4 blok mandatory PASS/FAIL, bukti bukan label | `DELIVERY-GATE.md`, `docs/quality/ANTISLOP-ACCEPTANCE-MATRIX.md` |
| CLI `cli/`, settings, plugins | `npx antislop-ai`, beberapa plugin doors, opt-in usage mode, file pointer per client | `INSTALLATION-AND-AGENTS.md`; **tidak** menjalankan installer otomatis |
| `GUIDE.md` | panduan pemilihan instalasi/update/removal, indeks pola | ringkasan desain terarah dan install aman, bukan copy code |
| `rules/antislop.md/.mdc` | pointer/konfigurasi aturan untuk klien tertentu | `AGENTS.md`, `GEMINI.md`, `CLAUDE.md` dengan sumber read-only |
| `scripts/check-repo.mjs`, smoke scripts, CI | validasi distribusi upstream | Validasi ZIP/dokumen v3 tersendiri, **tidak** mengklaim CI upstream berjalan di aplikasi kita |
| Asset comparison | before/after UI, copy, code, untuk ilustrasi pendekatan | referensi metode audit, **tidak** memakai asset sebagai desain MBG-Track |

## Yang tidak disalin sebagai package
Kami **tidak menyertakan executable upstream**, plugin manifest, atau gambar perbandingan ke ZIP proyek; tujuan ZIP ini adalah *dokumentasi integrasi* Laravel yang aman dibaca AI. Installer resmi dapat diaktifkan **opsional oleh pengguna** di lokasi terpisah, setelah meninjau kode dan risiko versi.

## Penilaian kritis, bukan fanatisme
- Upstream sendiri menegaskan *"a filter, not a style guide"*. **Melarang pola AI saja akan menghasilkan aplikasi steril** jika tidak punya direction positif; `DESIGN.md` wajib dibaca.
- Gambar before/after di repo bukan bukti bahwa semua UI akan otomatis jadi premium; hasil MBG-Track tetap harus diuji dalam browser nyata.
- Linter atau kode yang lulus build tidak memastikan alur bisnis MBG benar; gabungkan dengan 12 PBI, policy, jumlah porsi, status QC dan kerahasiaan GPS.
- Prinsip anti-slop harus memilih elemen berdasarkan domain: kadang tabel biasa atau card seragam **tepat** untuk data yang semantik setara. Jangan memvariasikan tanpa alasan hanya untuk terlihat unik.
- Sebagai adaptasi/parafrasa, rujukan MIT dan link commit dipertahankan untuk atribusi. Tidak mengklaim sertifikasi atau endorsement oleh penulis upstream.
