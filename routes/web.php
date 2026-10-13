<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Driver\DashboardController as DriverDashboardController;
use App\Http\Controllers\Head\DashboardController as HeadDashboardController;
use App\Http\Controllers\Head\EmployeeManagementController;
use App\Http\Controllers\Head\PortionMonitoringController;
use App\Http\Controllers\Head\ProductionPlanController;
use App\Http\Controllers\Head\SchoolManagementController;
use App\Http\Controllers\Nutrition\DashboardController as NutritionDashboardController;
use App\Http\Controllers\Nutrition\MenuManagementController;
use App\Http\Controllers\Production\DashboardController as ProductionDashboardController;
use App\Http\Controllers\Public\SchoolPublicController;
use App\Http\Controllers\School\DashboardController as SchoolDashboardController;
use App\Http\Controllers\School\PortionRequestController;
use App\Http\Controllers\School\SchoolOverviewController;
use Illuminate\Support\Facades\Route;

// Redirect root to login
Route::get('/', function () {
    if (auth()->check()) {
        $user = auth()->user();
        $targetRoute = $user->role ? $user->role->dashboardRoute() : 'login';
        return redirect()->route($targetRoute);
    }
    return redirect()->route('login');
})->name('home');

// Authentication routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.post');
});

Route::match(['get', 'post'], '/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Public School Transparency portal (No Auth required, sanitized whitelist)
Route::get('/public/schools/{slug}', [SchoolPublicController::class, 'show'])->name('public.schools.show');

// Protected Routes by Role and Active status
Route::middleware(['auth', 'active'])->group(function () {
    // 1. Kepala SPPG Module
    Route::prefix('head')
        ->name('head.')
        ->middleware('role:KEPALA_SPPG')
        ->group(function () {
            Route::get('/dashboard', HeadDashboardController::class)->name('dashboard');
            
            // PBI-01: School & PIC Management (Page K02)
            Route::get('/schools', [SchoolManagementController::class, 'index'])->name('schools.index');
            Route::get('/schools/create', [SchoolManagementController::class, 'create'])->name('schools.create');
            Route::post('/schools', [SchoolManagementController::class, 'store'])->name('schools.store');
            Route::get('/schools/{school}', [SchoolManagementController::class, 'show'])->name('schools.show');
            Route::get('/schools/{school}/edit', [SchoolManagementController::class, 'edit'])->name('schools.edit');
            Route::put('/schools/{school}', [SchoolManagementController::class, 'update'])->name('schools.update');
            Route::patch('/schools/{school}/toggle-active', [SchoolManagementController::class, 'toggleActive'])->name('schools.toggle-active');

            // PBI-02: Employee & Role Management (Page K03)
            Route::get('/employees', [EmployeeManagementController::class, 'index'])->name('employees.index');
            Route::get('/employees/create', [EmployeeManagementController::class, 'create'])->name('employees.create');
            Route::post('/employees', [EmployeeManagementController::class, 'store'])->name('employees.store');
            Route::get('/employees/{user}', [EmployeeManagementController::class, 'show'])->name('employees.show');
            Route::get('/employees/{user}/edit', [EmployeeManagementController::class, 'edit'])->name('employees.edit');
            Route::put('/employees/{user}', [EmployeeManagementController::class, 'update'])->name('employees.update');
            Route::patch('/employees/{user}/toggle-active', [EmployeeManagementController::class, 'toggleActive'])->name('employees.toggle-active');
            Route::post('/employees/{user}/reset-password', [EmployeeManagementController::class, 'resetPassword'])->name('employees.reset-password');

            // PBI-04: Portion Confirmation Monitoring (Page K04 initial)
            Route::get('/portions', [PortionMonitoringController::class, 'index'])->name('portions.index');

            // PBI-05: Production Planning & Allocation (Page K04)
            Route::get('/production', [ProductionPlanController::class, 'index'])->name('production.index');
            Route::post('/production/plans', [ProductionPlanController::class, 'store'])->name('production.plans.store');
            Route::post('/production/plans/{productionPlan}/finalize', [ProductionPlanController::class, 'finalize'])->name('production.plans.finalize');
            Route::post('/production/revisions/{portionRequest}/approve', [ProductionPlanController::class, 'approveRevision'])->name('production.revisions.approve');
            Route::post('/production/revisions/{portionRequest}/reject', [ProductionPlanController::class, 'rejectRevision'])->name('production.revisions.reject');
            Route::post('/production/exceptions/{portionRequest}/approve', [ProductionPlanController::class, 'approveException'])->name('production.exceptions.approve');
        });

    // 2. Pengawas Gizi Module
    Route::prefix('nutrition')
        ->name('nutrition.')
        ->middleware('role:PENGAWAS_GIZI')
        ->group(function () {
            Route::get('/dashboard', NutritionDashboardController::class)->name('dashboard');

            // PBI-03: Menu & Nutrition Management (Page G02)
            Route::get('/menus', [MenuManagementController::class, 'index'])->name('menus.index');
            Route::get('/menus/create', [MenuManagementController::class, 'create'])->name('menus.create');
            Route::post('/menus', [MenuManagementController::class, 'store'])->name('menus.store');
            Route::get('/menus/{menu}', [MenuManagementController::class, 'show'])->name('menus.show');
            Route::get('/menus/{menu}/edit', [MenuManagementController::class, 'edit'])->name('menus.edit');
            Route::put('/menus/{menu}', [MenuManagementController::class, 'update'])->name('menus.update');
            Route::post('/menus/{menu}/items', [MenuManagementController::class, 'addItem'])->name('menus.items.store');
            Route::delete('/menus/{menu}/items/{item}', [MenuManagementController::class, 'deleteItem'])->name('menus.items.destroy');
            Route::patch('/menus/{menu}/activate', [MenuManagementController::class, 'activate'])->name('menus.activate');
            Route::post('/menus/{menu}/new-version', [MenuManagementController::class, 'newVersion'])->name('menus.new-version');
        });

    // 3. Asisten Lapangan Module
    Route::prefix('production')
        ->name('production.')
        ->middleware('role:ASISTEN_LAPANGAN')
        ->group(function () {
            Route::get('/dashboard', ProductionDashboardController::class)->name('dashboard');
        });

    // 4. Kurir Module
    Route::prefix('driver')
        ->name('driver.')
        ->middleware('role:KURIR')
        ->group(function () {
            Route::get('/dashboard', DriverDashboardController::class)->name('dashboard');
        });

    // 5. PIC Sekolah Module
    Route::prefix('school')
        ->name('school.')
        ->middleware('role:PIC_SEKOLAH')
        ->group(function () {
            Route::get('/dashboard', SchoolDashboardController::class)->name('dashboard');

            // PBI-04: Portion Confirmation (Page P02)
            Route::get('/portions', [PortionRequestController::class, 'index'])->name('portions.index');
            Route::post('/portions/draft', [PortionRequestController::class, 'saveDraft'])->name('portions.draft');
            Route::post('/portions/confirm', [PortionRequestController::class, 'confirmDirect'])->name('portions.confirm-direct');
            Route::post('/portions/{portionRequest}/confirm', [PortionRequestController::class, 'confirm'])->name('portions.confirm');
            Route::post('/portions/{portionRequest}/revise', [PortionRequestController::class, 'revise'])->name('portions.revise');
            Route::post('/portions/{portionRequest}/cancel', [PortionRequestController::class, 'cancel'])->name('portions.cancel');
        });

    // Resource with Policy Gate enforcement
    Route::get('/schools/{school}', [SchoolOverviewController::class, 'show'])->name('schools.show');
});
