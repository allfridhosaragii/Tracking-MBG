# After mode: audit changes after implementation

> MBG-Track v3.0 | Asal inspirasi: [anti-slop oleh Miqdad Badjuber](https://github.com/miqdadbadjuber/anti-slop/tree/9685ca7eac70283d083fc23baa9a88e7d3cd93c4) (MIT, snapshot 12-10-2026). Dokumen ini adalah **adaptasi orisinal khusus MBG-Track**, bukan salinan skill resmi ataupun hasil pengujian aplikasi.

## Mode audit; khusus temuan, bukan rewrite massal
Upstream After mode mengharuskan audit bernomor, prioritas menurut tier, lalu meminta persetujuan nomor yang akan diperbaiki. Di proyek ini gunakan model yang sama pada desain yang sudah berjalan. Audit tidak boleh diam-diam mengganti seluruh CSS atau merusak backend. **Jika task audit diminta sekaligus dengan izin memperbaiki, terapkan hanya temuan yang secara eksplisit termasuk cakupan izin tersebut.**

## Audit format
```
Audit: audit-001-2026-10-12
Commit & environment: <SHA>, <browser>
Page/role: <K02, Kepala SPPG>
A-001 | P0 | R-26 | CTA tidak melakukan PATCH | bukti: video ... | saran: ...
A-002 | P1 | R-03 | 320px overflow 18px | screenshot ... | saran: ...
A-003 | P2 | R-14 | lima kartu identik tanpa fungsi prioritas | screenshot ...
Decision: awaiting approved issue numbers; NO code changes yet.
```

## Prioritas
- P0: kebocoran GPS/foto, kesalahan porsi/QC, aksi mati/palsu, otorisasi; tahan release.
- P1: WCAG contrast, keyboard, mobile overflow, status kosong/offline rusak; tahan status VISUAL_REVIEWED.
- P2: rhythm, icon, label, card, polish; jadwalkan tanpa memaksa rewrite PBI.

## Skenario pembanding
1. Screenshot lama vs baru pada browser aktual (390 + 1440) pada data **sama**, bukan gambar generated.
2. Uji halaman tidak berubah pada 320,360,430,768 dan keyboard; cek bahwa fold pertama masih menonjolkan keputusan utama.
3. Pastikan fixes tidak merusak Policy, test suite, MySQL dev, route, dan interaksi.
4. Keputusan accepted/waived dan alasan dicatat dalam log development, tidak pada `MBG-Track-Documentation` protected.

## Stop conditions
Tidak ada screenshot? Tandai `VISUAL AUDIT BLOCKED`, bukan PASS. Tidak ada tests? `FUNCTIONAL VERIFICATION BLOCKED`. Repositori upstream tidak mengaudit app Laravel secara otomatis; semua bukti harus berasal dari app sendiri.
