<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Trooper;
use App\Services\Forums\XenforoUserSyncService;
use Illuminate\Console\Command;

class SynchronizeXenforoUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tracker:synchronize-xenforo-user {trooper : The ID of the trooper to sync to XenForo}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize a single TroopTracker trooper to their linked XenForo user.';

    public function __construct(private readonly XenforoUserSyncService $syncService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $trooperId = (int) $this->argument('trooper');

        if ($trooperId <= 0)
        {
            $this->error('Please provide a valid trooper ID.');

            return;
        }

        $trooper = Trooper::find($trooperId);

        if (! $trooper)
        {
            $this->error("Trooper with ID {$trooperId} was not found.");

            return;
        }

        $this->info("Synchronizing trooper #{$trooper->id} ({$trooper->display_name}) to XenForo...");

        $this->syncService->syncTrooper($trooper);

        $this->info('Synchronization complete.');
    }
}
