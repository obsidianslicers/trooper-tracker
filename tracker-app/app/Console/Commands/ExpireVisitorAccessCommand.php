<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Bus\MagicBus;
use App\Features\Troopers\Commands\NotifyExpiredVisitorCommand;
use App\Features\Troopers\Queries\GetExpiredVisitorsQuery;
use Illuminate\Console\Command;

/**
 * Artisan command to notify visitor troopers whose 6-month access window has elapsed.
 *
 * Sends a one-time email so visitors know to log in and submit a renewal request.
 * Does not modify membership_status — the visitor must actively request renewal.
 */
class ExpireVisitorAccessCommand extends Command
{
    protected $signature = 'tracker:expire-visitor-access';

    protected $description = 'Notify visitor troopers when their 6-month access window has elapsed';

    public function handle(MagicBus $bus): int
    {
        $expired = $bus->send(new GetExpiredVisitorsQuery());

        foreach ($expired as $trooper)
        {
            $bus->send(new NotifyExpiredVisitorCommand($trooper));
        }

        $this->info("Notified {$expired->count()} visitor(s) of expired access.");

        return Command::SUCCESS;
    }
}
