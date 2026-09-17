<?php

declare(strict_types=1);

namespace App\Features\Events\Concerns;

use App\Models\Costume;
use App\Models\EventTrooper;
use App\Models\Organization;
use Illuminate\Support\Collection;

/**
 * Shared org-credit assignment logic for an EventTrooper row.
 *
 * Extracted from UpdateEventRosterCommandHandler so every entry point that can set
 * organization_id/costume_organization_ids (the bulk roster editor and the missing-credit
 * fix tool) goes through one implementation instead of drifting apart
 */
trait AssignsEventTrooperOrgCredit
{
    protected function applyCostumeAndOrgSelection(EventTrooper $event_trooper, array $input, ?array $allowed_org_ids, Collection $costumes_by_id): bool
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

        return $has_submitted_org_selection;
    }

    protected function applyWithCostume(
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
            $filtered_ids = $event_trooper->filterAccessibleRootOrgIds($submitted_parent_ids, $allowed_org_ids);
            $event_trooper->costume_organization_ids = $event_trooper->childOrgIdsForSelectedParents($filtered_ids);

            return;
        }

        $filtered_parent_ids = $event_trooper->filterAccessibleRootOrgIds(
            $submitted_parent_ids,
            $allowed_org_ids
        );

        $event_trooper->costume_organization_ids = $this->costumeChildOrgIdsForParents($event_trooper, $costume, $filtered_parent_ids);
    }

    protected function applyWithoutCostume(
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
        $accessible_parent_ids = $event_trooper->filterAccessibleRootOrgIds(
            $eligible_parent_ids,
            $allowed_org_ids
        );

        $event_trooper->costume_organization_ids = array_values(array_filter(
            $submitted_org_ids,
            fn ($id) => in_array($id, $accessible_parent_ids, true)
        ));
    }

    protected function costumeChildOrgIdsForParents(EventTrooper $event_trooper, Costume $costume, array $submitted_parent_ids): array
    {
        $approved_child_ids = $costume->approvedOrgIdsForTrooper($event_trooper->trooper_id);
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
}
