<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\CarbonImmutable;
use Exception;
use Generator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use SplFileInfo;

/**
 * Reads the newest error-level entries from the Laravel logs. Files are read backwards in
 * chunks and reading stops once enough errors are found, so a large log costs no more than the
 * entries shown.
 */
class LogViewerService
{
    private const ERROR_LEVELS = ['emergency', 'alert', 'critical', 'error'];

    private const RETENTION_DAYS = 30;

    private const CHUNK_BYTES = 262_144;

    private const MAX_SCAN_BYTES = 10_000_000;

    private const MAX_ENTRY_BYTES = 1_048_576;

    private const MAX_MESSAGE_CHARS = 20_000;

    private const HEADER_START = '/^\[\d{4}-\d{2}-\d{2}[^\]\n]*\]\s+\S+\.\w+:/m';

    private const ENTRY_PATTERN = '/\A\[(\d{4}-\d{2}-\d{2}[^\]\n]*)\]\s+(\S+)\.(\w+):\s+(.*)\z/s';

    /**
     * The newest errors from the last 30 days, newest first.
     *
     * @return Collection<int, LogEntry>
     */
    public function recentErrors(int $limit): Collection
    {
        $cutoff = CarbonImmutable::now()->subDays(self::RETENTION_DAYS)->startOfDay();
        $entries = collect();

        foreach ($this->logFiles($cutoff) as $file)
        {
            if ($this->olderThanAll($entries, $limit, $file->getMTime()))
            {
                break;
            }

            $entries = $entries->merge($this->errorsInFile($file->getPathname(), $cutoff, $limit));
        }

        return $entries
            ->sortByDesc(fn (LogEntry $entry): int => $this->loggedAt($entry)?->getTimestamp() ?? 0)
            ->take($limit)
            ->values();
    }

    /**
     * Log files touched since the cutoff, the most recently written first.
     *
     * @return array<int, SplFileInfo>
     */
    private function logFiles(CarbonImmutable $cutoff): array
    {
        if (!File::isDirectory(storage_path('logs')))
        {
            return [];
        }

        return collect(File::files(storage_path('logs')))
            ->filter(fn ($file) => $file->getExtension() === 'log')
            ->filter(fn ($file) => str_starts_with($file->getFilename(), 'laravel'))
            ->filter(fn ($file) => $file->getMTime() >= $cutoff->getTimestamp())
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->values()
            ->all();
    }

    /**
     * True once we hold enough errors that a file last written before all of them can't add any.
     *
     * @param  Collection<int, LogEntry>  $entries
     */
    private function olderThanAll(Collection $entries, int $limit, int $modified_at): bool
    {
        if ($entries->count() < $limit)
        {
            return false;
        }

        $oldest = $entries
            ->map(fn (LogEntry $entry): int => $this->loggedAt($entry)?->getTimestamp() ?? 0)
            ->sortDesc()
            ->values()
            ->get($limit - 1);

        return $modified_at < $oldest;
    }

    /**
     * @return array<int, LogEntry>
     */
    private function errorsInFile(string $path, CarbonImmutable $cutoff, int $limit): array
    {
        $errors = [];

        foreach ($this->entriesNewestFirst($path) as $entry)
        {
            if ($this->loggedAt($entry)?->lessThan($cutoff) === true)
            {
                break;
            }

            if (in_array(strtolower($entry->level), self::ERROR_LEVELS, true))
            {
                $errors[] = $entry;
            }

            if (count($errors) >= $limit)
            {
                break;
            }
        }

        return $errors;
    }

    /**
     * Every entry in the file, the last one first, reading at most MAX_SCAN_BYTES from the end.
     *
     * @return Generator<int, LogEntry>
     */
    private function entriesNewestFirst(string $path): Generator
    {
        $handle = @fopen($path, 'r');

        if ($handle === false)
        {
            return;
        }

        try
        {
            $size = (int) filesize($path);
            $position = $size;
            $floor = max(0, $size - self::MAX_SCAN_BYTES);
            $buffer = '';

            while ($position > $floor)
            {
                $read = min(self::CHUNK_BYTES, $position - $floor);
                $position -= $read;
                fseek($handle, $position);
                $buffer = (string) fread($handle, $read).$this->keepStart($buffer);

                yield from $this->takeCompleteEntries($buffer, $position === 0);
            }
        }
        finally
        {
            fclose($handle);
        }
    }

    /**
     * Pulls the entries whose header is in the buffer off its end, last first, and leaves the
     * text before the first header (the tail of an entry that started earlier in the file).
     *
     * @return Generator<int, LogEntry>
     */
    private function takeCompleteEntries(string &$buffer, bool $at_file_start): Generator
    {
        preg_match_all(self::HEADER_START, $buffer, $matches, PREG_OFFSET_CAPTURE);

        $offsets = array_column($matches[0], 1);

        //  a header at the very start of a chunk may be the middle of a line
        if (!$at_file_start && ($offsets[0] ?? null) === 0)
        {
            array_shift($offsets);
        }

        foreach (array_reverse($offsets) as $offset)
        {
            $entry = $this->parseEntry(substr($buffer, $offset));
            $buffer = substr($buffer, 0, $offset);

            if ($entry !== null)
            {
                yield $entry;
            }
        }
    }

    /**
     * A huge entry (a stack trace with a large context) keeps only its first megabyte, cut on a
     * character boundary.
     */
    private function keepStart(string $partial_entry): string
    {
        if (strlen($partial_entry) <= self::MAX_ENTRY_BYTES)
        {
            return $partial_entry;
        }

        return mb_strcut($partial_entry, 0, self::MAX_ENTRY_BYTES, 'UTF-8');
    }

    private function parseEntry(string $text): ?LogEntry
    {
        if (preg_match(self::ENTRY_PATTERN, $text, $match) !== 1)
        {
            return null;
        }

        return new LogEntry(
            logged_at: $match[1],
            channel: $match[2],
            level: $match[3],
            message: Str::limit(trim($match[4]), self::MAX_MESSAGE_CHARS),
        );
    }

    private function loggedAt(LogEntry $entry): ?CarbonImmutable
    {
        try
        {
            return CarbonImmutable::parse($entry->logged_at);
        }
        catch (Exception)
        {
            return null;
        }
    }
}
