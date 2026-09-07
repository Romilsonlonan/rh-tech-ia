<?php

use App\Providers\AppServiceProvider;
use App\Providers\RateLimitingServiceProvider;
use App\Providers\TelemetryServiceProvider;

return [
    AppServiceProvider::class,
    RateLimitingServiceProvider::class,
    TelemetryServiceProvider::class,
];
