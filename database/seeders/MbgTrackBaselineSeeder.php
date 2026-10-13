<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\School;
use App\Models\Sppg;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MbgTrackBaselineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1 SPPG Baseline
        $sppg = Sppg::firstOrCreate(
            ['code' => 'SPPG-SUB-01'],
            [
                'name' => 'SPPG Perintis Surabaya (DEMO/SIMULASI)',
                'address' => 'Jl. Mayjen Sungkono No. 88, Surabaya, Jawa Timur',
                'latitude' => -7.2912340,
                'longitude' => 112.7214560,
                'max_daily_portions' => 1500,
                'is_active' => true,
            ]
        );

        // 3 Sekolah dengan Target Porsi Terkontrol (Total: 1.026 porsi)
        // Sekolah A: 340
        $schoolA = School::firstOrCreate(
            ['school_code' => 'SCH-SDN-01'],
            [
                'sppg_id' => $sppg->id,
                'name' => 'SDN Percobaan 01 Surabaya (DEMO/SIMULASI)',
                'education_level' => 'SD',
                'address' => 'Jl. Raya Darmo No. 12, Wonokromo, Surabaya',
                'latitude' => -7.2885430,
                'longitude' => 112.7351230,
                'registered_beneficiaries' => 340,
                'public_slug' => 'sdn-percobaan-01-surabaya',
                'is_active' => true,
            ]
        );

        // Sekolah B: 276
        $schoolB = School::firstOrCreate(
            ['school_code' => 'SCH-SMPN-02'],
            [
                'sppg_id' => $sppg->id,
                'name' => 'SMPN 02 Harapan Bangsa (DEMO/SIMULASI)',
                'education_level' => 'SMP',
                'address' => 'Jl. Diponegoro No. 45, Tegalsari, Surabaya',
                'latitude' => -7.2961230,
                'longitude' => 112.7394560,
                'registered_beneficiaries' => 276,
                'public_slug' => 'smpn-02-harapan-bangsa',
                'is_active' => true,
            ]
        );

        // Sekolah C: 410
        $schoolC = School::firstOrCreate(
            ['school_code' => 'SCH-SMAN-05'],
            [
                'sppg_id' => $sppg->id,
                'name' => 'SMAN 05 Teladan Surabaya (DEMO/SIMULASI)',
                'education_level' => 'SMA',
                'address' => 'Jl. Wijaya Kusuma No. 48, Genteng, Surabaya',
                'latitude' => -7.2612340,
                'longitude' => 112.7489120,
                'registered_beneficiaries' => 410,
                'public_slug' => 'sman-05-teladan-surabaya',
                'is_active' => true,
            ]
        );

        // Password standar demo: 'password'
        $defaultPassword = Hash::make('password');

        // 1. Kepala SPPG
        User::firstOrCreate(
            ['email' => 'kepala@mbgtrack.test'],
            [
                'sppg_id' => $sppg->id,
                'school_id' => null,
                'name' => 'Budi Wicaksono, S.T. (Kepala SPPG)',
                'phone' => '081122334455',
                'password' => $defaultPassword,
                'role' => UserRole::KEPALA_SPPG,
                'is_active' => true,
            ]
        );

        // 2. Pengawas Gizi
        User::firstOrCreate(
            ['email' => 'gizi@mbgtrack.test'],
            [
                'sppg_id' => $sppg->id,
                'school_id' => null,
                'name' => 'dr. Ratna Sari, M.Gizi (Pengawas Gizi)',
                'phone' => '081122334456',
                'password' => $defaultPassword,
                'role' => UserRole::PENGAWAS_GIZI,
                'is_active' => true,
            ]
        );

        // 3. Asisten Lapangan
        User::firstOrCreate(
            ['email' => 'asisten@mbgtrack.test'],
            [
                'sppg_id' => $sppg->id,
                'school_id' => null,
                'name' => 'Ahmad Fauzi (Asisten Lapangan)',
                'phone' => '081122334457',
                'password' => $defaultPassword,
                'role' => UserRole::ASISTEN_LAPANGAN,
                'is_active' => true,
            ]
        );

        // 4. Kurir
        User::firstOrCreate(
            ['email' => 'kurir@mbgtrack.test'],
            [
                'sppg_id' => $sppg->id,
                'school_id' => null,
                'name' => 'Dedi Pratama (Kurir Pengantaran)',
                'phone' => '081122334458',
                'password' => $defaultPassword,
                'role' => UserRole::KURIR,
                'is_active' => true,
            ]
        );

        // 5. PIC Sekolah A (SDN Percobaan 01)
        User::firstOrCreate(
            ['email' => 'pic.sdn01@mbgtrack.test'],
            [
                'sppg_id' => null,
                'school_id' => $schoolA->id,
                'name' => 'Siti Aminah, S.Pd. (PIC SDN Percobaan 01)',
                'phone' => '081122334459',
                'password' => $defaultPassword,
                'role' => UserRole::PIC_SEKOLAH,
                'is_active' => true,
            ]
        );

        // 6. PIC Sekolah B (SMPN 02) - untuk uji batas isolasi lintas sekolah
        User::firstOrCreate(
            ['email' => 'pic.smpn02@mbgtrack.test'],
            [
                'sppg_id' => null,
                'school_id' => $schoolB->id,
                'name' => 'Bambang Sudrajat, M.Pd. (PIC SMPN 02)',
                'phone' => '081122334460',
                'password' => $defaultPassword,
                'role' => UserRole::PIC_SEKOLAH,
                'is_active' => true,
            ]
        );

        // 7. Akun Nonaktif - untuk uji penolakan login staf nonaktif
        User::firstOrCreate(
            ['email' => 'inactive@mbgtrack.test'],
            [
                'sppg_id' => $sppg->id,
                'school_id' => null,
                'name' => 'Eko Nonaktif (Mantan Staf)',
                'phone' => '081122334461',
                'password' => $defaultPassword,
                'role' => UserRole::ASISTEN_LAPANGAN,
                'is_active' => false,
            ]
        );

        // 8. Kendaraan Operasional Logistik PBI-08
        \App\Models\Vehicle::firstOrCreate(
            ['plate_number' => 'L 9102 AB'],
            [
                'sppg_id' => $sppg->id,
                'vehicle_name' => 'Blind Van Logistik 01',
                'vehicle_type' => 'VAN',
                'capacity_portions' => 800,
                'capacity_note' => 'Kapasitas maksimal 800 bento box standar MBG',
                'is_active' => true,
            ]
        );

        \App\Models\Vehicle::firstOrCreate(
            ['plate_number' => 'L 9103 CD'],
            [
                'sppg_id' => $sppg->id,
                'vehicle_name' => 'Pick-up Box Logistik 02',
                'vehicle_type' => 'PICKUP',
                'capacity_portions' => 1200,
                'capacity_note' => 'Kapasitas 1200 bento box dengan pendingin pasif',
                'is_active' => true,
            ]
        );
    }
}
