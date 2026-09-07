<?php

namespace App\Services\Notion;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Exception;

class NotionClient
{
    protected string $apiKey;
    protected string $baseUrl = 'https://api.notion.com/v1';
    protected string $notionVersion = '2022-06-28';
    protected int $timeout = 30;

    public function __construct()
    {
        $this->apiKey = config('services.notion.api_key');
    }

    public function setApiKey(string $apiKey): self
    {
        $this->apiKey = $apiKey;
        return $this;
    }

    protected function getHeaders(): array
    {
        return [
            'Authorization' => "Bearer {$this->apiKey}",
            'Notion-Version' => $this->notionVersion,
            'Content-Type' => 'application/json',
        ];
    }

    public function search(string $query, array $options = []): array
    {
        return $this->post('/search', array_merge([
            'query' => $query,
        ], $options));
    }

    public function getPage(string $pageId): array
    {
        return $this->get("/pages/{$pageId}");
    }

    public function getPageProperties(string $pageId): array
    {
        return $this->get("/pages/{$pageId}/properties");
    }

    public function createPage(array $properties, string $parentId, array $children = []): array
    {
        return $this->post('/pages', [
            'parent' => ['page_id' => $parentId],
            'properties' => $properties,
            'children' => $children,
        ]);
    }

    public function createDatabasePage(string $databaseId, array $properties, array $children = []): array
    {
        return $this->post('/pages', [
            'parent' => ['database_id' => $databaseId],
            'properties' => $properties,
            'children' => $children,
        ]);
    }

    public function updatePage(string $pageId, array $properties): array
    {
        return $this->patch("/pages/{$pageId}", [
            'properties' => $properties,
        ]);
    }

    public function archivePage(string $pageId): array
    {
        return $this->patch("/pages/{$pageId}", [
            'archived' => true,
        ]);
    }

    public function getBlockChildren(string $blockId): array
    {
        return $this->get("/blocks/{$blockId}/children");
    }

    public function appendBlockChildren(string $blockId, array $children): array
    {
        return $this->patch("/blocks/{$blockId}/children", [
            'children' => $children,
        ]);
    }

    public function getDatabase(string $databaseId): array
    {
        return $this->get("/databases/{$databaseId}");
    }

    public function queryDatabase(string $databaseId, array $filter = [], array $sorts = [], int $pageSize = 100): array
    {
        $payload = ['page_size' => $pageSize];

        if (!empty($filter)) {
            $payload['filter'] = $filter;
        }

        if (!empty($sorts)) {
            $payload['sorts'] = $sorts;
        }

        return $this->post("/databases/{$databaseId}/query", $payload);
    }

    public function getUsers(): array
    {
        return $this->get('/users');
    }

    public function getUser(string $userId): array
    {
        return $this->get("/users/{$userId}");
    }

    public function getComment(string $commentId): array
    {
        return $this->get("/comments/{$commentId}");
    }

    public function getBlock(string $blockId): array
    {
        return $this->get("/blocks/{$blockId}");
    }

    public function deleteBlock(string $blockId): array
    {
        return $this->delete("/blocks/{$blockId}");
    }

    public function getCommentChildren(string $blockId, array $filter = []): array
    {
        $params = [];
        if (!empty($filter)) {
            $params['filter'] = $filter;
        }

        return $this->get("/comments", $params);
    }

    protected function get(string $endpoint, array $params = []): array
    {
        $url = $this->baseUrl . $endpoint;

        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout($this->timeout)
                ->get($url);

            return $this->handleResponse($response);
        } catch (Exception $e) {
            throw new NotionException("GET {$endpoint}: " . $e->getMessage());
        }
    }

    protected function post(string $endpoint, array $data = []): array
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout($this->timeout)
                ->post($this->baseUrl . $endpoint, $data);

            return $this->handleResponse($response);
        } catch (Exception $e) {
            throw new NotionException("POST {$endpoint}: " . $e->getMessage());
        }
    }

    protected function patch(string $endpoint, array $data = []): array
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout($this->timeout)
                ->patch($this->baseUrl . $endpoint, $data);

            return $this->handleResponse($response);
        } catch (Exception $e) {
            throw new NotionException("PATCH {$endpoint}: " . $e->getMessage());
        }
    }

    protected function delete(string $endpoint): array
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout($this->timeout)
                ->delete($this->baseUrl . $endpoint);

            return $this->handleResponse($response);
        } catch (Exception $e) {
            throw new NotionException("DELETE {$endpoint}: " . $e->getMessage());
        }
    }

    protected function handleResponse($response): array
    {
        if ($response->successful()) {
            return $response->json();
        }

        $status = $response->status();
        $body = $response->json();

        $message = $body['message'] ?? $body['error']['message'] ?? 'Unknown error';

        throw new NotionException("HTTP {$status}: {$message}", $status);
    }
}
