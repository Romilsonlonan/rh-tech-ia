<?php

namespace App\AI\VectorStore;

enum VectorStoreType: string
{
    case PGVECTOR = 'pgvector';
    case ELASTICSEARCH = 'elasticsearch';
    case QDRANT = 'qdrant';
    case CHROMADB = 'chromadb';

    public function getStore(int $dimension = 1536): VectorStoreInterface
    {
        return match($this) {
            self::PGVECTOR => new PgVectorStore($dimension),
            self::ELASTICSEARCH => new ElasticsearchStore(),
            self::QDRANT => new QdrantStore(),
            self::CHROMADB => new ChromaDbStore(),
        };
    }
}
