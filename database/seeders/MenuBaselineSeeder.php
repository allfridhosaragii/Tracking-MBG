<?php

namespace Database\Seeders;

use App\Enums\MenuStatus;
use App\Models\Menu;
use App\Models\Sppg;
use App\Models\User;
use Illuminate\Database\Seeder;

class MenuBaselineSeeder extends Seeder
{
    /**
     * Seed initial demo menus for PBI-03.
     */
    public function run(): void
    {
        $sppg = Sppg::where('code', 'SPPG-SUB-01')->first() ?? Sppg::first();
        if (! $sppg) {
            return;
        }

        $nutritionist = User::where('email', 'gizi@mbgtrack.test')->first() ?? User::where('sppg_id', $sppg->id)->first();
        if (! $nutritionist) {
            return;
        }

        // 1. Menu Aktif (v1)
        $menu1 = Menu::firstOrCreate(
            [
                'sppg_id' => $sppg->id,
                'menu_code' => 'MN-AYAM-01',
                'version' => 1,
            ],
            [
                'name' => 'Paket Nasi Pulen Ayam Semur, Sup Bayam Wortel & Pisang Barangan',
                'status' => MenuStatus::ACTIVE,
                'effective_from' => '2026-10-13',
                'created_by' => $nutritionist->id,
                'approved_by' => $nutritionist->id,
                'approved_at' => now()->subDay(),
            ]
        );

        if ($menu1->items()->count() === 0) {
            $menu1->items()->createMany([
                [
                    'item_name' => 'Nasi Putih Pulen Wangi',
                    'serving_weight_g' => 150.00,
                    'energy_kcal' => 195.00,
                    'protein_g' => 3.60,
                    'carbohydrate_g' => 43.50,
                    'fat_g' => 0.40,
                    'fiber_g' => 0.30,
                    'nutrition_source' => 'Tabel Komposisi Pangan Indonesia (TKPI 2019 Kode 010)',
                    'notes' => 'Beras lokal poles medium standar Bulog',
                ],
                [
                    'item_name' => 'Ayam Semur Kecap Bumbu Jahe',
                    'serving_weight_g' => 85.00,
                    'energy_kcal' => 220.00,
                    'protein_g' => 25.50,
                    'carbohydrate_g' => 4.20,
                    'fat_g' => 11.50,
                    'fiber_g' => 0.20,
                    'nutrition_source' => 'TKPI 2019 Kode Daging Unggas 042 & Uji Lab SPPG',
                    'notes' => 'Dada ayam segar tanpa kulit dengan bumbu rempah jahe',
                ],
                [
                    'item_name' => 'Sup Bayam Bening Wortel & Jagung Pipil',
                    'serving_weight_g' => 100.00,
                    'energy_kcal' => 68.00,
                    'protein_g' => 2.40,
                    'carbohydrate_g' => 12.00,
                    'fat_g' => 1.20,
                    'fiber_g' => 3.10,
                    'nutrition_source' => 'TKPI 2019 Sayuran Kode 115',
                    'notes' => 'Dimasak tanpa penyedap sintetik berlebih',
                ],
                [
                    'item_name' => 'Pisang Barangan Segar',
                    'serving_weight_g' => 80.00,
                    'energy_kcal' => 74.00,
                    'protein_g' => 0.90,
                    'carbohydrate_g' => 18.50,
                    'fat_g' => 0.20,
                    'fiber_g' => 2.00,
                    'nutrition_source' => 'TKPI 2019 Buah Kode 220',
                    'notes' => 'Tingkat kematangan optimal untuk konsumsi siswa',
                ],
            ]);
        }

        // 2. Draft Menu Siap Sah (v1)
        $menu2 = Menu::firstOrCreate(
            [
                'sppg_id' => $sppg->id,
                'menu_code' => 'MN-IKAN-02',
                'version' => 1,
            ],
            [
                'name' => 'Paket Nasi Liwet Ikan Kembung Kukus Bumbu Kuning & Sayur Labu Siam',
                'status' => MenuStatus::DRAFT,
                'effective_from' => '2026-10-18',
                'created_by' => $nutritionist->id,
            ]
        );

        if ($menu2->items()->count() === 0) {
            $menu2->items()->createMany([
                [
                    'item_name' => 'Nasi Liwet Gurih Serai',
                    'serving_weight_g' => 150.00,
                    'energy_kcal' => 205.00,
                    'protein_g' => 3.80,
                    'carbohydrate_g' => 44.00,
                    'fat_g' => 1.80,
                    'fiber_g' => 0.50,
                    'nutrition_source' => 'TKPI 2019 Kode 012',
                ],
                [
                    'item_name' => 'Ikan Kembung Kukus Bumbu Kuning',
                    'serving_weight_g' => 80.00,
                    'energy_kcal' => 185.00,
                    'protein_g' => 22.00,
                    'carbohydrate_g' => 2.00,
                    'fat_g' => 9.50,
                    'fiber_g' => 0.10,
                    'nutrition_source' => 'TKPI 2019 Kode Ikan 080',
                    'notes' => 'Kaya asam lemak omega-3 untuk perkembangan kognitif siswa',
                ],
                [
                    'item_name' => 'Tumis Labu Siam Jagung Manis',
                    'serving_weight_g' => 90.00,
                    'energy_kcal' => 55.00,
                    'protein_g' => 1.80,
                    'carbohydrate_g' => 9.50,
                    'fat_g' => 1.10,
                    'fiber_g' => 2.40,
                    'nutrition_source' => 'TKPI 2019 Sayuran Kode 142',
                ],
            ]);
        }

        // 3. Draft Belum Lengkap (v1)
        $menu3 = Menu::firstOrCreate(
            [
                'sppg_id' => $sppg->id,
                'menu_code' => 'MN-TELUR-03',
                'version' => 1,
            ],
            [
                'name' => 'Paket Nasi Kuning Telur Balado Daun Jeruk & Tempe Bacem',
                'status' => MenuStatus::DRAFT,
                'effective_from' => '2026-10-22',
                'created_by' => $nutritionist->id,
            ]
        );

        if ($menu3->items()->count() === 0) {
            $menu3->items()->createMany([
                [
                    'item_name' => 'Nasi Kuning Gurih Kunyit',
                    'serving_weight_g' => 150.00,
                    'energy_kcal' => 210.00,
                    'protein_g' => 3.90,
                    'carbohydrate_g' => 45.00,
                    'fat_g' => 2.00,
                    'fiber_g' => 0.40,
                    'nutrition_source' => 'TKPI 2019 Kode 015',
                ],
                [
                    'item_name' => 'Telur Puyuh Bumbu Balado Halus',
                    'serving_weight_g' => 60.00,
                    'energy_kcal' => 120.00,
                    'protein_g' => null, // Sengaja null untuk demonstrasi validasi incompleteness
                    'carbohydrate_g' => 3.00,
                    'fat_g' => 8.00,
                    'fiber_g' => 0.00,
                    'nutrition_source' => '', // Sengaja kosong
                    'notes' => 'Perlu uji konfirmasi kandungan protein',
                ],
            ]);
        }
    }
}
