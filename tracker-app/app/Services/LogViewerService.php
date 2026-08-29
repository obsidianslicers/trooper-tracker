<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class LogViewerService
{
    private const ERROR_LEVELS = ['emergency', 'alert', 'critical', 'error'];

    private const TAIL_BYTES = 2_000_000;

    private const HEADER_PATTERN = '/^\[(\d{4}-\d{2}-\d{2}[^\]]+)\]\s+(\S+)\.(\w+):\s+(.*?)(?=^\[\d{4}-\d{2}-\d{2}|\z)/ms';

    /**
     * @return Collection<int, LogEntry>
     */
    public function recentErrors(int $limit = 25): Collection
    {
        $path = $this->currentLogPath();

        if ($path === null)
        {
            return collect();
        }

        return $this->parseEntries($this->tail($path, self::TAIL_BYTES))
            ->filter(fn (LogEntry $entry) => in_array(strtolower($entry->level), self::ERROR_LEVELS, true))
            ->reverse()
            ->take($limit)
            ->values();
    }

    private function currentLogPath(): ?string
    {
        if (!File::isDirectory(storage_path('logs')))
        {
            return null;
        }

        $latest = collect(File::files(storage_path('logs')))
            ->filter(fn ($file) => $file->getExtension() === 'log')
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->first();

        return $latest?->getPathname();
    }

    private function tail(string $path, int $max_bytes): string
    {
        $handle = fopen($path, 'r');

        if ($handle === false)
        {
            return '';
        }

        $size = filesize($path);

        if ($size !== false && $size > $max_bytes)
        {
            fseek($handle, -$max_bytes, SEEK_END);
        }

        $content = stream_get_contents($handle);
        fclose($handle);

        return $content === false ? '' : $content;
    }

    /**
     * @return Collection<int, LogEntry>
     */
    private function parseEntries(string $content): Collection
    {
        preg_match_all(self::HEADER_PATTERN, $content, $matches, PREG_SET_ORDER);

        return collect($matches)->map(fn (array $match) => new LogEntry(
            logged_at: $match[1],
            channel: $match[2],
            level: $match[3],
            message: trim($match[4]),
        ));
    }
}
