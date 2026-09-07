<?php

namespace App\Services\Notion;

use Illuminate\Support\Facades\Cache;

class StudyExporter
{
    protected NotionClient $notion;
    protected string $databaseId;

    public function __construct(NotionClient $notion)
    {
        $this->notion = $notion;
        $this->databaseId = config('services.notion.study_database_id');
    }

    public function exportStudyNote(string $title, string $category, string $content, array $tags = []): array
    {
        $blocks = $this->parseContentToBlocks($content);

        $properties = [
            'title' => NotionPropertyBuilder::title('title', $title),
            'Categoria' => NotionPropertyBuilder::select('Categoria', $category),
            'Tags' => NotionPropertyBuilder::multiSelect('Tags', $tags),
            'Criado em' => NotionPropertyBuilder::date('Criado em', date('Y-m-d')),
        ];

        return $this->notion->createDatabasePage($this->databaseId, $properties, $blocks);
    }

    public function syncFromNotion(): array
    {
        $cacheKey = 'notion_studies_' . md5($this->databaseId);
        $cacheTtl = config('services.notion.cache_ttl', 3600);

        return Cache::remember($cacheKey, $cacheTtl, function () {
            $results = [];
            $hasMore = true;
            $cursor = null;

            while ($hasMore) {
                $params = ['page_size' => 100];
                if ($cursor) {
                    $params['start_cursor'] = $cursor;
                }

                $response = $this->notion->queryDatabase($this->databaseId);

                foreach ($response['results'] as $page) {
                    $results[] = $this->parsePageToStudy($page);
                }

                $hasMore = $response['has_more'] ?? false;
                $cursor = $response['next_cursor'] ?? null;
            }

            return $results;
        });
    }

    public function updateStudyNote(string $pageId, array $data): array
    {
        $properties = [];

        if (isset($data['title'])) {
            $properties['title'] = NotionPropertyBuilder::title('title', $data['title']);
        }

        if (isset($data['category'])) {
            $properties['Categoria'] = NotionPropertyBuilder::select('Categoria', $data['category']);
        }

        if (isset($data['tags'])) {
            $properties['Tags'] = NotionPropertyBuilder::multiSelect('Tags', $data['tags']);
        }

        return $this->notion->updatePage($pageId, $properties);
    }

    public function deleteStudyNote(string $pageId): array
    {
        Cache::forget('notion_studies_' . md5($this->databaseId));
        return $this->notion->archivePage($pageId);
    }

    protected function parseContentToBlocks(string $content): array
    {
        $blocks = [];
        $lines = explode("\n", $content);
        $inCodeBlock = false;
        $codeContent = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if (str_starts_with($trimmed, '```')) {
                if ($inCodeBlock) {
                    $blocks[] = NotionBlockBuilder::code(implode("\n", $codeContent), 'php');
                    $codeContent = [];
                }
                $inCodeBlock = !$inCodeBlock;
                continue;
            }

            if ($inCodeBlock) {
                $codeContent[] = $line;
                continue;
            }

            if (str_starts_with($trimmed, '# ')) {
                $blocks[] = NotionBlockBuilder::heading1(substr($trimmed, 2));
            } elseif (str_starts_with($trimmed, '## ')) {
                $blocks[] = NotionBlockBuilder::heading2(substr($trimmed, 3));
            } elseif (str_starts_with($trimmed, '### ')) {
                $blocks[] = NotionBlockBuilder::heading3(substr($trimmed, 4));
            } elseif (str_starts_with($trimmed, '- ')) {
                $blocks[] = NotionBlockBuilder::bulletedListItem(substr($trimmed, 2));
            } elseif (preg_match('/^\d+\.\s/', $trimmed)) {
                $blocks[] = NotionBlockBuilder::numberedListItem(preg_replace('/^\d+\.\s/', '', $trimmed));
            } elseif (str_starts_with($trimmed, '> ')) {
                $blocks[] = NotionBlockBuilder::quote(substr($trimmed, 2));
            } elseif (!empty($trimmed)) {
                $blocks[] = NotionBlockBuilder::paragraph($trimmed);
            }
        }

        return $blocks;
    }

    protected function parsePageToStudy(array $page): array
    {
        $title = '';
        if (isset($page['properties']['title']['title'])) {
            $title = $page['properties']['title']['title'][0]['plain_text'] ?? '';
        }

        $category = '';
        if (isset($page['properties']['Categoria']['select'])) {
            $category = $page['properties']['Categoria']['select']['name'] ?? '';
        }

        $tags = [];
        if (isset($page['properties']['Tags']['multi_select'])) {
            $tags = array_column($page['properties']['Tags']['multi_select'], 'name');
        }

        return [
            'id' => $page['id'],
            'title' => $title,
            'category' => $category,
            'tags' => $tags,
            'url' => $page['url'] ?? '',
            'created_time' => $page['created_time'] ?? null,
            'last_edited_time' => $page['last_edited_time'] ?? null,
        ];
    }

    public function getStudyContent(string $pageId): string
    {
        $response = $this->notion->getBlockChildren($pageId);
        $content = [];

        foreach ($response['results'] as $block) {
            $content[] = $this->blockToMarkdown($block);
        }

        return implode("\n", $content);
    }

    protected function blockToMarkdown(array $block): string
    {
        $type = $block['type'] ?? '';
        $blockData = $block[$type] ?? [];
        $richText = $blockData['rich_text'] ?? [];

        $text = implode('', array_map(function ($rt) {
            return $rt['plain_text'] ?? '';
        }, $richText));

        return match ($type) {
            'heading_1' => "# {$text}",
            'heading_2' => "## {$text}",
            'heading_3' => "### {$text}",
            'paragraph' => $text,
            'bulleted_list_item' => "- {$text}",
            'numbered_list_item' => "1. {$text}",
            'quote' => "> {$text}",
            'code' => "```\n{$text}\n```",
            'callout' => "> 💡 {$text}",
            'divider' => "---",
            default => '',
        };
    }
}
