<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

class RateLimitingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->configureRateLimiting();
    }

    public function boot(): void
    {
        //
    }

    protected function configureRateLimiting(): void
    {
        // API Global
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        // Login attempts
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip() . '|' . $request->email);
        });

        // Registration
        RateLimiter::for('register', function (Request $request) {
            return Limit::perHour(3)->by($request->ip());
        });

        // Password reset
        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perHour(3)->by($request->ip() . '|' . $request->email);
        });
    }
}
