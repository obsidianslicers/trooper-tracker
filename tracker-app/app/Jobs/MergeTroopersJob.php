<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Messages\Troopers\Commands\Merge\MergeTroopers;
use App\Messages\Troopers\Queries\GetAdministrators;
use App\Models\Trooper;
use App\Notifications\Admin\TroopersMergedNotification;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class MergeTroopersJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Trooper $source_trooper,
        public readonly Trooper $target_trooper) {}

    public function handle(): void
    {
        MergeTroopers::call(
            source_trooper: $this->source_trooper,
            target_trooper: $this->target_trooper
        );

        $admins = GetAdministrators::call();

        foreach ($admins as $admin)
        {
            $admin->notify(new TroopersMergedNotification($this->source_trooper, $this->target_trooper));
        }
    }
}
