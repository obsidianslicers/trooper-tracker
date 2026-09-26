<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Features\Events\Concerns\AssignsEventTrooperOrgCredit;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventGuest;
use App\Models\EventShiftStation;
use App\Models\EventTrooper;
use App\Models\Trooper;
use App\Notifications\Events\ManualSelectionApprovedNotification;
use App\Notifications\Events\ManualSelectionStandByNotification;
use App\Services\EventRosterCapacityService;
use Illuminate\Support\Collection;

/**
 * Handler for applying a bulk event-roster form submission.
 *
 * @implements CommandHandlerInterface<UpdateEventRosterCommand>
 */
readonly class UpdateEventRosterCommandHandler implements CommandHandlerInterface
{
    use AssignsEventTrooperOrgCredit;

    public function __construct(private EventRosterCapacityService $capacity) {}

    public function __invoke(object $message): mixed
    {
        $is_manual_selection_event = $message->event->status === EventStatus::MANUAL_SELECTION;

        $event_troopers = $message->event->troopers()
            ->with('event_shift.event_shift_stations', 'trooper.organizations')
            ->get();
        $event_guests = $this->resolveEventGuests($message->event);
        $costumes_by_id = $this->resolveCostumesByIdFor($message->validated_troopers);

        foreach ($message->validated_troopers as $id => $input)
        {
            $this->processEventTrooper(
                $event_troopers,
                (int) $id,
                $input,
                $message->allowed_org_ids,
                $is_manual_selection_event,
                $message->auth_trooper,
                $costumes_by_id
            );
        }

        foreach ($message->validated_guests as $id => $input)
        {
            $this->processEventGuest($event_guests, (int) $id, $input);
        }

        return null;
    }

    private function resolveCostumesByIdFor(array $validated_troopers): Collection
    {
        $submitted_costume_ids = collect($validated_troopers)
            ->map(fn ($input) => isset($input['costume_id']) && $input['costume_id'] !== '' ? (int) $input['costume_id'] : null)
            ->filter()
            ->unique()
            ->values()
            ->all();

        return Costume::findMany($submitted_costume_ids)->keyBy('id');
    }

    private function resolveEventGuests(Event $event): Collection
    {
        $shift_ids = $event->event_shifts()->pluck('id');

        return EventGuest::query()->whereIn(EventGuest::EVENT_SHIFT_ID, $shift_ids)->get();
    }

    private function processEventTrooper(
        Collection $event_troopers,
        int $id,
        array $input,
        ?array $allowed_org_ids,
        bool $is_manual_selection_event,
        Trooper $auth_trooper,
        Collection $costumes_by_id
    ): void {
        $event_trooper = $event_troopers->filter(fn ($et) => $et->id === $id)->first();

        if ($event_trooper === null)
        {
            return;
        }

        $new_status = $input['status'] ?? null;

        if ($new_status === null)
        {
            return;
        }

        $old_status = $event_trooper->status;
        $event_trooper->status = $new_status;
        $this->applyStationSelection($event_trooper, $input);
        $this->applyCapacityDowngrade($event_trooper);
        $this->applyCostumeCreditSelection($event_trooper, $input, $allowed_org_ids, $costumes_by_id, $old_status);

        if ($event_trooper->isDirty())
        {
            $event_trooper->save();
        }

        $this->dispatchManualSelectionNotifications($event_trooper, $old_status, $is_manual_selection_event, $auth_trooper);
    }

    private function applyCapacityDowngrade(EventTrooper $event_trooper): void
    {
        $station_has_room = $this->capacity->canGoAtStation(
            $event_trooper->event_shift,
            $event_trooper->event_shift_station_id,
            $event_trooper->id,
        );

        if ($event_trooper->status === EventTrooperStatus::GOING && !$station_has_room)
        {
            $event_trooper->status = EventTrooperStatus::STAND_BY;
        }
    }

    /**
     * An ATTENDED row's costume/org credit is the historical record (see
     * HasEventDisplayAssembler::buildDisplayOrganizations()). The costume dropdown and org
     * checkboxes are still built from the trooper's current live eligibility, so re-deriving
     * costume_organization_ids from that live data below can silently drop credit that's still
     * checked but no longer "live eligible" (e.g. the trooper switched clubs since attending).
     * When the submission doesn't actually request a different costume/credit for this row,
     * restore the stored values instead of trusting that live re-derivation.
     */
    private function applyCostumeCreditSelection(
        EventTrooper $event_trooper,
        array $input,
        ?array $allowed_org_ids,
        Collection $costumes_by_id,
        EventTrooperStatus $old_status
    ): void {
        $original_costume_id = $event_trooper->costume_id;
        $original_costume_organization_ids = $event_trooper->costume_organization_ids;
        $original_organization_id = $event_trooper->organization_id;
        $original_credited_root_ids = $event_trooper->creditedRootOrgIds();

        $has_submitted_org_selection = $this->applyCostumeAndOrgSelection($event_trooper, $input, $allowed_org_ids, $costumes_by_id);

        if ($has_submitted_org_selection)
        {
            $event_trooper->organization_id = null;
        }

        if ($this->creditSelectionUnchanged($old_status, $input, $original_costume_id, $original_credited_root_ids))
        {
            $event_trooper->costume_organization_ids = $original_costume_organization_ids;
            $event_trooper->organization_id = $original_organization_id;
        }
    }

    private function creditSelectionUnchanged(
        EventTrooperStatus $original_status,
        array $input,
        ?int $original_costume_id,
        array $original_credited_root_ids
    ): bool {
        if ($original_status !== EventTrooperStatus::ATTENDED)
        {
            return false;
        }

        $submitted_costume_id = isset($input['costume_id']) && $input['costume_id'] !== '' ? (int) $input['costume_id'] : null;

        if ($submitted_costume_id !== $original_costume_id)
        {
            return false;
        }

        $submitted_root_ids = array_map('intval', $input['organization_ids'] ?? []);

        return empty(array_diff($submitted_root_ids, $original_credited_root_ids))
            && empty(array_diff($original_credited_root_ids, $submitted_root_ids));
    }

    private function applyStationSelection(EventTrooper $event_trooper, array $input): void
    {
        if (!array_key_exists('event_shift_station_id', $input))
        {
            return;
        }

        $station_id = $input['event_shift_station_id'] !== '' && $input['event_shift_station_id'] !== null
            ? (int) $input['event_shift_station_id']
            : null;

        if ($station_id === null)
        {
            return;
        }

        $valid_station_ids = $event_trooper->event_shift->event_shift_stations->pluck(EventShiftStation::ID)->all();

        if (in_array($station_id, $valid_station_ids, true))
        {
            $event_trooper->event_shift_station_id = $station_id;
        }
    }

    private function dispatchManualSelectionNotifications(
        EventTrooper $event_trooper,
        EventTrooperStatus $old_status,
        bool $is_manual_selection_event,
        Trooper $auth_trooper
    ): void {
        if (!$is_manual_selection_event)
        {
            return;
        }

        if ($old_status === EventTrooperStatus::STAND_BY && $event_trooper->intendsToGo())
        {
            $event_trooper->trooper->notify(new ManualSelectionApprovedNotification($event_trooper, $auth_trooper));
        }

        if ($old_status === EventTrooperStatus::GOING && $event_trooper->status === EventTrooperStatus::STAND_BY)
        {
            $event_trooper->trooper->notify(new ManualSelectionStandByNotification($event_trooper, $auth_trooper));
        }
    }

    private function processEventGuest(Collection $event_guests, int $id, array $input): void
    {
        $event_guest = $event_guests->first(fn ($eg) => $eg->id === $id);

        if ($event_guest === null)
        {
            return;
        }

        $new_status = $input['status'] ?? null;

        if ($new_status === null)
        {
            return;
        }

        $event_guest->status = $new_status;
        $event_guest->save();
    }
}
