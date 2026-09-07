<?php

namespace App\AI\KnowledgeBase;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Exception;

class OpenAIEmbeddingService implements EmbeddingServiceInterface
{
    protected string $apiKey;
    protected string $model;
    protected int $dimension;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
        $this->model = config('services.openai.embedding_model', 'text-embedding-3-small');
        $this->dimension = config('services.openai.embedding_dimensions', 1536);
    }

    public function embedText(string $text): array
    {
        $cacheKey = 'embedding_' . md5($text);
        
        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(30)
                ->post('https://api.openai.com/v1/embeddings', [
                    'input' => $text,
                    'model' => $this->model,
                ]);

            if ($response->successful()) {
                $embedding = $response->json('data.0.embedding');
                Cache::put($cacheKey, $embedding, now()->addDays(30));
                return $embedding;
            }
        } catch (Exception $e) {
            report($e);
        }

        return [];
    }

    public function embedTexts(array $texts): array
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(60)
                ->post('https://api.openai.com/v1/embeddings', [
                    'input' => $texts,
                    'model' => $this->model,
                ]);

            if ($response->successful()) {
                return array_column($response->json('data'), 'embedding');
            }
        } catch (Exception $e) {
            report($e);
        }

        return [];
    }

    public function getDimension(): int
    {
        return $this->dimension;
    }
}
