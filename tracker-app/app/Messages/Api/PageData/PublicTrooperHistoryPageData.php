<?php

declare(strict_types=1);

namespace App\Messages\Api\PageData;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Models\Event;
use App\Models\EventTrooper;
use App\Models\Trooper;
use App\Models\TrooperOrganization;
use Hyperdrive\Message;

/**
 * A trooper's closed, counted troops for the public API, found by id or by identifier and club.
 *
 * The keys are the legacy API's and must not change.
 *
 * @method static array call(int|null $trooper_id = null, string|null $identifier = null, int|null $organization_id = null)
 */
final class PublicTrooperHistoryPageData extends Message
{
    public function __construct(
        private readonly ?int $trooper_id = null,
        private readonly ?string $identifier = null,
        private readonly ?int $organization_id = null,
    ) {}

    public function handle(): array
    {
        $trooper = $this->findTrooper();

        if (!$trooper)
        {
            return [];
        }

        $identifier = TrooperOrganization::where(TrooperOrganization::TROOPER_ID, $trooper->id)
            ->value(TrooperOrganization::IDENTIFIER);

        $events = $this->events($trooper);

        return [[
            'trooperName' => $trooper->display_name,
            'tkid' => $identifier,
            '501forum' => null,
            'rebelforum' => null,
            'events' => $events,
            'troopCount' => count($events),
        ]];
    }

    private function findTrooper(): ?Trooper
    {
        if ($this->trooper_id !== null)
        {
            return Trooper::find($this->trooper_id);
        }

        if ($this->organization_id === null)
        {
            return null;
        }

        return TrooperOrganization::where(TrooperOrganization::IDENTIFIER, $this->identifier)
            ->where(TrooperOrganization::ORGANIZATION_ID, $this->organization_id)
            ->with('trooper')
            ->first()
            ?->trooper;
    }

    private function events(Trooper $trooper): array
    {
        return EventTrooper::where(EventTrooper::TROOPER_ID, $trooper->id)
            ->where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED->value)
            ->counted()
            ->whereHas('event_shift.event', fn ($q) => $q->where(Event::STATUS, EventStatus::CLOSED->value))
            ->with(['event_shift.event'])
            ->get()
            ->map(fn ($et) => [
                'eventID' => $et->event_shift->event->id,
                'eventName' => $et->event_shift->event->name,
                'dateStart' => $et->event_shift->event->event_start,
                'dateEnd' => $et->event_shift->event->event_end,
            ])
            ->sortByDesc('dateEnd')
            ->values()
            ->all();
    }
}
