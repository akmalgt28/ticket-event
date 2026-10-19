<?php

namespace App\Providers;

use App\Policies\ReportPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
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
        Gate::define('viewReports', [ReportPolicy::class, 'view']);

        Vite::prefetch(concurrency: 3);
    }
}
