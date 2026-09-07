<?php

namespace App\AI\VectorStore;

interface VectorStoreInterface
{
    public function insert(array $documents): bool;
    public function search(array $embedding, int $topK = 5): array;
    public function delete(string $id): bool;
    public function getById(string $id): ?array;
}
