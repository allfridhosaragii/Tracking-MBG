<?php

namespace App\Services;

use App\Enums\FoodBatchStatus;
use App\Enums\TrayReturnStatus;
use App\Enums\TripStatus;
use App\Enums\TripStopStatus;
use App\Enums\TripType;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\DeliveryItem;
use App\Models\FoodBatch;
use App\Models\School;
use App\Models\TrayReturn;
use App\Models\Trip;
use App\Models\TripStop;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TripDispatchService
{
    protected OsrmRoutingService $routingService;

    public function __construct(?OsrmRoutingService $routingService = null)
    {
        $this->routingService = $routingService ?? app(OsrmRoutingService::class);
    }

    /**
     * Get logistics and dispatch planning state for Kepala SPPG workspace.
     */
    public function getDispatchOverview(int $sppgId, ?string $serviceDate = null): array
    {
        $date = $serviceDate ?: Carbon::today()->toDateString();

        // 1. Released food batches for this SPPG on this service date
        $releasedBatches = FoodBatch::whereHas('productionPlan', function ($query) use ($sppgId, $date) {
            $query->where('sppg_id', $sppgId)
                ->whereDate('service_date', $date);
        })
            ->where('status', FoodBatchStatus::RELEASED)
            ->with(['recipeMenu', 'productionPlan', 'deliveryItems.productionAllocation.school'])
            ->get();

        // 2. Available delivery items (ready to assign, not yet linked to a trip stop)
        $unassignedDeliveryItems = DeliveryItem::whereHas('foodBatch.productionPlan', function ($query) use ($sppgId, $date) {
            $query->where('sppg_id', $sppgId)
                ->whereDate('service_date', $date);
        })
            ->whereHas('foodBatch', function ($query) {
                $query->where('status', FoodBatchStatus::RELEASED);
            })
            ->whereNull('trip_stop_id')
            ->whereNotNull('released_portions')
            ->where('released_portions', '>', 0)
            ->with(['foodBatch.recipeMenu', 'productionAllocation.school'])
            ->get();

        // 3. Active vehicles and current busy status for this date
        $vehicles = Vehicle::forSppg($sppgId)
            ->active()
            ->with(['trips' => function ($query) use ($date) {
                $query->whereDate('service_date', $date)
                    ->whereIn('status', [
                        TripStatus::ASSIGNED->value,
                        TripStatus::VEHICLE_CHECKED->value,
                        TripStatus::READY->value,
                        TripStatus::IN_PROGRESS->value,
                    ]);
            }])
            ->get()
            ->map(function ($vehicle) {
                $vehicle->is_busy = $vehicle->trips->isNotEmpty();
                $vehicle->active_trip = $vehicle->trips->first();
                return $vehicle;
            });

        // 4. Active drivers (KURIR) and busy status for this date
        $drivers = User::where('sppg_id', $sppgId)
            ->where('role', UserRole::KURIR->value)
            ->active()
            ->with(['driverTrips' => function ($query) use ($date) {
                $query->whereDate('service_date', $date)
                    ->whereIn('status', [
                        TripStatus::ASSIGNED->value,
                        TripStatus::VEHICLE_CHECKED->value,
                        TripStatus::READY->value,
                        TripStatus::IN_PROGRESS->value,
                    ]);
            }])
            ->get()
            ->map(function ($driver) {
                $driver->is_busy = $driver->driverTrips->isNotEmpty();
                $driver->active_trip = $driver->driverTrips->first();
                return $driver;
            });

        // 5. Existing trips for this SPPG and date
        $trips = Trip::forSppg($sppgId)
            ->whereDate('service_date', $date)
            ->with([
                'driver',
                'vehicle',
                'loadingVerifiedBy',
                'dispatchApprovedBy',
                'stops.school',
                'stops.deliveryItems.foodBatch',
            ])
            ->latest()
            ->get();

        // 6. Schools for this SPPG
        $schools = School::where('sppg_id', $sppgId)
            ->where('is_active', true)
            ->get();

        // 7. Ready Tray Returns waiting for pickup assignment (PBI-12)
        $readyTrayReturns = TrayReturn::whereHas('schoolServiceRecord.tripStop', function ($query) use ($sppgId) {
            $query->whereHas('school', function ($q2) use ($sppgId) {
                $q2->where('sppg_id', $sppgId);
            });
        })
        ->whereIn('status', [TrayReturnStatus::READY_FOR_PICKUP, TrayReturnStatus::PICKUP_ASSIGNED])
        ->with([
            'schoolServiceRecord.tripStop.school',
            'schoolServiceRecord.tripStop.trip',
            'pickupTripStop.trip.driver',
            'pickupTripStop.trip.vehicle',
            'evidencePhotos',
        ])
        ->orderBy('ready_at')
        ->get();

        return [
            'service_date' => $date,
            'released_batches' => $releasedBatches,
            'unassigned_delivery_items' => $unassignedDeliveryItems,
            'vehicles' => $vehicles,
            'drivers' => $drivers,
            'trips' => $trips,
            'schools' => $schools,
            'ready_tray_returns' => $readyTrayReturns,
        ];
    }

    /**
     * Create a new trip with scheduled stops and linked delivery items.
     * Enforces:
     * 1. Actor must be KEPALA_SPPG for their SPPG.
     * 2. Active vehicle with known capacity (> 0) and without schedule conflicts.
     * 3. Active driver (KURIR) without assignment conflicts.
     * 4. Multi-school unique stops in defined sequential sequence starting at 1.
     * 5. Delivery items must belong to RELEASED batches, same service_date, and correct schools.
     * 6. Planned portion <= QC released available portions (NO fallback to planned!).
     * 7. Total planned <= vehicle capacity.
     * 8. Safe concurrency & idempotent creation.
     */
    public function createTrip(array $data, User $actor): Trip
    {
        return DB::transaction(function () use ($data, $actor) {
            $sppgId = (int) $actor->sppg_id;
            $serviceDate = $data['service_date'] ?? Carbon::today()->toDateString();

            // 1. Validate & Lock Vehicle
            $vehicleId = (int) ($data['vehicle_id'] ?? 0);
            $vehicle = Vehicle::where('id', $vehicleId)
                ->where('sppg_id', $sppgId)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (! $vehicle) {
                throw ValidationException::withMessages([
                    'vehicle_id' => ['Kendaraan yang dipilih tidak valid, tidak aktif, atau bukan milik SPPG ini.'],
                ]);
            }

            // Enforce vehicle capacity known and > 0
            if (! $vehicle->capacity_portions || $vehicle->capacity_portions <= 0) {
                throw ValidationException::withMessages([
                    'vehicle_id' => ["Kapasitas armada kendaraan {$vehicle->plate_number} belum terdefinisi atau tidak valid (0). Lengkapi data resmi kapasitas armada."],
                ]);
            }

            // Check vehicle collision
            $vehicleBusy = Trip::where('vehicle_id', $vehicle->id)
                ->whereDate('service_date', $serviceDate)
                ->whereIn('status', [
                    TripStatus::ASSIGNED->value,
                    TripStatus::VEHICLE_CHECKED->value,
                    TripStatus::READY->value,
                    TripStatus::IN_PROGRESS->value,
                ])
                ->exists();

            if ($vehicleBusy) {
                throw ValidationException::withMessages([
                    'vehicle_id' => ["Kendaraan {$vehicle->plate_number} sudah memiliki penugasan perjalanan aktif pada tanggal {$serviceDate}."],
                ]);
            }

            // 2. Validate & Lock Driver
            $driverId = (int) ($data['driver_id'] ?? 0);
            $driver = User::where('id', $driverId)
                ->where('sppg_id', $sppgId)
                ->where('role', UserRole::KURIR->value)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (! $driver) {
                throw ValidationException::withMessages([
                    'driver_id' => ['Kurir yang dipilih tidak valid, bukan berstatus aktif, atau bukan bagian dari SPPG ini.'],
                ]);
            }

            // Check driver collision
            $driverBusy = Trip::where('driver_id', $driver->id)
                ->whereDate('service_date', $serviceDate)
                ->whereIn('status', [
                    TripStatus::ASSIGNED->value,
                    TripStatus::VEHICLE_CHECKED->value,
                    TripStatus::READY->value,
                    TripStatus::IN_PROGRESS->value,
                ])
                ->exists();

            if ($driverBusy) {
                throw ValidationException::withMessages([
                    'driver_id' => ["Kurir {$driver->name} sudah memiliki penugasan perjalanan aktif pada tanggal {$serviceDate}."],
                ]);
            }

            // 3. Validate Stops Structure & Sequence
            $stopsData = $data['stops'] ?? [];
            if (empty($stopsData) || ! is_array($stopsData)) {
                throw ValidationException::withMessages([
                    'stops' => ['Perjalanan harus memiliki minimal satu tujuan sekolah.'],
                ]);
            }

            $stopCount = count($stopsData);
            $sequences = [];
            foreach ($stopsData as $idx => $stopInput) {
                if (! isset($stopInput['sequence_number'])) {
                    throw ValidationException::withMessages([
                        "stops.{$idx}.sequence_number" => ['Nomor urutan pemberhentian wajib diisi.'],
                    ]);
                }
                $seq = (int) $stopInput['sequence_number'];
                $sequences[] = $seq;
            }

            // Validate sequence: starts at 1, strictly consecutive without gaps, no duplicates
            sort($sequences);
            $expectedSequences = range(1, $stopCount);
            if ($sequences !== $expectedSequences) {
                throw ValidationException::withMessages([
                    'sequence_number' => ['Urutan pemberhentian (sequence_number) harus mulai dari 1, berurutan tanpa jeda atau angka lompat, dan unik untuk setiap stop.'],
                ]);
            }

            $schoolIdsSeen = [];
            $deliveryItemIdsSeen = [];
            $batchCommittedPortions = [];
            $totalPlannedTrip = 0;
            $validatedStops = [];

            $tripType = $data['trip_type'] ?? TripType::DELIVERY->value;
            $isPickupTrip = in_array($tripType, [TripType::PICKUP->value, 'PICKUP'], true);
            $isMixedTrip = in_array($tripType, [TripType::MIXED->value, 'MIXED'], true);

            foreach ($stopsData as $index => $stopInput) {
                $schoolId = (int) ($stopInput['school_id'] ?? 0);
                $sequence = (int) $stopInput['sequence_number'];

                // Verify school tenant scope
                $school = School::where('id', $schoolId)
                    ->where('sppg_id', $sppgId)
                    ->where('is_active', true)
                    ->first();

                if (! $school) {
                    throw ValidationException::withMessages([
                        "stops.{$index}.school_id" => ['Sekolah tujuan tidak valid atau berada di luar cakupan SPPG Anda.'],
                    ]);
                }

                if (in_array($schoolId, $schoolIdsSeen, true)) {
                    throw ValidationException::withMessages([
                        "stops.{$index}.school_id" => ["Sekolah {$school->name} tidak boleh diduplikasi dalam satu rute perjalanan."],
                    ]);
                }
                $schoolIdsSeen[] = $schoolId;

                $stopType = $stopInput['stop_type'] ?? ($isPickupTrip ? 'PICKUP' : 'DELIVERY');

                if ($isPickupTrip && $stopType === 'DELIVERY') {
                    throw ValidationException::withMessages([
                        "stops.{$index}.stop_type" => ['Perjalanan penjemputan (PICKUP) tidak boleh memiliki perhentian pengantaran makanan (DELIVERY).'],
                    ]);
                }

                if (! $isPickupTrip && ! $isMixedTrip && $stopType === 'PICKUP') {
                    throw ValidationException::withMessages([
                        "stops.{$index}.stop_type" => ['Perjalanan pengantaran (DELIVERY) tidak boleh memiliki perhentian penjemputan (PICKUP). Gunakan tipe MIXED atau PICKUP.'],
                    ]);
                }

                if ($stopType === 'PICKUP') {
                    // GATE A: Valid reverse logistics task required for school
                    $hasContainers = TrayReturn::whereHas('schoolServiceRecord.tripStop', function ($q) use ($schoolId) {
                        $q->where('school_id', $schoolId);
                    })
                    ->whereIn('status', [TrayReturnStatus::READY_FOR_PICKUP, TrayReturnStatus::IN_USE, TrayReturnStatus::PICKUP_ASSIGNED])
                    ->exists();

                    if (! $hasContainers) {
                        throw ValidationException::withMessages([
                            "stops.{$index}.school_id" => ["Sekolah {$school->name} tidak memiliki catatan wadah ompreng yang siap dijemput."],
                        ]);
                    }

                    $validatedStops[] = [
                        'school_id' => $schoolId,
                        'sequence_number' => $sequence,
                        'stop_type' => 'PICKUP',
                        'planned_arrival_at' => $stopInput['planned_arrival_at'] ?? null,
                        'planned_portions' => 0,
                        'items' => [],
                    ];
                    continue;
                }

                // Validate Delivery Items for this DELIVERY stop
                $itemsInput = $stopInput['items'] ?? [];
                if (empty($itemsInput) || ! is_array($itemsInput)) {
                    throw ValidationException::withMessages([
                        "stops.{$index}.items" => ["Pemberhentian sekolah {$school->name} harus memiliki minimal satu alokasi makanan."],
                    ]);
                }

                $validatedItems = [];
                $stopPlannedPortions = 0;

                foreach ($itemsInput as $itemIdx => $itemData) {
                    $deliveryItemId = (int) ($itemData['delivery_item_id'] ?? 0);
                    $plannedPortions = (int) ($itemData['planned_portions'] ?? ($itemData['loaded_portions'] ?? 0));

                    if ($plannedPortions <= 0) {
                        throw ValidationException::withMessages([
                            "stops.{$index}.items.{$itemIdx}.planned_portions" => ['Jumlah porsi rencana alokasi harus lebih dari nol.'],
                        ]);
                    }

                    // Enforce unique delivery item in this request
                    if (in_array($deliveryItemId, $deliveryItemIdsSeen, true)) {
                        throw ValidationException::withMessages([
                            "stops.{$index}.items.{$itemIdx}.delivery_item_id" => ["Item alokasi makanan #{$deliveryItemId} diduplikasi dalam satu pengiriman."],
                        ]);
                    }
                    $deliveryItemIdsSeen[] = $deliveryItemId;

                    // Lock DeliveryItem
                    $deliveryItem = DeliveryItem::where('id', $deliveryItemId)
                        ->lockForUpdate()
                        ->with(['foodBatch.productionPlan', 'productionAllocation'])
                        ->first();

                    if (! $deliveryItem) {
                        throw ValidationException::withMessages([
                            "stops.{$index}.items.{$itemIdx}.delivery_item_id" => ['Item alokasi makanan tidak ditemukan.'],
                        ]);
                    }

                    // Check if already assigned to a stop
                    if ($deliveryItem->trip_stop_id !== null) {
                        throw ValidationException::withMessages([
                            "stops.{$index}.items.{$itemIdx}.delivery_item_id" => ['Item alokasi ini sudah ditugaskan ke perjalanan lain.'],
                        ]);
                    }

                    // Check school match
                    if ((int) $deliveryItem->productionAllocation->school_id !== $schoolId) {
                        throw ValidationException::withMessages([
                            "stops.{$index}.items.{$itemIdx}.delivery_item_id" => ['Alokasi makanan tidak sesuai dengan sekolah tujuan pemberhentian ini.'],
                        ]);
                    }

                    // Check FoodBatch status & safety
                    $batch = $deliveryItem->foodBatch;
                    if (! $batch || $batch->status !== FoodBatchStatus::RELEASED) {
                        $batchStatus = $batch?->status->label() ?? 'Belum ada';
                        throw ValidationException::withMessages([
                            "stops.{$index}.items.{$itemIdx}.delivery_item_id" => [
                                "Batch {$batch?->batch_code} belum berstatus RELEASED (Status saat ini: {$batchStatus}). Makanan belum lulus uji mutu QC.",
                            ],
                        ]);
                    }

                    // Tenant check on batch
                    if ((int) $batch->productionPlan->sppg_id !== $sppgId) {
                        throw ValidationException::withMessages([
                            "stops.{$index}.items.{$itemIdx}.delivery_item_id" => ['Batch produksi berasal dari SPPG lain.'],
                        ]);
                    }

                    // Date check: production plan service_date must match trip service_date
                    $planDate = Carbon::parse($batch->productionPlan->service_date)->toDateString();
                    if ($planDate !== $serviceDate) {
                        throw ValidationException::withMessages([
                            "stops.{$index}.items.{$itemIdx}.delivery_item_id" => [
                                "Batch {$batch->batch_code} dijadwalkan untuk tanggal layanan {$planDate}, berbeda dengan tanggal perjalanan {$serviceDate}.",
                            ],
                        ]);
                    }

                    // HARD GATE C: released_portions must NOT be null. NO fallback to planned_portions!
                    if ($deliveryItem->released_portions === null) {
                        throw ValidationException::withMessages([
                            "stops.{$index}.items.{$itemIdx}.delivery_item_id" => [
                                "Batch {$batch->batch_code} belum memiliki kuantitas rilisan QC yang sah (released_portions bernilai null).",
                            ],
                        ]);
                    }

                    $availablePortions = (int) $deliveryItem->released_portions;
                    if ($plannedPortions > $availablePortions) {
                        throw ValidationException::withMessages([
                            "stops.{$index}.items.{$itemIdx}.planned_portions" => [
                                "Jumlah porsi rencana ({$plannedPortions}) melebihi kuantitas yang disetujui QC ({$availablePortions} porsi) untuk sekolah ini.",
                            ],
                        ]);
                    }

                    // Cumulative batch check across entire request against batch accepted portions
                    $batchCommittedPortions[$batch->id] = ($batchCommittedPortions[$batch->id] ?? 0) + $plannedPortions;
                    if ($batchCommittedPortions[$batch->id] > (int) $batch->accepted_portions) {
                        throw ValidationException::withMessages([
                            "stops.{$index}.items.{$itemIdx}.delivery_item_id" => [
                                "Akumulasi muatan untuk batch {$batch->batch_code} ({$batchCommittedPortions[$batch->id]} porsi) melebihi total porsi lolos QC ({$batch->accepted_portions} porsi).",
                            ],
                        ]);
                    }

                    $totalPlannedTrip += $plannedPortions;
                    $stopPlannedPortions += $plannedPortions;

                    $validatedItems[] = [
                        'delivery_item' => $deliveryItem,
                        'planned_portions' => $plannedPortions,
                    ];
                }

                $validatedStops[] = [
                    'school_id' => $schoolId,
                    'sequence_number' => $sequence,
                    'stop_type' => 'DELIVERY',
                    'planned_arrival_at' => $stopInput['planned_arrival_at'] ?? null,
                    'planned_portions' => $stopPlannedPortions,
                    'items' => $validatedItems,
                ];
            }

            // 4. Vehicle Capacity Validation
            if ($totalPlannedTrip > $vehicle->capacity_portions) {
                throw ValidationException::withMessages([
                    'vehicle_id' => [
                        "Total muatan rencana ({$totalPlannedTrip} porsi) melampaui kapasitas armada {$vehicle->plate_number} ({$vehicle->capacity_portions} porsi).",
                    ],
                ]);
            }

            // 5. Generate unique Trip Code
            $datePrefix = Carbon::parse($serviceDate)->format('Ymd');
            $sppgCode = $actor->sppg?->sppg_code ?: 'SPPG-' . $sppgId;
            $tripCountToday = Trip::where('sppg_id', $sppgId)
                ->whereDate('service_date', $serviceDate)
                ->count();
            $seqPad = str_pad($tripCountToday + 1, 2, '0', STR_PAD_LEFT);
            $tripCode = "TRIP-{$datePrefix}-{$sppgCode}-{$seqPad}";

            // 6. Create Trip Record (Initialized with ASSIGNED, loading_verified = false unless PICKUP-only)
            $trip = Trip::create([
                'sppg_id' => $sppgId,
                'driver_id' => $driver->id,
                'vehicle_id' => $vehicle->id,
                'trip_code' => $tripCode,
                'trip_type' => $tripType,
                'service_date' => $serviceDate,
                'planned_departure_at' => $data['planned_departure_at'] ?? null,
                'status' => TripStatus::ASSIGNED,
                'vehicle_check_passed' => false,
                'loading_verified' => $isPickupTrip ? true : false,
                'loading_verified_at' => $isPickupTrip ? Carbon::now() : null,
                'loading_verified_by' => $isPickupTrip ? $actor->id : null,
                'loading_notes' => $isPickupTrip ? 'Penjemputan ompreng (kendaraan berangkat tanpa muatan makanan).' : null,
                'dispatch_approved' => false,
                'tracking_status' => 'OFF',
                'created_by' => $actor->id,
            ]);

            // 7. Create Stops & Link Delivery Items (HARD GATE A: status = PENDING_LOADING, loaded_portions = null)
            foreach ($validatedStops as $stopData) {
                $tripStop = TripStop::create([
                    'trip_id' => $trip->id,
                    'school_id' => $stopData['school_id'],
                    'stop_type' => $stopData['stop_type'],
                    'sequence_number' => $stopData['sequence_number'],
                    'planned_portions' => $stopData['planned_portions'],
                    'loaded_portions' => $stopData['stop_type'] === 'PICKUP' ? 0 : null,
                    'planned_arrival_at' => $stopData['planned_arrival_at'],
                    'status' => TripStopStatus::PENDING,
                ]);

                foreach ($stopData['items'] as $itemData) {
                    /** @var DeliveryItem $item */
                    $item = $itemData['delivery_item'];
                    $item->update([
                        'trip_stop_id' => $tripStop->id,
                        'planned_portions' => $itemData['planned_portions'],
                        'loaded_portions' => null,
                        'status' => 'PENDING_LOADING',
                    ]);
                }
            }

            // 8. Pre-calculate OSRM Route Geometry (Non-blocking fallback if OSRM unavailable)
            try {
                $this->routingService->calculateTripRoute($trip);
            } catch (\Throwable $e) {
                // Route calculation logged but does not block atomic trip creation
            }

            // 9. Record Activity Audit Trail
            ActivityLog::record(
                event: 'trip.created_and_assigned',
                auditable: $trip,
                actor: $actor,
                newValues: [
                    'trip_code' => $trip->trip_code,
                    'driver_id' => $driver->id,
                    'driver_name' => $driver->name,
                    'vehicle_id' => $vehicle->id,
                    'vehicle_plate' => $vehicle->plate_number,
                    'stops_count' => count($validatedStops),
                    'total_planned_portions' => $totalPlannedTrip,
                    'status' => TripStatus::ASSIGNED->value,
                ]
            );

            return $trip->load(['stops.school', 'stops.deliveryItems.foodBatch', 'driver', 'vehicle']);
        });
    }

    /**
     * Physical Loading Verification by Asisten Lapangan / SPPG Staff.
     * HARD GATE A & B:
     * Verifies physical counts of boxes loaded into vehicle per stop & batch.
     * Transitions DeliveryItem status: PENDING_LOADING -> LOADED.
     */
    /**
     * Physical Loading Verification by Asisten Lapangan / SPPG Staff.
     * HARD GATE A & B:
     * Verifies physical counts of boxes loaded into vehicle per stop & batch.
     * Transitions DeliveryItem status: PENDING_LOADING -> LOADED.
     * CRITICAL GATE B: Validates that ALL manifest items belonging to the trip are present,
     * rejects partial manifests, duplicates, or foreign items.
     * CRITICAL GATE C: Revokes any prior dispatch approval when loading is modified.
     */
    public function verifyLoading(Trip $trip, array $loadingData, User $actor): Trip
    {
        return DB::transaction(function () use ($trip, $loadingData, $actor) {
            $lockedTrip = Trip::where('id', $trip->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $lockedTrip->sppg_id !== (int) $actor->sppg_id) {
                throw ValidationException::withMessages([
                    'authorization' => ['Anda tidak berwenang memverifikasi muatan SPPG lain.'],
                ]);
            }

            if ($lockedTrip->trip_type === TripType::PICKUP || $lockedTrip->trip_type === 'PICKUP') {
                throw ValidationException::withMessages([
                    'trip' => ['Perjalanan penjemputan (PICKUP) tidak memiliki muatan makanan untuk diverifikasi.'],
                ]);
            }

            if (in_array($lockedTrip->status, [TripStatus::IN_PROGRESS, TripStatus::COMPLETED, TripStatus::CANCELLED], true)) {
                throw ValidationException::withMessages([
                    'status' => ["Verifikasi muatan tidak dapat dilakukan pada perjalanan berstatus {$lockedTrip->status->label()}."],
                ]);
            }

            $itemsData = $loadingData['items'] ?? [];
            if (empty($itemsData) || ! is_array($itemsData)) {
                throw ValidationException::withMessages([
                    'items' => ['Data verifikasi kuantitas fisik muatan wajib disertakan.'],
                ]);
            }

            // Retrieve all required delivery items for this trip
            $requiredItems = DeliveryItem::whereHas('tripStop', function ($q) use ($lockedTrip) {
                $q->where('trip_id', $lockedTrip->id);
            })->with('foodBatch')->lockForUpdate()->get()->keyBy('id');

            if ($requiredItems->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => ['Perjalanan ini tidak memiliki item alokasi muatan untuk diverifikasi.'],
                ]);
            }

            // Normalize items input and detect duplicates
            $normalizedItems = [];
            $seenIds = [];
            foreach ($itemsData as $idx => $itemInput) {
                if (is_array($itemInput)) {
                    $itemId = (int) ($itemInput['delivery_item_id'] ?? $idx);
                    $qty = (int) ($itemInput['loaded_portions'] ?? 0);
                } else {
                    $itemId = (int) $idx;
                    $qty = (int) $itemInput;
                }

                if (in_array($itemId, $seenIds, true)) {
                    throw ValidationException::withMessages([
                        'items' => ["Item muatan #{$itemId} diduplikasi dalam payload verifikasi."],
                    ]);
                }
                $seenIds[] = $itemId;
                $normalizedItems[$itemId] = $qty;
            }

            // CRITICAL GATE B: Validate that ALL required items in the trip are present
            $missingItemIds = $requiredItems->keys()->diff($seenIds);
            if ($missingItemIds->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'items' => [
                        "Seluruh item manifest muatan perjalanan wajib diverifikasi. Terdapat {$missingItemIds->count()} item alokasi yang belum tercantum dalam data verifikasi fisik.",
                    ],
                ]);
            }

            // Reject foreign items not belonging to this trip
            $foreignItemIds = collect($seenIds)->diff($requiredItems->keys());
            if ($foreignItemIds->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'items' => [
                        "Item muatan #{$foreignItemIds->first()} tidak terdaftar dalam perjalanan ini.",
                    ],
                ]);
            }

            $totalLoadedTrip = 0;
            $batchCommittedPortions = [];
            $vehicle = $lockedTrip->vehicle;

            foreach ($normalizedItems as $deliveryItemId => $loadedQuantity) {
                if ($loadedQuantity <= 0) {
                    throw ValidationException::withMessages([
                        "items.{$deliveryItemId}" => ['Kuantitas fisik muatan harus lebih dari nol.'],
                    ]);
                }

                $deliveryItem = $requiredItems[$deliveryItemId];
                $availablePortions = (int) $deliveryItem->released_portions;

                if ($loadedQuantity > $availablePortions) {
                    throw ValidationException::withMessages([
                        "items.{$deliveryItemId}" => [
                            "Kuantitas muat ({$loadedQuantity} porsi) melebihi kuota rilisan QC yang sah ({$availablePortions} porsi).",
                        ],
                    ]);
                }

                // Cumulative batch check across loaded items
                $batch = $deliveryItem->foodBatch;
                $batchId = $batch->id;
                $batchCommittedPortions[$batchId] = ($batchCommittedPortions[$batchId] ?? 0) + $loadedQuantity;
                if ($batchCommittedPortions[$batchId] > (int) $batch->accepted_portions) {
                    throw ValidationException::withMessages([
                        "items.{$deliveryItemId}" => [
                            "Akumulasi muatan fisik untuk batch {$batch->batch_code} ({$batchCommittedPortions[$batchId]} porsi) melebihi total porsi lolos QC ({$batch->accepted_portions} porsi).",
                        ],
                    ]);
                }

                $deliveryItem->update([
                    'loaded_portions' => $loadedQuantity,
                    'status' => 'LOADED',
                ]);

                $totalLoadedTrip += $loadedQuantity;
            }

            // Check against vehicle capacity
            if ($vehicle && $vehicle->capacity_portions && $totalLoadedTrip > $vehicle->capacity_portions) {
                throw ValidationException::withMessages([
                    'capacity' => [
                        "Total kuantitas fisik muatan ({$totalLoadedTrip} porsi) melebihi batas kapasitas armada {$vehicle->plate_number} ({$vehicle->capacity_portions} porsi).",
                    ],
                ]);
            }

            // Update loaded_portions on each trip_stop
            foreach ($lockedTrip->stops as $stop) {
                $stopLoaded = DeliveryItem::where('trip_stop_id', $stop->id)->sum('loaded_portions');
                $stop->update([
                    'loaded_portions' => (int) $stopLoaded,
                ]);
            }

            // CRITICAL GATE C: Invalidate prior dispatch approval upon modifying loaded portions
            $lockedTrip->update([
                'loading_verified' => true,
                'loading_verified_at' => Carbon::now(),
                'loading_verified_by' => $actor->id,
                'loading_notes' => $loadingData['notes'] ?? null,
                'dispatch_approved' => false, // Prior approval revoked until re-approved by Head
                'dispatch_approved_at' => null,
                'dispatch_approved_by' => null,
                'status' => $lockedTrip->vehicle_check_passed ? TripStatus::VEHICLE_CHECKED : TripStatus::ASSIGNED,
            ]);

            ActivityLog::record(
                event: 'trip.loading_verified',
                auditable: $lockedTrip,
                actor: $actor,
                newValues: [
                    'loading_verified' => true,
                    'total_loaded_portions' => $totalLoadedTrip,
                    'verified_by' => $actor->name,
                ]
            );

            return $lockedTrip->load(['stops.deliveryItems', 'loadingVerifiedBy']);
        });
    }

    /**
     * Driver performs pre-trip vehicle and safety checklist.
     */
    public function checkVehicle(Trip $trip, array $data, User $driver): Trip
    {
        return DB::transaction(function () use ($trip, $data, $driver) {
            $lockedTrip = Trip::where('id', $trip->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Authorization: only the assigned driver
            if ((int) $lockedTrip->driver_id !== (int) $driver->id) {
                throw ValidationException::withMessages([
                    'authorization' => ['Anda bukan kurir yang ditugaskan untuk perjalanan ini.'],
                ]);
            }

            if (! in_array($lockedTrip->status, [TripStatus::ASSIGNED, TripStatus::VEHICLE_CHECKED], true)) {
                throw ValidationException::withMessages([
                    'status' => ["Pemeriksaan kendaraan tidak dapat dilakukan pada status perjalanan {$lockedTrip->status->label()}."],
                ]);
            }

            $passed = (bool) ($data['passed'] ?? false);
            $notes = $data['notes'] ?? null;

            $oldValues = [
                'vehicle_check_passed' => $lockedTrip->vehicle_check_passed,
                'status' => $lockedTrip->status->value,
            ];

            if ($passed) {
                $lockedTrip->update([
                    'vehicle_check_passed' => true,
                    'vehicle_checked_at' => Carbon::now(),
                    'vehicle_check_notes' => $notes,
                    'status' => ($lockedTrip->loading_verified && $lockedTrip->dispatch_approved) ? TripStatus::READY : TripStatus::VEHICLE_CHECKED,
                ]);
            } else {
                $lockedTrip->update([
                    'vehicle_check_passed' => false,
                    'vehicle_checked_at' => Carbon::now(),
                    'vehicle_check_notes' => $notes,
                    'dispatch_approved' => false, // Invalidate dispatch approval if vehicle check fails
                    'dispatch_approved_at' => null,
                    'dispatch_approved_by' => null,
                    'status' => TripStatus::ASSIGNED,
                ]);
            }

            ActivityLog::record(
                event: 'trip.vehicle_inspected',
                auditable: $lockedTrip,
                actor: $driver,
                oldValues: $oldValues,
                newValues: [
                    'vehicle_check_passed' => $lockedTrip->vehicle_check_passed,
                    'vehicle_check_notes' => $notes,
                    'status' => $lockedTrip->status->value,
                ]
            );

            return $lockedTrip;
        });
    }

    /**
     * Pre-departure Dispatch Authorization by Kepala SPPG.
     * HARD GATE B & CRITICAL GATE C:
     * Validates:
     * 1. All batches RELEASED
     * 2. Physical loading verified for ALL delivery items (status = LOADED, loaded_portions > 0)
     * 3. Vehicle check passed & vehicle is currently active
     * 4. Driver is currently active
     * 5. Stop sequences are valid and complete
     * 6. Self-approval prevention (Kepala SPPG cannot approve their own trip if acting as driver)
     */
    public function approveDispatch(Trip $trip, User $actor, ?string $notes = null): Trip
    {
        return DB::transaction(function () use ($trip, $actor, $notes) {
            $lockedTrip = Trip::where('id', $trip->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $lockedTrip->sppg_id !== (int) $actor->sppg_id) {
                throw ValidationException::withMessages([
                    'authorization' => ['Anda tidak berwenang mengesahkan perjalanan SPPG lain.'],
                ]);
            }

            // Self-approval prevention: Kepala SPPG cannot approve if assigned as driver
            if ((int) $lockedTrip->driver_id === (int) $actor->id) {
                throw ValidationException::withMessages([
                    'authorization' => ['Penyelenggara tidak dapat menyetujui keberangkatan perjalanan di mana dirinya bertindak sebagai kurir.'],
                ]);
            }

            $isPickupTrip = ($lockedTrip->trip_type === TripType::PICKUP || $lockedTrip->trip_type === 'PICKUP');

            if (! $isPickupTrip) {
                // Gate 1: Check all batches are RELEASED
                $batchIds = DeliveryItem::whereHas('tripStop', function ($q) use ($lockedTrip) {
                    $q->where('trip_id', $lockedTrip->id);
                })->pluck('food_batch_id')->unique();

                $unreleasedCount = FoodBatch::whereIn('id', $batchIds)
                    ->where('status', '!=', FoodBatchStatus::RELEASED)
                    ->count();

                if ($unreleasedCount > 0) {
                    throw ValidationException::withMessages([
                        'dispatch' => ['Terdapat batch makanan pada manifest perjalanan ini yang belum berstatus lolos QC (RELEASED).'],
                    ]);
                }

                // Gate 2: Check physical loading is verified and ALL items are LOADED
                if (! $lockedTrip->loading_verified) {
                    throw ValidationException::withMessages([
                        'dispatch' => ['Kuantitas muatan fisik belum diverifikasi oleh petugas muat (loading verification).'],
                    ]);
                }

                $unloadedItemsCount = DeliveryItem::whereHas('tripStop', function ($q) use ($lockedTrip) {
                    $q->where('trip_id', $lockedTrip->id);
                })->where(function ($q) {
                    $q->where('status', '!=', 'LOADED')
                      ->orWhereNull('loaded_portions')
                      ->orWhere('loaded_portions', '<=', 0);
                })->count();

                if ($unloadedItemsCount > 0) {
                    throw ValidationException::withMessages([
                        'dispatch' => ['Masih terdapat item alokasi muatan yang belum selesai diverifikasi secara fisik.'],
                    ]);
                }
            }

            // Gate 3: Check vehicle condition check passed & vehicle is active
            if (! $lockedTrip->vehicle_check_passed) {
                throw ValidationException::withMessages([
                    'dispatch' => ['Pemeriksaan kelaikan kendaraan belum dilakukan atau belum dinyatakan lolos oleh kurir.'],
                ]);
            }

            $vehicle = $lockedTrip->vehicle;
            if (! $vehicle || ! $vehicle->is_active) {
                throw ValidationException::withMessages([
                    'dispatch' => ['Kendaraan yang ditugaskan tidak aktif atau tidak layak operasional.'],
                ]);
            }

            $totalLoadedPortions = (int) DeliveryItem::whereHas('tripStop', function ($q) use ($lockedTrip) {
                $q->where('trip_id', $lockedTrip->id);
            })->sum('loaded_portions');

            if ($vehicle->capacity_portions && $totalLoadedPortions > $vehicle->capacity_portions) {
                throw ValidationException::withMessages([
                    'dispatch' => ["Total muatan fisik ({$totalLoadedPortions} porsi) melebihi kapasitas kendaraan {$vehicle->plate_number} ({$vehicle->capacity_portions} porsi)."],
                ]);
            }

            // Gate 4: Driver is active
            $driver = $lockedTrip->driver;
            if (! $driver || ! $driver->is_active) {
                throw ValidationException::withMessages([
                    'dispatch' => ['Kurir yang ditugaskan tidak aktif dalam sistem.'],
                ]);
            }

            // Gate 5: Check valid stop sequences
            $stopCount = $lockedTrip->stops()->count();
            $sequences = $lockedTrip->stops()->pluck('sequence_number')->sort()->values()->all();
            if ($sequences !== range(1, $stopCount)) {
                throw ValidationException::withMessages([
                    'dispatch' => ['Urutan pemberhentian sekolah tidak valid atau terputus.'],
                ]);
            }

            $lockedTrip->update([
                'dispatch_approved' => true,
                'dispatch_approved_at' => Carbon::now(),
                'dispatch_approved_by' => $actor->id,
                'dispatch_notes' => $notes,
                'status' => TripStatus::READY,
            ]);

            // Ensure route geometry is cached
            try {
                $this->routingService->calculateTripRoute($lockedTrip);
            } catch (\Throwable $e) {
                // Ignore route failure; dispatch approval remains valid
            }

            ActivityLog::record(
                event: 'trip.dispatch_approved',
                auditable: $lockedTrip,
                actor: $actor,
                newValues: [
                    'dispatch_approved' => true,
                    'approved_by' => $actor->name,
                    'status' => TripStatus::READY->value,
                ]
            );

            return $lockedTrip->load(['dispatchApprovedBy', 'stops.school']);
        });
    }

    /**
     * Driver starts trip dispatch from kitchen to first school (Swipe-to-depart).
     * Transitions: READY -> IN_PROGRESS. First stop becomes EN_ROUTE.
     * HARD GATE E: tracking_status becomes WAITING_FOR_LOCATION (NOT ACTIVE, no fake GPS!).
     * CRITICAL GATE C: Re-evaluates all critical gate invariants atomically inside the transaction.
     */
    public function startTrip(Trip $trip, User $driver): Trip
    {
        return DB::transaction(function () use ($trip, $driver) {
            $lockedTrip = Trip::where('id', $trip->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Authorization
            if ((int) $lockedTrip->driver_id !== (int) $driver->id) {
                throw ValidationException::withMessages([
                    'authorization' => ['Anda bukan kurir yang ditugaskan untuk perjalanan ini.'],
                ]);
            }

            // Precondition: Must be READY
            if ($lockedTrip->status !== TripStatus::READY) {
                throw ValidationException::withMessages([
                    'status' => ["Perjalanan tidak dapat dimulai. Status saat ini: {$lockedTrip->status->label()}. Pastikan seluruh gerbang Dispatch Gate (QC, muatan, armada, dan otorisasi Kepala SPPG) telah terpenuhi."],
                ]);
            }

            $isPickupTrip = ($lockedTrip->trip_type === TripType::PICKUP || $lockedTrip->trip_type === 'PICKUP');

            if (! $isPickupTrip) {
                // Re-verify Dispatch Gates atomically
                if (! $lockedTrip->loading_verified) {
                    throw ValidationException::withMessages([
                        'loading_verified' => ['Kuantitas muatan belum diverifikasi secara resmi.'],
                    ]);
                }

                $unloadedItemsCount = DeliveryItem::whereHas('tripStop', function ($q) use ($lockedTrip) {
                    $q->where('trip_id', $lockedTrip->id);
                })->where(function ($q) {
                    $q->where('status', '!=', 'LOADED')
                      ->orWhereNull('loaded_portions')
                      ->orWhere('loaded_portions', '<=', 0);
                })->count();

                if ($unloadedItemsCount > 0) {
                    throw ValidationException::withMessages([
                        'loading_verified' => ['Terdapat item manifest muatan yang belum selesai diverifikasi.'],
                    ]);
                }
            }

            if (! $lockedTrip->vehicle_check_passed) {
                throw ValidationException::withMessages([
                    'vehicle_check' => ['Pemeriksaan kendaraan belum dinyatakan lolos memenuhi standar keselamatan.'],
                ]);
            }

            if (! $lockedTrip->vehicle || ! $lockedTrip->vehicle->is_active) {
                throw ValidationException::withMessages([
                    'vehicle' => ['Armada kendaraan tidak aktif atau ditarik dari operasional.'],
                ]);
            }

            if (! $driver->is_active) {
                throw ValidationException::withMessages([
                    'driver' => ['Akun kurir berstatus nonaktif dalam sistem.'],
                ]);
            }

            $vehicle = $lockedTrip->vehicle;
            $totalLoadedPortions = (int) DeliveryItem::whereHas('tripStop', function ($q) use ($lockedTrip) {
                $q->where('trip_id', $lockedTrip->id);
            })->sum('loaded_portions');

            if ($vehicle && $vehicle->capacity_portions && $totalLoadedPortions > $vehicle->capacity_portions) {
                throw ValidationException::withMessages([
                    'capacity' => ["Total muatan fisik ({$totalLoadedPortions} porsi) melebihi kapasitas armada {$vehicle->plate_number} ({$vehicle->capacity_portions} porsi)."],
                ]);
            }

            if (! $isPickupTrip) {
                // Re-verify batches are still RELEASED
                $batchIds = DeliveryItem::whereHas('tripStop', function ($q) use ($lockedTrip) {
                    $q->where('trip_id', $lockedTrip->id);
                })->pluck('food_batch_id')->unique();

                $unreleasedBatches = FoodBatch::whereIn('id', $batchIds)
                    ->where('status', '!=', FoodBatchStatus::RELEASED)
                    ->count();

                if ($unreleasedBatches > 0) {
                    throw ValidationException::withMessages([
                        'batch_status' => ['Terdapat batch makanan yang status rilisnya dibatalkan atau ditahan (HOLD).'],
                    ]);
                }
            }

            if (! $lockedTrip->dispatch_approved) {
                throw ValidationException::withMessages([
                    'dispatch_approved' => ['Otorisasi keberangkatan dari Kepala SPPG belum diterbitkan.'],
                ]);
            }

            $lockedTrip->update([
                'status' => TripStatus::IN_PROGRESS,
                'started_at' => Carbon::now(),
                'tracking_status' => 'WAITING_FOR_LOCATION', // HARD GATE E: NOT ACTIVE until real telemetry in PBI-09
                'tracking_started_at' => null,
            ]);

            // Transition first stop from PENDING to EN_ROUTE
            $firstStop = TripStop::where('trip_id', $lockedTrip->id)
                ->where('sequence_number', 1)
                ->first();

            if ($firstStop && $firstStop->status === TripStopStatus::PENDING) {
                $firstStop->update([
                    'status' => TripStopStatus::EN_ROUTE,
                ]);
            }

            ActivityLog::record(
                event: 'trip.started',
                auditable: $lockedTrip,
                actor: $driver,
                oldValues: ['status' => TripStatus::READY->value],
                newValues: [
                    'status' => TripStatus::IN_PROGRESS->value,
                    'started_at' => $lockedTrip->started_at->toIso8601String(),
                    'tracking_status' => 'WAITING_FOR_LOCATION',
                ]
            );

            return $lockedTrip;
        });
    }

    /**
     * Cancel an unstarted trip (DRAFT, ASSIGNED, VEHICLE_CHECKED, READY).
     * Re-releases delivery items back to unassigned pool.
     */
    public function cancelTrip(Trip $trip, string $reason, User $actor): Trip
    {
        return DB::transaction(function () use ($trip, $reason, $actor) {
            $lockedTrip = Trip::where('id', $trip->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $lockedTrip->sppg_id !== (int) $actor->sppg_id) {
                throw ValidationException::withMessages([
                    'authorization' => ['Anda tidak berwenang membatalkan perjalanan SPPG lain.'],
                ]);
            }

            if (in_array($lockedTrip->status, [TripStatus::IN_PROGRESS, TripStatus::COMPLETED], true)) {
                throw ValidationException::withMessages([
                    'status' => ["Perjalanan berstatus {$lockedTrip->status->label()} tidak dapat dibatalkan secara sepihak."],
                ]);
            }

            $oldStatus = $lockedTrip->status->value;

            // Re-release delivery items
            $stopIds = TripStop::where('trip_id', $lockedTrip->id)->pluck('id');
            DeliveryItem::whereIn('trip_stop_id', $stopIds)->update([
                'trip_stop_id' => null,
                'loaded_portions' => null,
                'status' => 'RELEASED',
            ]);

            // Set stops SKIPPED
            TripStop::where('trip_id', $lockedTrip->id)->update([
                'status' => TripStopStatus::SKIPPED,
            ]);

            $lockedTrip->update([
                'status' => TripStatus::CANCELLED,
            ]);

            ActivityLog::record(
                event: 'trip.cancelled',
                auditable: $lockedTrip,
                actor: $actor,
                oldValues: ['status' => $oldStatus],
                newValues: [
                    'status' => TripStatus::CANCELLED->value,
                    'reason' => $reason,
                ]
            );

            return $lockedTrip;
        });
    }
}
