<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands\Merge;

use App\Models\EventMissionAck;
use App\Models\Trooper;
use Hyperdrive\Message;

/**
 * Merges event mission acknowledgements from a source trooper into a target trooper.
 * This command ensures that all mission acknowledgements of the source trooper
 * are transferred to the target trooper, maintaining data integrity and consistency.
 *
 * @method static void call(Trooper $target_trooper, Trooper $source_trooper)
 */
final class MergeEventMissionAcks extends Message
{
    public function __construct(
        private readonly Trooper $target_trooper,
        private readonly Trooper $source_trooper,
    ) {
    }

    public function handle(): void
    {
        $source_event_mission_acks = EventMissionAck::query()
            ->withTrashed()
            ->where(EventMissionAck::TROOPER_ID, $this->source_trooper->id)
            ->orderBy(EventMissionAck::ID)
            ->get();

        foreach ($source_event_mission_acks as $source_event_mission_ack)
        {
            $target_event_mission_ack = $this->getTargetEventMissionAck($source_event_mission_ack);

            if ($target_event_mission_ack === null)
            {
                $source_event_mission_ack->trooper_id = $this->target_trooper->id;
                $source_event_mission_ack->save();

                continue;
            }

            $this->mergeEventMissionAcks($target_event_mission_ack, $source_event_mission_ack);
        }
    }

    private function getTargetEventMissionAck(EventMissionAck $source_event_mission_ack): ?EventMissionAck
    {
        return EventMissionAck::query()
            ->withTrashed()
            ->where(EventMissionAck::TROOPER_ID, $this->target_trooper->id)
            ->where(EventMissionAck::EVENT_ID, $source_event_mission_ack->event_id)
            ->first();
    }

    private function mergeEventMissionAcks(EventMissionAck $target_event_mission_ack, EventMissionAck $source_event_mission_ack, ): void
    {
        if ($target_event_mission_ack->trashed() && !$source_event_mission_ack->trashed())
        {
            $target_event_mission_ack->restore();
        }

        $target_event_mission_ack->acknowledged_at = $this->latestDateTime(
            $target_event_mission_ack->acknowledged_at,
            $source_event_mission_ack->acknowledged_at,
        );
        $target_event_mission_ack->save();

        $source_event_mission_ack->forceDelete();
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
