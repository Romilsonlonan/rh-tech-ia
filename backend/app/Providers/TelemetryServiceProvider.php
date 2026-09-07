<?php

namespace App\Providers;

use App\Telemetry\OpenTelemetryService;
use App\Telemetry\TelemetryConfig;
use Illuminate\Support\ServiceProvider;

class TelemetryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(OpenTelemetryService::class, function () {
            return new OpenTelemetryService();
        });
    }

    public function boot(): void
    {
        if (app()->runningInConsole()) {
            return;
        }

        TelemetryConfig::configure();
    }
}
