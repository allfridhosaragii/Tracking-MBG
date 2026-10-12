<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Driver\DashboardController as DriverDashboardController;
use App\Http\Controllers\Head\DashboardController as HeadDashboardController;
use App\Http\Controllers\Nutrition\DashboardController as NutritionDashboardController;
use App\Http\Controllers\Production\DashboardController as ProductionDashboardController;
use App\Http\Controllers\Public\SchoolPublicController;
use App\Http\Controllers\School\DashboardController as SchoolDashboardController;
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

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

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
        });

    // 2. Pengawas Gizi Module
    Route::prefix('nutrition')
        ->name('nutrition.')
        ->middleware('role:PENGAWAS_GIZI')
        ->group(function () {
            Route::get('/dashboard', NutritionDashboardController::class)->name('dashboard');
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
        });

    // Resource with Policy Gate enforcement
    Route::get('/schools/{school}', [SchoolOverviewController::class, 'show'])->name('schools.show');
});
