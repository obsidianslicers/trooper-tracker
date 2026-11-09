<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\MembershipRole;
use App\Messages\Events\Queries\Verification\CountStaleVerifications;
use App\Messages\Troopers\Queries\GetTroopersByRole;
use App\Notifications\Admin\StaleVerificationsNotification;
use Illuminate\Console\Command;

/**
 * Reminds administrators about troop verifications that have waited more than 30 days.
 */
class RemindStaleVerificationsCommand extends Command
{
    protected $signature = 'tracker:remind-stale-verifications';

    protected $description = 'Remind administrators about troop verifications waiting more than 30 days';

    public function handle(): int
    {
        $count = CountStaleVerifications::call(days: 30);

        if ($count === 0)
        {
            $this->info('No stale troop verifications.');

            return self::SUCCESS;
        }

        foreach (GetTroopersByRole::call(membership_role: MembershipRole::SYSTEM_OWNER) as $admin)
        {
            $admin->notify(new StaleVerificationsNotification($count));
        }

        $this->info("Reminded administrators about {$count} stale troop verification(s).");

        return self::SUCCESS;
    }
}
