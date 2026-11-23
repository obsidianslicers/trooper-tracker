<?php

declare(strict_types=1);

namespace App\Messages\Reports\PageData;

use App\Enums\EventTrooperStatus;
use App\Messages\Reports\Queries\GetStatusChangeLog;
use App\Models\ModelChange;
use App\Models\Trooper;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;

/**
 * Data for the Status Change Log report: troops marked attended by someone else, last 30 days.
 *
 * @method static array<string, mixed> call(Actor $actor)
 */
final class StatusChangeLogPageData extends Message
{
    public const DAYS = 30;

    /**
     * @param  Actor&Trooper  $actor
     */
    public function __construct(private readonly Actor $actor) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        $changes = GetStatusChangeLog::call(moderator: $this->actor, lookback: self::DAYS);

        return [
            'caption' => __('admin.Troopers who :attended an event where their status was updated by someone other than the trooper in action for the last :days days.', [
                'attended' => EventTrooperStatus::ATTENDED->label(),
                'days' => self::DAYS,
            ]),
            'changes' => $changes
                ->map(fn (ModelChange $change): array => $this->row($change))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function row(ModelChange $change): array
    {
        $event_trooper = $change->auditable;
        $event = $event_trooper->event_shift->event;

        return [
            'id' => $change->id,
            'changed_at' => $change->created_at->localFormat('D - M d, Y g:ia'),
            'event_name' => $event->name,
            'event_url' => route('admin.events.edit', ['event' => $event]),
            'shift' => $event_trooper->event_shift->time_display,
            'trooper_name' => $event_trooper->trooper->display_name,
            'trooper_url' => route('admin.troopers.show', [
                'trooper' => $event_trooper->trooper,
                'tab' => 'changes',
            ]),
            'updated_by' => $change->trooper->display_name ?? __('admin.System'),
        ];
    }
}
