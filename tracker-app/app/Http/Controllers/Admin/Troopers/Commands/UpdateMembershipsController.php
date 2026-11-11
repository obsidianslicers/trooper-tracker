<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers\Commands;

use App\Http\Requests\Admin\Troopers\UpdateMembershipsRequest;
use App\Messages\Troopers\Commands\UpdateTrooperIdentifiers;
use App\Messages\Troopers\Commands\UpdateTrooperMemberships;
use App\Models\Organization;
use App\Models\Trooper;
use Inertia\Response as InertiaResponse;

/**
 * Handles the submission of trooper membership updates.
 *
 * Processes form submissions to update a trooper's organization memberships
 * and identifiers via UpdateTrooperIdentifiers and UpdateTrooperMemberships.
 * Moderators are restricted to organizations within their moderation scope.
 */
class UpdateMembershipsController extends ProfileCommandController
{
    /**
     * Handle the incoming request to update a trooper's organization memberships
     *
     * This method authorizes the user, validates the membership data, dispatches
     * UpdateTrooperIdentifiers and UpdateTrooperMemberships, and
     * redirects back to the membership page.
     *
     * @param  UpdateMembershipsRequest  $request  The incoming HTTP request
     * @param  Trooper  $trooper  The trooper whose memberships are being updated
     * @return InertiaResponse The refreshed profile page data as results
     */
    public function __invoke(UpdateMembershipsRequest $request, Trooper $trooper): InertiaResponse
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

        return $this->respondWithResults($request);
    }
}
