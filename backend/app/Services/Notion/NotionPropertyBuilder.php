<?php

namespace App\Services\Notion;

class NotionPropertyBuilder
{
    public static function title(string $name, string $content): array
    {
        return [
            'type' => 'title',
            'title' => [[
                'type' => 'text',
                'text' => ['content' => $content],
            ]],
        ];
    }

    public static function richText(string $name, string $content): array
    {
        return [
            'type' => 'rich_text',
            'rich_text' => [[
                'type' => 'text',
                'text' => ['content' => $content],
            ]],
        ];
    }

    public static function number(string $name, int|float $value): array
    {
        return [
            'type' => 'number',
            'number' => $value,
        ];
    }

    public static function select(string $name, string $value): array
    {
        return [
            'type' => 'select',
            'select' => [
                'name' => $value,
            ],
        ];
    }

    public static function multiSelect(string $name, array $values): array
    {
        return [
            'type' => 'multi_select',
            'multi_select' => array_map(fn($v) => ['name' => $v], $values),
        ];
    }

    public static function date(string $name, string $start, ?string $end = null): array
    {
        $date = ['start' => $start];
        if ($end) {
            $date['end'] = $end;
        }

        return [
            'type' => 'date',
            'date' => $date,
        ];
    }

    public static function checkbox(string $name, bool $checked): array
    {
        return [
            'type' => 'checkbox',
            'checkbox' => $checked,
        ];
    }

    public static function url(string $name, string $url): array
    {
        return [
            'type' => 'url',
            'url' => $url,
        ];
    }

    public static function email(string $name, string $email): array
    {
        return [
            'type' => 'email',
            'email' => $email,
        ];
    }

    public static function phoneNumber(string $name, string $phone): array
    {
        return [
            'type' => 'phone_number',
            'phone_number' => $phone,
        ];
    }

    public static function files(string $name, array $files): array
    {
        return [
            'type' => 'files',
            'files' => $files,
        ];
    }

    public static function relation(string $name, array $pageIds): array
    {
        return [
            'type' => 'relation',
            'relation' => array_map(fn($id) => ['id' => $id], $pageIds),
        ];
    }

    public static function status(string $name, string $value): array
    {
        return [
            'type' => 'status',
            'status' => [
                'name' => $value,
            ],
        ];
    }
}
