<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo\Console;

use App\Models\Trooper;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Benchmark;
use TroopTracker\Plugins\Xenforo\Messages\SyncXenforoUser;

class SyncUsersCommand extends Command
{
    protected $signature = 'xenforo:sync-users
        {--chunk=100 : Number of troopers to process per chunk}';

    protected $description = 'Synchronize every trooper to their linked XenForo user.';

    public function handle(): int
    {
        $chunk_size = max(1, (int) $this->option('chunk'));

        $milliseconds = Benchmark::measure(function () use ($chunk_size): void
        {
            Trooper::query()
                ->orderBy(Trooper::ID)
                ->chunk($chunk_size, function (Collection $troopers): void
                {
                    foreach ($troopers as $trooper)
                    {
                        SyncXenforoUser::call(trooper: $trooper);
                    }
                });
        });

        $seconds = number_format($milliseconds / 1000, 3);
        $this->info("XenForo user synchronization completed in {$seconds} seconds.");

        return self::SUCCESS;
    }
}
