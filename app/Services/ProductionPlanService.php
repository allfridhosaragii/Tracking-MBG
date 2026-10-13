<?php

namespace App\Services;

use App\Enums\PortionRequestStatus;
use App\Enums\ProductionPlanStatus;
use App\Models\ActivityLog;
use App\Models\DailyPortionRequest;
use App\Models\Menu;
use App\Models\ProductionAllocation;
use App\Models\ProductionPlan;
use App\Models\School;
use App\Models\Sppg;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductionPlanService
{
    public function __construct(
        protected PortionRequestService $portionRequestService
    ) {}

    /**
     * Build the complete planning workspace state for Kepala SPPG (Page K04).
     */
    public function getPlanningWorkspace(int $sppgId, string $serviceDate, ?int $menuId = null): array
    {
        $sppg = Sppg::findOrFail($sppgId);
        $parsedDate = Carbon::parse($serviceDate, 'Asia/Jakarta');
        $dateStr = $parsedDate->format('Y-m-d');

        // Active menus for tenant
        $activeMenus = Menu::forSppg($sppgId)
            ->active()
            ->with(['items'])
            ->orderBy('name')
            ->get();

        // Selected menu (or default to first active)
        $selectedMenu = null;
        if ($menuId) {
            $selectedMenu = $activeMenus->firstWhere('id', $menuId);
        }
        if (! $selectedMenu && $activeMenus->isNotEmpty()) {
            $selectedMenu = $activeMenus->first();
        }

        // Portion request aggregate for this date
        $aggregate = $this->portionRequestService->getSppgAggregateForDate($sppgId, $dateStr);

        // Existing production plans for this date
        $existingPlans = ProductionPlan::forSppg($sppgId)
            ->forDate($dateStr)
            ->with(['menu.items', 'allocations.school', 'allocations.portionRequest', 'creator', 'finalizer'])
            ->get();

        $activePlan = $existingPlans->firstWhere('status', ProductionPlanStatus::FINALIZED)
            ?? $existingPlans->firstWhere('status', ProductionPlanStatus::PLANNED)
            ?? $existingPlans->first();

        // Resolve school allocation rows
        $schools = School::where('sppg_id', $sppgId)
            ->where('is_active', true)
            ->with(['portionRequests' => function ($q) use ($dateStr) {
                $q->whereDate('service_date', $dateStr);
            }])
            ->get();

        $allocationRows = [];
        $totalConfirmedEligible = 0;
        $totalAllocatedPortions = 0;
        $hasBlockers = false;
        $blockers = [];

        foreach ($schools as $school) {
            $req = $school->portionRequests->first();
            $existingAlloc = $activePlan ? $activePlan->allocations->firstWhere('school_id', $school->id) : null;

            $isEligible = $req && $req->isEligibleForProduction();
            $statusLabel = $req ? $req->status->label() : 'Belum Dibuat';
            $portionsCount = $req ? (int) $req->requested_portions : null;

            if ($existingAlloc) {
                $allocated = (int) $existingAlloc->allocated_portions;
            } elseif ($isEligible) {
                $allocated = (int) $req->requested_portions;
            } else {
                $allocated = 0;
            }

            if ($isEligible) {
                $totalConfirmedEligible += (int) $req->requested_portions;
            }

            $totalAllocatedPortions += $allocated;

            $allocationRows[] = [
                'school' => $school,
                'request' => $req,
                'is_eligible' => $isEligible,
                'has_exception' => $req ? $req->hasUnapprovedException() : false,
                'is_revised' => $req ? $req->isRevised() : false,
                'is_pending' => ! $req || $req->isDraft(),
                'status_label' => $statusLabel,
                'confirmed_portions' => $portionsCount,
                'allocated_portions' => $allocated,
            ];
        }

        // Evaluate Blocker Conditions
        if ($aggregate['pending_count'] > 0) {
            $hasBlockers = true;
            $blockers[] = "Terdapat {$aggregate['pending_count']} sekolah yang belum mengonfirmasi kebutuhan porsi harian.";
        }

        if ($aggregate['revised_count'] > 0) {
            $hasBlockers = true;
            $blockers[] = "Terdapat {$aggregate['revised_count']} sekolah dengan revisi porsi yang belum disahkan oleh Kepala SPPG.";
        }

        if ($aggregate['exception_count'] > 0) {
            $hasBlockers = true;
            $blockers[] = "Terdapat {$aggregate['exception_count']} permintaan porsi melebihi jumlah siswa terdaftar tanpa persetujuan khusus.";
        }

        if ($activeMenus->isEmpty()) {
            $hasBlockers = true;
            $blockers[] = "Belum ada menu siklus dengan status Aktif yang dapat digunakan untuk perencanaan produksi.";
        }

        $kitchenCapacity = (int) $sppg->max_daily_portions;
        $isOverCapacity = false;
        if ($kitchenCapacity <= 0) {
            $hasBlockers = true;
            $blockers[] = "Kapasitas maksimum harian dapur SPPG belum ditetapkan atau bernilai tidak sah (0 porsi).";
            $isOverCapacity = true;
        } elseif ($totalAllocatedPortions > $kitchenCapacity) {
            $hasBlockers = true;
            $blockers[] = "Total porsi dialokasikan ({$totalAllocatedPortions}) melebihi batas kapasitas dapur SPPG ({$kitchenCapacity} porsi).";
            $isOverCapacity = true;
        }

        $capacityPercent = $kitchenCapacity > 0 ? min(100, round(($totalAllocatedPortions / $kitchenCapacity) * 100)) : 0;

        return [
            'sppg' => $sppg,
            'service_date' => $dateStr,
            'parsed_date' => $parsedDate,
            'active_menus' => $activeMenus,
            'selected_menu' => $selectedMenu,
            'active_plan' => $activePlan,
            'existing_plans' => $existingPlans,
            'aggregate' => $aggregate,
            'allocation_rows' => $allocationRows,
            'kitchen_capacity' => $kitchenCapacity,
            'total_confirmed_eligible' => $totalConfirmedEligible,
            'total_allocated_portions' => $totalAllocatedPortions,
            'is_over_capacity' => $isOverCapacity,
            'capacity_percent' => $capacityPercent,
            'has_blockers' => $hasBlockers,
            'blockers' => $blockers,
            'can_finalize' => ! $hasBlockers && $totalAllocatedPortions > 0 && (! $activePlan || $activePlan->canBeFinalized()),
        ];
    }

    /**
     * Create or retrieve an initial PLANNED production plan for a specific date and active menu.
     */
    public function createPlan(int $sppgId, array $data, User $actor): ProductionPlan
    {
        $serviceDate = Carbon::parse($data['service_date'], 'Asia/Jakarta')->format('Y-m-d');
        $menuId = (int) $data['menu_id'];

        $menu = Menu::where('id', $menuId)
            ->where('sppg_id', $sppgId)
            ->first();

        if (! $menu) {
            throw ValidationException::withMessages([
                'menu_id' => ['Menu yang dipilih tidak ditemukan dalam SPPG ini.'],
            ]);
        }

        if (! $menu->isActive()) {
            throw ValidationException::withMessages([
                'menu_id' => ['Hanya menu berstatus Aktif yang dapat digunakan untuk rencana produksi.'],
            ]);
        }

        return DB::transaction(function () use ($sppgId, $serviceDate, $menu, $data, $actor) {
            $existing = ProductionPlan::forSppg($sppgId)
                ->forDate($serviceDate)
                ->where('menu_id', $menu->id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $plan = ProductionPlan::create([
                'sppg_id' => $sppgId,
                'menu_id' => $menu->id,
                'service_date' => $serviceDate,
                'planned_start_at' => isset($data['planned_start_at']) ? Carbon::parse($data['planned_start_at'], 'Asia/Jakarta') : null,
                'status' => ProductionPlanStatus::PLANNED,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            ActivityLog::record(
                event: 'production_plan.created',
                auditable: $plan,
                actor: $actor,
                newValues: [
                    'sppg_id' => $sppgId,
                    'menu_id' => $menu->id,
                    'service_date' => $serviceDate,
                    'status' => ProductionPlanStatus::PLANNED->value,
                ]
            );

            return $plan;
        });
    }

    /**
     * Finalize production plan and commit school allocations atomically.
     * Enforces:
     * 1. Menu must be ACTIVE and belong to tenant.
     * 2. Portion requests must be CONFIRMED or LOCKED, with no unapproved exceptions.
     * 3. Total portions must not exceed kitchen capacity.
     * 4. Transitions all associated portion requests to LOCKED.
     * 5. Atomically creates production_allocations and updates plan status to FINALIZED.
     * 6. Idempotent against double-submit.
     */
    public function finalizePlan(ProductionPlan $plan, array $allocationsData, User $actor): ProductionPlan
    {
        return DB::transaction(function () use ($plan, $allocationsData, $actor) {
            $lockedPlan = ProductionPlan::where('id', $plan->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Guard: Idempotency & legal state transition
            if ($lockedPlan->isFinalized()) {
                return $lockedPlan; // Already finalized safely
            }

            if (! $lockedPlan->canBeFinalized()) {
                throw ValidationException::withMessages([
                    'status' => ["Rencana produksi tidak dapat difinalisasi karena berstatus {$lockedPlan->status->label()}."],
                ]);
            }

            // Guard: Tenant & Actor Authority
            if ((int) $lockedPlan->sppg_id !== (int) $actor->sppg_id) {
                throw ValidationException::withMessages([
                    'authorization' => ['Anda tidak berwenang memfinalisasi rencana produksi lintas SPPG.'],
                ]);
            }

            // Guard: Menu validity
            $menu = Menu::where('id', $lockedPlan->menu_id)
                ->where('sppg_id', $lockedPlan->sppg_id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $menu->isActive()) {
                throw ValidationException::withMessages([
                    'menu_id' => ['Menu tidak valid atau sudah diarsipkan. Hanya menu berstatus Aktif yang dapat difinalisasi.'],
                ]);
            }

            // Guard: Kitchen Capacity
            $sppg = Sppg::where('id', $lockedPlan->sppg_id)->lockForUpdate()->firstOrFail();
            $maxDaily = (int) $sppg->max_daily_portions;

            if ($maxDaily <= 0) {
                throw ValidationException::withMessages([
                    'capacity' => ['Kapasitas maksimum harian dapur SPPG belum ditetapkan atau bernilai tidak sah. Finalisasi ditolak demi keselamatan operasional.'],
                ]);
            }

            if (empty($allocationsData)) {
                throw ValidationException::withMessages([
                    'allocations' => ['Rencana produksi harus memiliki setidaknya satu alokasi sekolah yang sah.'],
                ]);
            }

            $serviceDateStr = $lockedPlan->service_date->format('Y-m-d');

            // HARD GATE A: Invariant Kelengkapan Alokasi dari Database (Bukan Percaya Frontend)
            $allActiveSchools = School::where('sppg_id', $lockedPlan->sppg_id)
                ->where('is_active', true)
                ->with(['portionRequests' => fn ($q) => $q->whereDate('service_date', $serviceDateStr)])
                ->get();

            $blockingSchools = [];
            $requiredSchoolIds = [];

            foreach ($allActiveSchools as $activeSchool) {
                $req = $activeSchool->portionRequests->first();

                if (! $req || $req->isDraft()) {
                    $blockingSchools[] = "{$activeSchool->name} (Permintaan porsi belum dibuat/masih draf)";
                } elseif ($req->isRevised()) {
                    $blockingSchools[] = "{$activeSchool->name} (Revisi porsi menunggu persetujuan)";
                } elseif ($req->hasUnapprovedException()) {
                    $blockingSchools[] = "{$activeSchool->name} (Porsi melebihi siswa terdaftar belum disetujui)";
                } elseif ($req->isCancelled()) {
                    // Legally cancelled: safe exclusion from allocation
                    continue;
                } elseif ($req->isEligibleForProduction()) {
                    // Legitimate request: MUST be allocated in finalization!
                    $requiredSchoolIds[$activeSchool->id] = $activeSchool->name;
                }
            }

            if (! empty($blockingSchools)) {
                throw ValidationException::withMessages([
                    'allocations' => ['Finalisasi diblokir karena masih terdapat sekolah dengan status kebutuhan belum terselesaikan: ' . implode('; ', $blockingSchools)],
                ]);
            }

            $totalRequestedAllocation = 0;
            $seenSchools = [];
            $seenRequests = [];
            $validatedAllocations = [];

            foreach ($allocationsData as $index => $item) {
                $schoolId = (int) ($item['school_id'] ?? 0);
                $portionRequestId = (int) ($item['portion_request_id'] ?? 0);
                $allocatedPortions = (int) ($item['allocated_portions'] ?? 0);

                if ($allocatedPortions <= 0) {
                    continue; // Skip zero portions if any
                }

                if (in_array($schoolId, $seenSchools, true)) {
                    throw ValidationException::withMessages([
                        "allocations.{$index}.school_id" => ['Terdeteksi duplikasi alokasi untuk sekolah yang sama dalam satu rencana produksi.'],
                    ]);
                }
                $seenSchools[] = $schoolId;

                if (in_array($portionRequestId, $seenRequests, true)) {
                    throw ValidationException::withMessages([
                        "allocations.{$index}.portion_request_id" => ['Permintaan porsi tidak boleh dialokasikan dua kali.'],
                    ]);
                }
                $seenRequests[] = $portionRequestId;

                // Lock and validate DailyPortionRequest
                $portionRequest = DailyPortionRequest::where('id', $portionRequestId)
                    ->lockForUpdate()
                    ->first();

                if (! $portionRequest) {
                    throw ValidationException::withMessages([
                        "allocations.{$index}" => ['Data permintaan porsi tidak ditemukan.'],
                    ]);
                }

                // Cross-tenant verification
                if ((int) $portionRequest->school->sppg_id !== (int) $lockedPlan->sppg_id) {
                    throw ValidationException::withMessages([
                        "allocations.{$index}" => ['Sekolah tidak terdaftar dalam wilayah kerja SPPG ini.'],
                    ]);
                }

                if ((int) $portionRequest->school_id !== $schoolId) {
                    throw ValidationException::withMessages([
                        "allocations.{$index}" => ['Ketidaksesuaian ID sekolah dengan data permintaan porsi.'],
                    ]);
                }

                if ($portionRequest->service_date->format('Y-m-d') !== $serviceDateStr) {
                    throw ValidationException::withMessages([
                        "allocations.{$index}" => ['Tanggal permintaan porsi tidak sesuai dengan tanggal layanan rencana produksi.'],
                    ]);
                }

                // Status eligibility
                if (! $portionRequest->isEligibleForProduction()) {
                    throw ValidationException::withMessages([
                        "allocations.{$index}" => ["Permintaan porsi untuk {$portionRequest->school->name} belum memenuhi syarat resmi produksi (status: {$portionRequest->status->label()})."],
                    ]);
                }

                // Unapproved exception check
                if ($portionRequest->hasUnapprovedException()) {
                    throw ValidationException::withMessages([
                        "allocations.{$index}" => ["Permintaan porsi untuk {$portionRequest->school->name} melebihi siswa terdaftar dan belum disetujui Kepala SPPG."],
                    ]);
                }

                // Revision pending check
                if ($portionRequest->isRevised()) {
                    throw ValidationException::withMessages([
                        "allocations.{$index}" => ["Sekolah {$portionRequest->school->name} memiliki pengajuan revisi yang belum diputuskan."],
                    ]);
                }

                // HARD GATE B: Rekonsiliasi 1:1 antara porsi alokasi dengan permintaan resmi
                if ($allocatedPortions !== (int) $portionRequest->requested_portions) {
                    throw ValidationException::withMessages([
                        "allocations.{$index}.allocated_portions" => [
                            "Alokasi porsi untuk {$portionRequest->school->name} ({$allocatedPortions} porsi) tidak sesuai dengan kebutuhan resmi terkonfirmasi ({$portionRequest->requested_portions} porsi). Penyesuaian tanpa revisi resmi dilarang.",
                        ],
                    ]);
                }

                $totalRequestedAllocation += $allocatedPortions;

                $validatedAllocations[] = [
                    'school_id' => $schoolId,
                    'portion_request' => $portionRequest,
                    'allocated_portions' => $allocatedPortions,
                ];
            }

            // HARD GATE A: Verifikasi seluruh sekolah eligible ada dalam payload alokasi
            $missingSchools = [];
            foreach ($requiredSchoolIds as $reqSchoolId => $reqSchoolName) {
                if (! in_array($reqSchoolId, $seenSchools, true)) {
                    $missingSchools[] = $reqSchoolName;
                }
            }

            if (! empty($missingSchools)) {
                throw ValidationException::withMessages([
                    'allocations' => ['Finalisasi ditolak. Seluruh sekolah aktif dengan kebutuhan sah wajib dialokasikan. Sekolah berikut belum dialokasikan: ' . implode(', ', $missingSchools)],
                ]);
            }

            if ($totalRequestedAllocation <= 0) {
                throw ValidationException::withMessages([
                    'allocations' => ['Total alokasi porsi harus bernilai lebih dari nol.'],
                ]);
            }

            // HARD GATE C: Multiple Plans & Double Counting Prevention
            $alreadyAllocatedRequests = ProductionAllocation::whereHas('productionPlan', function ($q) use ($lockedPlan, $serviceDateStr) {
                $q->where('sppg_id', $lockedPlan->sppg_id)
                    ->whereDate('service_date', $serviceDateStr)
                    ->where('id', '!=', $lockedPlan->id)
                    ->whereIn('status', [
                        ProductionPlanStatus::FINALIZED->value,
                        ProductionPlanStatus::IN_PRODUCTION->value,
                        ProductionPlanStatus::READY_FOR_DISTRIBUTION->value,
                        ProductionPlanStatus::COMPLETED->value,
                    ]);
            })->whereIn('portion_request_id', $seenRequests)->with('school')->get();

            if ($alreadyAllocatedRequests->isNotEmpty()) {
                $dupSchoolName = $alreadyAllocatedRequests->first()->school->name ?? 'sekolah';
                throw ValidationException::withMessages([
                    'allocations' => ["Permintaan porsi untuk {$dupSchoolName} sudah dialokasikan pada rencana produksi lain yang telah difinalisasi pada tanggal yang sama."],
                ]);
            }

            $alreadyAllocatedSum = (int) ProductionAllocation::whereHas('productionPlan', function ($q) use ($lockedPlan, $serviceDateStr) {
                $q->where('sppg_id', $lockedPlan->sppg_id)
                    ->whereDate('service_date', $serviceDateStr)
                    ->where('id', '!=', $lockedPlan->id)
                    ->whereIn('status', [
                        ProductionPlanStatus::FINALIZED->value,
                        ProductionPlanStatus::IN_PRODUCTION->value,
                        ProductionPlanStatus::READY_FOR_DISTRIBUTION->value,
                        ProductionPlanStatus::COMPLETED->value,
                    ]);
            })->sum('allocated_portions');

            if (($alreadyAllocatedSum + $totalRequestedAllocation) > $maxDaily) {
                throw ValidationException::withMessages([
                    'capacity' => ["Total alokasi lintas rencana produksi ({$alreadyAllocatedSum} + {$totalRequestedAllocation} = " . ($alreadyAllocatedSum + $totalRequestedAllocation) . " porsi) melebihi kapasitas maksimum dapur SPPG ({$maxDaily} porsi)."],
                ]);
            }

            // Clean up any stale draft allocations for this plan before inserting fresh finalized allocations
            $lockedPlan->allocations()->delete();

            // Persist Allocations and transition PortionRequests to LOCKED
            foreach ($validatedAllocations as $alloc) {
                $portionRequest = $alloc['portion_request'];

                // Legal transition CONFIRMED -> LOCKED via PortionRequestService
                $this->portionRequestService->lockRequest($portionRequest, $actor);

                ProductionAllocation::create([
                    'production_plan_id' => $lockedPlan->id,
                    'portion_request_id' => $portionRequest->id,
                    'school_id' => $alloc['school_id'],
                    'allocated_portions' => $alloc['allocated_portions'],
                ]);
            }

            // Commit Plan Finalization
            $oldValues = [
                'status' => $lockedPlan->status->value,
                'finalized_by' => null,
                'finalized_at' => null,
            ];

            $lockedPlan->update([
                'status' => ProductionPlanStatus::FINALIZED,
                'finalized_by' => $actor->id,
                'finalized_at' => now(),
            ]);

            ActivityLog::record(
                event: 'production_plan.finalized',
                auditable: $lockedPlan,
                actor: $actor,
                oldValues: $oldValues,
                newValues: [
                    'status' => ProductionPlanStatus::FINALIZED->value,
                    'finalized_by' => $actor->id,
                    'finalized_at' => $lockedPlan->finalized_at->toIso8601String(),
                    'total_allocated_portions' => $totalRequestedAllocation,
                    'schools_count' => count($validatedAllocations),
                ]
            );

            return $lockedPlan->fresh(['menu.items', 'allocations.school', 'finalizer']);
        });
    }

    /**
     * Get finalized production plans for Asisten Lapangan (Read-Only Operational View).
     */
    public function getFinalizedPlansForAssistant(int $sppgId, ?string $serviceDate = null): Collection
    {
        $query = ProductionPlan::forSppg($sppgId)
            ->where('status', ProductionPlanStatus::FINALIZED)
            ->with(['menu.items', 'allocations.school', 'finalizer'])
            ->orderBy('service_date', 'asc');

        if ($serviceDate) {
            $query->forDate($serviceDate);
        }

        return $query->get();
    }
}
