<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers;

use App\Enums\MembershipRole;
use App\Http\Controllers\MagicBusController;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Removes a trooper's membership from a top-level organization.
 *
 * This deletes the trooper-organization membership record and clears member
 * assignments within that organization's hierarchy.
 */
class MembershipRemoveController extends MagicBusController
{
    /**
     * Handle the incoming request to remove a membership.
     */
    public function __invoke(Request $request, Trooper $trooper, Organization $organization): RedirectResponse
    {
        $this->authorize('update', $trooper);

        abort_if($request->user()?->membership_role !== MembershipRole::ADMINISTRATOR, 403);

        DB::transaction(function () use ($trooper, $organization): void
        {
            TrooperOrganization::query()
                ->where(TrooperOrganization::TROOPER_ID, $trooper->id)
                ->where(TrooperOrganization::ORGANIZATION_ID, $organization->id)
                ->delete();

            $organization_ids = Organization::query()
                ->where(Organization::NODE_PATH, 'like', $organization->node_path.'%')
                ->pluck(Organization::ID);

            if ($organization_ids->isNotEmpty())
            {
                TrooperAssignment::query()
                    ->where(TrooperAssignment::TROOPER_ID, $trooper->id)
                    ->whereIn(TrooperAssignment::ORGANIZATION_ID, $organization_ids)
                    ->where(TrooperAssignment::IS_MEMBER, true)
                    ->update([
                        TrooperAssignment::IS_MEMBER => false,
                    ]);
            }
        });

        $this->flash->success("Removed {$trooper->display_name} from {$organization->name}");

        return redirect()->route('admin.troopers.membership', compact('trooper'));
    }
}
