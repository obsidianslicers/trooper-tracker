<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\MembershipStatus;
use App\Models\Trooper;
use App\Notifications\Troopers\VisitorAccessExpiredNotification;
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

    public function handle(): void
    {
        $expired = Trooper::where(Trooper::IS_VISITOR, true)
            ->where(Trooper::MEMBERSHIP_STATUS, MembershipStatus::ACTIVE)
            ->where(Trooper::VISITOR_EXPIRES_AT, '<', now())
            ->whereNull(Trooper::VISITOR_NOTIFIED_AT)
            ->get();

        foreach ($expired as $trooper)
        {
            $trooper->visitor_notified_at = now();
            $trooper->save();

            $trooper->notify(new VisitorAccessExpiredNotification);
        }

        $this->info("Notified {$expired->count()} visitor(s) of expired access.");
    }
}
