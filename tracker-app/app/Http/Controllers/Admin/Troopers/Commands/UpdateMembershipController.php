<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers\Commands;

use App\Enums\FlashType;
use App\Http\Requests\Admin\Troopers\UpdateMembershipRequest;
use App\Messages\Troopers\Commands\UpdateTrooperIdentifiers;
use App\Messages\Troopers\Commands\UpdateTrooperMemberships;
use App\Models\Organization;
use App\Models\Trooper;
use Inertia\Response as InertiaResponse;

/**
 * Handles the update of a single organization membership for a trooper.
 *
 * Updates the identifier, assignment and status for one organization via
 * UpdateTrooperIdentifiers and UpdateTrooperMemberships. Moderators are
 * restricted to organizations within their moderation scope.
 */
class UpdateMembershipController extends ProfileCommandController
{
    public function __invoke(
        UpdateMembershipRequest $request,
        Trooper $trooper,
        Organization $organization,
    ): InertiaResponse {
        $this->authorize('update', $trooper);

        $in_scope = Organization::ofTypeOrganizations()
            ->moderatedBy($request->user())
            ->whereKey($organization->id)
            ->exists();

        abort_unless($in_scope, 403);

        $valid_data = [$organization->id => $request->validated()];

        UpdateTrooperIdentifiers::call(trooper: $trooper, valid_data: $valid_data);

        UpdateTrooperMemberships::call(trooper: $trooper, valid_data: $valid_data);

        FlashType::success("Updated {$organization->name} membership.");

        return $this->respondWithResults($request);
    }
}
