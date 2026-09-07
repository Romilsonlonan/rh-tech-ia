<?php

namespace App\AI\VectorStore;

use Pgvector\Laravel\Vector;
use App\Models\Document;
use Exception;

class PgVectorStore implements VectorStoreInterface
{
    protected int $embeddingDimension;

    public function __construct(int $dimension = 1536)
    {
        $this->embeddingDimension = $dimension;
    }

    public function insert(array $documents): bool
    {
        try {
            foreach ($documents as $doc) {
                Document::create([
                    'content' => $doc['content'],
                    'embedding' => new Vector($doc['embedding']),
                    'metadata' => $doc['metadata'] ?? [],
                    'collection' => $doc['collection'] ?? 'default',
                ]);
            }
            return true;
        } catch (Exception $e) {
            report($e);
            return false;
        }
    }

    public function search(array $embedding, int $topK = 5): array
    {
        $vector = new Vector($embedding);

        return Document::where('collection', 'default')
            ->orderByRaw('embedding <=> ?', [$vector])
            ->limit($topK)
            ->get()
            ->toArray();
    }

    public function delete(string $id): bool
    {
        $doc = Document::find($id);
        return $doc ? $doc->delete() : false;
    }

    public function getById(string $id): ?array
    {
        return Document::find($id)?->toArray();
    }
}
