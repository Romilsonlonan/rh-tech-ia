<?php

namespace App\AI\MCP;

use Illuminate\Support\Facades\Http;
use Exception;

class MCPClient implements MCPClientInterface
{
    protected string $serverUrl;
    protected bool $connected = false;
    protected ?string $sessionId = null;

    public function __construct(string $serverUrl = '')
    {
        $this->serverUrl = $serverUrl;
    }

    public function connect(string $serverUrl): bool
    {
        try {
            $this->serverUrl = $serverUrl;
            $response = Http::timeout(10)->post($serverUrl . '/initialize', [
                'protocolVersion' => '2024-11-05',
                'capabilities' => ['tools' => true, 'resources' => true],
            ]);

            if ($response->successful()) {
                $this->connected = true;
                $this->sessionId = $response->json('sessionId');
                return true;
            }
        } catch (Exception $e) {
            report($e);
        }

        return false;
    }

    public function disconnect(): void
    {
        $this->connected = false;
        $this->sessionId = null;
    }

    public function sendRequest(string $tool, array $params = []): array
    {
        if (!$this->connected) {
            throw new Exception('MCP Client not connected');
        }

        $response = Http::timeout(30)->post($this->serverUrl . '/v1/tools/call', [
            'name' => $tool,
            'arguments' => $params,
            'sessionId' => $this->sessionId,
        ]);

        return $response->successful() ? $response->json() : [];
    }

    public function listTools(): array
    {
        if (!$this->connected) {
            return [];
        }

        $response = Http::timeout(10)->get($this->serverUrl . '/v1/tools');
        return $response->successful() ? $response->json('tools', []) : [];
    }

    public function isConnected(): bool
    {
        return $this->connected;
    }
}
