<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Holonet\Console;

use Illuminate\Console\Command;
use TroopTracker\Plugins\Holonet\Messages\Commands\RefreshTrackerLink;
use TroopTracker\Plugins\Holonet\Models\TrackerLink;

/**
 * Copies every active link's troop history again.
 */
class RefreshLinksCommand extends Command
{
    protected $signature = 'holonet:refresh-links';

    protected $description = 'Copy troop history from every linked tracker';

    public function handle(): int
    {
        $refreshed = 0;
        $failed = 0;

        TrackerLink::query()
            ->where(TrackerLink::STATUS, TrackerLink::STATUS_ACTIVE)
            ->orderBy(TrackerLink::ID)
            ->each(function (TrackerLink $link) use (&$refreshed, &$failed): void
            {
                RefreshTrackerLink::call(link: $link) ? $refreshed++ : $failed++;
            });

        $this->info("Refreshed {$refreshed} links, {$failed} failed.");

        return self::SUCCESS;
    }
}
