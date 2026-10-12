<?php

namespace App\Http\Controllers\Head;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\User;
use App\Services\EmployeeManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class EmployeeManagementController extends Controller
{
    public function __construct(
        protected EmployeeManagementService $employeeService
    ) {}

    /**
     * Display a listing of employees in the SPPG.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $sppg = $user->sppg;

        $search = $request->input('q');
        $roleFilter = $request->input('role', 'all');
        $statusFilter = $request->input('status', 'all');

        $employees = User::where('sppg_id', $user->sppg_id)
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($roleFilter !== 'all', fn ($q) => $q->where('role', $roleFilter))
            ->when($statusFilter === 'active', fn ($q) => $q->where('is_active', true))
            ->when($statusFilter === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('name')
            ->get();

        $allStaffQuery = User::where('sppg_id', $user->sppg_id);

        return view('head.employees.index', [
            'user' => $user,
            'sppg' => $sppg,
            'employees' => $employees,
            'search' => $search,
            'roleFilter' => $roleFilter,
            'statusFilter' => $statusFilter,
            'totalStaff' => (clone $allStaffQuery)->count(),
            'totalActive' => (clone $allStaffQuery)->where('is_active', true)->count(),
            'totalNutrition' => (clone $allStaffQuery)->where('role', UserRole::PENGAWAS_GIZI->value)->count(),
            'totalProduction' => (clone $allStaffQuery)->where('role', UserRole::ASISTEN_LAPANGAN->value)->count(),
            'totalDrivers' => (clone $allStaffQuery)->where('role', UserRole::KURIR->value)->count(),
        ]);
    }

    /**
     * Show form for registering a new internal employee.
     */
    public function create(): View
    {
        Gate::authorize('create', User::class);

        return view('head.employees.create', [
            'roles' => [
                UserRole::PENGAWAS_GIZI,
                UserRole::ASISTEN_LAPANGAN,
                UserRole::KURIR,
            ],
        ]);
    }

    /**
     * Store newly created employee in database.
     */
    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $user = $request->user();
        $employee = $this->employeeService->createEmployee(
            $request->validated(),
            (int) $user->sppg_id,
            $user,
            $request
        );

        $roleLabel = $employee->role instanceof UserRole ? $employee->role->label() : $employee->role;

        return redirect()->route('head.employees.index')->with(
            'status',
            "Pegawai {$employee->name} ({$roleLabel}) berhasil didaftarkan ke SPPG."
        );
    }

    /**
     * Display the specified employee profile, role status, and audit logs.
     */
    public function show(User $user): View
    {
        Gate::authorize('view', $user);

        $user->load([
            'sppg',
            'activityLogs' => fn ($q) => $q->with('actor')->latest()->take(20),
        ]);

        return view('head.employees.show', [
            'employee' => $user,
            'sppg' => $user->sppg,
            'activityLogs' => $user->activityLogs,
        ]);
    }

    /**
     * Show the form for editing an existing employee.
     */
    public function edit(User $user): View
    {
        Gate::authorize('update', $user);

        return view('head.employees.edit', [
            'employee' => $user,
            'roles' => [
                UserRole::PENGAWAS_GIZI,
                UserRole::ASISTEN_LAPANGAN,
                UserRole::KURIR,
                UserRole::KEPALA_SPPG,
            ],
        ]);
    }

    /**
     * Update employee credentials, details, and assigned role.
     */
    public function update(UpdateEmployeeRequest $request, User $user): RedirectResponse
    {
        Gate::authorize('update', $user);

        $updated = $this->employeeService->updateEmployee(
            $user,
            $request->validated(),
            $request->user(),
            $request
        );

        return redirect()->route('head.employees.show', $updated)->with(
            'status',
            "Data dan hak akses pegawai {$updated->name} berhasil diperbarui."
        );
    }

    /**
     * Toggle active/inactive status safely.
     */
    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('toggleActive', $user);

        $this->employeeService->toggleActive($user, $request->user(), $request);

        $statusText = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with(
            'status',
            "Akun pegawai {$user->name} berhasil {$statusText}."
        );
    }

    /**
     * Reset employee password with audit trail.
     */
    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('resetPassword', $user);

        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.min' => 'Kata sandi minimal harus 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
        ]);

        $this->employeeService->resetPassword(
            $user,
            $request->input('password'),
            $request->user(),
            $request
        );

        return back()->with(
            'status',
            "Kata sandi untuk pegawai {$user->name} berhasil direset."
        );
    }
}
