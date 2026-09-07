<?php

namespace App\AI\Agents;

class AgentResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly array $data = [],
        public readonly array $errors = []
    ) {}

    public static function success(string $message, array $data = []): self
    {
        return new self(true, $message, $data);
    }

    public static function failure(string $message, array $errors = []): self
    {
        return new self(false, $message, [], $errors);
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'data' => $this->data,
            'errors' => $this->errors,
        ];
    }
}
