<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Bus\MagicBus;
use App\Features\Troopers\Commands\RecalculateTrooperRankCommand;
use Carbon\CarbonInterval;
use Illuminate\Console\Command;
use Illuminate\Support\Benchmark;

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
    protected $signature = 'tracker:calculate-trooper-achievements
        {--without-notifications : Create milestone achievements without dispatching milestone notifications}';

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
     * @param  MagicBus  $bus  The message bus for dispatching commands
     */
    public function handle(MagicBus $bus): void
    {
        $summary = null;
        $send_milestone_notifications = !$this->option('without-notifications');

        $ms = Benchmark::measure(function () use ($bus, $send_milestone_notifications, &$summary) {
            $summary = $bus->send(new RecalculateTrooperRankCommand(
                send_milestone_notifications: $send_milestone_notifications,
            ));
        });

        $readable = CarbonInterval::millisecond($ms)->cascade()->forHumans();

        $this->info("Trooper achievement calculation completed in {$readable}.");
        $this->displayMilestoneSummary($summary);

        if (!$send_milestone_notifications)
        {
            $this->warn('Milestone notifications were disabled for this run.');
        }
    }

    /**
     * @param  array<string, mixed>|null  $summary
     */
    private function displayMilestoneSummary(?array $summary): void
    {
        $created = $summary['created_milestones'] ?? null;

        if (!is_array($created))
        {
            return;
        }

        $this->info(sprintf(
            'Milestones created: %d (global: %d, club-scoped: %d).',
            $created['total'] ?? 0,
            $created['global'] ?? 0,
            $created['club'] ?? 0,
        ));

        foreach ($created['by_type'] ?? [] as $type => $count)
        {
            $this->line(" - {$type}: {$count}");
        }
    }
}
