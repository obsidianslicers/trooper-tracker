<?php

declare(strict_types=1);

namespace App\Messages\Events\Resources\Display;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Models\EventGuest;
use App\Models\EventShift;
use App\Models\EventShiftStation;
use App\Models\EventTrooper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * One shift on the event page: how full it is, its stations, its roster in sign-up order
 * (going, then stand-by, then guests and cancelled), the Sign Up form, and its quieter links.
 *
 * @mixin EventShift
 */
class EventDisplayShift extends JsonResource
{
    public function __construct(EventShift $event_shift, private readonly EventDisplayContext $context)
    {
        parent::__construct($event_shift);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $troopers = $this->event_troopers;
        $holding = $troopers->reject(fn (EventTrooper $et) => in_array(
            $et->status,
            [EventTrooperStatus::STAND_BY, EventTrooperStatus::CANCELLED],
            true
        ));

        return [
            'id' => $this->id,
            'label' => $this->short_time_display,
            'is_open' => $this->is_open,
            'is_mine' => $this->isSignedUp($this->context->viewer),
            'status_label' => $this->status !== EventStatus::OPEN ? $this->status->label() : null,
            'fullness' => $this->fullness(),
            'stations' => $this->stationSummary(),
            'same_day' => $this->sameDayNote(),
            'going' => $this->rows($holding, true),
            'stand_by' => $this->rows($troopers->where('status', EventTrooperStatus::STAND_BY), true),
            'cancelled' => $this->rows($troopers->where('status', EventTrooperStatus::CANCELLED), false),
            'guests' => $this->guests(),
            'sign_up' => (new EventDisplaySignUp($this->resource, $this->context))->toArray($request),
            'links' => $this->links(),
        ];
    }

    /**
     * @param  Collection<int, EventTrooper>  $event_troopers
     * @return list<array<string, mixed>>
     */
    private function rows(Collection $event_troopers, bool $numbered): array
    {
        return $event_troopers->values()
            ->map(fn (EventTrooper $et, int $i) => (new EventDisplayTrooperRow(
                $et,
                $this->context,
                $numbered ? $i + 1 : null,
            ))->resolve())
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function guests(): array
    {
        $guests_allowed = $this->context->event->guests_allowed;

        if ($guests_allowed !== null && $guests_allowed <= 0)
        {
            return [];
        }

        return $this->event_guests
            ->map(function (EventGuest $guest)
            {
                $guest->setRelation('event_shift', $this->resource);

                return (new EventDisplayGuestRow($guest, $this->context))->resolve();
            })
            ->values()
            ->all();
    }

    /**
     * How full the shift is, counted the way the limit counts: troopers going, not handlers.
     *
     * @return array<string, mixed>|null
     */
    private function fullness(): ?array
    {
        if (!$this->is_open)
        {
            return null;
        }

        $by_status = fn (EventTrooperStatus $status) => $this->event_troopers->where('status', $status)->count();

        return [
            'spots_left' => $this->trooperSpotsLeft(),
            'limit' => $this->context->event->troopers_allowed,
            'going' => $by_status(EventTrooperStatus::GOING),
            'tentative' => $by_status(EventTrooperStatus::TENTATIVE),
            'stand_by' => $this->standByCount(),
        ];
    }

    /**
     * @return list<array{id: int, name: string, going: int, limit: int|null}>
     */
    private function stationSummary(): array
    {
        if (!$this->usesStations())
        {
            return [];
        }

        return $this->event_shift_stations
            ->map(fn (EventShiftStation $station) => [
                'id' => $station->id,
                'name' => $station->name,
                'going' => $this->event_troopers
                    ->where('event_shift_station_id', $station->id)
                    ->where('status', EventTrooperStatus::GOING)
                    ->count(),
                'limit' => $station->troopers_allowed,
            ])
            ->values()
            ->all();
    }

    /**
     * "One troop per day" at the shift: a note when they're on another shift that day, or a
     * warning before they sign up for a second one.
     *
     * @return array{is_warning: bool, times: string, day: string}|null
     */
    private function sameDayNote(): ?array
    {
        $event = $this->context->event;
        $viewer = $this->context->viewer;
        $others = $event->sameDayShiftsFor($this->resource, $viewer);
        $is_on_shift = $event->isOnShift($this->resource, $viewer);

        if ($others->isEmpty() || (!$is_on_shift && !$this->is_open))
        {
            return null;
        }

        return [
            'is_warning' => !$is_on_shift,
            'times' => $others->map(fn (EventShift $s) => $s->compact_time_display)
                ->join(', ', ' '.__('events.and').' '),
            'day' => $this->shift_starts_at->localFormat('l, M j'),
        ];
    }

    /**
     * Add a friend, Add a guest, Add to calendar and the shift's CSV.
     *
     * @return array<string, mixed>
     */
    private function links(): array
    {
        $event = $this->context->event;
        $viewer = $this->context->viewer;
        $can_moderate = $this->context->can_moderate;
        $closed_moderator = $event->canModerateIfClosed($can_moderate);
        $may_add = $closed_moderator || ($viewer->is_adult && $this->is_open && $this->context->has_brief_ack);
        $is_with_them = $can_moderate || $this->isGoing($viewer);
        $mine = $this->signUpFor($viewer);

        return [
            'friend_form_url' => route('events.add-trooper-form', ['event_shift' => $this->resource]),
            'can_add_friend' => $may_add && $is_with_them && ($closed_moderator
                || ($event->friends_allowed !== 0 && $this->hasRemainingFriendSlots($viewer))),
            'can_add_guest' => $may_add && $is_with_them && $event->guests_allowed !== 0
                && $this->hasRemainingGuestSlots($viewer),
            'guest_url' => route('events.guest-signup', ['event_shift' => $this->resource]),
            'signup_url' => route('events.signup', ['event_shift' => $this->resource]),
            'calendar' => $mine !== null && $mine->status !== EventTrooperStatus::CANCELLED
                ? $this->calendar() : null,
            'csv_url' => $can_moderate ? route('events.download-shift-roster-csv', [
                'event' => $event,
                'event_shift' => $this->resource,
            ]) : null,
        ];
    }

    /**
     * @return array{google: string, outlook: string, yahoo: string, ics: string}
     */
    private function calendar(): array
    {
        $link = $this->createCalendarLink();

        return [
            'google' => $link->google(),
            'outlook' => $link->webOutlook(),
            'yahoo' => $link->yahoo(),
            'ics' => route('events.download-shift-ics', [
                'event' => $this->context->event,
                'event_shift' => $this->resource,
            ]),
        ];
    }
}
