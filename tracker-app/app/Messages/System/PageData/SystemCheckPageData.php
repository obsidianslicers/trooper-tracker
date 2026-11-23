<?php

declare(strict_types=1);

namespace App\Messages\System\PageData;

use App\Services\LogEntry;
use App\Services\LogViewerService;
use App\Services\SystemCheckResult;
use App\Services\SystemCheckService;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * Data for the System Check page: each group of checks and the recent error log entries.
 * Diagnostics stay in English, like the log.
 *
 * @method static array<string, mixed> call()
 */
final class SystemCheckPageData extends Message
{
    /**
     * @return array<string, mixed>
     */
    public function handle(SystemCheckService $service, LogViewerService $log_viewer): array
    {
        return [
            'groups' => $service->run()
                ->map(fn (Collection $results, string $name): array => [
                    'name' => $name,
                    'results' => $results->map(fn (SystemCheckResult $result): array => [
                        'label' => $result->label,
                        'detail' => $result->detail,
                        'status' => ucfirst($result->status->value),
                        'badge' => $result->status->badgeClass(),
                        'icon' => $result->status->icon(),
                    ])->values()->all(),
                ])
                ->values()
                ->all(),
            'log_entries' => $log_viewer->recentErrors()
                ->map(fn (LogEntry $entry): array => [
                    'level' => strtoupper($entry->level),
                    'badge' => $entry->badgeClass(),
                    'logged_at' => $entry->logged_at,
                    'summary' => $entry->summary(),
                    'message' => $entry->message,
                ])
                ->values()
                ->all(),
        ];
    }
}
