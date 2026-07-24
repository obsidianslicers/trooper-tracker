<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands\Merge;

use App\Enums\EventTrooperStatus;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Hyperdrive\Message;

/**
 * Merges event troop signups from a source trooper into a target trooper.
 *
 * @method static void call(Trooper $target_trooper, Trooper $source_trooper)
 */
final class MergeEventWatches extends Message
{
    public function __construct(
        private readonly Trooper $target_trooper,
        private readonly Trooper $source_trooper,
    ) {
    }

    public function handle(): void
    {

    }
}
