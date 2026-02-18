<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Bus\MagicBus;
use App\Features\Troopers\Commands\RecalculateTrooperRankCommand;
use Illuminate\Console\Command;

/**
 * Artisan command to recalculate trooper ranks based on their event history.
 *
 * This command dispatches the rank recalculation process, which aggregates
 * event data for each trooper and updates their corresponding rank in the database.
 */
class CalculateTrooperAchievementsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tracker:calculate-trooper-achievements';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate trooper achievements.';

    /**
     * Execute the console command.
     *
     * Orchestrates the trooper rank recalculation process by dispatching
     * the RecalculateTrooperRankCommand through the message bus.
     *
     * @param MagicBus $bus The message bus for dispatching commands
     * @return void
     */
    public function handle(MagicBus $bus): void
    {
        $bus->send(new RecalculateTrooperRankCommand());
    }
}
