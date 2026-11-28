<?php

declare(strict_types=1);

namespace App\Messages\Events\PageData\Corrections;

use App\Enums\EventStatus;
use App\Messages\Events\Queries\Corrections\GetEventsForTroopCorrection;
use App\Messages\Events\Resources\Corrections\TroopCorrectionCreditOptions;
use App\Messages\Events\Resources\Corrections\TroopCorrectionEventResource;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\Trooper;
use App\Plugins\PluginSlots;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;

/**
 * The Request Troop Credit page. First the trooper finds the event, by the date they
 * remember or by its title; then, once they pick it, the form asks for the shift, the
 * costume, the clubs and the details that prove they were there.
 *
 * Plugins can fill the "events.troop-correction.no-match" slot under the results, to point
 * troopers somewhere when their troop isn't listed.
 *
 * @method static array call(Actor $actor, ?string $date = null, ?string $search = null, ?int $event_id = null)
 */
final class TroopCorrectionFormPageData extends Message
{
    public const NO_MATCH_SLOT = 'events.troop-correction.no-match';

    /** @param  Actor&Trooper  $actor */
    public function __construct(
        private readonly Actor $actor,
        private readonly ?string $date = null,
        private readonly ?string $search = null,
        private readonly ?int $event_id = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        $event = $this->selectedEvent();
        $searched = $this->date !== null || $this->search !== null;

        return [
            'date' => $this->date,
            'search' => $this->search,
            'searched' => $searched,
            'events' => $searched
                ? GetEventsForTroopCorrection::call(date: $this->date, search: $this->search)
                    ->map(fn (Event $match) => new TroopCorrectionEventResource($match, $this->actor))
                    ->values()
                : [],
            'event' => $event ? new TroopCorrectionEventResource($event, $this->actor) : null,
            'credit_options' => $event ? $this->creditOptions($event) : null,
            'no_match_html' => $this->noMatchHtml(),
        ];
    }

    private function selectedEvent(): ?Event
    {
        if ($this->event_id === null)
        {
            return null;
        }

        return Event::query()
            ->where(Event::STATUS, EventStatus::CLOSED)
            ->with(['event_shifts' => fn ($query) => $query->orderBy(EventShift::SHIFT_STARTS_AT)])
            ->find($this->event_id);
    }

    private function creditOptions(Event $event): ?TroopCorrectionCreditOptions
    {
        $shift = $event->event_shifts->first();

        return $shift ? new TroopCorrectionCreditOptions($shift, $this->actor) : null;
    }

    private function noMatchHtml(): string
    {
        $context = ['actor' => $this->actor, 'date' => $this->date, 'search' => $this->search];

        return app(PluginSlots::class)->renderMany([self::NO_MATCH_SLOT], $context)[self::NO_MATCH_SLOT] ?? '';
    }
}
