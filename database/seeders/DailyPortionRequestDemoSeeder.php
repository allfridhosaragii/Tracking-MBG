<?php

namespace Database\Seeders;

use App\Enums\PortionRequestStatus;
use App\Models\DailyPortionRequest;
use App\Models\School;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DailyPortionRequestDemoSeeder extends Seeder
{
    /**
     * Run the database seeds for PBI-04 demo visualization.
     */
    public function run(): void
    {
        $schoolA = School::where('school_code', 'SCH-SDN-01')->first();
        $schoolB = School::where('school_code', 'SCH-SMPN-02')->first();
        $schoolC = School::where('school_code', 'SCH-SMAN-05')->first();

        if (! $schoolA || ! $schoolB || ! $schoolC) {
            return;
        }

        $picA = User::where('email', 'pic.sdn01@mbgtrack.test')->first();
        $picB = User::where('email', 'pic.smpn02@mbgtrack.test')->first();

        $today = Carbon::today('Asia/Jakarta')->format('Y-m-d');
        $tomorrow = Carbon::tomorrow('Asia/Jakarta')->format('Y-m-d');
        $dayAfter = Carbon::today('Asia/Jakarta')->addDays(2)->format('Y-m-d');

        // 1. Tomorrow: School A CONFIRMED (340 portions)
        DailyPortionRequest::updateOrCreate(
            ['school_id' => $schoolA->id, 'service_date' => $tomorrow],
            [
                'registered_count' => $schoolA->registered_beneficiaries,
                'present_count' => 340,
                'requested_portions' => 340,
                'status' => PortionRequestStatus::CONFIRMED,
                'confirmed_by' => $picA?->id,
                'confirmed_at' => Carbon::now('Asia/Jakarta')->subHours(2),
            ]
        );

        // 2. Tomorrow: School B DRAFT (Pending)
        DailyPortionRequest::updateOrCreate(
            ['school_id' => $schoolB->id, 'service_date' => $tomorrow],
            [
                'registered_count' => $schoolB->registered_beneficiaries,
                'present_count' => 270,
                'requested_portions' => 270,
                'status' => PortionRequestStatus::DRAFT,
            ]
        );

        // 3. Tomorrow: School C CONFIRMED (410 portions)
        DailyPortionRequest::updateOrCreate(
            ['school_id' => $schoolC->id, 'service_date' => $tomorrow],
            [
                'registered_count' => $schoolC->registered_beneficiaries,
                'present_count' => 410,
                'requested_portions' => 410,
                'status' => PortionRequestStatus::CONFIRMED,
                'confirmed_by' => $picA?->id,
                'confirmed_at' => Carbon::now('Asia/Jakarta')->subHours(1),
            ]
        );

        // 4. Today: School A LOCKED (Past cutoff)
        DailyPortionRequest::updateOrCreate(
            ['school_id' => $schoolA->id, 'service_date' => $today],
            [
                'registered_count' => $schoolA->registered_beneficiaries,
                'present_count' => 340,
                'requested_portions' => 340,
                'status' => PortionRequestStatus::LOCKED,
                'confirmed_by' => $picA?->id,
                'confirmed_at' => Carbon::yesterday('Asia/Jakarta')->setTime(15, 30),
                'locked_at' => Carbon::yesterday('Asia/Jakarta')->setTime(16, 0),
            ]
        );

        // 5. Day After Tomorrow: School A DRAFT
        DailyPortionRequest::updateOrCreate(
            ['school_id' => $schoolA->id, 'service_date' => $dayAfter],
            [
                'registered_count' => $schoolA->registered_beneficiaries,
                'present_count' => null,
                'requested_portions' => null,
                'status' => PortionRequestStatus::DRAFT,
            ]
        );
    }
}
