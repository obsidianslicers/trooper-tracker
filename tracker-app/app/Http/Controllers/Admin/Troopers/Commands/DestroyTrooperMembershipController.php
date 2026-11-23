<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers\Commands;

use App\Enums\FlashType;
use App\Http\Requests\Admin\Troopers\Commands\DestroyTrooperMembershipRequest;
use App\Messages\Troopers\Commands\RemoveTrooperMembership;
use App\Models\Organization;
use App\Models\Trooper;
use Inertia\Response as InertiaResponse;

/**
 * Removes a trooper's membership from a top-level organization.
 *
 * This deletes the trooper-organization membership record and clears member
 * assignments within that organization's hierarchy.
 */
class DestroyTrooperMembershipController extends ProfileCommandController
{
    /**
     * Handle the incoming request to remove a membership.
     */
    public function __invoke(DestroyTrooperMembershipRequest $request, Trooper $trooper, Organization $organization): InertiaResponse
    {
        RemoveTrooperMembership::call(trooper: $trooper, organization: $organization);

        FlashType::success(__('admin.Removed :trooper from :organization', ['trooper' => $trooper->display_name, 'organization' => $organization->name]));

        return $this->respondWithResults($request);
    }
}
