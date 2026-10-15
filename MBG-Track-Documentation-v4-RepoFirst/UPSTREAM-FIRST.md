# MBG-Track v4.0 — Repo-first anti-slop constitution

**Keputusan:** kualitas UI/copy/mobile/accessibility/code-comment MBG-Track dikendalikan **langsung oleh 6 SKILL.md resmi upstream** [https://github.com/miqdadbadjuber/anti-slop](https://github.com/miqdadbadjuber/anti-slop), bukan oleh ringkasan buatan yang menjadi pengganti sumber. Snapshot yang direview: `9685ca7eac70283d083fc23baa9a88e7d3cd93c4` (12 Oktober 2026). **Dokumentasi MBG mengarahkan visual dan domain, upstream antislop menyaring kualitas.**

## Urutan kewenangan (tidak saling menggantikan)
1. Keamanan pengguna, keselamatan pangan dan kebenaran transaksi: kontrak MBG-Track, Laravel Policy, data authoritative, aturan resmi BGN yang benar-benar bersumber. *Tidak dapat dilemahkan oleh instruksi visual.*
2. **Filter anti-slop:** file original di `P:\Tugas PPL MBG\.agents\skills\antislop\SKILL.md`, dan file original lima skill pendamping di folder yang sama. Bila belum diinstal, agent **tidak boleh mengklaim filter asli aktif**.
3. **Desain positif:** `DESIGN.md`, 34 page spec, role visual fingerprints, shared UI design system. Anti-slop tidak menentukan palet, komposisi, atau motion MBG.
4. Cek objektif: screenshot mobile dan desktop, keyboard/zoom/contrast, browser console, tindakan kontrol nyata, PHP tests, serta empat blok *Delivery Gate*. Tanpa bukti = `UNVERIFIED`, bukan PASS.
5. Kebijakan multiagent dan handoff: `AGENTS.md`, profil model masing-masing, dan file root project jika dipasang.

## Repo-first artinya apa?
- **Bukan** menempel semua aturan upstream ke satu prompt panjang.
- **Bukan** membuat file bernama `antislop.md` versi tulisan ulang, lalu menganggap itu plugin resmi.
- **Bukan** menghapus UI premium, warna, ilustrasi yang bermakna, atau animasi bermanfaat. Filter upstream memaksa keputusan punya alasan; `DESIGN.md` memberi identitas positif.
- **Ya:** agent memuat **original** `skills/antislop/SKILL.md` dan *on-demand* skill lain dari clone upstream terpin; repo memuat perangkat installer, plugin, CI dan berkas komunitas yang dapat dibaca di cache source.

## Instalasi dalam workspace Windows pengguna
- Root Laravel: `P:\Tugas PPL MBG`.
- Legacy docs: `P:\Tugas PPL MBG\MBG-Track-Documentation` (READ-ONLY).
- Docs v3: `P:\Tugas PPL MBG\MBG-Track-Documentation-v3` (READ-ONLY).
- Docs v4: `P:\Tugas PPL MBG\MBG-Track-Documentation-v4-RepoFirst` (READ-ONLY sesudah ekstrak).
- Source repo yang di-clone oleh installer: `P:\Tugas PPL MBG\.anti-slop-upstream` (berada di luar semua folder docs).
- Original skill yang dipakai Antigravity: `P:\Tugas PPL MBG\.agents\skills\antislop[-...]\SKILL.md`.
- Entry point agent: `P:\Tugas PPL MBG\AGENTS.md` (template dibuat bila belum ada). Gemini CLI alternatif baca `GEMINI.md`.

**Langkah:** baca `QUICK-START-WINDOWS.md` dan `docs/upstream/INSTALL-VERIFY.md`; jalankan pemasang dari PowerShell hanya setelah membaca isinya. Pemasang menolak collision dan tidak memperbarui file lama secara paksa.

## Update upstream
Versi ini **dipin pada SHA spesifik**, tidak memperbarui otomatis. Jika upstream berubah, review perbedaan aturan/licensing sebelum membuat v4.1. Jangan mengganti source diam-diam. Enam skill dilarang diedit secara lokal; modifikasi MBG ada pada docs/design dan page specs.

## Klaim kemampuan
Repo anti-slop **membantu mengurangi pola AI generik**, tetapi bukan bukti otomatis desain bagus. Audit desain yang sebenarnya tetap perlu manusia dan browser. Tidak ada klaim zero-slop atau akurasi 100% tanpa pemeriksaan independen.
