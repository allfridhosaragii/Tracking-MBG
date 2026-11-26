<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * Display the operational login workspace with dynamic multi-school PIC presets in dev/testing.
     */
    public function showLoginForm(): View
    {
        $enablePresets = filter_var(env('ENABLE_DEMO_PRESETS', false), FILTER_VALIDATE_BOOLEAN);
        $isProduction = (app()->isProduction() || config('app.env') === 'production') && ! $enablePresets;

        $sampleSchoolSlug = null;
        $publicSchools = collect();
        $staffPresets = collect();
        $picPresets = collect();

        try {
            $publicSchools = School::query()
                ->where('is_active', true)
                ->whereNotNull('public_slug')
                ->orderBy('id')
                ->get(['id', 'name', 'school_code', 'education_level', 'public_slug']);

            $sampleSchoolSlug = $publicSchools->first()?->public_slug;

            if (! $isProduction) {
                $staffPresets = User::query()
                    ->where('is_active', true)
                    ->whereIn('role', [
                        UserRole::KEPALA_SPPG->value,
                        UserRole::PENGAWAS_GIZI->value,
                        UserRole::ASISTEN_LAPANGAN->value,
                        UserRole::KURIR->value,
                    ])
                    ->orderBy('id')
                    ->get(['id', 'name', 'email', 'role', 'sppg_id']);

                $picPresets = User::query()
                    ->where('is_active', true)
                    ->where('role', UserRole::PIC_SEKOLAH->value)
                    ->with('school:id,name,school_code,education_level')
                    ->orderBy('school_id')
                    ->orderBy('id')
                    ->get(['id', 'name', 'email', 'role', 'school_id']);
            }
        } catch (\Throwable) {
            $sampleSchoolSlug = null;
        }

        return view('auth.login', [
            'isProduction' => $isProduction,
            'sampleSchoolSlug' => $sampleSchoolSlug,
            'publicSchools' => $publicSchools,
            'staffPresets' => $staffPresets,
            'picPresets' => $picPresets,
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = Str::transliterate(Str::lower($credentials['email']) . '|' . $request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'email' => trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ]);
        }

        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages([
                'email' => 'Kombinasi email atau kata sandi tidak cocok dengan data kami.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Akun Anda berstatus nonaktif. Akses operasional MBG-Track ditolak.',
            ]);
        }

        $user->forceFill([
            'last_login_at' => now(),
        ])->save();

        $request->session()->regenerate();

        $targetRoute = $user->role ? $user->role->dashboardRoute() : 'login';

        return redirect()->intended(route($targetRoute));
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Anda telah berhasil keluar dari sistem operasional.');
    }
}
