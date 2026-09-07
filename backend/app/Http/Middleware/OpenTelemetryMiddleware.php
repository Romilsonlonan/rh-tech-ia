<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Telemetry\OpenTelemetryService;
use Symfony\Component\HttpFoundation\Response;

class OpenTelemetryMiddleware
{
    public function __construct(
        protected OpenTelemetryService $telemetry
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $span = $this->telemetry->startHttpSpan(
            'HTTP ' . $request->method(),
            $request->method(),
            $request->fullUrl()
        );

        try {
            $response = $next($request);

            if ($span) {
                $span->setAttribute('http.status_code', $response->getStatusCode());
                
                if ($response->getStatusCode() >= 400) {
                    $span->setStatus(2, 'Error');
                } else {
                    $span->setStatus(1, 'OK');
                }
            }

            return $response;
        } catch (\Throwable $e) {
            if ($span) {
                $span->recordException($e);
            }
            throw $e;
        } finally {
            if ($span) {
                $span->end();
            }
        }
    }
}
