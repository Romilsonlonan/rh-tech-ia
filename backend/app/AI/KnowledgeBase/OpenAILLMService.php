<?php

namespace App\AI\KnowledgeBase;

use Illuminate\Support\Facades\Http;
use Exception;

class OpenAILLMService implements LLMServiceInterface
{
    protected string $apiKey;
    protected string $model;
    protected float $temperature;
    protected int $maxTokens;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
        $this->model = config('services.openai.chat_model', 'gpt-4o');
        $this->temperature = (float) config('services.openai.temperature', 0.7);
        $this->maxTokens = (int) config('services.openai.max_tokens', 2000);
    }

    public function generate(string $prompt, array $options = []): string
    {
        return $this->chat([
            ['role' => 'user', 'content' => $prompt]
        ], $options);
    }

    public function chat(array $messages, array $options = []): string
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(60)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $options['model'] ?? $this->model,
                    'messages' => $messages,
                    'temperature' => $options['temperature'] ?? $this->temperature,
                    'max_tokens' => $options['max_tokens'] ?? $this->maxTokens,
                ]);

            if ($response->successful()) {
                return $response->json('choices.0.message.content');
            }
        } catch (Exception $e) {
            report($e);
        }

        return '';
    }
}
