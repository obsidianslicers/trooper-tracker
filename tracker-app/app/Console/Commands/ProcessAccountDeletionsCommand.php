<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Bus\MagicBus;
use App\Features\Troopers\Commands\ExecuteAccountDeletionCommand;
use App\Models\Trooper;
use Illuminate\Console\Command;

/**
 * Artisan command that permanently anonymizes accounts that have been pending deletion
 * for at least 30 days, completing the GDPR right-to-erasure workflow.
 */
class ProcessAccountDeletionsCommand extends Command
{
    protected $signature = 'tracker:process-account-deletions';

    protected $description = 'Anonymize and soft-delete trooper accounts past the 30-day grace period';

    public function handle(MagicBus $bus): int
    {
        $pending = Trooper::query()
            ->whereNotNull(Trooper::DELETION_REQUESTED_AT)
            ->where(Trooper::DELETION_REQUESTED_AT, '<=', now()->subDays(30))
            ->get();

        foreach ($pending as $trooper)
        {
            $bus->send(new ExecuteAccountDeletionCommand($trooper));
        }

        $this->info("Processed {$pending->count()} account deletion(s).");

        return Command::SUCCESS;
    }
}
