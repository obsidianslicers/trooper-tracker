<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Organization;
use App\Plugins\PluginClubSynchronizers;
use Carbon\CarbonInterval;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Benchmark;

/**
 * Runs each club's chosen roster sync, which updates its troopers and costumes
 * from the club's own records.
 */
class SynchronizeOrganizations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tracker:synchronize-organizations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize Organizations.';

    public function handle(PluginClubSynchronizers $synchronizers): void
    {
        $ms = Benchmark::measure(function () use ($synchronizers): void
        {
            $organizations = Organization::ofTypeOrganizations()
                ->whereNotNull(Organization::SYNCHRONIZER)
                ->orderByDesc(Organization::NAME)
                ->get();

            foreach ($organizations as $organization)
            {
                $this->synchronize($synchronizers, $organization);
            }
        });

        $readable = CarbonInterval::millisecond($ms)->cascade()->forHumans();

        $this->info("Synchronizer completed in {$readable}.");
    }

    private function synchronize(
        PluginClubSynchronizers $synchronizers,
        Organization $organization,
    ): void {
        $this->info("Synchronize Started: {$organization->name}");

        try
        {
            $synchronizer = $synchronizers->synchronizer($organization);

            if ($synchronizer === null)
            {
                $this->warn("No roster sync [{$organization->synchronizer}] is available for "
                    ."{$organization->name}. Is the plugin that provides it enabled?");

                return;
            }

            $time = Benchmark::measure(fn () => $synchronizer->run());
            $readable = CarbonInterval::millisecond($time)->cascade()->forHumans();

            $this->info("Synchronize Ended:   {$readable}");
        }
        catch (Exception $e)
        {
            $this->error("Error synchronizing {$organization->name}: {$e->getMessage()}");
            $this->info('Synchronize Ended:   ERR');
        }
    }
}
