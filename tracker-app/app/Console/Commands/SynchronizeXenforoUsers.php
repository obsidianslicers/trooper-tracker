<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Bus\MagicBus;
use App\Features\Forums\Commands\SyncXenforoUserCommand;
use App\Models\Trooper;
use Illuminate\Console\Command;
use Illuminate\Support\Benchmark;

class SynchronizeXenforoUsers extends Command
{
    protected $signature = 'tracker:synchronize-xenforo-users {--chunk=100 : Number of troopers to process per chunk}';

    protected $description = 'Synchronize all TroopTracker troopers to their linked XenForo users.';

    public function __construct(private readonly MagicBus $bus)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $chunk_size = (int) $this->option('chunk');
        $chunk_size = $chunk_size > 0 ? $chunk_size : 100;

        $ms = Benchmark::measure(function () use ($chunk_size) {
            Trooper::query()
                ->orderBy(Trooper::ID)
                ->chunk($chunk_size, function ($troopers) {
                    foreach ($troopers as $trooper)
                    {
                        $this->bus->send(new SyncXenforoUserCommand($trooper));
                    }
                });
        });

        $this->info('XenForo user synchronization completed in '.($ms->asSeconds(3)).' seconds.');
    }
}
