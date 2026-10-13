<?php

namespace Database\Seeders;

use App\Enums\FoodBatchStatus;
use App\Enums\MenuStatus;
use App\Enums\PortionRequestStatus;
use App\Enums\ProductionPlanStatus;
use App\Enums\ProductionStage;
use App\Models\DailyPortionRequest;
use App\Models\FoodBatch;
use App\Models\Menu;
use App\Models\ProductionAllocation;
use App\Models\ProductionPlan;
use App\Models\ProductionStageLog;
use App\Models\School;
use App\Models\Sppg;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ProductionExecutionDemoSeeder extends Seeder
{
    public function run(): void
    {
        $sppg = Sppg::where('code', 'SPPG-SUB-01')->first();
        if (! $sppg) {
            return;
        }

        $menu = Menu::where('sppg_id', $sppg->id)->where('status', MenuStatus::ACTIVE)->first();
        $kepala = User::where('email', 'kepala@mbgtrack.test')->first();
        $asisten = User::where('email', 'asisten@mbgtrack.test')->first();
        $schools = School::where('sppg_id', $sppg->id)->get();
        $today = Carbon::today('Asia/Jakarta');

        $plan = ProductionPlan::updateOrCreate(
            ['sppg_id' => $sppg->id, 'service_date' => $today->format('Y-m-d')],
            [
                'menu_id' => $menu->id,
                'status' => ProductionPlanStatus::IN_PRODUCTION,
                'created_by' => $kepala->id,
                'finalized_by' => $kepala->id,
                'finalized_at' => Carbon::now('Asia/Jakarta')->subHours(4),
                'notes' => 'Rencana produksi resmi demo dapur',
            ]
        );

        foreach ($schools as $sch) {
            $req = DailyPortionRequest::updateOrCreate(
                ['school_id' => $sch->id, 'service_date' => $today->format('Y-m-d')],
                [
                    'registered_count' => $sch->registered_beneficiaries,
                    'requested_portions' => $sch->registered_beneficiaries,
                    'status' => PortionRequestStatus::CONFIRMED,
                    'confirmed_by' => $kepala->id,
                    'confirmed_at' => Carbon::now('Asia/Jakarta')->subHours(5),
                ]
            );

            ProductionAllocation::updateOrCreate(
                ['production_plan_id' => $plan->id, 'school_id' => $sch->id],
                [
                    'portion_request_id' => $req->id,
                    'allocated_portions' => $sch->registered_beneficiaries,
                ]
            );
        }

        // Batch 1: PACKED & QC_CHECK (500 portions)
        $batch1 = FoodBatch::firstOrCreate(
            ['batch_code' => 'BATCH-20261013-SPPG-SUB-01-01'],
            [
                'production_plan_id' => $plan->id,
                'menu_id' => $menu->id,
                'portion_category' => 'STANDARD',
                'planned_portions' => 500,
                'actual_portions' => 498,
                'status' => FoodBatchStatus::QC_CHECK,
                'created_by' => $asisten->id,
                'notes' => 'Kloter pertama SD & SMP',
            ]
        );

        ProductionStageLog::firstOrCreate(
            ['food_batch_id' => $batch1->id, 'stage' => ProductionStage::PREPARATION],
            ['started_at' => Carbon::now('Asia/Jakarta')->subHours(3), 'completed_at' => Carbon::now('Asia/Jakarta')->subHours(2), 'updated_by' => $asisten->id, 'notes' => 'Persiapan bahan sayur dan ayam']
        );
        ProductionStageLog::firstOrCreate(
            ['food_batch_id' => $batch1->id, 'stage' => ProductionStage::COOKING],
            ['started_at' => Carbon::now('Asia/Jakarta')->subHours(2), 'completed_at' => Carbon::now('Asia/Jakarta')->subHours(1), 'updated_by' => $asisten->id, 'notes' => 'Penggorengan ayam dan penumisan sayur']
        );
        ProductionStageLog::firstOrCreate(
            ['food_batch_id' => $batch1->id, 'stage' => ProductionStage::PORTIONING],
            ['started_at' => Carbon::now('Asia/Jakarta')->subHours(1), 'completed_at' => Carbon::now('Asia/Jakarta')->subMinutes(30), 'updated_by' => $asisten->id, 'notes' => 'Penimbangan porsi ompreng']
        );
        ProductionStageLog::firstOrCreate(
            ['food_batch_id' => $batch1->id, 'stage' => ProductionStage::PACKING],
            ['started_at' => Carbon::now('Asia/Jakarta')->subMinutes(30), 'completed_at' => Carbon::now('Asia/Jakarta')->subMinutes(10), 'updated_by' => $asisten->id, 'notes' => 'Wadah ditutup steril dan diberi label']
        );
        ProductionStageLog::firstOrCreate(
            ['food_batch_id' => $batch1->id, 'stage' => ProductionStage::READY_FOR_QC],
            ['started_at' => Carbon::now('Asia/Jakarta')->subMinutes(10), 'updated_by' => $asisten->id, 'notes' => 'Diserahkan ke meja uji organoleptik gizi']
        );

        // Batch 2: COOKING in progress (526 portions)
        $batch2 = FoodBatch::firstOrCreate(
            ['batch_code' => 'BATCH-20261013-SPPG-SUB-01-02'],
            [
                'production_plan_id' => $plan->id,
                'menu_id' => $menu->id,
                'portion_category' => 'SMA',
                'planned_portions' => 526,
                'actual_portions' => null,
                'status' => FoodBatchStatus::COOKING,
                'created_by' => $asisten->id,
                'notes' => 'Kloter kedua SMA',
            ]
        );

        ProductionStageLog::firstOrCreate(
            ['food_batch_id' => $batch2->id, 'stage' => ProductionStage::PREPARATION],
            ['started_at' => Carbon::now('Asia/Jakarta')->subMinutes(45), 'completed_at' => Carbon::now('Asia/Jakarta')->subMinutes(20), 'updated_by' => $asisten->id, 'notes' => 'Persiapan bahan kloter 2']
        );
        ProductionStageLog::firstOrCreate(
            ['food_batch_id' => $batch2->id, 'stage' => ProductionStage::COOKING],
            ['started_at' => Carbon::now('Asia/Jakarta')->subMinutes(20), 'updated_by' => $asisten->id, 'notes' => 'Memasak nasi dan lauk']
        );
    }
}
