<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Messages\Plugins\Commands\ReconcilePlugins;
use Illuminate\Console\Command;

/**
 * Run by deploy.sh after Composer and migrations. Never fails the deploy: each
 * plugin's problem is recorded and shown on the Plugins page instead.
 */
class ReconcilePluginsCommand extends Command
{
    protected $signature = 'plugins:reconcile';

    protected $description = 'Bring every plugin to its chosen state after a deploy.';

    public function handle(): int
    {
        $report = ReconcilePlugins::call();

        foreach ($report as $name => $line)
        {
            $this->line("  {$name}: {$line}");
        }

        $this->info($report === [] ? 'Every plugin is as it was.' : 'Plugins reconciled.');

        return self::SUCCESS;
    }
}
