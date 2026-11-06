<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Messages\Troopers\Commands\NotifyExpiredVisitor;
use App\Messages\Troopers\Queries\GetExpiredVisitors;
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

    public function handle(): int
    {
        $expired = GetExpiredVisitors::call();

        foreach ($expired as $trooper)
        {
            NotifyExpiredVisitor::call(trooper: $trooper);
        }

        $this->info("Notified {$expired->count()} visitor(s) of expired access.");

        return Command::SUCCESS;
    }
}
