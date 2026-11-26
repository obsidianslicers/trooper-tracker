<?php

declare(strict_types=1);

namespace App\Messages\System\PageData;

use App\Messages\System\Resources\LogEntryResource;
use App\Messages\System\Resources\SystemCheckGroupResource;
use App\Services\LogViewerService;
use App\Services\SystemCheckService;
use Hyperdrive\Message;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;

/**
 * Data for the System Check page: each group of checks and the newest error log entries.
 * Diagnostics stay in English, like the log. The checks are a closure, so showing more errors
 * (a partial reload of `log_entries` and `has_more_errors` with a larger `errors`) doesn't run
 * them again.
 *
 * @method static array<string, mixed> call(?int $errors = null)
 */
final class SystemCheckPageData extends Message
{
    public const ERRORS_PER_PAGE = 25;

    public const MAX_ERRORS = 500;

    public function __construct(private readonly ?int $errors = null) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(SystemCheckService $service, LogViewerService $log_viewer): array
    {
        $limit = $this->limit();
        $entries = $log_viewer->recentErrors($limit + 1);

        return [
            'groups' => fn (): AnonymousResourceCollection => SystemCheckGroupResource::collection(
                $service->run()
                    ->map(fn (Collection $results, string $name): array => ['name' => $name, 'results' => $results])
                    ->values()
            ),
            'log_entries' => LogEntryResource::collection($entries->take($limit)->values()),
            'has_more_errors' => $entries->count() > $limit && $limit < self::MAX_ERRORS,
        ];
    }

    private function limit(): int
    {
        return min(max($this->errors ?? self::ERRORS_PER_PAGE, self::ERRORS_PER_PAGE), self::MAX_ERRORS);
    }
}
