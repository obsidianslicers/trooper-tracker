<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands\Merge;

use App\Models\EventWatch;
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
        $source_event_watches = EventWatch::query()
            ->where(EventWatch::TROOPER_ID, $this->source_trooper->id)
            ->orderBy(EventWatch::ID)
            ->get();

        foreach ($source_event_watches as $source_event_watch)
        {
            $target_event_watch = $this->getTargetEventWatch($source_event_watch);

            if ($target_event_watch === null)
            {
                $source_event_watch->trooper_id = $this->target_trooper->id;
                $source_event_watch->save();

                continue;
            }

            $source_event_watch->delete();
        }
    }

    private function getTargetEventWatch(EventWatch $source_event_watch): ?EventWatch
    {
        return EventWatch::query()
            ->where(EventWatch::TROOPER_ID, $this->target_trooper->id)
            ->where(EventWatch::EVENT_ID, $source_event_watch->event_id)
            ->first();
    }
}
