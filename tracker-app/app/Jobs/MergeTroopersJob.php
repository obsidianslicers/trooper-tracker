<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Messages\Troopers\Commands\Merge\MergeTroopers;
use App\Models\Trooper;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class MergeTroopersJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Trooper $source_trooper,
        public readonly Trooper $target_trooper)
    {
    }

    public function handle(): void
    {
        MergeTroopers::call(
            source_trooper: $this->source_trooper,
            target_trooper: $this->target_trooper
        );
    }
}
