# Version 3 changelog and v2 compatibility

> MBG-Track v3.0 | Asal inspirasi: [anti-slop oleh Miqdad Badjuber](https://github.com/miqdadbadjuber/anti-slop/tree/9685ca7eac70283d083fc23baa9a88e7d3cd93c4) (MIT, snapshot 12-10-2026). Dokumen ini adalah **adaptasi orisinal khusus MBG-Track**, bukan salinan skill resmi ataupun hasil pengujian aplikasi.

## Tambahan substansial
- Source v2 (76 file) dipertahankan; setiap halaman mendapat **page-specific design read**, aturan komposisi mobile, daftar false-data/red-flag, input/aksi harus nyata, motion limits dan browser evidence.
- Root `DESIGN.md` menyatukan *calm premium operational editorial*, motif jalur pelayanan, typographic rationale, dials per role, dan alasan warna dari implementasi yang dilaporkan.
- `docs/anti-slop`: 38-rule crosswalk, provenance repo, inventory source, enam skill lens, During/After, liveliness, Delivery Gate, client integrations, protected workspace adoption, report template.
- Design: fingerprints per role, provenance aset/konten, katalog motion by state.
- Quality: acceptance matrix dengan bukti dan severity.
- Project: baseline laporan Gemini Sprint 0/PBI-01 dengan caveat verification.
- AI: seluruh model-profile diberi instruksi anti-slop berbasis tugas dan guard agar tidak mengganti UI menjadi steril/menambah fake data.

## Kompatibilitas
- 12 PBI, 34 page spec dan 22 tabel dasar v2 tidak dihapus/diubah jadi fitur baru.
- V2 tetap utuh sebagai file ZIP dan folder sumber. V3 diekstrak dalam nama folder yang berbeda.
- `docs/pages/PAGE-REGISTRY.md` tetap berfungsi sebagai index. Semua file baru dirujuk melalui README/AGENTS/DESIGN.
- Dokumentasi ini bukan *plugin upstream* dan tidak melakukan install or execute code otomatis. Izin user diperlukan sebelum memasang tool tambahan atau menyentuh app lokal.
