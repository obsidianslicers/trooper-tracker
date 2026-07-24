<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands\Merge;

use App\Models\EventNotification;
use App\Models\Trooper;
use Hyperdrive\Message;

/**
 * Merges event notifications from a source trooper into a target trooper.
 * This command ensures that all event notifications of the source trooper
 * are transferred to the target trooper, maintaining data integrity and consistency.
 *
 * @method static void call(Trooper $target_trooper, Trooper $source_trooper)
 */
final class MergeEventNotifications extends Message
{
    public function __construct(
        private readonly Trooper $target_trooper,
        private readonly Trooper $source_trooper,
    ) {
    }

    public function handle(): void
    {
        $source_event_notifications = EventNotification::query()
            ->withTrashed()
            ->where(EventNotification::TROOPER_ID, $this->source_trooper->id)
            ->orderBy(EventNotification::ID)
            ->get();

        foreach ($source_event_notifications as $source_event_notification)
        {
            $target_event_notification = $this->getTargetEventNotification($source_event_notification);

            if ($target_event_notification === null)
            {
                $source_event_notification->trooper_id = $this->target_trooper->id;
                $source_event_notification->save();

                continue;
            }

            $this->mergeEventNotifications($target_event_notification, $source_event_notification);
        }
    }

    private function getTargetEventNotification(
        EventNotification $source_event_notification,
    ): ?EventNotification {
        return EventNotification::query()
            ->withTrashed()
            ->where(EventNotification::TROOPER_ID, $this->target_trooper->id)
            ->where(EventNotification::EVENT_ID, $source_event_notification->event_id)
            ->first();
    }

    private function mergeEventNotifications(
        EventNotification $target_event_notification,
        EventNotification $source_event_notification,
    ): void {
        if ($target_event_notification->trashed() && !$source_event_notification->trashed())
        {
            $target_event_notification->restore();
        }

        $target_event_notification->processed_at = $this->latestDateTime(
            $target_event_notification->processed_at,
            $source_event_notification->processed_at,
        );
        $target_event_notification->sent_at = $this->latestDateTime(
            $target_event_notification->sent_at,
            $source_event_notification->sent_at,
        );
        $target_event_notification->save();

        $source_event_notification->forceDelete();
    }

    private function latestDateTime(mixed $target_value, mixed $source_value): mixed
    {
        if ($target_value === null)
        {
            return $source_value;
        }

        if ($source_value === null)
        {
            return $target_value;
        }

        return $source_value->greaterThan($target_value) ? $source_value : $target_value;
    }
}
