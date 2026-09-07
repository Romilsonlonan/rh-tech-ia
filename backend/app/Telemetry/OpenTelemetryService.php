<?php

namespace App\Telemetry;

use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\Context\Context;
use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Resource\ResourceInfoFactory;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SemConv\ResourceAttributes;

class OpenTelemetryService
{
    protected TracerInterface $tracer;
    protected bool $enabled = false;

    public function __construct()
    {
        $this->enabled = $this->isEnabled();
        
        if ($this->enabled) {
            $this->initializeTracer();
        }
    }

    protected function isEnabled(): bool
    {
        return env('OTEL_ENABLED', false) === true;
    }

    protected function initializeTracer(): void
    {
        $this->tracer = Globals::tracerProvider()->getTracer(
            'rhtechia-backend',
            '1.0.0'
        );
    }

    public function startSpan(string $name, array $attributes = []): ?SpanWrapper
    {
        if (!$this->enabled) {
            return null;
        }

        $span = $this->tracer->spanBuilder($name)
            ->setSpanKind(SpanKind::KIND_INTERNAL)
            ->startSpan();

        foreach ($attributes as $key => $value) {
            $span->setAttribute($key, $value);
        }

        return new SpanWrapper($span);
    }

    public function startHttpSpan(string $name, string $method, string $uri): ?SpanWrapper
    {
        if (!$this->enabled) {
            return null;
        }

        $span = $this->tracer->spanBuilder($name)
            ->setSpanKind(SpanKind::KIND_SERVER)
            ->startSpan();

        $span->setAttribute('http.method', $method);
        $span->setAttribute('http.url', $uri);
        $span->setAttribute('http.route', $uri);

        return new SpanWrapper($span);
    }

    public function startDbSpan(string $name, string $query, string $connection): ?SpanWrapper
    {
        if (!$this->enabled) {
            return null;
        }

        $span = $this->tracer->spanBuilder($name)
            ->setSpanKind(SpanKind::KIND_CLIENT)
            ->startSpan();

        $span->setAttribute('db.system', 'postgresql');
        $span->setAttribute('db.connection', $connection);
        $span->setAttribute('db.statement', $query);

        return new SpanWrapper($span);
    }

    public function addSpanEvent(string $name, array $attributes = []): void
    {
        if (!$this->enabled) {
            return;
        }
    }

    public function recordException(\Throwable $exception, ?SpanWrapper $span = null): void
    {
        if (!$this->enabled) {
            return;
        }

        if ($span) {
            $span->recordException($exception);
        }
    }
}
