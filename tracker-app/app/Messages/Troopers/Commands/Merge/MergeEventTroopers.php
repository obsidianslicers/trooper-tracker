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
final class MergeEventTroopers extends Message
{
    //  todo - merge org ids'
    use DateConcerns;

    public function __construct(
        private readonly Trooper $target_trooper,
        private readonly Trooper $source_trooper,
    ) {}

    public function handle(): void
    {
        $source_event_troopers = EventTrooper::query()
            ->withTrashed()
            ->where(EventTrooper::TROOPER_ID, $this->source_trooper->id)
            ->orderBy(EventTrooper::ID)
            ->get();

        foreach ($source_event_troopers as $source_event_trooper)
        {
            $target_event_trooper = $this->getTargetEventTrooper($source_event_trooper);

            if ($target_event_trooper === null)
            {
                $source_event_trooper->trooper_id = $this->target_trooper->id;
                $source_event_trooper->added_by_trooper_id = $this->resolveAddedByTrooperId(
                    $source_event_trooper->added_by_trooper_id,
                );
                $source_event_trooper->save();

                continue;
            }

            $this->mergeEventTroopers($target_event_trooper, $source_event_trooper);
        }
    }

    private function getTargetEventTrooper(EventTrooper $source_event_trooper): ?EventTrooper
    {
        return EventTrooper::query()
            ->withTrashed()
            ->where(EventTrooper::TROOPER_ID, $this->target_trooper->id)
            ->where(EventTrooper::EVENT_SHIFT_ID, $source_event_trooper->event_shift_id)
            ->first();
    }

    private function mergeEventTroopers(EventTrooper $target_event_trooper, EventTrooper $source_event_trooper): void
    {
        if ($target_event_trooper->trashed() && !$source_event_trooper->trashed())
        {
            $target_event_trooper->restore();
        }

        $source_event_trooper->forceDelete();

        $target_event_trooper->organization_id = $source_event_trooper->organization_id ?? $target_event_trooper->organization_id;
        $target_event_trooper->costume_id = $source_event_trooper->costume_id ?? $target_event_trooper->costume_id;
        $target_event_trooper->costume_organization_ids = array_merge(
            $source_event_trooper->costume_organization_ids ?? [],
            $target_event_trooper->costume_organization_ids ?? []
        );
        $target_event_trooper->backup_costume_id = $source_event_trooper->backup_costume_id ?? $target_event_trooper->backup_costume_id;
        $target_event_trooper->backup_costume_organization_ids = array_merge(
            $source_event_trooper->backup_costume_organization_ids ?? [],
            $target_event_trooper->backup_costume_organization_ids ?? []
        );
        $target_event_trooper->added_by_trooper_id = $this->resolveAddedByTrooperId(
            $source_event_trooper->added_by_trooper_id
            ?? $target_event_trooper->added_by_trooper_id,
        );
        $target_event_trooper->is_handler = $target_event_trooper->is_handler || $source_event_trooper->is_handler;
        $target_event_trooper->status = $this->resolveStatus(
            $target_event_trooper->status,
            $source_event_trooper->status,
        );
        $target_event_trooper->signed_up_at = $this->earliestDateTime(
            $target_event_trooper->signed_up_at,
            $source_event_trooper->signed_up_at,
        );
        $target_event_trooper->save();
    }

    private function resolveAddedByTrooperId(?int $added_by_trooper_id): ?int
    {
        if ($added_by_trooper_id === $this->source_trooper->id)
        {
            return $this->target_trooper->id;
        }

        return $added_by_trooper_id;
    }

    private function resolveStatus(mixed $target_status, mixed $source_status): mixed
    {
        if ($target_status === null)
        {
            return $source_status;
        }

        if ($source_status === null)
        {
            return $target_status;
        }

        $rankings = [
            'none' => 0,
            'pending' => 10,
            'tentative' => 20,
            'standby' => 30,
            'going' => 40,
            'attended' => 50,
            'notpicked' => 60,
            'unabletoattend' => 70,
            'noshow' => 80,
            'cancelled' => 90,
        ];

        $target_status_value = $this->statusValue($target_status);
        $source_status_value = $this->statusValue($source_status);

        return ($rankings[$source_status_value] ?? -1) > ($rankings[$target_status_value] ?? -1)
            ? $source_status
            : $target_status;
    }

    private function statusValue(mixed $status): ?string
    {
        if ($status instanceof EventTrooperStatus)
        {
            return $status->value;
        }

        return is_string($status) ? $status : null;
    }
}
