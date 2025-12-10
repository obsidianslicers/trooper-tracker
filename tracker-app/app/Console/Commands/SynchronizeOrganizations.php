<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Organization;
use Illuminate\Console\Command;

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
        $organizations = Organization::ofTypeOrganizations()
            ->whereNotNull(Organization::SERVICE_CLASS)
            ->orderBy(Organization::NAME)
            ->where('id', 5)
            ->get();

        foreach ($organizations as $organization)
        {
            $service_class = $organization->service_class;

            $service_class = app($service_class, ['organization' => $organization]);

            $service_class->syncAll();
        }
    }
}