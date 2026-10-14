<?php

namespace App\Services;

use App\Enums\IncidentCategory;
use App\Enums\IncidentSeverity;
use App\Enums\IncidentStatus;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\EvidencePhoto;
use App\Models\Incident;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class IncidentService
{
    /**
     * Create a new formal incident report (Page C01 /incidents).
     */
    public function createIncident(array $data, User $reporter, ?UploadedFile $photo = null): Incident
    {
        $uploadedPaths = [];

        try {
            return DB::transaction(function () use ($data, $reporter, $photo, &$uploadedPaths) {
                // Category validation
                $categoryInput = $data['category'] ?? null;
                $category = $categoryInput instanceof IncidentCategory
                    ? $categoryInput
                    : IncidentCategory::tryFrom((string) $categoryInput);

                if (!$category) {
                    throw ValidationException::withMessages([
                        'category' => ['Kategori kendala/insiden tidak valid.'],
                    ]);
                }

                // Severity validation
                $severityInput = $data['severity'] ?? IncidentSeverity::MEDIUM->value;
                $severity = $severityInput instanceof IncidentSeverity
                    ? $severityInput
                    : (IncidentSeverity::tryFrom((string) $severityInput) ?? IncidentSeverity::MEDIUM);

                // Description
                $description = trim((string) ($data['description'] ?? ''));
                if (empty($description)) {
                    throw ValidationException::withMessages([
                        'description' => ['Deskripsi kendala wajib diisi dengan rincian kronologi.'],
                    ]);
                }

                $sppgId = $data['sppg_id'] ?? $reporter->sppg_id;
                $schoolId = $data['school_id'] ?? $reporter->school_id;

                if (!$sppgId) {
                    // Try to derive from school
                    if ($schoolId) {
                        $sppgId = \App\Models\School::find($schoolId)?->sppg_id;
                    }
                }

                if (!$sppgId) {
                    throw ValidationException::withMessages([
                        'sppg_id' => ['Tenant SPPG tidak dapat ditentukan untuk pencatatan insiden ini.'],
                    ]);
                }

                $subjectType = $data['subject_type'] ?? Incident::class;
                $subjectId = $data['subject_id'] ?? 0;

                $incident = Incident::create([
                    'sppg_id' => $sppgId,
                    'school_id' => $schoolId,
                    'subject_type' => $subjectType,
                    'subject_id' => $subjectId,
                    'reported_by' => $reporter->id,
                    'category' => $category,
                    'severity' => $severity,
                    'status' => IncidentStatus::OPEN,
                    'description' => $description,
                ]);

                if ($photo) {
                    $path = $photo->store('evidence_photos/incidents', 'local');
                    $uploadedPaths[] = $path;

                    EvidencePhoto::create([
                        'evidenceable_type' => Incident::class,
                        'evidenceable_id' => $incident->id,
                        'evidence_type' => 'INCIDENT_REPORT',
                        'uploaded_by' => $reporter->id,
                        'file_path' => $path,
                        'mime_type' => $photo->getClientMimeType() ?: 'image/jpeg',
                        'file_size' => $photo->getSize() ?: 0,
                        'captured_at' => Carbon::now(),
                        'notes' => 'Bukti foto laporan kendala.',
                    ]);
                }

                ActivityLog::record(
                    event: 'incident.created',
                    auditable: $incident,
                    actor: $reporter,
                    newValues: [
                        'category' => $category->value,
                        'severity' => $severity->value,
                        'status' => IncidentStatus::OPEN->value,
                        'description' => $description,
                    ]
                );

                return $incident;
            });
        } catch (\Throwable $e) {
            foreach ($uploadedPaths as $path) {
                if ($path && Storage::disk('local')->exists($path)) {
                    Storage::disk('local')->delete($path);
                }
            }
            throw $e;
        }
    }

    /**
     * Acknowledge receipt of incident (OPEN -> ACKNOWLEDGED).
     */
    public function acknowledge(Incident $incident, User $actor, ?string $notes = null): Incident
    {
        return DB::transaction(function () use ($incident, $actor, $notes) {
            $locked = Incident::where('id', $incident->id)->lockForUpdate()->firstOrFail();

            if ((int) $actor->sppg_id !== (int) $locked->sppg_id) {
                throw new HttpException(403, 'Akses ditolak. Anda berada di luar lingkup unit SPPG insiden ini.');
            }

            if (!in_array($actor->role, [UserRole::KEPALA_SPPG, UserRole::PENGAWAS_GIZI], true)) {
                throw new HttpException(403, 'Akses ditolak. Hanya Kepala SPPG atau Pengawas Gizi yang berwenang menerima laporan insiden.');
            }

            if ($locked->status !== IncidentStatus::OPEN) {
                if ($locked->status === IncidentStatus::ACKNOWLEDGED) {
                    return $locked; // Idempotent
                }
                throw new ConflictHttpException(
                    "Penerimaan laporan hanya dapat dilakukan pada insiden berstatus Terbuka (OPEN). Status saat ini: {$locked->status->label()}."
                );
            }

            $locked->status = IncidentStatus::ACKNOWLEDGED;
            if ($notes) {
                $locked->description .= "\n[Diterima oleh {$actor->name}: {$notes}]";
            }
            $locked->save();

            ActivityLog::record(
                event: 'incident.acknowledged',
                auditable: $locked,
                actor: $actor,
                newValues: ['status' => IncidentStatus::ACKNOWLEDGED->value]
            );

            return $locked;
        });
    }

    /**
     * Advance incident to active investigation/progress (ACKNOWLEDGED / OPEN / REOPENED -> IN_PROGRESS).
     */
    public function startProgress(Incident $incident, User $actor, ?string $notes = null): Incident
    {
        return DB::transaction(function () use ($incident, $actor, $notes) {
            $locked = Incident::where('id', $incident->id)->lockForUpdate()->firstOrFail();

            if ((int) $actor->sppg_id !== (int) $locked->sppg_id) {
                throw new HttpException(403, 'Akses ditolak. Anda berada di luar lingkup unit SPPG insiden ini.');
            }

            if (!in_array($actor->role, [UserRole::KEPALA_SPPG, UserRole::PENGAWAS_GIZI, UserRole::ASISTEN_LAPANGAN], true)) {
                throw new HttpException(403, 'Akses ditolak. Anda tidak memiliki wewenang untuk menindaklanjuti insiden ini.');
            }

            if (!in_array($locked->status, [IncidentStatus::OPEN, IncidentStatus::ACKNOWLEDGED, IncidentStatus::REOPENED], true)) {
                if ($locked->status === IncidentStatus::IN_PROGRESS) {
                    return $locked;
                }
                throw new ConflictHttpException(
                    "Insiden tidak dapat dipindahkan ke investigasi aktif dari status {$locked->status->label()}."
                );
            }

            $locked->status = IncidentStatus::IN_PROGRESS;
            if ($notes) {
                $locked->description .= "\n[Tindak lanjut oleh {$actor->name}: {$notes}]";
            }
            $locked->save();

            ActivityLog::record(
                event: 'incident.in_progress',
                auditable: $locked,
                actor: $actor,
                newValues: ['status' => IncidentStatus::IN_PROGRESS->value]
            );

            return $locked;
        });
    }

    /**
     * Resolve incident with formal findings and corrective actions (IN_PROGRESS / ACKNOWLEDGED -> RESOLVED).
     */
    public function resolve(Incident $incident, User $actor, string $resolutionNotes): Incident
    {
        return DB::transaction(function () use ($incident, $actor, $resolutionNotes) {
            $locked = Incident::where('id', $incident->id)->lockForUpdate()->firstOrFail();

            if ((int) $actor->sppg_id !== (int) $locked->sppg_id) {
                throw new HttpException(403, 'Akses ditolak. Anda berada di luar lingkup unit SPPG insiden ini.');
            }

            if (!in_array($actor->role, [UserRole::KEPALA_SPPG, UserRole::PENGAWAS_GIZI], true)) {
                throw new HttpException(403, 'Akses ditolak. Hanya Kepala SPPG atau Pengawas Gizi yang berwenang menetapkan resolusi insiden.');
            }

            if ($locked->status === IncidentStatus::CLOSED) {
                throw new ConflictHttpException('Insiden telah ditutup permanen (CLOSED) dan tidak dapat diubah statusnya.');
            }

            $notes = trim($resolutionNotes);
            if (empty($notes)) {
                throw ValidationException::withMessages([
                    'resolution_notes' => ['Catatan resolusi dan tindakan korektif wajib diisi secara komprehensif.'],
                ]);
            }

            $now = Carbon::now();
            $locked->status = IncidentStatus::RESOLVED;
            $locked->resolved_by = $actor->id;
            $locked->resolved_at = $now;
            $locked->resolution_notes = $notes;
            $locked->save();

            ActivityLog::record(
                event: 'incident.resolved',
                auditable: $locked,
                actor: $actor,
                newValues: [
                    'status' => IncidentStatus::RESOLVED->value,
                    'resolved_by' => $actor->id,
                    'resolved_at' => $now->toIso8601String(),
                    'resolution_notes' => $notes,
                ]
            );

            return $locked;
        });
    }

    /**
     * Formally close incident after resolution review (RESOLVED -> CLOSED).
     * Strictly restricted to Kepala SPPG.
     */
    public function close(Incident $incident, User $actor, ?string $notes = null): Incident
    {
        return DB::transaction(function () use ($incident, $actor, $notes) {
            $locked = Incident::where('id', $incident->id)->lockForUpdate()->firstOrFail();

            if ($actor->role !== UserRole::KEPALA_SPPG) {
                throw new HttpException(403, 'Akses ditolak. Penutupan administratif insiden hanya dapat disahkan oleh Kepala SPPG.');
            }

            if ((int) $actor->sppg_id !== (int) $locked->sppg_id) {
                throw new HttpException(403, 'Akses ditolak. Insiden berada di luar unit SPPG Anda.');
            }

            if ($locked->status !== IncidentStatus::RESOLVED) {
                if ($locked->status === IncidentStatus::CLOSED) {
                    return $locked;
                }
                throw new ConflictHttpException(
                    "Insiden hanya dapat ditutup (CLOSED) setelah status Terselesaikan (RESOLVED) dengan catatan tindakan korektif resmi. Status saat ini: {$locked->status->label()}."
                );
            }

            $locked->status = IncidentStatus::CLOSED;
            if ($notes) {
                $locked->resolution_notes .= "\n[Pengesahan penutupan oleh {$actor->name}: {$notes}]";
            }
            $locked->save();

            ActivityLog::record(
                event: 'incident.closed',
                auditable: $locked,
                actor: $actor,
                newValues: ['status' => IncidentStatus::CLOSED->value]
            );

            return $locked;
        });
    }

    /**
     * Reopen an incident if new evidence or recurrence occurs (RESOLVED / CLOSED -> REOPENED).
     */
    public function reopen(Incident $incident, User $actor, string $reopenReason): Incident
    {
        return DB::transaction(function () use ($incident, $actor, $reopenReason) {
            $locked = Incident::where('id', $incident->id)->lockForUpdate()->firstOrFail();

            $isReporter = ((int) $locked->reported_by === (int) $actor->id);
            $isManager = ($actor->role === UserRole::KEPALA_SPPG && (int) $actor->sppg_id === (int) $locked->sppg_id);

            if (!$isReporter && !$isManager) {
                throw new HttpException(403, 'Akses ditolak. Pembukaan kembali insiden hanya dapat diajukan oleh pelapor asli atau Kepala SPPG.');
            }

            if (!in_array($locked->status, [IncidentStatus::RESOLVED, IncidentStatus::CLOSED], true)) {
                throw new ConflictHttpException(
                    "Pembukaan kembali hanya berlaku untuk insiden yang sudah berstatus Terselesaikan (RESOLVED) atau Ditutup (CLOSED). Status saat ini: {$locked->status->label()}."
                );
            }

            $reason = trim($reopenReason);
            if (empty($reason)) {
                throw ValidationException::withMessages([
                    'reopen_reason' => ['Alasan pembukaan kembali insiden dan bukti baru wajib dicantumkan.'],
                ]);
            }

            $locked->status = IncidentStatus::REOPENED;
            $locked->description .= "\n[DIBUKA KEMBALI oleh {$actor->name} pada " . Carbon::now()->toFormattedDateString() . ": {$reason}]";
            $locked->save();

            ActivityLog::record(
                event: 'incident.reopened',
                auditable: $locked,
                actor: $actor,
                newValues: [
                    'status' => IncidentStatus::REOPENED->value,
                    'reopen_reason' => $reason,
                ]
            );

            return $locked;
        });
    }
}
