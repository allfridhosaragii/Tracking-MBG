# UI Design System — Tokens & Rules v2.0

> **Rancangan awal** yang perlu divalidasi secara visual dan dengan penguji kontras; bukan identitas BGN. Sistem token adalah batas konsistensi, bukan izin menerapkan satu komponen identik di semua halaman.

## 1. Fondasi warna: “Calm Utility”
| Token | Nilai awal | Fungsi |
|---|---|---|
| `canvas` | `#F5F7F3` | bidang aplikasi terang, hangat |
| `surface` | `#FFFFFF` | area kerja, formulir |
| `surface-muted` | `#EDF2ED` | pengelompokan ringan |
| `ink` | `#172923` | teks utama |
| `ink-muted` | `#53645B` | teks kedua, bukan untuk angka kritis |
| `line` | `#DCE5DD` | border dan divider |
| `brand-700` | `#1C6654` | tindakan utama & navigasi terpilih |
| `brand-800` | `#164D42` | hover/tekan |
| `accent-warm` | `#B78C56` | aksen dekor minimal, tidak untuk status masalah |
| `success-dark` | `#21684B` | status aman/selesai + label teks |
| `warning-dark` | `#885D1C` | keterlambatan/menunggu + label teks |
| `danger-dark` | `#9B3232` | hold/kritis/gagal + label teks |
| `info-dark` | `#285F80` | informasi/progres + label teks |

Jangan memakai aksen emas untuk tombol utama berulang. Pastikan kontras teks minimal memenuhi WCAG 2.2 AA: teks biasa 4.5:1; teks besar 3:1; komponen UI non-teks esensial 3:1. Nilai token di atas adalah **kandidat**; dokumentasikan pemeriksaan kontras kombinasi warna nyata sebelum implementasi di-merge. Jangan memakai status warna tanpa teks, ikon, dan timestamp.

## 2. Tipografi
- Gunakan satu keluarga sans utama legal/berlisensi jelas, misalnya `Inter` (disediakan lewat sumber yang diizinkan) atau sistem font sebagai fallback; opsional satu editorial font untuk angka/headline produk publik **hanya jika tidak mengganggu readability**.
- Skala awal (rem): `caption .75`, `small .875`, `body 1`, `subtitle 1.125`, `section 1.375`, `page 1.75`, `hero 2.25` dengan line-height sesuai konteks. Hindari font 11px untuk data mobile penting.
- Angka KPI memakai `font-variant-numeric: tabular-nums`; label terpisah dari angka; jangan singkat satuan tanpa menjelaskan.
- Satu H1 yang mendeskripsikan tujuan kerja, H2 untuk blok informasi; tidak mengganti level heading demi ukuran visual.

## 3. Sistem ruang dan grid
- Base spacing 4px: `1=4`, `2=8`, `3=12`, `4=16`, `5=20`, `6=24`, `8=32`, `10=40`, `12=48`.
- Mobile safe horizontal padding awal 16px (12px di layar sangat sempit bila diuji); desktop container adaptif, bukan `max-w-7xl` untuk setiap halaman.
- Card bukan default semua konten. Gunakan section divider, list rail, tabel adaptif, atau stacked transaction summary sesuai kebutuhan.
- Radius: `input 10–12px`, `interactive panel 14–18px`, `bottom sheet 20–24px`. Hindari semua elemen radius 24px yang membuat hierarki kabur.
- Shadow terbatas overlay/floating action; struktur utama lebih baik dengan edge + spacing.

## 4. States komponen global
Semua komponen interaktif punya: default, hover bila pointer, keyboard focus-visible, pressed, disabled dengan alasan, loading, success persisten bila commit, error yang dapat diperbaiki. `aria-live` untuk status non-destruktif. Setiap kontrol punya label nyata. Tidak ada aksi penting yang hanya diwakili ikon.

## 5. Layout adaptif
- `xs: 320–359`: fallback aksesibilitas, informasi tetap dapat dibaca.
- `mobile: 360–599`: acuan desain utama PIC/driver/asisten/publik.
- `tablet: 600–1023`: dapat menggunakan dual-pane untuk list/detail bila tinggi cukup.
- `desktop: >=1024`: side nav dan workspace kompleks diperbolehkan Kepala/Gizi.
- Breakpoint adalah titik perubahan **konten**, bukan semata salinan angka framework. Jangan mengubah semantik data untuk breakpoint lain.

## 6. Pemeriksaan kontra-AI-slop
- Hindari gradient ungu-biru, semua card ukuran identik, dashboard dengan 6 KPI tanpa prioritas, hero copy abstrak, ilustrasi robot stok.
- Jangan gunakan visualisasi chart untuk dua angka yang lebih mudah dibaca secara tekstual.
- Jangan minta pengguna memilih role saat login; akses ditentukan server.
- Jangan membuat diagram rute decorative; peta harus mewakili data aktif, fallback teks wajib.

## 7. Pendefinisian token dalam kode
Disarankan CSS variables semantic (`--color-canvas`, `--color-ink`, `--color-danger`, `--space-*`) lalu terhubung ke Tailwind bila dipilih. Hindari hex hardcoded tersebar di Blade. Komponen shared menerima variant semantik dan batas props; larang custom class panjang di tiap page karena mengacaukan bahasa visual.

## 8. Validasi sebelum kunci final
Bangun *design probe* untuk enam layar: D03, P04, A02, K01, G03, U02 pada 390px dan 1440px. Screenshot dan tinjau bersama rubrik pada `UI-QUALITY-CHECKLIST.md`; perubahan token baru menjadi final setelah terbukti kontras dan fungsinya.

**Acuan:** https://www.w3.org/TR/WCAG22/ ; https://www.w3.org/WAI/WCAG22/Understanding/target-size-minimum


## V3 | Token evidence and color restraint
Token warna dari desain Calm Utility yang dilaporkan sudah digunakan di source Laravel hanya boleh diganti setelah computed contrast & screenshot. Primary semantic: action=pinus; text=deep ink; canvas=alabaster; lines=muted. Gold hanya aksen editorial kecil bila kontrasnya sesuai; status unsafe/warning/error harus memakai palet semantik terpisah dengan teks eksplisit, bukan nama warna "gold". 

Skala radius untuk controls/surface/dialog dibedakan; shadow bukan default untuk seluruh card. Ukur AA 4.5:1 normal dan 3:1 large, serta focus ring kontras. Tidak ada klaim "WCAG compliant" tanpa audit nyata. Test typography pada 320px, kenaikan text 200%, nilai porsi 1.026+, dan elemen tanggal panjang. Pastikan `prefers-reduced-motion` tetap dapat menampilkan state.
