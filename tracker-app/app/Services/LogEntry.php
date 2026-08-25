<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Str;

class LogEntry
{
    public function __construct(
        public readonly string $logged_at,
        public readonly string $channel,
        public readonly string $level,
        public readonly string $message,
    ) {}

    public function badgeClass(): string
    {
        return match (strtolower($this->level))
        {
            'emergency', 'alert', 'critical', 'error' => 'bg-danger',
            'warning' => 'bg-warning',
            'notice', 'info' => 'bg-info',
            default => 'bg-secondary',
        };
    }

    public function summary(int $length = 200): string
    {
        $first_line = trim((string) strtok($this->message, "\n"));

        return Str::limit($first_line, $length);
    }
}
