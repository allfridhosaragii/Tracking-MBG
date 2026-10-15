# Konflik, versi dan keputusan yang tidak boleh disembunyikan

## Aturan prioritas
1. Keselamatan pengguna/pangan, sumber fakta, integritas transaksi dan privasi mendahului efek visual.
2. `skills/antislop/SKILL.md` upstream orisinal menentukan kelulusan anti-slop UI, bukan adaptasi v3 bila berbeda.
3. `DESIGN.md` menentukan arah kreatif MBG; core upstream **filter**, tidak menyuplai gaya pengganti.
4. Page spec spesifik menentukan hierarchy, actions dan states; komponen bersama menyediakan token, bukan memaksa card identik.
5. Jika mode After: audit dulu, jangan otomatis mengubah halaman sebelum user memilih temuan yang ingin diperbaiki (mengikuti mode upstream).
6. Jika mode During: baca source target dan pasang check selama implementasi, lalu jalankan final gate.

## Konflik aktual yang mesti dicek
- Dokumentasi v3 memakai kalimat `anti-slop` sebagai referensi konsep. v4 mewajibkan original SKILL sudah terpasang. Bila belum: status `UPSTREAM NOT INSTALLED`; jangan mengklaim compliant.
- Dokumentasi v2 meminta mengedit `docs/project/HANDOFF-LOG.md`. Itu **tidak boleh** karena folder dokumentasi terlindungi; tulis pada root Laravel `HANDOFF-LOG.md`.
- Screenshot/hasil test dari Gemini yang dilaporkan dalam chat bukan bukti terkini commit berikutnya. Ulangi untuk fitur/commit yang diuji.
- Jangan lakukan redesign masal Sprint 0/PBI-01 sebelum audit kualitas nyata. Hindari menciptakan scope tambahan saat pengguna meminta antislop.

## Ketika satu aturan ambigu
Dokumentasikan: requirement; source file and section; proposed fix; risk to existing PBI; reviewer approval. Hanya ubah kode yang terkait. Jika berpotensi menimbulkan pelanggaran makanan/tenant, hentikan aksi berisiko.
