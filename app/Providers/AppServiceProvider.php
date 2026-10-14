<?php

namespace App\Providers;

use App\Models\DailyPortionRequest;
use App\Policies\DailyPortionRequestPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(DailyPortionRequest::class, DailyPortionRequestPolicy::class);
        Gate::policy(\App\Models\TrayReturn::class, \App\Policies\TrayReturnPolicy::class);
        Gate::policy(\App\Models\Incident::class, \App\Policies\IncidentPolicy::class);

        if (app()->environment('production') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }
}
