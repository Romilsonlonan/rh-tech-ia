<?php

namespace App\AI\KnowledgeBase;

interface LLMServiceInterface
{
    public function generate(string $prompt, array $options = []): string;
    public function chat(array $messages, array $options = []): string;
}
