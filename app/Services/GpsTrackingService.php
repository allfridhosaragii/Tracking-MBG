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
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GpsTrackingService
{
    /**
     * Start a new authenticated driver GPS tracking session with server-authoritative session UUID (Gate A).
     * Enforces tenant check, driver assignment, and in-progress status.
     * Generates a cryptographically secure UUID session identifier, sets tracking_status to ONLINE,
     * and logs the session creation for audit integrity.
     *
     * @return array{session_id: string, started_at: string, tracking_status: string}
     */
    public function startDriverTracking(Trip $trip, User $driver): array
    {
        return DB::transaction(function () use ($trip, $driver) {
            $lockedTrip = Trip::where('id', $trip->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $lockedTrip->driver_id !== (int) $driver->id) {
                throw ValidationException::withMessages([
                    'driver' => ['Anda bukan kurir yang ditugaskan untuk perjalanan ini.'],
                ]);
            }

            if ((int) $lockedTrip->sppg_id !== (int) $driver->sppg_id) {
                throw ValidationException::withMessages([
                    'tenant' => ['Kurir berada di luar lingkup unit SPPG perjalanan ini.'],
                ]);
            }

            if ($lockedTrip->status !== TripStatus::IN_PROGRESS) {
                throw ValidationException::withMessages([
                    'status' => [
                        "Pelacakan GPS hanya dapat dimulai saat perjalanan berstatus AKTIF (IN_PROGRESS). Status saat ini: {$lockedTrip->status->label()}.",
                    ],
                ]);
            }

            $sessionId = (string) Str::uuid();
            $now = Carbon::now();

            $lockedTrip->update([
                'active_tracking_session_id' => $sessionId,
                'tracking_status' => 'ONLINE',
                'tracking_started_at' => $now,
                'tracking_ended_at' => null,
            ]);

            ActivityLog::record(
                event: 'trip.tracking_started',
                auditable: $lockedTrip,
                actor: $driver,
                newValues: [
                    'tracking_status' => 'ONLINE',
                    'session_id' => $sessionId,
                    'started_at' => $now->toIso8601String(),
                ]
            );

            return [
                'session_id' => $sessionId,
                'started_at' => $now->toIso8601String(),
                'tracking_status' => 'ONLINE',
            ];
        });
    }

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

            // 4. Time skew validation (Gate B)
            $now = Carbon::now();
            $recordedAt = isset($data['recorded_at'])
                ? Carbon::parse($data['recorded_at'])
                : $now;

            // Strict future timestamp rejection (within max 1 minute clock drift)
            if ($recordedAt->gt($now->copy()->addMinute())) {
                throw ValidationException::withMessages([
                    'recorded_at' => ['Timestamp GPS berada di masa depan melebihi toleransi sinkronisasi (1 menit).'],
                ]);
            }

            if ($recordedAt->lt($now->copy()->subHours(24))) {
                throw ValidationException::withMessages([
                    'recorded_at' => ['Timestamp GPS kedaluwarsa lebih dari 24 jam.'],
                ]);
            }

            // 5. Accuracy, speed, and heading sanitization & range enforcement (Gate C)
            $accuracy = null;
            if (isset($data['accuracy'])) {
                $val = filter_var($data['accuracy'], FILTER_VALIDATE_FLOAT);
                if ($val === false || $val < 0) {
                    throw ValidationException::withMessages([
                        'accuracy' => ['Nilai akurasi GPS tidak boleh negatif.'],
                    ]);
                }
                $accuracy = $val;
            }

            $speed = null;
            if (isset($data['speed'])) {
                $val = filter_var($data['speed'], FILTER_VALIDATE_FLOAT);
                if ($val === false || $val < 0) {
                    throw ValidationException::withMessages([
                        'speed' => ['Kecepatan armada tidak boleh bernilai negatif.'],
                    ]);
                }
                $speed = $val;
            }

            $heading = null;
            if (isset($data['heading']) && $data['heading'] !== null) {
                $val = filter_var($data['heading'], FILTER_VALIDATE_FLOAT);
                if ($val === false || $val < 0 || $val > 360) {
                    throw ValidationException::withMessages([
                        'heading' => ['Sudut arah (heading) harus berada dalam rentang 0 hingga 360 derajat.'],
                    ]);
                }
                $heading = $val;
            }

            // 6. Duplicate detection (Gate C)
            $existing = GpsLocation::where('trip_id', $lockedTrip->id)
                ->where('recorded_at', $recordedAt)
                ->where('latitude', $lat)
                ->where('longitude', $lng)
                ->first();
            if ($existing) {
                return $existing;
            }

            // 7. Telemetry outlier detection (Gate C)
            if ($lockedTrip->last_latitude !== null && $lockedTrip->last_longitude !== null && $lockedTrip->last_location_at !== null) {
                $distanceMeters = $this->calculateDistanceMeters(
                    (float) $lockedTrip->last_latitude,
                    (float) $lockedTrip->last_longitude,
                    $lat,
                    $lng
                );
                $elapsedSeconds = abs($recordedAt->diffInSeconds($lockedTrip->last_location_at));

                // If distance jump is > 500m and implied speed exceeds physical limits (> 60 m/s = ~216 km/h)
                if ($distanceMeters > 500 && ($elapsedSeconds === 0 || ($distanceMeters / max(1, $elapsedSeconds)) > 60)) {
                    throw ValidationException::withMessages([
                        'coordinates' => ['Lompatan koordinat GPS tidak wajar (outlier) terdeteksi terhadap waktu tempuh sebelumnya.'],
                    ]);
                }
            }

            // 8. Create historical telemetry record (always persisted for audit trail)
            $sessionId = isset($data['session_id']) && $data['session_id'] !== '' ? (string) $data['session_id'] : null;

            $gpsLocation = GpsLocation::create([
                'trip_id' => $lockedTrip->id,
                'driver_id' => $driver->id,
                'latitude' => $lat,
                'longitude' => $lng,
                'accuracy_m' => $accuracy,
                'speed_mps' => $speed,
                'heading_deg' => $heading,
                'recorded_at' => $recordedAt,
                'received_at' => $now,
                'session_id' => $sessionId,
            ]);

            // 9. Chronological ordering & Server-Authoritative Session Enforcement (Gate B & C)
            // Session integrity check:
            // Telemetry can only update active trip location and set status to ONLINE if:
            // (a) If active_tracking_session_id is set on trip, the telemetry session_id MUST match it.
            // (b) If active_tracking_session_id is NULL, old telemetry from a closed session must NOT revive ONLINE status.
            // (c) The trip is not explicitly stopped (tracking_status != 'SHARING_STOPPED' and (tracking_ended_at is null or recorded_at > tracking_ended_at)).
            // (d) The telemetry recorded_at is newer than the trip's last_location_at.
            $isMatchingSession = false;
            if ($lockedTrip->active_tracking_session_id !== null) {
                $isMatchingSession = ($sessionId !== null && hash_equals($lockedTrip->active_tracking_session_id, $sessionId));
            } else {
                $isMatchingSession = ($lockedTrip->tracking_status !== 'SHARING_STOPPED' && $lockedTrip->tracking_ended_at === null);
            }

            $isNewer = $lockedTrip->last_location_at === null || $recordedAt->gt($lockedTrip->last_location_at);
            $isNotStopped = $lockedTrip->tracking_status !== 'SHARING_STOPPED' && ($lockedTrip->tracking_ended_at === null || $recordedAt->gt($lockedTrip->tracking_ended_at));

            if ($isMatchingSession && $isNotStopped && $isNewer) {
                $lockedTrip->update([
                    'last_latitude' => $lat,
                    'last_longitude' => $lng,
                    'last_accuracy_m' => $accuracy,
                    'last_location_at' => $recordedAt,
                    'tracking_status' => 'ONLINE',
                    'tracking_started_at' => $lockedTrip->tracking_started_at ?: $now,
                    'tracking_ended_at' => null,
                ]);
            } elseif ($isMatchingSession && $isNewer) {
                // Update coordinates for audit trail, but maintain SHARING_STOPPED status
                $lockedTrip->update([
                    'last_latitude' => $lat,
                    'last_longitude' => $lng,
                    'last_accuracy_m' => $accuracy,
                    'last_location_at' => $recordedAt,
                ]);
            }

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
                'is_stopped' => false,
                'is_waiting' => false,
            ];
        }

        if ($trip->tracking_status === 'SHARING_STOPPED' || $trip->tracking_ended_at !== null) {
            $ageSeconds = $trip->last_location_at
                ? max(0, Carbon::now()->getTimestamp() - ($trip->last_location_at instanceof Carbon ? $trip->last_location_at->getTimestamp() : Carbon::parse($trip->last_location_at)->getTimestamp()))
                : null;

            return [
                'status' => 'SHARING_STOPPED',
                'label' => 'Berbagi Lokasi Dihentikan oleh Kurir',
                'badge_class' => 'bg-slate-100 text-slate-700 border-slate-300',
                'latitude' => $trip->last_latitude ? (float) $trip->last_latitude : null,
                'longitude' => $trip->last_longitude ? (float) $trip->last_longitude : null,
                'accuracy_m' => $trip->last_accuracy_m ? (float) $trip->last_accuracy_m : null,
                'last_location_at' => $trip->last_location_at?->toIso8601String(),
                'age_seconds' => $ageSeconds,
                'age_human' => $trip->last_location_at ? Carbon::parse($trip->last_location_at)->diffForHumans() : 'Lokasi tersimpan',
                'is_online' => false,
                'is_stale' => false,
                'is_offline' => false,
                'is_stopped' => true,
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
     * Stop driver GPS telemetry sharing explicitly (Gate C).
     * Synchronizes stopped tracking state to server while preserving last known location.
     * Enforces server-authoritative session identity check and race condition guards:
     * - If sessionId does not match active_tracking_session_id, the stale stop request is ignored.
     * - If clientStoppedAt is malformed, in the distant past/future, or null, it is safely normalized.
     * - Clears active_tracking_session_id to null upon valid stop.
     */
    public function stopDriverTracking(Trip $trip, User $driver, ?string $clientStoppedAt = null, string|int|null $sessionId = null): Trip
    {
        return DB::transaction(function () use ($trip, $driver, $clientStoppedAt, $sessionId) {
            $lockedTrip = Trip::where('id', $trip->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $lockedTrip->driver_id !== (int) $driver->id) {
                throw ValidationException::withMessages([
                    'driver' => ['Anda bukan kurir yang ditugaskan untuk perjalanan ini.'],
                ]);
            }

            if ($lockedTrip->status !== TripStatus::IN_PROGRESS) {
                throw ValidationException::withMessages([
                    'status' => ['Penghentian pelacakan hanya dapat dilakukan pada perjalanan yang sedang berlangsung.'],
                ]);
            }

            $now = Carbon::now();
            $stoppedAt = $now;

            // Scenario F: Safe parsing of clientStoppedAt (handle null, malformed, future, or past bounds without HTTP 500)
            if (!empty($clientStoppedAt)) {
                try {
                    $parsed = Carbon::parse($clientStoppedAt);
                    if ($parsed->gt($now->copy()->addMinute())) {
                        $stoppedAt = $now;
                    } elseif ($parsed->lt($now->copy()->subHours(24))) {
                        $stoppedAt = $now;
                    } else {
                        $stoppedAt = $parsed;
                    }
                } catch (\Throwable $e) {
                    $stoppedAt = $now;
                }
            }

            $sessionIdStr = $sessionId !== null ? (string) $sessionId : null;

            // Session identity validation (Scenario A & D)
            if ($lockedTrip->active_tracking_session_id !== null) {
                // If a stop request arrives without a sessionId or does not match active server session,
                // it is an out-of-order request from an older session or another tab -> ignore
                if ($sessionIdStr === null || !hash_equals($lockedTrip->active_tracking_session_id, $sessionIdStr)) {
                    return $lockedTrip;
                }
            } else {
                // Fallback timestamp check for legacy calls without session ID
                if ($lockedTrip->tracking_started_at !== null && $lockedTrip->tracking_started_at->gt($stoppedAt)) {
                    return $lockedTrip;
                }
            }

            $lockedTrip->update([
                'active_tracking_session_id' => null,
                'tracking_status' => 'SHARING_STOPPED',
                'tracking_ended_at' => $stoppedAt,
            ]);

            ActivityLog::record(
                event: 'trip.tracking_stopped',
                auditable: $lockedTrip,
                actor: $driver,
                newValues: [
                    'tracking_status' => 'SHARING_STOPPED',
                    'tracking_ended_at' => $stoppedAt->toIso8601String(),
                    'last_location_at' => $lockedTrip->last_location_at?->toIso8601String(),
                    'session_id' => $sessionIdStr,
                ]
            );

            return $lockedTrip;
        });
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

            // Terminal status cannot be transitioned back to ARRIVED (Requirement D)
            if (in_array($lockedStop->status, [TripStopStatus::HANDED_OVER, TripStopStatus::COMPLETED, TripStopStatus::SKIPPED], true)) {
                throw ValidationException::withMessages([
                    'stop' => [
                        "Pemberhentian #{$lockedStop->sequence_number} ({$lockedStop->school->name}) telah berada pada status final ({$lockedStop->status->label()}) dan tidak dapat diubah kembali menjadi tiba.",
                    ],
                ]);
            }

            // Idempotent: if already arrived and awaiting confirmation
            if (in_array($lockedStop->status, [TripStopStatus::ARRIVED, TripStopStatus::ARRIVED_AWAITING_CONFIRMATION], true)) {
                return $lockedStop;
            }

            // Enforce sequential stop arrival order (Gate D):
            // Prior stops must have completed delivery handover (PBI-10) or skipped before subsequent stop can arrive
            $precedingIncomplete = TripStop::where('trip_id', $trip->id)
                ->where('sequence_number', '<', $lockedStop->sequence_number)
                ->whereNotIn('status', [
                    TripStopStatus::HANDED_OVER,
                    TripStopStatus::COMPLETED,
                    TripStopStatus::SKIPPED,
                ])
                ->orderBy('sequence_number')
                ->first();

            if ($precedingIncomplete) {
                $statusDetail = match ($precedingIncomplete->status) {
                    TripStopStatus::PENDING => 'belum dikunjungi',
                    TripStopStatus::EN_ROUTE => 'masih dalam perjalanan',
                    TripStopStatus::ARRIVED,
                    TripStopStatus::ARRIVED_AWAITING_CONFIRMATION => 'masih menunggu penyelesaian serah terima makanan oleh PIC Sekolah (PBI-10)',
                    default => 'belum diselesaikan',
                };

                throw ValidationException::withMessages([
                    'stop' => [
                        "Pemberhentian #{$lockedStop->sequence_number} ({$lockedStop->school->name}) tidak dapat ditandai tiba karena pemberhentian #{$precedingIncomplete->sequence_number} ({$precedingIncomplete->school->name}) {$statusDetail}.",
                    ],
                ]);
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

    /**
     * Calculate approximate surface distance between two coordinates in meters (Haversine formula).
     */
    private function calculateDistanceMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // meters
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}
