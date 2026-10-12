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
    }
}
