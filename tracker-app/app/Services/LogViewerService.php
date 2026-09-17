<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class LogViewerService
{
    private const ERROR_LEVELS = ['emergency', 'alert', 'critical', 'error'];

    private const RETENTION_DAYS = 30;

    private const TAIL_BYTES = 10_000_000;

    private const HEADER_PATTERN = '/^\[(\d{4}-\d{2}-\d{2}[^\]]+)\]\s+(\S+)\.(\w+):\s+(.*?)(?=^\[\d{4}-\d{2}-\d{2}|\z)/ms';

    /**
     * @return Collection<int, LogEntry>
     */
    public function recentErrors(?int $limit = null): Collection
    {
        $cutoff = CarbonImmutable::now()->subDays(self::RETENTION_DAYS)->startOfDay();

        $entries = collect($this->logPaths($cutoff))
            ->flatMap(fn (string $path) => $this->parseEntries($this->tail($path, self::TAIL_BYTES)))
            ->filter(fn (LogEntry $entry) => in_array(strtolower($entry->level), self::ERROR_LEVELS, true))
            ->filter(fn (LogEntry $entry) => $this->loggedAt($entry)?->greaterThanOrEqualTo($cutoff) === true)
            ->sortByDesc(fn (LogEntry $entry) => $entry->logged_at)
            ->values();

        return $limit === null ? $entries : $entries->take($limit)->values();
    }

    /**
     * @return array<int, string>
     */
    private function logPaths(CarbonImmutable $cutoff): array
    {
        if (!File::isDirectory(storage_path('logs')))
        {
            return [];
        }

        return collect(File::files(storage_path('logs')))
            ->filter(fn ($file) => $file->getExtension() === 'log')
            ->filter(fn ($file) => str_starts_with($file->getFilename(), 'laravel'))
            ->filter(fn ($file) => $file->getMTime() >= $cutoff->getTimestamp())
            ->sortBy(fn ($file) => $file->getFilename())
            ->map(fn ($file) => $file->getPathname())
            ->values()
            ->all();
    }

    private function loggedAt(LogEntry $entry): ?CarbonImmutable
    {
        try
        {
            return CarbonImmutable::parse($entry->logged_at);
        }
        catch (\Exception)
        {
            return null;
        }
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
