<?php

namespace App\Telemetry;

use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\API\Trace\StatusCode;

class SpanWrapper
{
    protected SpanInterface $span;
    protected bool $ended = false;

    public function __construct(SpanInterface $span)
    {
        $this->span = $span;
    }

    public function setAttribute(string $key, mixed $value): self
    {
        if (!$this->ended) {
            $this->span->setAttribute($key, $value);
        }
        return $this;
    }

    public function setStatus(int $code, ?string $description = null): self
    {
        if (!$this->ended) {
            $this->span->setStatus(StatusCode::fromCode($code), $description);
        }
        return $this;
    }

    public function recordException(\Throwable $exception): self
    {
        if (!$this->ended) {
            $this->span->recordException($exception);
            $this->span->setStatus(StatusCode::STATUS_ERROR, $exception->getMessage());
        }
        return $this;
    }

    public function addEvent(string $name, array $attributes = []): self
    {
        if (!$this->ended) {
            $this->span->addEvent($name, $attributes);
        }
        return $this;
    }

    public function end(): void
    {
        if (!$this->ended) {
            $this->span->end();
            $this->ended = true;
        }
    }

    public function __destruct()
    {
        $this->end();
    }
}
