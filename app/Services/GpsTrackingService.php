<?php

namespace App\Services;

use App\Enums\TripStatus;
use App\Enums\TripStopStatus;
use App\Models\ActivityLog;
use App\Models\GpsLocation;
use App\Models\Trip;
use App\Models\TripStop;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GpsTrackingService
{
    /**
     * Record authenticated driver's real GPS telemetry point during active trip.
     * Enforces tenant check, driver assignment, trip in-progress state, coordinate validity, and time skew check.
     */
    public function recordDriverLocation(Trip $trip, User $driver, array $data): GpsLocation
    {
        return DB::transaction(function () use ($trip, $driver, $data) {
            $lockedTrip = Trip::where('id', $trip->id)
                ->lockForUpdate()
                ->firstOrFail();

            // 1. Authorization: Only the assigned driver can submit telemetry
            if ((int) $lockedTrip->driver_id !== (int) $driver->id) {
                throw ValidationException::withMessages([
                    'driver' => ['Anda bukan kurir yang ditugaskan untuk perjalanan ini.'],
                ]);
            }

            // Tenant isolation
            if ((int) $lockedTrip->sppg_id !== (int) $driver->sppg_id) {
                throw ValidationException::withMessages([
                    'tenant' => ['Kurir berada di luar lingkup unit SPPG perjalanan ini.'],
                ]);
            }

            // 2. Precondition: Trip must be actively IN_PROGRESS
            if ($lockedTrip->status !== TripStatus::IN_PROGRESS) {
                throw ValidationException::withMessages([
                    'status' => [
                        "Telemetri lokasi GPS hanya dapat dicatat saat perjalanan berstatus AKTIF (IN_PROGRESS). Status saat ini: {$lockedTrip->status->label()}.",
                    ],
                ]);
            }

            // 3. Coordinate validation
            $lat = filter_var($data['latitude'] ?? null, FILTER_VALIDATE_FLOAT);
            $lng = filter_var($data['longitude'] ?? null, FILTER_VALIDATE_FLOAT);

            if ($lat === false || $lng === false) {
                throw ValidationException::withMessages([
                    'coordinates' => ['Format koordinat latitude/longitude tidak valid.'],
                ]);
            }

            if ($lat < -90.0 || $lat > 90.0 || $lng < -180.0 || $lng > 180.0) {
                throw ValidationException::withMessages([
                    'coordinates' => ['Koordinat GPS berada di luar rentang geografis bumi yang sah.'],
                ]);
            }

            // Reject placeholder coordinates (0,0)
            if (abs($lat) < 0.0001 && abs($lng) < 0.0001) {
                throw ValidationException::withMessages([
                    'coordinates' => ['Koordinat bernilai nol (0,0). Diperlukan pembacaan GPS riil perangkat.'],
                ]);
            }

            // 4. Time skew validation
            $now = Carbon::now();
            $recordedAt = isset($data['recorded_at'])
                ? Carbon::parse($data['recorded_at'])
                : $now;

            if ($recordedAt->gt($now->copy()->addMinutes(5))) {
                throw ValidationException::withMessages([
                    'recorded_at' => ['Timestamp GPS berada di masa depan melebihi toleransi waktu sinkronisasi (5 menit).'],
                ]);
            }

            if ($recordedAt->lt($now->copy()->subHours(24))) {
                throw ValidationException::withMessages([
                    'recorded_at' => ['Timestamp GPS kedaluwarsa lebih dari 24 jam.'],
                ]);
            }

            // 5. Accuracy & speed sanitization
            $accuracy = isset($data['accuracy']) ? filter_var($data['accuracy'], FILTER_VALIDATE_FLOAT) : null;
            $speed = isset($data['speed']) ? filter_var($data['speed'], FILTER_VALIDATE_FLOAT) : null;
            $heading = isset($data['heading']) ? filter_var($data['heading'], FILTER_VALIDATE_FLOAT) : null;

            // Create record
            $gpsLocation = GpsLocation::create([
                'trip_id' => $lockedTrip->id,
                'driver_id' => $driver->id,
                'latitude' => $lat,
                'longitude' => $lng,
                'accuracy_m' => $accuracy !== false ? $accuracy : null,
                'speed_mps' => $speed !== false ? $speed : null,
                'heading_deg' => $heading !== false ? $heading : null,
                'recorded_at' => $recordedAt,
                'received_at' => $now,
            ]);

            // Update trip cached latest telemetry
            $lockedTrip->update([
                'last_latitude' => $lat,
                'last_longitude' => $lng,
                'last_accuracy_m' => $accuracy !== false ? $accuracy : null,
                'last_location_at' => $recordedAt,
                'tracking_status' => 'ONLINE',
                'tracking_started_at' => $lockedTrip->tracking_started_at ?: $now,
            ]);

            return $gpsLocation;
        });
    }

    /**
     * Compute honest real tracking status, staleness, and uncertainty for a trip.
     * Prevents fake live animation when timestamp is outdated or offline.
     *
     * @return array{
     *     status: string,
     *     label: string,
     *     badge_class: string,
     *     latitude: float|null,
     *     longitude: float|null,
     *     accuracy_m: float|null,
     *     last_location_at: string|null,
     *     age_seconds: int|null,
     *     age_human: string|null,
     *     is_online: bool,
     *     is_stale: bool,
     *     is_offline: bool,
     *     is_waiting: bool
     * }
     */
    public function getDriverTrackingStatus(Trip $trip): array
    {
        if ($trip->status === TripStatus::COMPLETED) {
            return [
                'status' => 'COMPLETED',
                'label' => 'Perjalanan Selesai',
                'badge_class' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                'latitude' => $trip->last_latitude,
                'longitude' => $trip->last_longitude,
                'accuracy_m' => $trip->last_accuracy_m,
                'last_location_at' => $trip->last_location_at?->toIso8601String(),
                'age_seconds' => null,
                'age_human' => 'Pengantaran Selesai',
                'is_online' => false,
                'is_stale' => false,
                'is_offline' => false,
                'is_waiting' => false,
            ];
        }

        if ($trip->status !== TripStatus::IN_PROGRESS || ! $trip->last_location_at) {
            return [
                'status' => 'WAITING_FOR_LOCATION',
                'label' => 'Menunggu Sinyal GPS Kurir',
                'badge_class' => 'bg-amber-50 text-amber-800 border-amber-200',
                'latitude' => null,
                'longitude' => null,
                'accuracy_m' => null,
                'last_location_at' => null,
                'age_seconds' => null,
                'age_human' => 'Belum ada telemetri lokasi',
                'is_online' => false,
                'is_stale' => false,
                'is_offline' => false,
                'is_waiting' => true,
            ];
        }

        $lastLocation = $trip->last_location_at instanceof Carbon
            ? $trip->last_location_at
            : Carbon::parse($trip->last_location_at);

        $ageSeconds = max(0, Carbon::now()->getTimestamp() - $lastLocation->getTimestamp());

        if ($ageSeconds <= 120) {
            $status = 'ONLINE';
            $label = 'Terhubung (GPS Aktif)';
            $badgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
            $isOnline = true;
            $isStale = false;
            $isOffline = false;
        } elseif ($ageSeconds <= 600) {
            $status = 'STALE';
            $label = 'Sinyal Tidak Bergerak (Stale)';
            $badgeClass = 'bg-yellow-50 text-yellow-800 border-yellow-200';
            $isOnline = false;
            $isStale = true;
            $isOffline = false;
        } else {
            $status = 'OFFLINE';
            $label = 'GPS Terputus (Offline)';
            $badgeClass = 'bg-slate-100 text-slate-700 border-slate-300';
            $isOnline = false;
            $isStale = false;
            $isOffline = true;
        }

        return [
            'status' => $status,
            'label' => $label,
            'badge_class' => $badgeClass,
            'latitude' => (float) $trip->last_latitude,
            'longitude' => (float) $trip->last_longitude,
            'accuracy_m' => $trip->last_accuracy_m ? (float) $trip->last_accuracy_m : null,
            'last_location_at' => $trip->last_location_at->toIso8601String(),
            'age_seconds' => $ageSeconds,
            'age_human' => $trip->last_location_at->diffForHumans(),
            'is_online' => $isOnline,
            'is_stale' => $isStale,
            'is_offline' => $isOffline,
            'is_waiting' => false,
        ];
    }

    /**
     * Driver arrives at a trip stop.
     */
    public function markStopArrived(Trip $trip, TripStop $stop, User $driver): TripStop
    {
        return DB::transaction(function () use ($trip, $stop, $driver) {
            $lockedStop = TripStop::where('id', $stop->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $lockedStop->trip_id !== (int) $trip->id) {
                throw ValidationException::withMessages([
                    'stop' => ['Pemberhentian tidak terdaftar dalam perjalanan ini.'],
                ]);
            }

            if ((int) $trip->driver_id !== (int) $driver->id) {
                throw ValidationException::withMessages([
                    'driver' => ['Anda bukan kurir yang ditugaskan untuk perjalanan ini.'],
                ]);
            }

            if ($trip->status !== TripStatus::IN_PROGRESS) {
                throw ValidationException::withMessages([
                    'status' => ['Kedatangan hanya dapat ditandai pada perjalanan yang sedang berlangsung.'],
                ]);
            }

            if (in_array($lockedStop->status, [TripStopStatus::ARRIVED, TripStopStatus::ARRIVED_AWAITING_CONFIRMATION, TripStopStatus::HANDED_OVER, TripStopStatus::COMPLETED], true)) {
                return $lockedStop; // Idempotent
            }

            $lockedStop->update([
                'status' => TripStopStatus::ARRIVED_AWAITING_CONFIRMATION,
                'arrived_at' => Carbon::now(),
            ]);

            ActivityLog::record(
                event: 'trip_stop.arrived',
                auditable: $lockedStop,
                actor: $driver,
                newValues: [
                    'status' => TripStopStatus::ARRIVED_AWAITING_CONFIRMATION->value,
                    'arrived_at' => Carbon::now()->toIso8601String(),
                    'school_id' => $lockedStop->school_id,
                ]
            );

            return $lockedStop;
        });
    }
}
