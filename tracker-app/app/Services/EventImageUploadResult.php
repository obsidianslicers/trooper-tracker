<?php

declare(strict_types=1);

namespace App\Services;

class EventImageUploadResult
{
    public function __construct(
        public readonly int $uploads,
        public readonly int $failures,
    ) {}

    public function hasFailures(): bool
    {
        return $this->failures > 0;
    }

    public function hasUploads(): bool
    {
        return $this->uploads > 0;
    }
}
