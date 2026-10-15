# Handoff Log — Buku Kerja Antar Manusia dan AI

> File ini dibuat sebagai template operasional. **Belum ada handoff kode nyata**, jadi tidak diisi klaim test/commit palsu.

## Protokol
1. Sebelum mulai, cek task ID + branch + owner; tidak ada dua AI menyunting migration/controller yang sama tanpa koordinasi.
2. Setelah selesai, isi satu entri di bawah; cantumkan file persis, perubahan kontrak, migration, commands test, output dan outstanding issues.
3. Penerima handoff membaca spesifikasi sumber dan diff, bukan hanya ringkasan agent sebelumnya.
4. Jika Agent A mengklaim tes lulus, Agent B boleh re-run test dan harus melaporkan jika gagal.
5. Jangan unggah `.env`, token, kredensial, foto anak, atau GPS presisi dalam log.

## Template entri
```markdown
### HND-YYYYMMDD-001 — PBI-XX / PAGE-ID
- Dikerjakan oleh (tool/model yang benar-benar dipakai):
- Owner manusia/pemeriksa:
- Branch/commit (atau BELUM ADA):
- Files changed:
- Tujuan/acceptance yang selesai:
- Migration/API/status changed?:
- Test commands + exit codes (atau BELUM DIJALANKAN):
- Masalah/risiko belum selesai:
- Dokumen terdampak:
- Next owner/task:
```

## Backlog handoff pertama
- HND-TBD: verifikasi runtime Laravel dan versi composer, lalu implementasi fondasi.
- HND-TBD: terjemahkan DATA-DICTIONARY ke migration dan test constraints.

## Template handoff wajib untuk enam AI
```markdown
### [YYYY-MM-DD] TICKET PBI-XX / PAGE-ID
- Author / tool / model ID / mode yang benar-benar dipakai:
- Git branch & commit awal:
- Scope file yang boleh berubah:
- Input docs (path):
- File dibuat/diubah:
- Route/Policy/DB affected:
- Fitur bekerja / belum:
- Tests dijalankan + exit codes:
- Screenshot mobile 390 / desktop 1440:
- Audit 320px, reduced motion, no AI slop:
- Risiko / blockers:
- Reviewer & keputusan merge:
- Follow-up tugas kecil berikutnya:
```
Jika agent tidak punya akses repo/browser, tulis *No access — advisory only*. Jangan mengisi PASS dari prediksi. Satu writer di satu file selama satu tugas agar tidak ada perubahan berbenturan.


## V3 | Handoff evidence not chat memory
Log ini adalah **referensi snapshot read-only** setelah ekstraksi. Agen menulis progres aktual pada `P:\Tugas PPL MBG\HANDOFF-LOG.md` di project root. Handoff mencantumkan PBI, page, paths touched, tests PASS/FAIL/NOT VERIFIED, browser screenshots, Design Read + dials dan empat blok Delivery Gate. Reviewer kedua tidak mengklaim verifikasi bila hanya membaca laporan penulis.
