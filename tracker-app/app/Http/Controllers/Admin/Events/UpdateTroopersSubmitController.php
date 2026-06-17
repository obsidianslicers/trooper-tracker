<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Admin\Events\UpdateTroopersRequest;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventGuest;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Notifications\Events\ManualSelectionApprovedNotification;
use App\Notifications\Events\ManualSelectionStandByNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;

class UpdateTroopersSubmitController extends MagicBusController
{
    public function __invoke(UpdateTroopersRequest $request, Event $event): RedirectResponse
    {
        $this->authorize('update', $event);

        $auth_trooper = $request->user();
        $is_manual_selection_event = $event->status === EventStatus::MANUAL_SELECTION;
        $allowed_org_ids = $auth_trooper->resolveModeratorOrgIds();

        $event_troopers = $event->troopers()->with('trooper.organizations')->get();
        $event_guests = $this->resolveEventGuests($event);

        $validated_troopers = $request->validated('troopers', []);

        $submitted_costume_ids = collect($validated_troopers)
            ->map(fn ($input) => isset($input['costume_id']) && $input['costume_id'] !== '' ? (int) $input['costume_id'] : null)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $costumes_by_id = Costume::findMany($submitted_costume_ids)->keyBy('id');

        foreach ($validated_troopers as $id => $input)
        {
            $this->processEventTrooper($event_troopers, (int) $id, $input, $allowed_org_ids, $is_manual_selection_event, $auth_trooper, $costumes_by_id);
        }

        foreach ($request->validated('guests', []) as $id => $input)
        {
            $this->processEventGuest($event_guests, (int) $id, $input);
        }

        $this->flash->updated($event);

        return redirect()->route('admin.events.troopers', compact('event'));
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

        $this->applyCostumeAndOrgSelection($event_trooper, $input, $allowed_org_ids, $costumes_by_id);

        $event_trooper->organization_id = null;
        $event_trooper->save();

        $this->dispatchManualSelectionNotifications($event_trooper, $old_status, $is_manual_selection_event, $auth_trooper);
    }

    private function applyCostumeAndOrgSelection(EventTrooper $event_trooper, array $input, ?array $allowed_org_ids, Collection $costumes_by_id): void
    {
        $submitted_costume_id = isset($input['costume_id']) && $input['costume_id'] !== '' ? (int) $input['costume_id'] : null;
        $costume = $submitted_costume_id !== null ? $costumes_by_id->get($submitted_costume_id) : null;
        $has_submitted_org_selection = array_key_exists('organization_selection', $input)
            || array_key_exists('organization_ids', $input);

        if ($costume !== null)
        {
            $submitted_parent_ids = array_map('intval', $input['organization_ids'] ?? []);
            $this->applyWithCostume($event_trooper, $costume, $submitted_parent_ids, $allowed_org_ids, $has_submitted_org_selection);
        }
        else
        {
            $submitted_org_ids = array_map('intval', $input['organization_ids'] ?? []);
            $this->applyWithoutCostume($event_trooper, $submitted_org_ids, $allowed_org_ids, $has_submitted_org_selection);
        }
    }

    private function applyWithCostume(
        EventTrooper $event_trooper,
        Costume $costume,
        array $submitted_parent_ids,
        ?array $allowed_org_ids,
        bool $has_submitted_org_selection
    ): void {
        $event_trooper->costume_id = $costume->id;
        $event_trooper->is_handler = $costume->countsAsHandler();

        if (!$has_submitted_org_selection)
        {
            return;
        }

        if ($costume->countsAsHandler())
        {
            $filtered_ids = $allowed_org_ids === null
                ? $submitted_parent_ids
                : array_values(array_filter($submitted_parent_ids, fn ($id) => in_array($id, $allowed_org_ids, true)));
            $event_trooper->costume_organization_ids = $event_trooper->childOrgIdsForSelectedParents($filtered_ids);

            return;
        }

        $filtered_parent_ids = $allowed_org_ids === null
            ? $submitted_parent_ids
            : array_values(array_filter($submitted_parent_ids, fn ($id) => in_array($id, $allowed_org_ids, true)));

        $event_trooper->costume_organization_ids = $this->costumeChildOrgIdsForParents($event_trooper, $costume, $filtered_parent_ids);
    }

    private function applyWithoutCostume(
        EventTrooper $event_trooper,
        array $submitted_org_ids,
        ?array $allowed_org_ids,
        bool $has_submitted_org_selection
    ): void {
        $event_trooper->costume_id = null;

        if (!$has_submitted_org_selection)
        {
            return;
        }

        $eligible_parent_ids = $event_trooper->getEligibleCreditParentOrganizations()->pluck('id')->toArray();

        $event_trooper->costume_organization_ids = array_values(array_filter(
            $submitted_org_ids,
            fn ($id) => in_array($id, $eligible_parent_ids, true)
                && ($allowed_org_ids === null || in_array($id, $allowed_org_ids, true))
        ));
    }

    private function costumeChildOrgIdsForParents(EventTrooper $event_trooper, Costume $costume, array $submitted_parent_ids): array
    {
        $approved_child_ids = OrganizationCostume::where('costume_id', $costume->id)
            ->whereHas('trooper_costumes', fn ($q) => $q->where('trooper_id', $event_trooper->trooper_id))
            ->pluck('organization_id')
            ->toArray();
        $approved_orgs = Organization::findMany($approved_child_ids)->keyBy('id');

        return collect($approved_child_ids)
            ->filter(function ($child_id) use ($approved_orgs, $submitted_parent_ids) {
                $org = $approved_orgs->get($child_id);
                $root_id = $org ? (int) explode(':', $org->node_path)[0] : (int) $child_id;

                return in_array($root_id, $submitted_parent_ids, true);
            })
            ->values()
            ->all();
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
