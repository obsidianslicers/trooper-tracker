<?php

declare(strict_types=1);

namespace App\Models\Observers;

use App\Facades\TroopTrackerFacade;
use App\Jobs\UpdateEventForumThreadJob;
use App\Models\Base\TrooperCostume;
use App\Models\Costume;
use App\Models\EventOrganization;
use App\Models\EventTrooper;
use App\Models\OrganizationCostume;
use App\Models\TrooperAssignment;

/**
 * Handles lifecycle events for the EventTrooper model.
 *
 * This observer automatically manages the organization assignments when costume
 * selections change. It ensures that when a trooper's costume or backup costume
 * is updated, the associated organization IDs are calculated and stored based on:
 *
 * 1. The organizations approved for the event shift (via can_attend flag)
 * 2. The trooper's approval for that costume in each organization
 *
 * The observer persists `costume_organization_ids` and `backup_costume_organization_ids`
 * as JSON arrays in the database for efficient querying and display.
 *
 * @see EventTrooper::costume_organization_ids
 * @see EventTrooper::backup_costume_organization_ids
 */
class EventTrooperObserver
{
    /**
     * Handle the EventTrooper saving event.
     *
     * When either costume_id or backup_costume_id is changed, this method
     * recalculates the organization associations for both costumes. This ensures
     * the organization IDs stored in the model remain accurate when costumes change.
     *
     * @param  \App\Models\EventTrooper  $event_trooper  The event trooper being saved
     * @return void
     */
    public function saving(EventTrooper $event_trooper): void
    {
        $attributes = [
            'costume_id',
            'backup_costume_id',
        ];

        if ($event_trooper->isDirty($attributes))
        {
            $event_shift = $event_trooper->event_shift;
            $event = $event_shift->event;

            $organization_ids = $event->event_organizations()->pluckCanAttend($event_shift)->toArray();

            $costume_org_ids = $this->assignOrganizations($event_trooper->trooper_id, $event_trooper->costume_id, $organization_ids);
            $event_trooper->costume_organization_ids = $costume_org_ids;
            $event_trooper->backup_costume_organization_ids = $this->assignOrganizations($event_trooper->trooper_id, $event_trooper->backup_costume_id, $organization_ids);

            // Auto-set organization_id when the costume resolves to exactly one
            // per-org-limited organization and no org has been explicitly chosen.
            if ($event_trooper->organization_id === null && count($costume_org_ids) === 1)
            {
                $limited_org_ids = $event->event_organizations
                    ->filter(fn ($eo) => $eo->troopers_allowed !== null || $eo->handlers_allowed !== null)
                    ->pluck(EventOrganization::ORGANIZATION_ID)
                    ->all();

                if (in_array($costume_org_ids[0], $limited_org_ids, true))
                {
                    $event_trooper->organization_id = $costume_org_ids[0];
                }
            }
        }
    }

    public function created(EventTrooper $event_trooper): void
    {
        $this->queueForumThreadSync($event_trooper);
    }

    public function updated(EventTrooper $event_trooper): void
    {
        if ($event_trooper->wasChanged([
            EventTrooper::STATUS,
            EventTrooper::COSTUME_ID,
        ]))
        {
            $this->queueForumThreadSync($event_trooper);
        }
    }

    public function deleted(EventTrooper $event_trooper): void
    {
        $this->queueForumThreadSync($event_trooper);
    }

    /**
     * Determine which organizations a costume is approved for in this event.
     *
     * Queries the OrganizationCostume table to find all organizations where:
     * - The costume is linked (costume_id matches)
     * - The organization is approved for this event (in $organization_ids)
     * - The trooper has approval rights (TrooperCostume exists)
     *
     * This ensures that organization assignments reflect both the event's
     * configuration and the trooper's actual costume approvals.
     *
     * @param  int         $trooper_id        The ID of the trooper to check approvals for
     * @param  int|null    $costume_id        The ID of the costume (null returns empty array)
     * @param  array       $organization_ids  The organization IDs allowed for this event shift
     * @return array<int>  Array of organization IDs where this costume is approved for use
     */
    private function assignOrganizations(int $trooper_id, ?int $costume_id, array $organization_ids): array
    {
        if ($costume_id === null)
        {
            return [];
        }

        $costume = Costume::find($costume_id);

        if ($costume?->countsAsHandler())
        {
            return TrooperAssignment::where(TrooperAssignment::TROOPER_ID, $trooper_id)
                ->where(TrooperAssignment::IS_MEMBER, true)
                ->whereIn(TrooperAssignment::ORGANIZATION_ID, $organization_ids)
                ->pluck(TrooperAssignment::ORGANIZATION_ID)
                ->toArray();
        }

        return OrganizationCostume::query()
            ->where('costume_id', $costume_id)
            ->whereIn('organization_id', $organization_ids)
            ->whereHas('trooper_costumes', function ($query) use ($trooper_id)
            {
                $query->where(TrooperCostume::TROOPER_ID, $trooper_id);
            })
            ->pluck('organization_id')
            ->toArray();
    }

    private function queueForumThreadSync(EventTrooper $event_trooper): void
    {
        if (! TroopTrackerFacade::isXenforoIntegrationConfigured())
        {
            return;
        }

        $event_trooper->loadMissing('event_shift.event');

        $event_id = $event_trooper->event_shift?->event?->getKey();

        if ($event_id === null)
        {
            return;
        }

        dispatch(new UpdateEventForumThreadJob($event_id));
    }
}
