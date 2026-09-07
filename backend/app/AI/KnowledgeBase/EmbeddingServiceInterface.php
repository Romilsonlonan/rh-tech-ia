<?php

namespace App\AI\KnowledgeBase;

interface EmbeddingServiceInterface
{
    public function embedText(string $text): array;
    public function embedTexts(array $texts): array;
    public function getDimension(): int;
}
