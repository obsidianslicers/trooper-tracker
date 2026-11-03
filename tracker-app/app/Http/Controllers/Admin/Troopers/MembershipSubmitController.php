<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers;

use App\Http\Controllers\PageController;
use App\Http\Requests\Admin\Troopers\MembershipRequest;
use App\Messages\Troopers\Commands\UpdateTrooperIdentifiers;
use App\Messages\Troopers\Commands\UpdateTrooperMemberships;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Http\RedirectResponse;

/**
 * Handles the submission of trooper membership updates.
 *
 * Processes form submissions to update a trooper's organization memberships
 * and identifiers via UpdateTrooperIdentifiers and UpdateTrooperMemberships.
 * Moderators are restricted to organizations within their moderation scope.
 */
class MembershipSubmitController extends PageController
{
    /**
     * Handle the incoming request to update a trooper's organization memberships
     *
     * This method authorizes the user, validates the membership data, dispatches
     * UpdateTrooperIdentifiers and UpdateTrooperMemberships, and
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

        UpdateTrooperIdentifiers::call(trooper: $trooper, valid_data: $organizations);

        UpdateTrooperMemberships::call(trooper: $trooper, valid_data: $organizations);

        $data = compact('trooper');

        return redirect()->route('admin.troopers.membership', $data);
    }
}
