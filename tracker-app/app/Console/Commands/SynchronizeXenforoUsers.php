<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Trooper;
use App\Services\Forums\XenforoUserSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Benchmark;

class SynchronizeXenforoUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tracker:synchronize-xenforo-users {--chunk=100 : Number of troopers to process per chunk}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize all TroopTracker troopers to their linked XenForo users.';

    public function __construct(private readonly XenforoUserSyncService $syncService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $chunkSize = (int) $this->option('chunk');
        $chunkSize = $chunkSize > 0 ? $chunkSize : 100;

        $ms = Benchmark::measure(function () use ($chunkSize) {
            Trooper::query()
                ->orderBy(Trooper::ID)
                ->chunk($chunkSize, function ($troopers) {
                    foreach ($troopers as $trooper)
                    {
                        $this->syncService->syncTrooper($trooper);
                    }
                });
        });

        $this->info('XenForo user synchronization completed in '.($ms->asSeconds(3)).' seconds.');
    }
}
