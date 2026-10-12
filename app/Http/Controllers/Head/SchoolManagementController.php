<?php

namespace App\Http\Controllers\Head;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSchoolRequest;
use App\Http\Requests\UpdateSchoolRequest;
use App\Models\School;
use App\Services\SchoolManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SchoolManagementController extends Controller
{
    public function __construct(
        protected SchoolManagementService $schoolService
    ) {}

    /**
     * Display a listing of schools under the current SPPG.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $sppg = $user->sppg;

        $search = $request->input('q');
        $status = $request->input('status', 'all');

        $schools = School::where('sppg_id', $user->sppg_id)
            ->with(['picUsers' => function ($query) {
                $query->where('is_active', true);
            }])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('school_code', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            })
            ->when($status === 'active', fn($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn($q) => $q->where('is_active', false))
            ->orderBy('name')
            ->get();

        return view('head.schools.index', [
            'user' => $user,
            'sppg' => $sppg,
            'schools' => $schools,
            'search' => $search,
            'status' => $status,
            'totalActive' => School::where('sppg_id', $user->sppg_id)->where('is_active', true)->count(),
            'totalBeneficiaries' => School::where('sppg_id', $user->sppg_id)->where('is_active', true)->sum('registered_beneficiaries'),
        ]);
    }

    /**
     * Show the form for creating a new school.
     */
    public function create(): View
    {
        $picCandidates = $this->schoolService->getAvailablePicCandidates();

        return view('head.schools.create', [
            'picCandidates' => $picCandidates,
        ]);
    }

    /**
     * Store a newly created school in storage.
     */
    public function store(StoreSchoolRequest $request): RedirectResponse
    {
        $user = $request->user();
        $school = $this->schoolService->createSchool($request->validated(), (int) $user->sppg_id);

        return redirect()->route('head.schools.index')->with(
            'status',
            "Sekolah {$school->name} ({$school->school_code}) berhasil ditambahkan ke daftar binaan SPPG."
        );
    }

    /**
     * Display the specified school details.
     */
    public function show(School $school): View
    {
        Gate::authorize('view', $school);

        $school->load(['sppg', 'picUsers']);

        return view('head.schools.show', [
            'school' => $school,
            'sppg' => $school->sppg,
            'currentPic' => $school->picUsers->first(),
        ]);
    }

    /**
     * Show the form for editing the specified school.
     */
    public function edit(School $school): View
    {
        Gate::authorize('update', $school);

        $school->load(['picUsers']);
        $currentPic = $school->picUsers->first();
        $picCandidates = $this->schoolService->getAvailablePicCandidates($school->id);

        return view('head.schools.edit', [
            'school' => $school,
            'currentPic' => $currentPic,
            'picCandidates' => $picCandidates,
        ]);
    }

    /**
     * Update the specified school in storage.
     */
    public function update(UpdateSchoolRequest $request, School $school): RedirectResponse
    {
        Gate::authorize('update', $school);

        $updatedSchool = $this->schoolService->updateSchool($school, $request->validated());

        return redirect()->route('head.schools.index')->with(
            'status',
            "Data sekolah {$updatedSchool->name} berhasil diperbarui."
        );
    }

    /**
     * Toggle school active status (deactivate without hard delete).
     */
    public function toggleActive(School $school): RedirectResponse
    {
        Gate::authorize('update', $school);

        $updatedSchool = $this->schoolService->toggleSchoolActive($school);
        $stateText = $updatedSchool->is_active ? 'diaktifkan kembali' : 'dinonaktifkan dari jadwal layanan';

        return redirect()->route('head.schools.index')->with(
            'status',
            "Sekolah {$updatedSchool->name} berhasil {$stateText}."
        );
    }
}
