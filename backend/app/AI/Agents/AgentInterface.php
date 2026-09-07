<?php

namespace App\AI\Agents;

interface AgentInterface
{
    public function execute(array $context = []): AgentResponse;
    public function getName(): string;
    public function getDescription(): string;
}
