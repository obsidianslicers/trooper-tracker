<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Messages\System\Commands\StampDeployment;
use Illuminate\Console\Command;

/**
 * Called by the deploy scripts once a release is live, so /status can say what is running.
 */
class StampDeploymentCommand extends Command
{
    protected $signature = 'tracker:stamp-deployment {commit : The git hash that is now deployed}';

    protected $description = 'Record the deployed commit and time for the /status page';

    public function handle(): int
    {
        StampDeployment::call(commit: (string) $this->argument('commit'));

        $this->info('Stamped deployment.');

        return Command::SUCCESS;
    }
}
