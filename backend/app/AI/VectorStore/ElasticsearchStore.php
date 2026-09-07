<?php

namespace App\AI\VectorStore;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Exception;

class ElasticsearchStore implements VectorStoreInterface
{
    protected Client $client;
    protected string $index;

    public function __construct(string $index = 'knowledge_base')
    {
        $this->client = ClientBuilder::create()->build();
        $this->index = $index;
    }

    public function insert(array $documents): bool
    {
        try {
            $params = ['body' => []];
            
            foreach ($documents as $doc) {
                $params['body'][] = [
                    'index' => ['_index' => $this->index],
                ];
                $params['body'][] = [
                    'content' => $doc['content'],
                    'embedding' => $doc['embedding'],
                    'metadata' => $doc['metadata'] ?? [],
                    'collection' => $doc['collection'] ?? 'default',
                ];
            }

            $this->client->bulk($params);
            return true;
        } catch (Exception $e) {
            report($e);
            return false;
        }
    }

    public function search(array $embedding, int $topK = 5): array
    {
        try {
            $response = $this->client->search([
                'index' => $this->index,
                'body' => [
                    'size' => $topK,
                    'query' => [
                        'knn' => [
                            'field' => 'embedding',
                            'vector' => $embedding,
                            'k' => $topK,
                        ],
                    ],
                ],
            ]);

            return $response['hits']['hits'];
        } catch (Exception $e) {
            report($e);
            return [];
        }
    }

    public function delete(string $id): bool
    {
        try {
            $this->client->delete(['index' => $this->index, 'id' => $id]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getById(string $id): ?array
    {
        try {
            $response = $this->client->get(['index' => $this->index, 'id' => $id]);
            return $response['_source'];
        } catch (Exception $e) {
            return null;
        }
    }
}
