<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Organization;
use App\Services\Synchronizers\TheLegionService;
use Carbon\CarbonInterval;
use Illuminate\Console\Command;
use Illuminate\Support\Benchmark;

/**
 * Artisan command to calculate and store trooper achievements based on their event history.
 *
 * This command aggregates event data for each trooper, such as total troops,
 * volunteer hours, and funds raised, and then updates their corresponding
 * achievements in the database.
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

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $ms = Benchmark::measure(function ()
        {
            $organizations = Organization::ofTypeOrganizations()
                ->whereNotNull(Organization::SERVICE_CLASS)
                ->orderByDesc(Organization::NAME)
                ->get();

            foreach ($organizations as $organization)
            {
                $this->info("Synchronize Started: {$organization->name}");

                $service_class = $organization->service_class;

                $service_class = app($service_class, compact('organization'));

                $time = Benchmark::measure(fn() => $service_class->run());

                $readable = CarbonInterval::millisecond($time)->cascade()->forHumans();

                $this->info("Synchronize Ended:   {$readable}");
            }
        });

        $readable = CarbonInterval::millisecond($ms)->cascade()->forHumans();

        $this->info("Synchronizer completed in {$readable}.");
    }
}