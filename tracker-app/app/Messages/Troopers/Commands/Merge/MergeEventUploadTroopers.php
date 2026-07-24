<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands\Merge;

use App\Models\EventUploadTrooper;
use App\Models\Trooper;
use Hyperdrive\Message;

/**
 * Merges event upload trooper rows from a source trooper into a target trooper.
 *
 * @method static void call(Trooper $target_trooper, Trooper $source_trooper)
 */
final class MergeEventUploadTroopers extends Message
{
    public function __construct(
        private readonly Trooper $target_trooper,
        private readonly Trooper $source_trooper,
    ) {
    }

    public function handle(): void
    {
        $source_event_upload_troopers = EventUploadTrooper::query()
            ->withTrashed()
            ->where(EventUploadTrooper::TROOPER_ID, $this->source_trooper->id)
            ->orderBy(EventUploadTrooper::ID)
            ->get();

        /** @var EventUploadTrooper $source_event_upload_trooper */
        foreach ($source_event_upload_troopers as $source_event_upload_trooper)
        {
            $target_event_upload_trooper = $this->getTargetEventUploadTrooper($source_event_upload_trooper);

            if ($target_event_upload_trooper === null)
            {
                $source_event_upload_trooper->trooper_id = $this->target_trooper->id;
                $source_event_upload_trooper->save();

                continue;
            }

            $this->mergeEventUploadTroopers($target_event_upload_trooper, $source_event_upload_trooper);
        }
    }

    private function getTargetEventUploadTrooper(EventUploadTrooper $source_event_upload_trooper): ?EventUploadTrooper
    {
        return EventUploadTrooper::query()
            ->withTrashed()
            ->where(EventUploadTrooper::TROOPER_ID, $this->target_trooper->id)
            ->where(EventUploadTrooper::EVENT_UPLOAD_ID, $source_event_upload_trooper->event_upload_id)
            ->first();
    }

    private function mergeEventUploadTroopers(EventUploadTrooper $target_event_upload_trooper, EventUploadTrooper $source_event_upload_trooper, ): void
    {
        if ($target_event_upload_trooper->trashed() && !$source_event_upload_trooper->trashed())
        {
            $target_event_upload_trooper->restore();
        }

        $source_event_upload_trooper->forceDelete();
    }
}
