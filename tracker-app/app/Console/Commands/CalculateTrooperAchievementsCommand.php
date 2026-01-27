<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Bus\MagicBus;
use App\Features\Troopers\Commands\StoreTrooperAchievementsCommand;
use App\Features\Troopers\Queries\GetTrooperEventStatsQuery;
use Illuminate\Console\Command;

/**
 * Artisan command to calculate and store trooper achievements based on their event history.
 *
 * This command aggregates event data for each trooper, such as total troops,
 * volunteer hours, and funds raised, and then updates their corresponding
 * achievements in the database.
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
     * Orchestrates the achievement calculation process by:
     * 1. Dispatching GetTrooperEventStatsQuery to retrieve aggregated event statistics
     * 2. Dispatching StoreTrooperAchievementsCommand to process and store achievements
     *
     * @param MagicBus $bus The message bus for dispatching queries and commands
     * @return void
     */
    public function handle(MagicBus $bus): void
    {
        $trooper_events = $bus->send(new GetTrooperEventStatsQuery());

        $this->info("Storing trooper achievements... Count={$trooper_events->count()}");

        $bus->send(new StoreTrooperAchievementsCommand($trooper_events));
    }
}
