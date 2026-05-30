<?php

declare(strict_types=1);

namespace App\Models\Observers;

use App\Models\Costume;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\TrooperAssignment;
use App\Models\TrooperCostume;
use Exception;

/**
 * Handles lifecycle events for the TrooperAssignment model.
 */
class TrooperAssignmentObserver
{
    /**
     * Handle the TrooperAssignment "saving" event.
     *
     * Enforces the business rule that a trooper can only be a "member" of an
     * organization that is a leaf node (has no children).
     *
     * @param TrooperAssignment $trooper_assignment The trooper assignment instance being saved.
     * @return void
     * @throws Exception if a trooper is assigned as a member to a non-leaf organization.
     */
    public function saving(TrooperAssignment $trooper_assignment): void
    {
        if ($trooper_assignment->trooper->is_visitor)
        {
            $organization = $trooper_assignment->organization;

            if ($organization && $organization->depth > 1)
            {
                throw new Exception('Visitors can only join top-level organizations.');
            }
        }

        // Membership is allowed at any organizational level (org, region, or unit).

        if (!$trooper_assignment->is_member)
        {
            return;
        }

        $organization = $trooper_assignment->organization;
        $node_path = $organization->node_path;

        $conflict_query = TrooperAssignment::query()
            ->where(TrooperAssignment::TROOPER_ID, $trooper_assignment->trooper_id)
            ->where(TrooperAssignment::IS_MEMBER, true)
            ->where(TrooperAssignment::ID, '!=', (int) $trooper_assignment->getKey())
            ->whereHas('organization', function ($query) use ($node_path): void
            {
                $query->where(Organization::NODE_PATH, 'like', $node_path . '%')
                    ->orWhereRaw('? LIKE CONCAT(' . Organization::NODE_PATH . ', "%")', [$node_path]);
            });

        if ($conflict_query->exists())
        {
            throw new Exception(
                'Trooper can only have one primary membership in the same organization hierarchy.'
            );
        }
    }

    public function saved(TrooperAssignment $trooper_assignment): void
    {
        if ($trooper_assignment->wasChanged(TrooperAssignment::IS_MEMBER))
        {
            $this->syncSpecialCostumes($trooper_assignment);
        }
    }

    public function created(TrooperAssignment $trooper_assignment): void
    {
        if ($trooper_assignment->is_member)
        {
            $this->syncSpecialCostumes($trooper_assignment);
        }
    }

    private function syncSpecialCostumes(TrooperAssignment $trooper_assignment): void
    {
        $org_costumes = OrganizationCostume::query()
            ->whereHas('costume', fn ($q) => $q->whereIn(Costume::NAME, [Costume::HANDLER, Costume::COMMAND_STAFF]))
            ->where(OrganizationCostume::ORGANIZATION_ID, $trooper_assignment->organization_id)
            ->get();

        foreach ($org_costumes as $org_costume)
        {
            $trooper_costume = $trooper_assignment->trooper
                ->trooper_costumes()
                ->withTrashed()
                ->where(TrooperCostume::ORGANIZATION_COSTUME_ID, $org_costume->id)
                ->first();

            if ($trooper_assignment->is_member)
            {
                if ($trooper_costume === null)
                {
                    $trooper_assignment->trooper->trooper_costumes()->create([
                        TrooperCostume::ORGANIZATION_COSTUME_ID => $org_costume->id,
                    ]);
                }
                else
                {
                    $trooper_costume->restore();
                }
            }
            else
            {
                $trooper_costume?->delete();
            }
        }
    }
}
