<?php

namespace App\AI\MCP;

interface MCPClientInterface
{
    public function connect(string $serverUrl): bool;
    public function disconnect(): void;
    public function sendRequest(string $tool, array $params = []): array;
    public function listTools(): array;
    public function isConnected(): bool;
}
