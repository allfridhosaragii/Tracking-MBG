<?php

namespace Tests\Feature;

use App\Enums\FoodBatchStatus;
use App\Enums\MenuStatus;
use App\Enums\PortionRequestStatus;
use App\Enums\ProductionPlanStatus;
use App\Enums\QualityCheckConclusion;
use App\Enums\QualityCheckStage;
use App\Enums\TripStatus;
use App\Enums\TripStopStatus;
use App\Enums\UserRole;
use App\Models\DailyPortionRequest;
use App\Models\DeliveryItem;
use App\Models\FoodBatch;
use App\Models\Menu;
use App\Models\ProductionAllocation;
use App\Models\ProductionPlan;
use App\Models\QualityCheck;
use App\Models\School;
use App\Models\Sppg;
use App\Models\Trip;
use App\Models\TripStop;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\OsrmRoutingService;
use App\Services\TripDispatchService;
use Carbon\Carbon;
use Database\Seeders\MbgTrackBaselineSeeder;
use Database\Seeders\MenuBaselineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TripDispatchHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected Sppg $sppgA;
    protected Sppg $sppgB;
    protected School $schoolA1;
    protected School $schoolA2;
    protected School $schoolB1;
    protected Menu $menuA;
    protected User $asistenA;
    protected User $giziA;
    protected User $kepalaA;
    protected User $driverA;
    protected User $driverB;
    protected Vehicle $vehicleA;
    protected Vehicle $vehicleB;
    protected ProductionPlan $planToday;
    protected FoodBatch $batchToday;
    protected DeliveryItem $itemSchool1;
    protected DeliveryItem $itemSchool2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MbgTrackBaselineSeeder::class);
        $this->seed(MenuBaselineSeeder::class);

        $this->sppgA = Sppg::where('code', 'SPPG-SUB-01')->firstOrFail();
        $this->sppgB = Sppg::firstOrCreate(
            ['code' => 'SPPG-SUB-02'],
            [
                'name' => 'SPPG Kedua Malang',
                'address' => 'Jl. Ijen No. 10, Malang',
                'latitude' => -7.9785,
                'longitude' => 112.6289,
                'max_daily_portions' => 1000,
                'is_active' => true,
            ]
        );

        $this->schoolA1 = School::where('school_code', 'SCH-SDN-01')->firstOrFail();
        $this->schoolA2 = School::where('school_code', 'SCH-SMPN-02')->firstOrFail();
        $this->schoolB1 = School::where('school_code', 'SCH-SMAN-05')->firstOrFail();

        $this->asistenA = User::where('email', 'asisten@mbgtrack.test')->firstOrFail();
        $this->giziA = User::where('email', 'gizi@mbgtrack.test')->firstOrFail();
        $this->kepalaA = User::where('email', 'kepala@mbgtrack.test')->firstOrFail();
        $this->driverA = User::where('email', 'kurir@mbgtrack.test')->firstOrFail();

        $this->driverB = User::create([
            'sppg_id' => $this->sppgB->id,
            'name' => 'Kurir SPPG B',
            'email' => 'kurir_b@mbgtrack.test',
            'phone' => '08999999999',
            'password' => bcrypt('password'),
            'role' => UserRole::KURIR,
            'is_active' => true,
        ]);

        $this->vehicleA = Vehicle::create([
            'sppg_id' => $this->sppgA->id,
            'plate_number' => 'L 1234 MBG',
            'vehicle_name' => 'Grand Max Box SPPG A',
            'vehicle_type' => 'VAN',
            'capacity_portions' => 600,
            'is_active' => true,
        ]);

        $this->vehicleB = Vehicle::create([
            'sppg_id' => $this->sppgB->id,
            'plate_number' => 'N 5678 MBG',
            'vehicle_name' => 'Grand Max Box SPPG B',
            'vehicle_type' => 'VAN',
            'capacity_portions' => 500,
            'is_active' => true,
        ]);

        $this->menuA = Menu::where('sppg_id', $this->sppgA->id)->firstOrFail();

        // Create Production Plan for Today
        $today = Carbon::today()->toDateString();
        $this->planToday = ProductionPlan::create([
            'sppg_id' => $this->sppgA->id,
            'menu_id' => $this->menuA->id,
            'plan_code' => 'PLAN-HARDEN-01',
            'service_date' => $today,
            'total_target_portions' => 350,
            'status' => ProductionPlanStatus::FINALIZED,
            'created_by' => $this->kepalaA->id,
            'finalized_by' => $this->kepalaA->id,
            'finalized_at' => Carbon::now(),
        ]);

        // Allocation 1: School A1 (200 portions)
        $req1 = DailyPortionRequest::create([
            'school_id' => $this->schoolA1->id,
            'sppg_id' => $this->sppgA->id,
            'service_date' => $today,
            'registered_count' => 200,
            'requested_portions' => 200,
            'status' => PortionRequestStatus::CONFIRMED,
            'created_by' => $this->asistenA->id,
        ]);
        $alloc1 = ProductionAllocation::create([
            'production_plan_id' => $this->planToday->id,
            'portion_request_id' => $req1->id,
            'school_id' => $this->schoolA1->id,
            'allocated_portions' => 200,
        ]);

        // Allocation 2: School A2 (150 portions)
        $req2 = DailyPortionRequest::create([
            'school_id' => $this->schoolA2->id,
            'sppg_id' => $this->sppgA->id,
            'service_date' => $today,
            'registered_count' => 150,
            'requested_portions' => 150,
            'status' => PortionRequestStatus::CONFIRMED,
            'created_by' => $this->asistenA->id,
        ]);
        $alloc2 = ProductionAllocation::create([
            'production_plan_id' => $this->planToday->id,
            'portion_request_id' => $req2->id,
            'school_id' => $this->schoolA2->id,
            'allocated_portions' => 150,
        ]);

        // FoodBatch RELEASED
        $this->batchToday = FoodBatch::create([
            'production_plan_id' => $this->planToday->id,
            'menu_id' => $this->menuA->id,
            'batch_code' => 'BATCH-TEST-H01',
            'planned_portions' => 350,
            'actual_portions' => 350,
            'accepted_portions' => 350,
            'cooking_started_at' => Carbon::now()->subHours(3),
            'cooking_finished_at' => Carbon::now()->subHours(1),
            'status' => FoodBatchStatus::RELEASED,
            'created_by' => $this->asistenA->id,
        ]);

        QualityCheck::create([
            'food_batch_id' => $this->batchToday->id,
            'stage' => QualityCheckStage::BEFORE_DISPATCH,
            'checked_by' => $this->giziA->id,
            'temperature_celsius' => 68.5,
            'appearance_ok' => true,
            'aroma_ok' => true,
            'texture_ok' => true,
            'taste_tested' => true,
            'taste_ok' => true,
            'accepted_portions' => 350,
            'conclusion' => QualityCheckConclusion::SAFE,
            'checked_at' => Carbon::now()->subMinutes(45),
        ]);

        $this->itemSchool1 = DeliveryItem::create([
            'food_batch_id' => $this->batchToday->id,
            'production_allocation_id' => $alloc1->id,
            'planned_portions' => 200,
            'released_portions' => 200,
            'loaded_portions' => null,
            'status' => 'RELEASED',
        ]);

        $this->itemSchool2 = DeliveryItem::create([
            'food_batch_id' => $this->batchToday->id,
            'production_allocation_id' => $alloc2->id,
            'planned_portions' => 150,
            'released_portions' => 150,
            'loaded_portions' => null,
            'status' => 'RELEASED',
        ]);
    }

    /**
     * HARD GATE A: Trip creation sets DeliveryItem to PENDING_LOADING, never LOADED.
     */
    public function test_trip_creation_does_not_set_delivery_items_to_loaded(): void
    {
        $service = app(TripDispatchService::class);
        $trip = $service->createTrip([
            'service_date' => Carbon::today()->toDateString(),
            'vehicle_id' => $this->vehicleA->id,
            'driver_id' => $this->driverA->id,
            'stops' => [
                [
                    'school_id' => $this->schoolA1->id,
                    'sequence_number' => 1,
                    'items' => [
                        [
                            'delivery_item_id' => $this->itemSchool1->id,
                            'planned_portions' => 200,
                        ],
                    ],
                ],
            ],
        ], $this->kepalaA);

        $this->assertEquals(TripStatus::ASSIGNED, $trip->status);
        $this->assertFalse($trip->loading_verified);

        $this->itemSchool1->refresh();
        $this->assertEquals('PENDING_LOADING', $this->itemSchool1->status);
        $this->assertNull($this->itemSchool1->loaded_portions);
        $this->assertEquals(200, $this->itemSchool1->planned_portions);
    }

    /**
     * HARD GATE B: Driver cannot start trip before all 4 gates pass.
     */
    public function test_driver_cannot_start_before_all_gates_pass(): void
    {
        $service = app(TripDispatchService::class);
        $trip = $service->createTrip([
            'service_date' => Carbon::today()->toDateString(),
            'vehicle_id' => $this->vehicleA->id,
            'driver_id' => $this->driverA->id,
            'stops' => [
                [
                    'school_id' => $this->schoolA1->id,
                    'sequence_number' => 1,
                    'items' => [
                        [
                            'delivery_item_id' => $this->itemSchool1->id,
                            'planned_portions' => 200,
                        ],
                    ],
                ],
            ],
        ], $this->kepalaA);

        // Gate not satisfied: status is ASSIGNED
        $this->expectException(ValidationException::class);
        $service->startTrip($trip, $this->driverA);
    }

    /**
     * HARD GATE B: Loading verification updates loaded_portions and status to LOADED.
     */
    public function test_physical_loading_verification_flow(): void
    {
        $service = app(TripDispatchService::class);
        $trip = $service->createTrip([
            'service_date' => Carbon::today()->toDateString(),
            'vehicle_id' => $this->vehicleA->id,
            'driver_id' => $this->driverA->id,
            'stops' => [
                [
                    'school_id' => $this->schoolA1->id,
                    'sequence_number' => 1,
                    'items' => [
                        [
                            'delivery_item_id' => $this->itemSchool1->id,
                            'planned_portions' => 200,
                        ],
                    ],
                ],
            ],
        ], $this->kepalaA);

        $service->verifyLoading($trip, [
            'items' => [
                $this->itemSchool1->id => 200,
            ],
            'notes' => 'Muatan diverifikasi fisik lengkap.',
        ], $this->asistenA);

        $trip->refresh();
        $this->assertTrue($trip->loading_verified);
        $this->assertEquals($this->asistenA->id, $trip->loading_verified_by);

        $this->itemSchool1->refresh();
        $this->assertEquals('LOADED', $this->itemSchool1->status);
        $this->assertEquals(200, $this->itemSchool1->loaded_portions);
    }

    /**
     * HARD GATE B: Self-approval prevention (Kepala SPPG who is assigned as driver cannot approve).
     */
    public function test_self_approval_prevention(): void
    {
        $service = app(TripDispatchService::class);
        $trip = $service->createTrip([
            'service_date' => Carbon::today()->toDateString(),
            'vehicle_id' => $this->vehicleA->id,
            'driver_id' => $this->driverA->id,
            'stops' => [
                [
                    'school_id' => $this->schoolA1->id,
                    'sequence_number' => 1,
                    'items' => [
                        [
                            'delivery_item_id' => $this->itemSchool1->id,
                            'planned_portions' => 200,
                        ],
                    ],
                ],
            ],
        ], $this->kepalaA);

        // Verify loading
        $service->verifyLoading($trip, [
            'items' => [$this->itemSchool1->id => 200],
        ], $this->asistenA);

        // Check vehicle
        $service->checkVehicle($trip, ['passed' => true], $this->driverA);

        // 1. Assigned driver attempting to approve dispatch is denied by policy
        $this->actingAs($this->driverA)
            ->post(route('head.distribution.trips.approve-dispatch', $trip))
            ->assertStatus(403);

        // 2. Asisten Lapangan attempting to approve dispatch is denied by policy
        $this->actingAs($this->asistenA)
            ->post(route('head.distribution.trips.approve-dispatch', $trip))
            ->assertStatus(403);

        // 3. Driver attempting service-level self-approval throws ValidationException
        try {
            $service->approveDispatch($trip, $this->driverA);
            $this->fail('Expected ValidationException when driver attempts self-approval');
        } catch (ValidationException $e) {
            $this->assertTrue(true);
        }

        // 4. Authorized Kepala SPPG can approve dispatch
        $approvedTrip = $service->approveDispatch($trip, $this->kepalaA);
        $this->assertTrue($approvedTrip->dispatch_approved);
        $this->assertEquals(TripStatus::READY, $approvedTrip->status);
    }

    /**
     * HARD GATE C: Loaded cannot exceed released portions.
     */
    public function test_loaded_portions_cannot_exceed_released(): void
    {
        $service = app(TripDispatchService::class);
        $trip = $service->createTrip([
            'service_date' => Carbon::today()->toDateString(),
            'vehicle_id' => $this->vehicleA->id,
            'driver_id' => $this->driverA->id,
            'stops' => [
                [
                    'school_id' => $this->schoolA1->id,
                    'sequence_number' => 1,
                    'items' => [
                        [
                            'delivery_item_id' => $this->itemSchool1->id,
                            'planned_portions' => 200,
                        ],
                    ],
                ],
            ],
        ], $this->kepalaA);

        // Attempt to load 250 when released is only 200
        $this->expectException(ValidationException::class);
        $service->verifyLoading($trip, [
            'items' => [
                $this->itemSchool1->id => 250,
            ],
        ], $this->asistenA);
    }

    /**
     * HARD GATE C: Null or zero vehicle capacity is rejected.
     */
    public function test_vehicle_zero_or_null_capacity_is_rejected(): void
    {
        $invalidVehicle = Vehicle::create([
            'sppg_id' => $this->sppgA->id,
            'plate_number' => 'L 9999 NUL',
            'vehicle_name' => 'Motor Tak Terdaftar Kapasitas',
            'vehicle_type' => 'MOTORCYCLE',
            'capacity_portions' => 0,
            'is_active' => true,
        ]);

        $service = app(TripDispatchService::class);
        $this->expectException(ValidationException::class);

        $service->createTrip([
            'service_date' => Carbon::today()->toDateString(),
            'vehicle_id' => $invalidVehicle->id,
            'driver_id' => $this->driverA->id,
            'stops' => [
                [
                    'school_id' => $this->schoolA1->id,
                    'sequence_number' => 1,
                    'items' => [
                        [
                            'delivery_item_id' => $this->itemSchool1->id,
                            'planned_portions' => 200,
                        ],
                    ],
                ],
            ],
        ], $this->kepalaA);
    }

    /**
     * HARD GATE D: Batch from different service date is rejected.
     */
    public function test_batch_from_different_service_date_is_rejected(): void
    {
        $yesterday = Carbon::yesterday()->toDateString();
        $planYesterday = ProductionPlan::create([
            'sppg_id' => $this->sppgA->id,
            'menu_id' => $this->menuA->id,
            'plan_code' => 'PLAN-HARDEN-YEST',
            'service_date' => $yesterday,
            'total_target_portions' => 100,
            'status' => ProductionPlanStatus::FINALIZED,
            'created_by' => $this->kepalaA->id,
            'finalized_by' => $this->kepalaA->id,
            'finalized_at' => Carbon::now()->subDay(),
        ]);

        $batchYesterday = FoodBatch::create([
            'production_plan_id' => $planYesterday->id,
            'menu_id' => $this->menuA->id,
            'batch_code' => 'BATCH-YEST-01',
            'planned_portions' => 100,
            'actual_portions' => 100,
            'accepted_portions' => 100,
            'status' => FoodBatchStatus::RELEASED,
            'created_by' => $this->asistenA->id,
        ]);

        $reqYest = DailyPortionRequest::create([
            'school_id' => $this->schoolA1->id,
            'sppg_id' => $this->sppgA->id,
            'service_date' => $yesterday,
            'registered_count' => 100,
            'requested_portions' => 100,
            'status' => PortionRequestStatus::CONFIRMED,
            'created_by' => $this->asistenA->id,
        ]);

        $allocYest = ProductionAllocation::create([
            'production_plan_id' => $planYesterday->id,
            'portion_request_id' => $reqYest->id,
            'school_id' => $this->schoolA1->id,
            'allocated_portions' => 100,
        ]);

        $itemYesterday = DeliveryItem::create([
            'food_batch_id' => $batchYesterday->id,
            'production_allocation_id' => $allocYest->id,
            'planned_portions' => 100,
            'released_portions' => 100,
            'status' => 'RELEASED',
        ]);

        $service = app(TripDispatchService::class);
        $this->expectException(ValidationException::class);

        // Attempting to assign yesterday's batch to today's trip
        $service->createTrip([
            'service_date' => Carbon::today()->toDateString(),
            'vehicle_id' => $this->vehicleA->id,
            'driver_id' => $this->driverA->id,
            'stops' => [
                [
                    'school_id' => $this->schoolA1->id,
                    'sequence_number' => 1,
                    'items' => [
                        [
                            'delivery_item_id' => $itemYesterday->id,
                            'planned_portions' => 100,
                        ],
                    ],
                ],
            ],
        ], $this->kepalaA);
    }

    /**
     * HARD GATE D: Duplicate delivery items in same payload are rejected.
     */
    public function test_duplicate_delivery_item_in_payload_rejected(): void
    {
        $service = app(TripDispatchService::class);
        $this->expectException(ValidationException::class);

        $service->createTrip([
            'service_date' => Carbon::today()->toDateString(),
            'vehicle_id' => $this->vehicleA->id,
            'driver_id' => $this->driverA->id,
            'stops' => [
                [
                    'school_id' => $this->schoolA1->id,
                    'sequence_number' => 1,
                    'items' => [
                        ['delivery_item_id' => $this->itemSchool1->id, 'planned_portions' => 100],
                        ['delivery_item_id' => $this->itemSchool1->id, 'planned_portions' => 100],
                    ],
                ],
            ],
        ], $this->kepalaA);
    }

    /**
     * HARD GATE D: Sequence validation: must start at 1, consecutive, no gaps, no duplicates.
     */
    public function test_invalid_stop_sequence_rejected(): void
    {
        $service = app(TripDispatchService::class);

        // Sequence starting at 2 (not 1)
        try {
            $service->createTrip([
                'service_date' => Carbon::today()->toDateString(),
                'vehicle_id' => $this->vehicleA->id,
                'driver_id' => $this->driverA->id,
                'stops' => [
                    [
                        'school_id' => $this->schoolA1->id,
                        'sequence_number' => 2,
                        'items' => [['delivery_item_id' => $this->itemSchool1->id, 'planned_portions' => 200]],
                    ],
                ],
            ], $this->kepalaA);
            $this->fail('Expected ValidationException for sequence not starting at 1');
        } catch (ValidationException $e) {
            $this->assertTrue(true);
        }

        // Sequence with duplicate (1, 1)
        try {
            $service->createTrip([
                'service_date' => Carbon::today()->toDateString(),
                'vehicle_id' => $this->vehicleA->id,
                'driver_id' => $this->driverA->id,
                'stops' => [
                    [
                        'school_id' => $this->schoolA1->id,
                        'sequence_number' => 1,
                        'items' => [['delivery_item_id' => $this->itemSchool1->id, 'planned_portions' => 200]],
                    ],
                    [
                        'school_id' => $this->schoolA2->id,
                        'sequence_number' => 1,
                        'items' => [['delivery_item_id' => $this->itemSchool2->id, 'planned_portions' => 150]],
                    ],
                ],
            ], $this->kepalaA);
            $this->fail('Expected ValidationException for duplicate sequence numbers');
        } catch (ValidationException $e) {
            $this->assertTrue(true);
        }

        // Sequence with gap (1, 3)
        try {
            $service->createTrip([
                'service_date' => Carbon::today()->toDateString(),
                'vehicle_id' => $this->vehicleA->id,
                'driver_id' => $this->driverA->id,
                'stops' => [
                    [
                        'school_id' => $this->schoolA1->id,
                        'sequence_number' => 1,
                        'items' => [['delivery_item_id' => $this->itemSchool1->id, 'planned_portions' => 200]],
                    ],
                    [
                        'school_id' => $this->schoolA2->id,
                        'sequence_number' => 3,
                        'items' => [['delivery_item_id' => $this->itemSchool2->id, 'planned_portions' => 150]],
                    ],
                ],
            ], $this->kepalaA);
            $this->fail('Expected ValidationException for skipped sequence numbers');
        } catch (ValidationException $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * Cross-tenant checks: Vehicle, driver, or school belonging to another SPPG is rejected.
     */
    public function test_cross_tenant_entities_rejected(): void
    {
        $service = app(TripDispatchService::class);

        // Vehicle belonging to SPPG B
        try {
            $service->createTrip([
                'service_date' => Carbon::today()->toDateString(),
                'vehicle_id' => $this->vehicleB->id,
                'driver_id' => $this->driverA->id,
                'stops' => [
                    [
                        'school_id' => $this->schoolA1->id,
                        'sequence_number' => 1,
                        'items' => [['delivery_item_id' => $this->itemSchool1->id, 'planned_portions' => 200]],
                    ],
                ],
            ], $this->kepalaA);
            $this->fail('Expected ValidationException for cross-tenant vehicle');
        } catch (ValidationException $e) {
            $this->assertTrue(true);
        }

        // Driver belonging to SPPG B
        try {
            $service->createTrip([
                'service_date' => Carbon::today()->toDateString(),
                'vehicle_id' => $this->vehicleA->id,
                'driver_id' => $this->driverB->id,
                'stops' => [
                    [
                        'school_id' => $this->schoolA1->id,
                        'sequence_number' => 1,
                        'items' => [['delivery_item_id' => $this->itemSchool1->id, 'planned_portions' => 200]],
                    ],
                ],
            ], $this->kepalaA);
            $this->fail('Expected ValidationException for cross-tenant driver');
        } catch (ValidationException $e) {
            $this->assertTrue(true);
        }

        // School belonging to SPPG B
        try {
            $service->createTrip([
                'service_date' => Carbon::today()->toDateString(),
                'vehicle_id' => $this->vehicleA->id,
                'driver_id' => $this->driverA->id,
                'stops' => [
                    [
                        'school_id' => $this->schoolB1->id,
                        'sequence_number' => 1,
                        'items' => [['delivery_item_id' => $this->itemSchool1->id, 'planned_portions' => 200]],
                    ],
                ],
            ], $this->kepalaA);
            $this->fail('Expected ValidationException for cross-tenant school');
        } catch (ValidationException $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * HARD GATE E: Starting trip sets status IN_PROGRESS and tracking_status WAITING_FOR_LOCATION (no fake GPS).
     */
    public function test_trip_start_sets_waiting_for_location_and_prevents_fake_gps(): void
    {
        $service = app(TripDispatchService::class);
        $trip = $service->createTrip([
            'service_date' => Carbon::today()->toDateString(),
            'vehicle_id' => $this->vehicleA->id,
            'driver_id' => $this->driverA->id,
            'stops' => [
                [
                    'school_id' => $this->schoolA1->id,
                    'sequence_number' => 1,
                    'items' => [['delivery_item_id' => $this->itemSchool1->id, 'planned_portions' => 200]],
                ],
            ],
        ], $this->kepalaA);

        // Complete all gates
        $service->verifyLoading($trip, ['items' => [$this->itemSchool1->id => 200]], $this->asistenA);
        $service->checkVehicle($trip, ['passed' => true], $this->driverA);
        $service->approveDispatch($trip, $this->kepalaA);

        // Driver starts trip
        $startedTrip = $service->startTrip($trip, $this->driverA);

        $this->assertEquals(TripStatus::IN_PROGRESS, $startedTrip->status);
        $this->assertEquals('WAITING_FOR_LOCATION', $startedTrip->tracking_status);
        $this->assertNull($startedTrip->tracking_started_at); // No fake active telemetry before PBI-09
    }

    /**
     * Double-submit protection on swipe-to-depart.
     */
    public function test_swipe_double_submit_protection(): void
    {
        $service = app(TripDispatchService::class);
        $trip = $service->createTrip([
            'service_date' => Carbon::today()->toDateString(),
            'vehicle_id' => $this->vehicleA->id,
            'driver_id' => $this->driverA->id,
            'stops' => [
                [
                    'school_id' => $this->schoolA1->id,
                    'sequence_number' => 1,
                    'items' => [['delivery_item_id' => $this->itemSchool1->id, 'planned_portions' => 200]],
                ],
            ],
        ], $this->kepalaA);

        $service->verifyLoading($trip, ['items' => [$this->itemSchool1->id => 200]], $this->asistenA);
        $service->checkVehicle($trip, ['passed' => true], $this->driverA);
        $service->approveDispatch($trip, $this->kepalaA);

        // First start
        $service->startTrip($trip, $this->driverA);

        // Second start attempt throws validation exception
        $this->expectException(ValidationException::class);
        $service->startTrip($trip, $this->driverA);
    }

    /**
     * OSRM Routing Service GeoJSON parsing on success.
     */
    public function test_osrm_routing_service_parses_geojson_successfully(): void
    {
        $osrmService = app(OsrmRoutingService::class);

        Http::fake([
            'router.project-osrm.org/*' => Http::response([
                'code' => 'Ok',
                'routes' => [
                    [
                        'distance' => 5400.5,
                        'duration' => 640.2,
                        'geometry' => [
                            'type' => 'LineString',
                            'coordinates' => [
                                [112.735, -7.288],
                                [112.740, -7.290],
                                [112.745, -7.295],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $route = $osrmService->getRoute([
            ['lat' => -7.288, 'lng' => 112.735],
            ['lat' => -7.295, 'lng' => 112.745],
        ]);

        $this->assertEquals(5401, $route['distance_m']);
        $this->assertEquals(640, $route['duration_s']);
        $this->assertEquals('LineString', $route['geometry']['type']);
        $this->assertCount(3, $route['geometry']['coordinates']);
    }

    /**
     * OSRM Routing Service graceful fallback on error without fabricating straight lines.
     */
    public function test_osrm_routing_service_falls_back_gracefully_on_error(): void
    {
        $osrmService = app(OsrmRoutingService::class);

        Http::fake([
            'router.project-osrm.org/*' => Http::response(null, 500),
            'routing.openstreetmap.de/*' => Http::response(null, 500),
        ]);

        $fallbackRoute = $osrmService->getRoute([
            ['lat' => -7.280, 'lng' => 112.730],
            ['lat' => -7.290, 'lng' => 112.740],
        ]);

        $this->assertNull($fallbackRoute['distance_m']);
        $this->assertNull($fallbackRoute['duration_s']);
        $this->assertNull($fallbackRoute['geometry']);
    }

    /**
     * Public delivery map is read-only and strictly sanitized (no driver phone, plate, or private notes).
     */
    public function test_public_delivery_map_is_read_only_and_sanitized(): void
    {
        $service = app(TripDispatchService::class);
        $trip = $service->createTrip([
            'service_date' => Carbon::today()->toDateString(),
            'vehicle_id' => $this->vehicleA->id,
            'driver_id' => $this->driverA->id,
            'stops' => [
                [
                    'school_id' => $this->schoolA1->id,
                    'sequence_number' => 1,
                    'items' => [['delivery_item_id' => $this->itemSchool1->id, 'planned_portions' => 200]],
                ],
            ],
        ], $this->kepalaA);

        // Under publication policy, public map displays active in-progress/completed trips
        $trip->update(['status' => TripStatus::IN_PROGRESS]);

        // Guest user can view public map without authentication
        $response = $this->get(route('public.trips.map', $trip->trip_code));
        $response->assertStatus(200);
        $response->assertSee($trip->trip_code);
        $response->assertSee($this->schoolA1->name);
        // Sensitive data must NOT appear in HTML
        $response->assertDontSee($this->driverA->phone);
        $response->assertDontSee($this->vehicleA->plate_number);

        // JSON Map Data endpoint is sanitized
        $jsonResponse = $this->get(route('public.trips.map-data', $trip->trip_code));
        $jsonResponse->assertStatus(200);
        $jsonResponse->assertJsonStructure([
            'trip_code',
            'service_date',
            'delivery_stage',
            'status',
            'sppg' => ['name', 'lat', 'lng'],
            'stops' => [
                '*' => ['sequence_number', 'school_name', 'education_level', 'lat', 'lng', 'stage', 'is_current_target'],
            ],
        ]);

        $data = $jsonResponse->json();
        // Ensure no driver phone or vehicle plate in JSON
        $this->assertArrayNotHasKey('driver', $data);
        $this->assertArrayNotHasKey('vehicle', $data);
    }
}
