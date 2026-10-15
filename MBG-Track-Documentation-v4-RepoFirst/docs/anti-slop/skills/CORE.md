# MBG-Track anti-slop skill lens | CORE

> MBG-Track v3.0 | Asal inspirasi: [anti-slop oleh Miqdad Badjuber](https://github.com/miqdadbadjuber/anti-slop/tree/9685ca7eac70283d083fc23baa9a88e7d3cd93c4) (MIT, snapshot 12-10-2026). Dokumen ini adalah **adaptasi orisinal khusus MBG-Track**, bukan salinan skill resmi ataupun hasil pengujian aplikasi.

## Core responsibilities
Seluruh pekerjaan UI, copy, dan kode mengikuti 38 aturan di `../RULE-MATRIX.md` dan filosofi C1 intent, C2 functional completeness, C3 content-driven composition, C4 resilience, C5 evidence. Skill ini **selalu** aktif secara konseptual; enam skill berbeda untuk kedalaman sesuai task. Ini instruksi adaptasi, bukan skill upstream yang diinstal otomatis.

### Keputusan yang tidak boleh luput
- Pilih mode **During** untuk pekerjaan baru atau **After** untuk audit atas hasil yang sudah dibangun. Mode saat ini ditulis pada handoff; jika sudah jelas dari tugas, tidak perlu menanyai user setiap turn.
- Bacalah `DESIGN.md` lebih dahulu. Jika arah tidak ada, beri label `DRAFT WITHOUT DIRECTION`; jangan menyebut hasil premium.
- Jalankan Delivery Gate empat blok. Skor visual yang tinggi tidak mengubah status FAIL keamanan/aksi palsu.
- Setiap elemen UI harus punya pembaca, sumber data, kondisi state dan aksi sesudahnya. Bila hal ini tidak ada, hapus dekorasinya.
- Kebenaran data, permission dan keselamatan dapur/sekolah menang ketika aturan estetika bertentangan.

### Gate pada MBG-Track
Jangan mengira satu screenshot home membuktikan D03/P04/G03 aman. Bukti lintas role: cek view publik tak mendapat koordinat GPS, PIC tidak melihat sekolah lain, Kepala tidak melihat SPPG lain, batch `ON_HOLD` tidak dikirim, dan ompreng tidak disamakan dengan porsi. Pemenuhan UI tanpa tes Laravel bukan lengkap.
