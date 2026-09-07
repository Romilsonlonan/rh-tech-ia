<?php

namespace App\Telemetry;

use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SDK\Trace\SpanProcessor\BatchSpanProcessor;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Resource\ResourceInfoFactory;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SemConv\ResourceAttributes;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\Contrib\Otlp\SpanExporter;

class TelemetryConfig
{
    public static function configure(): void
    {
        if (!self::isEnabled()) {
            return;
        }

        $exporter = self::createExporter();
        $spanProcessor = new SimpleSpanProcessor($exporter);
        
        $resource = ResourceInfoFactory::defaultResource()->merge(
            ResourceInfo::create(Attributes::create([
                ResourceAttributes::SERVICE_NAME => 'rhtechia-backend',
                ResourceAttributes::SERVICE_VERSION => '1.0.0',
                ResourceAttributes::DEPLOYMENT_ENVIRONMENT => self::getEnvironment(),
            ]))
        );

        $tracerProvider = TracerProvider::builder()
            ->setResource($resource)
            ->addSpanProcessor($spanProcessor)
            ->build();

        \OpenTelemetry\API\Globals::registerInitializer(
            fn () => $tracerProvider
        );
    }

    protected static function isEnabled(): bool
    {
        return env('OTEL_ENABLED', false) === true;
    }

    protected static function createExporter()
    {
        $endpoint = env('OTEL_EXPORTER_OTLP_ENDPOINT', 'http://localhost:4318');
        $transport = (new OtlpHttpTransportFactory())->create(
            $endpoint . '/v1/traces',
            'application/x-protobuf'
        );

        return new SpanExporter($transport);
    }

    protected static function getEnvironment(): string
    {
        return env('APP_ENV', 'production');
    }
}
