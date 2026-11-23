<?php

declare(strict_types=1);

namespace App\Messages\System\PageData;

use App\Messages\System\Resources\LogEntryResource;
use App\Messages\System\Resources\SystemCheckGroupResource;
use App\Services\LogViewerService;
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
            'groups' => SystemCheckGroupResource::collection(
                $service->run()
                    ->map(fn (Collection $results, string $name): array => ['name' => $name, 'results' => $results])
                    ->values()
            ),
            'log_entries' => LogEntryResource::collection($log_viewer->recentErrors()->values()),
        ];
    }
}
