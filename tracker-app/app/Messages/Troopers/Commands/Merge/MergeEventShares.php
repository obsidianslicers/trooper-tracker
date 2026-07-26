<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands\Merge;

use App\Models\EventShare;
use App\Models\Trooper;
use Hyperdrive\Message;

/**
 * Merges event shares from a source trooper into a target trooper.
 * This command ensures that all event shares of the source trooper
 * are transferred to the target trooper, maintaining data integrity and consistency.
 *
 * @method static void call(Trooper $target_trooper, Trooper $source_trooper)
 */
final class MergeEventShares extends Message
{
    public function __construct(
        private readonly Trooper $target_trooper,
        private readonly Trooper $source_trooper,
    ) {}

    public function handle(): void
    {
        $source_event_shares = EventShare::query()
            ->withTrashed()
            ->where(EventShare::TROOPER_ID, $this->source_trooper->id)
            ->orderBy(EventShare::ID)
            ->get();

        foreach ($source_event_shares as $source_event_share)
        {
            $source_event_share->trooper_id = $this->target_trooper->id;
            $source_event_share->save();
        }
    }
}
