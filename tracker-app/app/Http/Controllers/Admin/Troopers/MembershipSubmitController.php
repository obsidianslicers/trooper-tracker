<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers;

use App\Features\Troopers\Commands\UpdateTrooperIdentifiersCommand;
use App\Features\Troopers\Commands\UpdateTrooperMembershipsCommand;
use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Admin\Troopers\MembershipRequest;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Http\RedirectResponse;

/**
 * Handles the submission of trooper membership updates.
 *
 * Processes form submissions to update a trooper's organization memberships
 * and identifiers via UpdateTrooperIdentifiersCommand and UpdateTrooperMembershipsCommand.
 * Moderators are restricted to organizations within their moderation scope.
 */
class MembershipSubmitController extends MagicBusController
{
    /**
     * Handle the incoming request to update a trooper's organization memberships
     *
     * This method authorizes the user, validates the membership data, dispatches
     * UpdateTrooperIdentifiersCommand and UpdateTrooperMembershipsCommand, and
     * redirects back to the membership page.
     *
     * @param  MembershipRequest  $request  The incoming HTTP request
     * @param  Trooper  $trooper  The trooper whose memberships are being updated
     * @return RedirectResponse A redirect to the membership page
     */
    public function __invoke(MembershipRequest $request, Trooper $trooper): RedirectResponse
    {
        $this->authorize('update', $trooper);

        $actor = $request->user();
        $organizations = $request->validated('organizations', []);

        $allowed_org_ids = Organization::ofTypeOrganizations()
            ->moderatedBy($actor)
            ->pluck(Organization::ID)
            ->toArray();

        $organizations = array_intersect_key($organizations, array_flip($allowed_org_ids));

        $identifier_cmd = new UpdateTrooperIdentifiersCommand($trooper, $organizations);

        $this->bus->send($identifier_cmd);

        $membership_cmd = new UpdateTrooperMembershipsCommand($trooper, $organizations);

        $this->bus->send($membership_cmd);

        $data = compact('trooper');

        return redirect()->route('admin.troopers.membership', $data);
    }
}
