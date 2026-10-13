<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CrossRole\BatchDetailController;
use App\Http\Controllers\CrossRole\TripDetailController;
use App\Http\Controllers\Driver\DashboardController as DriverDashboardController;
use App\Http\Controllers\Driver\DriverTripController;
use App\Http\Controllers\EvidencePhotoController;
use App\Http\Controllers\Head\DashboardController as HeadDashboardController;
use App\Http\Controllers\Head\DistributionController;
use App\Http\Controllers\Head\EmployeeManagementController;
use App\Http\Controllers\Head\PortionMonitoringController;
use App\Http\Controllers\Head\ProductionPlanController;
use App\Http\Controllers\Head\SchoolManagementController;
use App\Http\Controllers\Nutrition\DashboardController as NutritionDashboardController;
use App\Http\Controllers\Nutrition\MenuManagementController;
use App\Http\Controllers\Nutrition\QualityControlController;
use App\Http\Controllers\Production\BatchExecutionController;
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

            // PBI-08: Distribution & Fleet Control (Page K05)
            Route::get('/distribution', [DistributionController::class, 'index'])->name('distribution.index');
            Route::post('/distribution/trips', [DistributionController::class, 'storeTrip'])->name('distribution.trips.store');
            Route::post('/distribution/trips/{trip}/cancel', [DistributionController::class, 'cancelTrip'])->name('distribution.trips.cancel');
            Route::post('/distribution/trips/{trip}/verify-loading', [DistributionController::class, 'verifyLoading'])->name('distribution.trips.verify-loading');
            Route::post('/distribution/trips/{trip}/approve-dispatch', [DistributionController::class, 'approveDispatch'])->name('distribution.trips.approve-dispatch');
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

            // PBI-06: Quality Control Workspace (Page G03)
            Route::get('/quality-control', [QualityControlController::class, 'index'])->name('qc.index');
            Route::get('/quality-control/batches/{batch}', [QualityControlController::class, 'show'])->name('qc.show');
            Route::post('/quality-control/batches/{batch}/inspect', [QualityControlController::class, 'storeCheck'])->name('qc.inspect');
        });

    // 3. Asisten Lapangan Module
    Route::prefix('production')
        ->name('production.')
        ->middleware('role:ASISTEN_LAPANGAN')
        ->group(function () {
            Route::get('/dashboard', ProductionDashboardController::class)->name('dashboard');

            // PBI-06: Kitchen Execution & Batch Tracking (Page A02)
            Route::get('/plans/{plan}/execute', [BatchExecutionController::class, 'showExecutionWorkspace'])->name('plans.execute');
            Route::post('/plans/{plan}/batches', [BatchExecutionController::class, 'storeBatch'])->name('plans.batches.store');
            Route::post('/batches/{batch}/stage', [BatchExecutionController::class, 'advanceStage'])->name('batches.stage');
            Route::post('/batches/{batch}/actual', [BatchExecutionController::class, 'recordActual'])->name('batches.actual');
            Route::post('/batches/{batch}/submit-qc', [BatchExecutionController::class, 'submitToQc'])->name('batches.submit-qc');
        });

    // 4. Kurir Module
    Route::prefix('driver')
        ->name('driver.')
        ->middleware('role:KURIR')
        ->group(function () {
            Route::get('/dashboard', DriverDashboardController::class)->name('dashboard');

            // PBI-08: Driver Trips & Pre-trip Inspection (Pages D01, D02)
            Route::get('/trips', [DriverTripController::class, 'index'])->name('trips.index');
            Route::get('/trips/{trip}/prepare', [DriverTripController::class, 'prepare'])->name('trips.prepare');
            Route::post('/trips/{trip}/vehicle-check', [DriverTripController::class, 'vehicleCheck'])->name('trips.vehicle-check');
            Route::post('/trips/{trip}/start', [DriverTripController::class, 'start'])->name('trips.start');

            // PBI-09: Real GPS Telemetry & Active Trip Navigation (Page D03)
            Route::get('/trips/{trip}/active', [DriverTripController::class, 'active'])->name('trips.active');
            Route::post('/trips/{trip}/telemetry', [DriverTripController::class, 'telemetry'])->name('trips.telemetry');
            Route::post('/trips/{trip}/stops/{stop}/arrive', [DriverTripController::class, 'arrive'])->name('trips.stops.arrive');
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

    // Cross-role PBI-07: Batch Traceability (Page C02)
    Route::get('/batches/{batch}', [BatchDetailController::class, 'show'])->name('batches.show');

    // Cross-role PBI-08: Trip Detail & Multi-stop (Page C03)
    Route::get('/trips/{trip}', [TripDetailController::class, 'show'])->name('trips.show');
    Route::get('/trips/{trip}/map-data', [TripDetailController::class, 'mapData'])->name('trips.map-data');

    // Private Evidence Photos with Policy Gate enforcement
    Route::get('/evidence-photos/{evidencePhoto}', [EvidencePhotoController::class, 'show'])->name('evidence-photos.show');

    // Resource with Policy Gate enforcement
    Route::get('/schools/{school}', [SchoolOverviewController::class, 'show'])->name('schools.show');
});

// Public Sanitized Delivery Map (No authentication required, rate limited)
Route::get('/public/trips/{tripCode}/map', [\App\Http\Controllers\Public\PublicDeliveryMapController::class, 'show'])
    ->name('public.trips.map')
    ->middleware('throttle:60,1');
Route::get('/public/trips/{tripCode}/map-data', [\App\Http\Controllers\Public\PublicDeliveryMapController::class, 'mapData'])
    ->name('public.trips.map-data')
    ->middleware('throttle:60,1');

