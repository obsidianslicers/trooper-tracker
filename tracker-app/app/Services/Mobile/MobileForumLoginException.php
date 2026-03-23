<?php

declare(strict_types=1);

namespace App\Services\Mobile;

use RuntimeException;

class MobileForumLoginException extends RuntimeException
{
    public function __construct(string $message, private readonly int $status_code)
    {
        parent::__construct($message);
    }

    public function statusCode(): int
    {
        return $this->status_code;
    }
}