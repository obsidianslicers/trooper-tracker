<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Holonet\Console;

use Illuminate\Console\Command;
use TroopTracker\Plugins\Holonet\Messages\Commands\RefreshTrackerLink;
use TroopTracker\Plugins\Holonet\Models\HolonetLink;

/**
 * Copies every active link's troop history again.
 */
class RefreshLinksCommand extends Command
{
    protected $signature = 'holonet:refresh-links
        {--spread : Wait a random time first, so trackers don\'t all refresh at once}';

    protected $description = 'Copy troop history from every linked tracker';

    public function handle(): int
    {
        if ($this->option('spread'))
        {
            sleep(random_int(0, max(0, (int) config('plugins.holonet.limits.nightly_jitter_seconds'))));
        }

        $refreshed = 0;
        $failed = 0;

        HolonetLink::query()
            ->where(HolonetLink::STATUS, HolonetLink::STATUS_ACTIVE)
            ->orderBy(HolonetLink::TRACKER_URL)
            ->orderBy(HolonetLink::ID)
            ->each(function (HolonetLink $link) use (&$refreshed, &$failed): void
            {
                RefreshTrackerLink::call(link: $link) ? $refreshed++ : $failed++;
            });

        $this->info("Refreshed {$refreshed} links, {$failed} failed.");

        return self::SUCCESS;
    }
}
