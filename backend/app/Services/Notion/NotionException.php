<?php

namespace App\Services\Notion;

use Exception;

class NotionException extends Exception
{
    protected int $statusCode;

    public function __construct(string $message, int $statusCode = 0)
    {
        parent::__construct($message);
        $this->statusCode = $statusCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
