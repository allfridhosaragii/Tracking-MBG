<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Batas Waktu (Cutoff) Konfirmasi Porsi Harian Sekolah
    |--------------------------------------------------------------------------
    |
    | Jam cutoff waktu Indonesia Barat (WIB) pada H-1 tanggal layanan.
    | Contoh: Layanan tanggal 15 Oktober 2026 memiliki cutoff 14 Oktober 16:00 WIB.
    |
    */
    'portion_cutoff_time' => env('MBG_PORTION_CUTOFF_TIME', '16:00'),
    'portion_cutoff_days_prior' => (int) env('MBG_PORTION_CUTOFF_DAYS_PRIOR', 1),

    /*
    |--------------------------------------------------------------------------
    | Kebijakan Revisi Setelah Cutoff
    |--------------------------------------------------------------------------
    |
    | Perubahan setelah cutoff atau status LOCKED memerlukan alasan wajib
    | dan pencatatan jejak audit (activity_logs).
    |
    */
    'allow_revision_with_reason' => true,
];
