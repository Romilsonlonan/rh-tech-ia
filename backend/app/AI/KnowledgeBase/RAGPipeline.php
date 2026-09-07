<?php

namespace App\AI\KnowledgeBase;

use App\AI\VectorStore\VectorStoreInterface;
use Exception;

class RAGPipeline
{
    public function __construct(
        protected VectorStoreInterface $vectorStore,
        protected EmbeddingServiceInterface $embeddingService,
        protected LLMServiceInterface $llmService
    ) {}

    public function ingestDocument(string $content, array $metadata = []): bool
    {
        try {
            $chunks = $this->chunkText($content);
            $embeddings = $this->embeddingService->embedTexts($chunks);

            $documents = array_map(function ($chunk, $embedding) use ($metadata) {
                return [
                    'content' => $chunk,
                    'embedding' => $embedding,
                    'metadata' => $metadata,
                ];
            }, $chunks, $embeddings);

            return $this->vectorStore->insert($documents);
        } catch (Exception $e) {
            report($e);
            return false;
        }
    }

    public function query(string $question, int $contextLimit = 5): string
    {
        $questionEmbedding = $this->embeddingService->embedText($question);
        $contextDocs = $this->vectorStore->search($questionEmbedding, $contextLimit);
        
        $context = implode("\n\n", array_column($contextDocs, 'content'));
        
        return $this->llmService->generate(
            $this->buildPrompt($question, $context)
        );
    }

    protected function chunkText(string $text, int $chunkSize = 500, int $overlap = 50): array
    {
        $words = explode(' ', $text);
        $chunks = [];
        
        for ($i = 0; $i < count($words); $i += $chunkSize - $overlap) {
            $chunk = implode(' ', array_slice($words, $i, $chunkSize));
            if (!empty(trim($chunk))) {
                $chunks[] = trim($chunk);
            }
        }
        
        return $chunks;
    }

    protected function buildPrompt(string $question, string $context): string
    {
        return <<<EOT
        Context:
        {$context}

        Question: {$question}

        Based only on the context above, answer the question. If the context doesn't contain relevant information, say so.
        EOT;
    }
}
