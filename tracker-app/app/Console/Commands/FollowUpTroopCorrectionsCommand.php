<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Messages\Events\Commands\Corrections\FollowUpTroopCorrections;
use Illuminate\Console\Command;

/**
 * Reminds event coordinators about troop credit requests they haven't answered in a week,
 * and hands the ones left for two weeks to the moderators.
 */
class FollowUpTroopCorrectionsCommand extends Command
{
    protected $signature = 'tracker:follow-up-troop-corrections';

    protected $description = 'Remind event coordinators about troop credit requests, then hand them to moderators';

    public function handle(): int
    {
        $counts = FollowUpTroopCorrections::call();

        $this->info("Reminded {$counts['reminded']}, handed {$counts['escalated']} to moderators.");

        return self::SUCCESS;
    }
}
