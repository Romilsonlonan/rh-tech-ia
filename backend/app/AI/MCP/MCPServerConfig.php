<?php

namespace App\AI\MCP;

class MCPServerConfig
{
    public function __construct(
        public readonly string $name,
        public readonly string $command,
        public readonly array $args = [],
        public readonly array $env = [],
        public readonly string $url = ''
    ) {}

    public static function fromArray(array $config): self
    {
        return new self(
            name: $config['name'],
            command: $config['command'],
            args: $config['args'] ?? [],
            env: $config['env'] ?? [],
            url: $config['url'] ?? ''
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'command' => $this->command,
            'args' => $this->args,
            'env' => $this->env,
            'url' => $this->url,
        ];
    }
}
