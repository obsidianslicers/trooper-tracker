<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
use App\Features\Events\Concerns\AssignsEventTrooperOrgCredit;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\TrooperOrganization;

/**
 * @implements CommandHandlerInterface<AssignEventTrooperCreditCommand>
 */
readonly class AssignEventTrooperCreditCommandHandler implements CommandHandlerInterface
{
    use AssignsEventTrooperOrgCredit;

    public function __invoke(object $message): EventTrooper
    {
        $event_trooper = $message->event_trooper;
        $event_trooper->loadMissing('costume', 'trooper');

        $allowed_org_ids = $message->actor->resolveModeratorOrgIds();

        if ($message->is_override)
        {
            $this->applyOverride($event_trooper, $message->organization_ids, $allowed_org_ids);
            $event_trooper->save();

            return $event_trooper;
        }

        $costumes_by_id = $event_trooper->costume_id !== null
            ? collect([$event_trooper->costume_id => $event_trooper->costume])
            : collect();

        $has_submitted_org_selection = $this->applyCostumeAndOrgSelection(
            $event_trooper,
            [
                'costume_id' => $event_trooper->costume_id,
                'organization_ids' => $message->organization_ids,
            ],
            $allowed_org_ids,
            $costumes_by_id
        );

        if ($has_submitted_org_selection)
        {
            $event_trooper->organization_id = null;
        }

        $event_trooper->save();

        return $event_trooper;
    }

    /**
     * Every branch of AssignsEventTrooperOrgCredit re-derives credit from the trooper's own
     * *live* eligibility (getEligibleCreditParentOrganizations/getEligibleCreditOrganizations)
     * and intersects the submission against it. That's correct for the normal path, but the
     * fallback picker only ever appears when that live eligibility is already empty — so running
     * the submission through the same trait would silently discard it. The override instead
     * trusts the submitted org ids directly, re-validated against two independent things: what
     * the acting mod/admin is authorized to credit, and — just as important — which clubs the
     * trooper is *currently* a member of (tt_trooper_organizations). The display logic
     * (HasOrgCreditAnnotation) can only ever resolve a credited org against that second list, so
     * writing anything outside it would "succeed" but never actually render — the same failure
     * mode this tool exists to fix. This mirrors GetEventTroopersMissingCreditQueryHandler's own
     * fallback-option query exactly, so the server never accepts more than the picker offered.
     *
     * @param  array<int, int>  $organization_ids
     * @param  array<int, int>|null  $allowed_org_ids
     */
    private function applyOverride(EventTrooper $event_trooper, array $organization_ids, ?array $allowed_org_ids): void
    {
        $accessible_ids = $event_trooper->filterAccessibleRootOrgIds($organization_ids, $allowed_org_ids);

        $trooper_org_root_ids = $event_trooper->trooper->organizations()
            ->wherePivotNull(TrooperOrganization::DELETED_AT)
            ->get()
            ->map(fn (Organization $org) => $org->getPrimaryClub()->id)
            ->unique()
            ->all();

        $event_trooper->costume_organization_ids = array_values(array_intersect($accessible_ids, $trooper_org_root_ids));
        $event_trooper->organization_id = null;
    }
}
