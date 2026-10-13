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
use App\Enums\TripType;
use App\Enums\UserRole;
use App\Models\ActivityLog;
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
use App\Services\FoodBatchService;
use App\Services\QualityControlService;
use App\Services\TripDispatchService;
use Carbon\Carbon;
use Database\Seeders\MbgTrackBaselineSeeder;
use Database\Seeders\MenuBaselineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TripDispatchTest extends TestCase
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
    protected User $driverA2;
    protected User $driverB;
    protected User $picSchoolA1;
    protected User $picSchoolB1;
    protected Vehicle $vehicleA1;
    protected Vehicle $vehicleA2;
    protected Vehicle $vehicleB1;
    protected ProductionPlan $planA;
    protected FoodBatch $batchA1;
    protected DeliveryItem $deliveryItemSchool1;
    protected DeliveryItem $deliveryItemSchool2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MbgTrackBaselineSeeder::class);
        $this->seed(MenuBaselineSeeder::class);

        $this->sppgA = Sppg::where('code', 'SPPG-SUB-01')->firstOrFail();
        $this->sppgB = Sppg::firstOrCreate(
            ['code' => 'SPPG-SUB-02'],
            [
                'name' => 'SPPG Kedua Malang (DEMO/SIMULASI)',
                'address' => 'Jl. Ijen No. 10, Malang, Jawa Timur',
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

        $this->driverA2 = User::create([
            'sppg_id' => $this->sppgA->id,
            'name' => 'Kurir Kedua SPPG A',
            'email' => 'kurir2@mbgtrack.test',
            'phone' => '081234567899',
            'password' => bcrypt('password'),
            'role' => UserRole::KURIR,
            'is_active' => true,
        ]);

        $this->driverB = User::create([
            'sppg_id' => $this->sppgB->id,
            'name' => 'Kurir SPPG B',
            'email' => 'kurir.b@mbgtrack.test',
            'phone' => '081234567888',
            'password' => bcrypt('password'),
            'role' => UserRole::KURIR,
            'is_active' => true,
        ]);

        $this->picSchoolA1 = User::where('email', 'pic.sdn01@mbgtrack.test')->firstOrFail();
        $this->picSchoolB1 = User::firstOrCreate(
            ['email' => 'pic.sman05@mbgtrack.test'],
            [
                'sppg_id' => $this->sppgB->id,
                'school_id' => $this->schoolB1->id,
                'name' => 'PIC SMAN 05 Malang',
                'password' => bcrypt('password'),
                'role' => UserRole::PIC_SEKOLAH,
                'is_active' => true,
            ]
        );

        // Create Vehicles
        $this->vehicleA1 = Vehicle::create([
            'sppg_id' => $this->sppgA->id,
            'plate_number' => 'N 1234 AB',
            'vehicle_name' => 'Van Logistik 01',
            'vehicle_type' => 'VAN',
            'capacity_portions' => 500,
            'is_active' => true,
        ]);

        $this->vehicleA2 = Vehicle::create([
            'sppg_id' => $this->sppgA->id,
            'plate_number' => 'N 5678 CD',
            'vehicle_name' => 'Motor Roda Tiga 02',
            'vehicle_type' => 'MOTORCYCLE',
            'capacity_portions' => 200,
            'is_active' => true,
        ]);

        $this->vehicleB1 = Vehicle::create([
            'sppg_id' => $this->sppgB->id,
            'plate_number' => 'N 9999 ZZ',
            'vehicle_name' => 'Truk SPPG B',
            'vehicle_type' => 'TRUCK',
            'capacity_portions' => 1000,
            'is_active' => true,
        ]);

        $this->menuA = Menu::where('sppg_id', $this->sppgA->id)
            ->where('status', MenuStatus::ACTIVE)
            ->firstOrFail();

        // Setup a finalized plan with 2 allocations: School A1 (200), School A2 (150)
        $today = Carbon::today()->toDateString();

        $req1 = DailyPortionRequest::create([
            'school_id' => $this->schoolA1->id,
            'sppg_id' => $this->sppgA->id,
            'service_date' => $today,
            'registered_count' => 200,
            'requested_portions' => 200,
            'status' => PortionRequestStatus::CONFIRMED,
            'created_by' => $this->kepalaA->id,
            'confirmed_at' => now(),
        ]);

        $req2 = DailyPortionRequest::create([
            'school_id' => $this->schoolA2->id,
            'sppg_id' => $this->sppgA->id,
            'service_date' => $today,
            'registered_count' => 150,
            'requested_portions' => 150,
            'status' => PortionRequestStatus::CONFIRMED,
            'created_by' => $this->kepalaA->id,
            'confirmed_at' => now(),
        ]);

        $this->planA = ProductionPlan::create([
            'sppg_id' => $this->sppgA->id,
            'menu_id' => $this->menuA->id,
            'plan_code' => 'PLAN-TEST-LOGISTICS-01',
            'service_date' => $today,
            'total_target_portions' => 350,
            'status' => ProductionPlanStatus::FINALIZED,
            'created_by' => $this->kepalaA->id,
            'finalized_by' => $this->kepalaA->id,
            'finalized_at' => Carbon::now(),
        ]);

        ProductionAllocation::create([
            'production_plan_id' => $this->planA->id,
            'portion_request_id' => $req1->id,
            'school_id' => $this->schoolA1->id,
            'allocated_portions' => 200,
            'notes' => 'Alokasi SD 1',
        ]);

        ProductionAllocation::create([
            'production_plan_id' => $this->planA->id,
            'portion_request_id' => $req2->id,
            'school_id' => $this->schoolA2->id,
            'allocated_portions' => 150,
            'notes' => 'Alokasi SMP 2',
        ]);

        // Create Batch for 350 portions
        $batchService = app(FoodBatchService::class);
        $this->batchA1 = $batchService->createBatch($this->planA, [
            'planned_portions' => 350,
            'notes' => 'Batch pengujian logistik PBI-08',
        ], $this->asistenA);

        // Record actual 340 portions
        $batchService->recordActualPortions($this->batchA1, 340, 'Realisasi aktual dapur', $this->asistenA);

        $this->batchA1->update([
            'status' => FoodBatchStatus::QC_CHECK,
            'has_evidence_exception' => true,
            'evidence_exception_status' => 'APPROVED',
            'evidence_exception_approved_by' => $this->kepalaA->id,
        ]);

        // Complete QC: Safe, accepted 330 portions (shortage 10)
        $qcService = app(QualityControlService::class);
        $qcService->recordQualityCheck($this->batchA1, [
            'stage' => QualityCheckStage::BEFORE_DISPATCH->value,
            'temperature_celsius' => 67.5,
            'appearance_ok' => 1,
            'aroma_ok' => 1,
            'texture_ok' => 1,
            'taste_tested' => 1,
            'taste_ok' => 1,
            'conclusion' => QualityCheckConclusion::SAFE->value,
            'accepted_portions' => 330,
            'decision_reason' => 'Lolos uji mutu organoleptik pra-distribusi tanpa cemaran.',
            'sampling_passed' => true,
            'notes' => 'Lolos QC mutu pra-distribusi',
        ], $this->giziA);

        $this->batchA1->refresh();

        // Get the reconciled delivery items
        $this->deliveryItemSchool1 = DeliveryItem::where('food_batch_id', $this->batchA1->id)
            ->whereHas('productionAllocation', fn($q) => $q->where('school_id', $this->schoolA1->id))
            ->firstOrFail();

        $this->deliveryItemSchool2 = DeliveryItem::where('food_batch_id', $this->batchA1->id)
            ->whereHas('productionAllocation', fn($q) => $q->where('school_id', $this->schoolA2->id))
            ->firstOrFail();
    }

    /**
     * Test 1: Kepala SPPG can view distribution workspace (Page K05).
     */
    public function test_kepala_sppg_can_view_distribution_workspace_k05(): void
    {
        $response = $this->actingAs($this->kepalaA)
            ->get(route('head.distribution.index'));

        $response->assertStatus(200);
        $response->assertSee('Kontrol Distribusi, Armada &amp; Rute Pengantaran', false);
        $response->assertSee('Armada Tersedia');
        $response->assertSee('Form Penugasan Rute &amp; Muatan Baru', false);
        $response->assertSee($this->vehicleA1->plate_number);
        $response->assertSee($this->driverA->name);
    }

    /**
     * Test 2: Unauthorized roles cannot access distribution workspace.
     */
    public function test_unauthorized_roles_cannot_access_distribution_workspace(): void
    {
        $this->actingAs($this->driverA)
            ->get(route('head.distribution.index'))
            ->assertStatus(403);

        $this->actingAs($this->asistenA)
            ->get(route('head.distribution.index'))
            ->assertStatus(403);

        $this->actingAs($this->giziA)
            ->get(route('head.distribution.index'))
            ->assertStatus(403);

        $this->actingAs($this->picSchoolA1)
            ->get(route('head.distribution.index'))
            ->assertStatus(403);
    }

    /**
     * Test 3: Kepala SPPG can plan and create a multi-stop trip with RELEASED batches.
     */
    public function test_kepala_sppg_can_plan_and_create_trip_with_valid_released_batches(): void
    {
        $payload = [
            'service_date' => Carbon::today()->toDateString(),
            'vehicle_id' => $this->vehicleA1->id,
            'driver_id' => $this->driverA->id,
            'trip_type' => 'DELIVERY',
            'planned_departure_at' => Carbon::today()->setTime(9, 30)->toDateTimeString(),
            'stops' => [
                [
                    'school_id' => $this->schoolA1->id,
                    'sequence_number' => 1,
                    'stop_type' => 'DELIVERY',
                    'items' => [
                        [
                            'delivery_item_id' => $this->deliveryItemSchool1->id,
                            'loaded_portions' => 200,
                        ],
                    ],
                ],
                [
                    'school_id' => $this->schoolA2->id,
                    'sequence_number' => 2,
                    'stop_type' => 'DELIVERY',
                    'items' => [
                        [
                            'delivery_item_id' => $this->deliveryItemSchool2->id,
                            'loaded_portions' => 130, // 130 released portions
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->kepalaA)
            ->post(route('head.distribution.trips.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify Trip in DB
        $trip = Trip::where('driver_id', $this->driverA->id)->first();
        $this->assertNotNull($trip);
        $this->assertEquals(TripStatus::ASSIGNED, $trip->status);
        $this->assertStringStartsWith('TRIP-', $trip->trip_code);
        $this->assertEquals($this->vehicleA1->id, $trip->vehicle_id);

        // Verify Stops
        $this->assertCount(2, $trip->stops);
        $stop1 = $trip->stops->where('sequence_number', 1)->first();
        $stop2 = $trip->stops->where('sequence_number', 2)->first();
        $this->assertEquals($this->schoolA1->id, $stop1->school_id);
        $this->assertEquals(TripStopStatus::PENDING, $stop1->status);
        $this->assertEquals($this->schoolA2->id, $stop2->school_id);

        // Verify DeliveryItems linked (HARD GATE A: PENDING_LOADING, loaded_portions = null, planned_portions set)
        $this->deliveryItemSchool1->refresh();
        $this->deliveryItemSchool2->refresh();
        $this->assertEquals($stop1->id, $this->deliveryItemSchool1->trip_stop_id);
        $this->assertNull($this->deliveryItemSchool1->loaded_portions);
        $this->assertEquals(200, $this->deliveryItemSchool1->planned_portions);
        $this->assertEquals('PENDING_LOADING', $this->deliveryItemSchool1->status);
        $this->assertEquals($stop2->id, $this->deliveryItemSchool2->trip_stop_id);
        $this->assertNull($this->deliveryItemSchool2->loaded_portions);
        $this->assertEquals(130, $this->deliveryItemSchool2->planned_portions);
        $this->assertEquals('PENDING_LOADING', $this->deliveryItemSchool2->status);

        // Verify Audit Log
        $this->assertDatabaseHas('activity_logs', [
            'event' => 'trip.created_and_assigned',
            'auditable_id' => $trip->id,
            'actor_id' => $this->kepalaA->id,
        ]);
    }

    /**
     * Test 4: Cannot assign unreleased batch to a trip.
     */
    public function test_cannot_assign_unreleased_batch_to_trip(): void
    {
        // Set batch back to ON_HOLD
        $this->batchA1->update(['status' => FoodBatchStatus::ON_HOLD]);

        $payload = [
            'service_date' => Carbon::today()->toDateString(),
            'vehicle_id' => $this->vehicleA1->id,
            'driver_id' => $this->driverA->id,
            'stops' => [
                [
                    'school_id' => $this->schoolA1->id,
                    'sequence_number' => 1,
                    'items' => [
                        [
                            'delivery_item_id' => $this->deliveryItemSchool1->id,
                            'loaded_portions' => 200,
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->kepalaA)
            ->post(route('head.distribution.trips.store'), $payload);

        $response->assertSessionHasErrors();
        $this->assertDatabaseCount('trips', 0);
    }

    /**
     * Test 5: Cannot load more portions than accepted by QC.
     */
    public function test_cannot_load_more_portions_than_accepted_by_qc(): void
    {
        // deliveryItemSchool2 only has 130 released portions. Trying to load 135
        $payload = [
            'service_date' => Carbon::today()->toDateString(),
            'vehicle_id' => $this->vehicleA1->id,
            'driver_id' => $this->driverA->id,
            'stops' => [
                [
                    'school_id' => $this->schoolA2->id,
                    'sequence_number' => 1,
                    'items' => [
                        [
                            'delivery_item_id' => $this->deliveryItemSchool2->id,
                            'loaded_portions' => 135,
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->kepalaA)
            ->post(route('head.distribution.trips.store'), $payload);

        $response->assertSessionHasErrors();
        $this->assertDatabaseCount('trips', 0);
    }

    /**
     * Test 6: Cannot exceed vehicle capacity.
     */
    public function test_cannot_exceed_vehicle_capacity(): void
    {
        // vehicleA2 capacity is only 200 portions. Trying to load 200 + 130 = 330
        $payload = [
            'service_date' => Carbon::today()->toDateString(),
            'vehicle_id' => $this->vehicleA2->id, // capacity 200
            'driver_id' => $this->driverA->id,
            'stops' => [
                [
                    'school_id' => $this->schoolA1->id,
                    'sequence_number' => 1,
                    'items' => [
                        [
                            'delivery_item_id' => $this->deliveryItemSchool1->id,
                            'loaded_portions' => 200,
                        ],
                    ],
                ],
                [
                    'school_id' => $this->schoolA2->id,
                    'sequence_number' => 2,
                    'items' => [
                        [
                            'delivery_item_id' => $this->deliveryItemSchool2->id,
                            'loaded_portions' => 130,
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->kepalaA)
            ->post(route('head.distribution.trips.store'), $payload);

        $response->assertSessionHasErrors('vehicle_id');
        $this->assertDatabaseCount('trips', 0);
    }

    /**
     * Test 7: Busy vehicle or driver cannot have conflicting active trips on the same service date.
     */
    public function test_busy_vehicle_or_driver_cannot_have_conflicting_active_trips(): void
    {
        // Create an existing trip for vehicleA1 and driverA
        Trip::create([
            'sppg_id' => $this->sppgA->id,
            'driver_id' => $this->driverA->id,
            'vehicle_id' => $this->vehicleA1->id,
            'trip_code' => 'TRIP-EXISTING-01',
            'service_date' => Carbon::today()->toDateString(),
            'status' => TripStatus::ASSIGNED,
            'created_by' => $this->kepalaA->id,
        ]);

        // Attempt to create second trip with same driver but different vehicle
        $payload = [
            'service_date' => Carbon::today()->toDateString(),
            'vehicle_id' => $this->vehicleA2->id,
            'driver_id' => $this->driverA->id, // BUSY!
            'stops' => [
                [
                    'school_id' => $this->schoolA1->id,
                    'sequence_number' => 1,
                    'items' => [
                        [
                            'delivery_item_id' => $this->deliveryItemSchool1->id,
                            'loaded_portions' => 100,
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->kepalaA)
            ->post(route('head.distribution.trips.store'), $payload);

        $response->assertSessionHasErrors('driver_id');

        // Attempt to create second trip with same vehicle but different driver
        $payload2 = [
            'service_date' => Carbon::today()->toDateString(),
            'vehicle_id' => $this->vehicleA1->id, // BUSY!
            'driver_id' => $this->driverA2->id,
            'stops' => [
                [
                    'school_id' => $this->schoolA1->id,
                    'sequence_number' => 1,
                    'items' => [
                        [
                            'delivery_item_id' => $this->deliveryItemSchool1->id,
                            'loaded_portions' => 100,
                        ],
                    ],
                ],
            ],
        ];

        $response2 = $this->actingAs($this->kepalaA)
            ->post(route('head.distribution.trips.store'), $payload2);

        $response2->assertSessionHasErrors('vehicle_id');
    }

    /**
     * Test 8: Cross-tenant trip planning is strictly rejected.
     */
    public function test_cross_tenant_trip_planning_is_strictly_rejected(): void
    {
        // Kepala SPPG A attempts to assign vehicle from SPPG B
        $payload = [
            'service_date' => Carbon::today()->toDateString(),
            'vehicle_id' => $this->vehicleB1->id, // Belongs to SPPG B!
            'driver_id' => $this->driverA->id,
            'stops' => [
                [
                    'school_id' => $this->schoolA1->id,
                    'sequence_number' => 1,
                    'items' => [
                        [
                            'delivery_item_id' => $this->deliveryItemSchool1->id,
                            'loaded_portions' => 200,
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->kepalaA)
            ->post(route('head.distribution.trips.store'), $payload);

        $response->assertSessionHasErrors('vehicle_id');

        // Kepala SPPG A attempts to assign driver from SPPG B
        $payload2 = [
            'service_date' => Carbon::today()->toDateString(),
            'vehicle_id' => $this->vehicleA1->id,
            'driver_id' => $this->driverB->id, // Belongs to SPPG B!
            'stops' => [
                [
                    'school_id' => $this->schoolA1->id,
                    'sequence_number' => 1,
                    'items' => [
                        [
                            'delivery_item_id' => $this->deliveryItemSchool1->id,
                            'loaded_portions' => 200,
                        ],
                    ],
                ],
            ],
        ];

        $response2 = $this->actingAs($this->kepalaA)
            ->post(route('head.distribution.trips.store'), $payload2);

        $response2->assertSessionHasErrors('driver_id');

        // Kepala SPPG A attempts to set stop for School of SPPG B
        $schoolFromSppgB = School::create([
            'sppg_id' => $this->sppgB->id,
            'school_code' => 'SCH-MALANG-99',
            'name' => 'SD Negeri Malang 99',
            'education_level' => 'SD',
            'address' => 'Jl. Ijen Malang',
            'registered_beneficiaries' => 100,
            'public_slug' => 'sdn-malang-99',
            'is_active' => true,
        ]);

        $payload3 = [
            'service_date' => Carbon::today()->toDateString(),
            'vehicle_id' => $this->vehicleA1->id,
            'driver_id' => $this->driverA->id,
            'stops' => [
                [
                    'school_id' => $schoolFromSppgB->id, // Belongs to SPPG B!
                    'sequence_number' => 1,
                    'items' => [
                        [
                            'delivery_item_id' => $this->deliveryItemSchool1->id,
                            'loaded_portions' => 200,
                        ],
                    ],
                ],
            ],
        ];

        $response3 = $this->actingAs($this->kepalaA)
            ->post(route('head.distribution.trips.store'), $payload3);

        $response3->assertSessionHasErrors('stops.0.school_id');
    }

    /**
     * Test 9: Driver can view their own assigned trips on D01.
     */
    public function test_driver_can_view_only_their_own_assigned_trips_d01(): void
    {
        $tripService = app(TripDispatchService::class);
        $tripA = $tripService->createTrip([
            'service_date' => Carbon::today()->toDateString(),
            'vehicle_id' => $this->vehicleA1->id,
            'driver_id' => $this->driverA->id,
            'stops' => [
                [
                    'school_id' => $this->schoolA1->id,
                    'sequence_number' => 1,
                    'items' => [
                        [
                            'delivery_item_id' => $this->deliveryItemSchool1->id,
                            'loaded_portions' => 200,
                        ],
                    ],
                ],
            ],
        ], $this->kepalaA);

        // Driver A sees tripA
        $responseA = $this->actingAs($this->driverA)
            ->get(route('driver.trips.index'));

        $responseA->assertStatus(200);
        $responseA->assertSee($tripA->trip_code);
        $responseA->assertSee($this->schoolA1->name);

        // Driver A2 sees empty state (no trips assigned)
        $responseA2 = $this->actingAs($this->driverA2)
            ->get(route('driver.trips.index'));

        $responseA2->assertStatus(200);
        $responseA2->assertDontSee($tripA->trip_code);
        $responseA2->assertSee('Belum Ada Tugas Pengantaran Aktif', false);
    }

    /**
     * Test 10: Driver can inspect vehicle and transition trip to READY (D02).
     */
    public function test_driver_can_inspect_vehicle_and_transition_to_ready(): void
    {
        $tripService = app(TripDispatchService::class);
        $trip = $tripService->createTrip([
            'service_date' => Carbon::today()->toDateString(),
            'vehicle_id' => $this->vehicleA1->id,
            'driver_id' => $this->driverA->id,
            'stops' => [
                [
                    'school_id' => $this->schoolA1->id,
                    'sequence_number' => 1,
                    'items' => [
                        [
                            'delivery_item_id' => $this->deliveryItemSchool1->id,
                            'loaded_portions' => 200,
                        ],
                    ],
                ],
            ],
        ], $this->kepalaA);

        $this->assertEquals(TripStatus::ASSIGNED, $trip->status);

        // Driver views preparation page D02
        $this->actingAs($this->driverA)
            ->get(route('driver.trips.prepare', $trip))
            ->assertStatus(200)
            ->assertSee('Pemeriksaan Fisik Kelaikan Armada', false)
            ->assertSee($this->vehicleA1->plate_number);

        // Driver submits passing vehicle checklist
        $response = $this->actingAs($this->driverA)
            ->post(route('driver.trips.vehicle-check', $trip), [
                'passed' => 1,
                'notes' => 'Rem, ban, dan boks makanan prima.',
            ]);

        $response->assertRedirect(route('driver.trips.prepare', $trip));
        $response->assertSessionHas('success');

        $trip->refresh();
        $this->assertTrue($trip->vehicle_check_passed);
        $this->assertEquals(TripStatus::VEHICLE_CHECKED, $trip->status);
        $this->assertNotNull($trip->vehicle_checked_at);
        $this->assertEquals('Rem, ban, dan boks makanan prima.', $trip->vehicle_check_notes);

        // Verify physical loading and approve dispatch to transition to READY
        $tripService = app(TripDispatchService::class);
        $tripService->verifyLoading($trip, [
            'items' => [
                $this->deliveryItemSchool1->id => 200,
            ],
        ], $this->asistenA);
        $tripService->approveDispatch($trip, $this->kepalaA);
        $trip->refresh();
        $this->assertEquals(TripStatus::READY, $trip->status);
    }

    /**
     * Test 11: Driver cannot start trip before vehicle inspection is passed.
     */
    public function test_driver_cannot_start_trip_before_vehicle_inspection(): void
    {
        $tripService = app(TripDispatchService::class);
        $trip = $tripService->createTrip([
            'service_date' => Carbon::today()->toDateString(),
            'vehicle_id' => $this->vehicleA1->id,
            'driver_id' => $this->driverA->id,
            'stops' => [
                [
                    'school_id' => $this->schoolA1->id,
                    'sequence_number' => 1,
                    'items' => [
                        [
                            'delivery_item_id' => $this->deliveryItemSchool1->id,
                            'loaded_portions' => 200,
                        ],
                    ],
                ],
            ],
        ], $this->kepalaA);

        $this->assertEquals(TripStatus::ASSIGNED, $trip->status);

        // Attempting to start trip without vehicle check throws error
        $this->expectException(ValidationException::class);
        $tripService->startTrip($trip, $this->driverA);
    }

    /**
     * Test 12: Driver can start trip when READY, transitioning to IN_PROGRESS and first stop to EN_ROUTE.
     */
    public function test_driver_can_start_trip_when_ready_and_first_stop_becomes_en_route(): void
    {
        $tripService = app(TripDispatchService::class);
        $trip = $tripService->createTrip([
            'service_date' => Carbon::today()->toDateString(),
            'vehicle_id' => $this->vehicleA1->id,
            'driver_id' => $this->driverA->id,
            'stops' => [
                [
                    'school_id' => $this->schoolA1->id,
                    'sequence_number' => 1,
                    'items' => [
                        [
                            'delivery_item_id' => $this->deliveryItemSchool1->id,
                            'loaded_portions' => 200,
                        ],
                    ],
                ],
            ],
        ], $this->kepalaA);

        // Complete loading verification
        $tripService->verifyLoading($trip, [
            'items' => [
                $this->deliveryItemSchool1->id => 200,
            ],
        ], $this->asistenA);

        // Complete vehicle check
        $tripService->checkVehicle($trip, ['passed' => true, 'notes' => 'OK'], $this->driverA);

        // Complete dispatch authorization
        $tripService->approveDispatch($trip, $this->kepalaA);

        $trip->refresh();
        $this->assertEquals(TripStatus::READY, $trip->status);

        // Driver starts trip via HTTP POST
        $response = $this->actingAs($this->driverA)
            ->post(route('driver.trips.start', $trip));

        $response->assertRedirect(route('driver.trips.prepare', $trip));
        $response->assertSessionHas('success');

        $trip->refresh();
        $this->assertEquals(TripStatus::IN_PROGRESS, $trip->status);
        $this->assertNotNull($trip->started_at);
        $this->assertEquals('WAITING_FOR_LOCATION', $trip->tracking_status);

        // First stop is EN_ROUTE
        $stop1 = $trip->stops->first();
        $this->assertEquals(TripStopStatus::EN_ROUTE, $stop1->status);

        // Audit log recorded
        $this->assertDatabaseHas('activity_logs', [
            'event' => 'trip.started',
            'auditable_id' => $trip->id,
            'actor_id' => $this->driverA->id,
        ]);
    }

    /**
     * Test 13: Non-assigned driver cannot inspect or start someone else's trip.
     */
    public function test_non_assigned_driver_cannot_inspect_or_start_trip(): void
    {
        $tripService = app(TripDispatchService::class);
        $trip = $tripService->createTrip([
            'service_date' => Carbon::today()->toDateString(),
            'vehicle_id' => $this->vehicleA1->id,
            'driver_id' => $this->driverA->id, // Assigned to Driver A
            'stops' => [
                [
                    'school_id' => $this->schoolA1->id,
                    'sequence_number' => 1,
                    'items' => [
                        [
                            'delivery_item_id' => $this->deliveryItemSchool1->id,
                            'loaded_portions' => 200,
                        ],
                    ],
                ],
            ],
        ], $this->kepalaA);

        // Driver A2 tries to access prepare page
        $this->actingAs($this->driverA2)
            ->get(route('driver.trips.prepare', $trip))
            ->assertStatus(403);

        // Driver A2 tries to submit vehicle check
        $this->actingAs($this->driverA2)
            ->post(route('driver.trips.vehicle-check', $trip), ['passed' => 1])
            ->assertStatus(403);

        // Driver A2 tries to start trip
        $this->actingAs($this->driverA2)
            ->post(route('driver.trips.start', $trip))
            ->assertStatus(403);
    }

    /**
     * Test 14: Cross-role Trip Detail (Page C03) scoping and authorization.
     */
    public function test_cross_role_trip_detail_c03_authorizations(): void
    {
        $tripService = app(TripDispatchService::class);
        $trip = $tripService->createTrip([
            'service_date' => Carbon::today()->toDateString(),
            'vehicle_id' => $this->vehicleA1->id,
            'driver_id' => $this->driverA->id,
            'stops' => [
                [
                    'school_id' => $this->schoolA1->id, // School A1
                    'sequence_number' => 1,
                    'items' => [
                        [
                            'delivery_item_id' => $this->deliveryItemSchool1->id,
                            'loaded_portions' => 200,
                        ],
                    ],
                ],
                [
                    'school_id' => $this->schoolA2->id, // School A2
                    'sequence_number' => 2,
                    'items' => [
                        [
                            'delivery_item_id' => $this->deliveryItemSchool2->id,
                            'loaded_portions' => 130,
                        ],
                    ],
                ],
            ],
        ], $this->kepalaA);

        // 1. Kepala SPPG A can view full trip
        $this->actingAs($this->kepalaA)
            ->get(route('trips.show', $trip))
            ->assertStatus(200)
            ->assertSee($trip->trip_code)
            ->assertSee($this->schoolA1->name)
            ->assertSee($this->schoolA2->name);

        // 2. Driver A can view full trip
        $this->actingAs($this->driverA)
            ->get(route('trips.show', $trip))
            ->assertStatus(200)
            ->assertSee($trip->trip_code);

        // 3. PIC of School A1 can view trip with safe scoping
        $this->actingAs($this->picSchoolA1)
            ->get(route('trips.show', $trip))
            ->assertStatus(200)
            ->assertSee('Sekolah Anda')
            ->assertSee('Terlindungi Privasi');

        // 4. PIC of School B1 (different tenant) CANNOT view trip
        $this->actingAs($this->picSchoolB1)
            ->get(route('trips.show', $trip))
            ->assertStatus(403);
    }

    /**
     * Test 15: Kepala SPPG can cancel an unstarted trip and re-release delivery items.
     */
    public function test_kepala_sppg_can_cancel_unstarted_trip_and_re_release_delivery_items(): void
    {
        $tripService = app(TripDispatchService::class);
        $trip = $tripService->createTrip([
            'service_date' => Carbon::today()->toDateString(),
            'vehicle_id' => $this->vehicleA1->id,
            'driver_id' => $this->driverA->id,
            'stops' => [
                [
                    'school_id' => $this->schoolA1->id,
                    'sequence_number' => 1,
                    'items' => [
                        [
                            'delivery_item_id' => $this->deliveryItemSchool1->id,
                            'loaded_portions' => 200,
                        ],
                    ],
                ],
            ],
        ], $this->kepalaA);

        $this->assertNotNull($this->deliveryItemSchool1->fresh()->trip_stop_id);

        // Cancel trip
        $response = $this->actingAs($this->kepalaA)
            ->post(route('head.distribution.trips.cancel', $trip), [
                'reason' => 'Perubahan jadwal penugasan armada',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $trip->refresh();
        $this->assertEquals(TripStatus::CANCELLED, $trip->status);

        // Stop set to SKIPPED
        $this->assertEquals(TripStopStatus::SKIPPED, $trip->stops->first()->status);

        // DeliveryItem re-released
        $this->deliveryItemSchool1->refresh();
        $this->assertNull($this->deliveryItemSchool1->trip_stop_id);
        $this->assertNull($this->deliveryItemSchool1->loaded_portions);
        $this->assertEquals('RELEASED', $this->deliveryItemSchool1->status);

        // Audit log recorded
        $this->assertDatabaseHas('activity_logs', [
            'event' => 'trip.cancelled',
            'auditable_id' => $trip->id,
            'actor_id' => $this->kepalaA->id,
        ]);
    }
}
