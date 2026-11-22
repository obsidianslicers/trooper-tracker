<?php

declare(strict_types=1);

namespace App\Messages\Reports\PageData;

use App\Messages\Concerns\FormatsReportNumbers;
use App\Messages\Reports\Queries\GetEventTypeCount;
use App\Models\Trooper;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;

/**
 * Data for the Event Type Counts report: closed events of each type in the last 30 days.
 *
 * @method static array<string, mixed> call(Actor $actor)
 */
final class EventTypeCountPageData extends Message
{
    use FormatsReportNumbers;

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
        $types = GetEventTypeCount::call(moderator: $this->actor, lookback: self::DAYS);

        return [
            'days' => self::DAYS,
            'event_types' => $types
                ->map(fn (object $type): array => [
                    'id' => $type->event_type->id,
                    'name' => $type->event_type->name,
                    'events_url' => route('admin.events.list', [
                        'event_type_id' => $type->event_type->id,
                    ]),
                    'count' => $this->count($type->count),
                    'unique_trooper_count' => $this->count($type->unique_trooper_count),
                    'total_trooper_count' => $this->count($type->total_trooper_count),
                ])
                ->values()
                ->all(),
            'totals' => [
                'count' => $this->count($types->sum('count')),
                'unique_trooper_count' => $this->count($types->sum('unique_trooper_count')),
                'total_trooper_count' => $this->count($types->sum('total_trooper_count')),
            ],
        ];
    }
}
