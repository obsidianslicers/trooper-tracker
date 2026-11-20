<?php

declare(strict_types=1);

namespace App\Messages\System\Queries;

use App\Enums\SystemCheckStatus;
use App\Services\SystemCheckResult;
use App\Services\SystemCheckService;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * The System Check rows that are not passing, failures first.
 *
 * @method static Collection<int, SystemCheckResult> call()
 */
final class GetSystemCheckProblems extends Message
{
    /**
     * @return Collection<int, SystemCheckResult>
     */
    public function handle(SystemCheckService $service): Collection
    {
        return $service->run()
            ->flatten(1)
            ->reject(fn (SystemCheckResult $result): bool => $this->passed($result))
            ->sortBy(fn (SystemCheckResult $result): int => $this->failed($result) ? 0 : 1)
            ->values();
    }

    private function passed(SystemCheckResult $result): bool
    {
        return $result->status === SystemCheckStatus::PASS;
    }

    private function failed(SystemCheckResult $result): bool
    {
        return $result->status === SystemCheckStatus::FAIL;
    }
}
