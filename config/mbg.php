<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Batas Waktu (Cutoff) Konfirmasi Porsi Harian Sekolah
    |--------------------------------------------------------------------------
    |
    | Parameter operasional baseline sistem MBG-Track untuk batasan konfirmasi
    | mandiri oleh PIC Sekolah. Nilai ini merupakan kesepakatan operasional proyek
    | (bukan mandat regulasi resmi BGN).
    |
    | Default: H-1 pukul 16:00 WIB (Asia/Jakarta).
    |
    */
    'portion_cutoff_time' => env('MBG_PORTION_CUTOFF_TIME', '16:00'),
    'portion_cutoff_days_prior' => (int) env('MBG_PORTION_CUTOFF_DAYS_PRIOR', 1),
    'timezone' => 'Asia/Jakarta',

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
