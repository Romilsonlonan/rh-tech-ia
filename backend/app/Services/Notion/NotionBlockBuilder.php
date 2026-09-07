<?php

namespace App\Services\Notion;

class NotionBlockBuilder
{
    public static function heading1(string $text): array
    {
        return [
            'object' => 'block',
            'type' => 'heading_1',
            'heading_1' => [
                'rich_text' => [[
                    'type' => 'text',
                    'text' => ['content' => $text],
                ]],
            ],
        ];
    }

    public static function heading2(string $text): array
    {
        return [
            'object' => 'block',
            'type' => 'heading_2',
            'heading_2' => [
                'rich_text' => [[
                    'type' => 'text',
                    'text' => ['content' => $text],
                ]],
            ],
        ];
    }

    public static function heading3(string $text): array
    {
        return [
            'object' => 'block',
            'type' => 'heading_3',
            'heading_3' => [
                'rich_text' => [[
                    'type' => 'text',
                    'text' => ['content' => $text],
                ]],
            ],
        ];
    }

    public static function paragraph(string $text, array $annotations = []): array
    {
        $richText = [[
            'type' => 'text',
            'text' => ['content' => $text],
        ]];

        if (!empty($annotations)) {
            $richText[0]['annotations'] = $annotations;
        }

        return [
            'object' => 'block',
            'type' => 'paragraph',
            'paragraph' => [
                'rich_text' => $richText,
            ],
        ];
    }

    public static function code(string $code, string $language = 'php'): array
    {
        return [
            'object' => 'block',
            'type' => 'code',
            'code' => [
                'rich_text' => [[
                    'type' => 'text',
                    'text' => ['content' => $code],
                ]],
                'language' => $language,
            ],
        ];
    }

    public static function bulletedListItem(string $text): array
    {
        return [
            'object' => 'block',
            'type' => 'bulleted_list_item',
            'bulleted_list_item' => [
                'rich_text' => [[
                    'type' => 'text',
                    'text' => ['content' => $text],
                ]],
            ],
        ];
    }

    public static function numberedListItem(string $text): array
    {
        return [
            'object' => 'block',
            'type' => 'numbered_list_item',
            'numbered_list_item' => [
                'rich_text' => [[
                    'type' => 'text',
                    'text' => ['content' => $text],
                ]],
            ],
        ];
    }

    public static function quote(string $text): array
    {
        return [
            'object' => 'block',
            'type' => 'quote',
            'quote' => [
                'rich_text' => [[
                    'type' => 'text',
                    'text' => ['content' => $text],
                ]],
            ],
        ];
    }

    public static function callout(string $text, string $icon = '💡'): array
    {
        return [
            'object' => 'block',
            'type' => 'callout',
            'callout' => [
                'rich_text' => [[
                    'type' => 'text',
                    'text' => ['content' => $text],
                ]],
                'icon' => [
                    'emoji' => $icon,
                ],
            ],
        ];
    }

    public static function divider(): array
    {
        return [
            'object' => 'block',
            'type' => 'divider',
            'divider' => new \stdClass(),
        ];
    }

    public static function toDo(bool $checked, string $text): array
    {
        return [
            'object' => 'block',
            'type' => 'to_do',
            'to_do' => [
                'checked' => $checked,
                'rich_text' => [[
                    'type' => 'text',
                    'text' => ['content' => $text],
                ]],
            ],
        ];
    }

    public static function link(string $text, string $url): array
    {
        return [
            'object' => 'block',
            'type' => 'paragraph',
            'paragraph' => [
                'rich_text' => [[
                    'type' => 'text',
                    'text' => [
                        'content' => $text,
                        'link' => ['url' => $url],
                    ],
                ]],
            ],
        ];
    }
}
