<?php

declare(strict_types=1);

namespace App\Messages\Reports\PageData;

use App\Messages\Concerns\FormatsReportNumbers;
use App\Messages\Reports\Queries\GetEventSummary;
use App\Models\Event;
use App\Models\Trooper;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;

/**
 * Data for the Event Summary report: closed events of the last 30 days, with who came and the
 * money raised.
 *
 * @method static array<string, mixed> call(Actor $actor)
 */
final class EventSummaryPageData extends Message
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
        $events = GetEventSummary::call(moderator: $this->actor, lookback: self::DAYS);

        return [
            'days' => self::DAYS,
            'events' => $events
                ->map(fn (Event $event): array => [
                    'id' => $event->id,
                    'name' => $event->name,
                    'url' => route('admin.events.update', ['event' => $event]),
                    'logo_url' => map_image_url(
                        $event->organization?->image_path_sm,
                        'img/icons/organization-32x32.png'
                    ),
                    'shifts' => (string) $event->event_shifts_count,
                    'unique_trooper_count' => $this->count($event->unique_trooper_count),
                    'total_trooper_count' => $this->count($event->total_trooper_count),
                    'direct' => $this->funds($event->charity_direct_funds),
                    'indirect' => $this->funds($event->charity_indirect_funds),
                ])
                ->values()
                ->all(),
            'totals' => [
                'shifts' => $this->count($events->sum('event_shifts_count')),
                'unique_trooper_count' => $this->count($events->sum('unique_trooper_count')),
                'total_trooper_count' => $this->count($events->sum('total_trooper_count')),
                'direct' => $this->funds($events->sum('charity_direct_funds')),
                'indirect' => $this->funds($events->sum('charity_indirect_funds')),
            ],
        ];
    }
}
