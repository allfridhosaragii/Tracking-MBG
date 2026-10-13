<?php

namespace App\Services;

use App\Enums\FoodBatchStatus;
use App\Enums\TripStatus;
use App\Enums\TripStopStatus;
use App\Enums\TripType;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\DeliveryItem;
use App\Models\FoodBatch;
use App\Models\School;
use App\Models\Trip;
use App\Models\TripStop;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TripDispatchService
{
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

        // 2. Available delivery items (ready to load, not yet assigned to a trip stop)
        $unassignedDeliveryItems = DeliveryItem::whereHas('foodBatch.productionPlan', function ($query) use ($sppgId, $date) {
            $query->where('sppg_id', $sppgId)
                ->whereDate('service_date', $date);
        })
            ->whereHas('foodBatch', function ($query) {
                $query->where('status', FoodBatchStatus::RELEASED);
            })
            ->whereNull('trip_stop_id')
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
            ->with(['driver', 'vehicle', 'stops.school', 'stops.deliveryItems.foodBatch'])
            ->latest()
            ->get();

        // 6. Schools for this SPPG
        $schools = School::where('sppg_id', $sppgId)
            ->where('is_active', true)
            ->get();

        return [
            'service_date' => $date,
            'released_batches' => $releasedBatches,
            'unassigned_delivery_items' => $unassignedDeliveryItems,
            'vehicles' => $vehicles,
            'drivers' => $drivers,
            'trips' => $trips,
            'schools' => $schools,
        ];
    }

    /**
     * Create a new trip with scheduled stops and linked delivery items.
     * Enforces:
     * 1. Actor must be KEPALA_SPPG for their SPPG.
     * 2. Active vehicle without schedule conflicts.
     * 3. Active driver (KURIR) without assignment conflicts.
     * 4. Multi-school unique stops in defined sequence.
     * 5. Delivery items must belong to RELEASED batches and correct schools.
     * 6. Loaded quantity <= QC accepted available portions.
     * 7. Total loaded <= vehicle capacity (if defined).
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

            // 3. Validate Stops Structure
            $stopsData = $data['stops'] ?? [];
            if (empty($stopsData) || ! is_array($stopsData)) {
                throw ValidationException::withMessages([
                    'stops' => ['Perjalanan harus memiliki minimal satu tujuan sekolah.'],
                ]);
            }

            $schoolIdsSeen = [];
            $totalLoadedTrip = 0;
            $validatedStops = [];

            foreach ($stopsData as $index => $stopInput) {
                $schoolId = (int) ($stopInput['school_id'] ?? 0);
                $sequence = (int) ($stopInput['sequence_number'] ?? ($index + 1));

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

                // Validate Delivery Items for this stop
                $itemsInput = $stopInput['items'] ?? [];
                if (empty($itemsInput) || ! is_array($itemsInput)) {
                    throw ValidationException::withMessages([
                        "stops.{$index}.items" => ["Pemberhentian sekolah {$school->name} harus memiliki minimal satu alokasi makanan."],
                    ]);
                }

                $validatedItems = [];
                foreach ($itemsInput as $itemIdx => $itemData) {
                    $deliveryItemId = (int) ($itemData['delivery_item_id'] ?? 0);
                    $loadedPortions = (int) ($itemData['loaded_portions'] ?? 0);

                    if ($loadedPortions <= 0) {
                        throw ValidationException::withMessages([
                            "stops.{$index}.items.{$itemIdx}.loaded_portions" => ['Jumlah porsi yang dimuat harus lebih dari nol.'],
                        ]);
                    }

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

                    // Quantity check: loaded <= released_portions (or accepted_portions)
                    $availablePortions = $deliveryItem->released_portions ?? $deliveryItem->planned_portions;
                    if ($loadedPortions > $availablePortions) {
                        throw ValidationException::withMessages([
                            "stops.{$index}.items.{$itemIdx}.loaded_portions" => [
                                "Jumlah muatan ({$loadedPortions}) melebihi kuantitas yang disetujui QC ({$availablePortions} porsi) untuk sekolah ini.",
                            ],
                        ]);
                    }

                    $totalLoadedTrip += $loadedPortions;

                    $validatedItems[] = [
                        'delivery_item' => $deliveryItem,
                        'loaded_portions' => $loadedPortions,
                    ];
                }

                $validatedStops[] = [
                    'school_id' => $schoolId,
                    'sequence_number' => $sequence,
                    'stop_type' => $stopInput['stop_type'] ?? 'DELIVERY',
                    'planned_arrival_at' => $stopInput['planned_arrival_at'] ?? null,
                    'items' => $validatedItems,
                ];
            }

            // 4. Vehicle Capacity Validation
            if ($vehicle->capacity_portions && $totalLoadedTrip > $vehicle->capacity_portions) {
                throw ValidationException::withMessages([
                    'vehicle_id' => [
                        "Total muatan ({$totalLoadedTrip} porsi) melampaui kapasitas armada {$vehicle->plate_number} ({$vehicle->capacity_portions} porsi).",
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

            // 6. Create Trip Record
            $trip = Trip::create([
                'sppg_id' => $sppgId,
                'driver_id' => $driver->id,
                'vehicle_id' => $vehicle->id,
                'trip_code' => $tripCode,
                'trip_type' => $data['trip_type'] ?? TripType::DELIVERY->value,
                'service_date' => $serviceDate,
                'planned_departure_at' => $data['planned_departure_at'] ?? null,
                'status' => TripStatus::ASSIGNED,
                'created_by' => $actor->id,
            ]);

            // 7. Create Stops & Link Delivery Items
            foreach ($validatedStops as $stopData) {
                $tripStop = TripStop::create([
                    'trip_id' => $trip->id,
                    'school_id' => $stopData['school_id'],
                    'stop_type' => $stopData['stop_type'],
                    'sequence_number' => $stopData['sequence_number'],
                    'planned_arrival_at' => $stopData['planned_arrival_at'],
                    'status' => TripStopStatus::PENDING,
                ]);

                foreach ($stopData['items'] as $itemData) {
                    /** @var DeliveryItem $item */
                    $item = $itemData['delivery_item'];
                    $item->update([
                        'trip_stop_id' => $tripStop->id,
                        'loaded_portions' => $itemData['loaded_portions'],
                        'status' => 'LOADED',
                    ]);
                }
            }

            // 8. Record Activity Audit Trail
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
                    'total_loaded_portions' => $totalLoadedTrip,
                    'status' => TripStatus::ASSIGNED->value,
                ]
            );

            return $trip->load(['stops.school', 'stops.deliveryItems.foodBatch', 'driver', 'vehicle']);
        });
    }

    /**
     * Driver performs pre-trip vehicle and safety checklist.
     * Transitions: ASSIGNED -> VEHICLE_CHECKED -> READY (if passed).
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
                    'status' => TripStatus::READY,
                ]);
            } else {
                $lockedTrip->update([
                    'vehicle_check_passed' => false,
                    'vehicle_checked_at' => Carbon::now(),
                    'vehicle_check_notes' => $notes,
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
     * Driver starts trip dispatch from kitchen to first school.
     * Transitions: READY -> IN_PROGRESS. First stop becomes EN_ROUTE.
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
                    'status' => ["Perjalanan tidak dapat dimulai. Status saat ini: {$lockedTrip->status->label()}. Pastikan checklist kendaraan telah lolos pemeriksaan."],
                ]);
            }

            if (! $lockedTrip->vehicle_check_passed) {
                throw ValidationException::withMessages([
                    'vehicle_check' => ['Pemeriksaan kendaraan belum dinyatakan lolos memenuhi standar keselamatan.'],
                ]);
            }

            $lockedTrip->update([
                'status' => TripStatus::IN_PROGRESS,
                'started_at' => Carbon::now(),
                'tracking_status' => 'ACTIVE',
                'tracking_started_at' => Carbon::now(),
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
