<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers\Commands;

use App\Enums\FlashType;
use App\Http\Controllers\PageController;
use App\Http\Requests\Admin\Troopers\RemoveMembershipRequest;
use App\Messages\Troopers\Commands\RemoveTrooperMembership;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Http\RedirectResponse;

/**
 * Removes a trooper's membership from a top-level organization.
 *
 * This deletes the trooper-organization membership record and clears member
 * assignments within that organization's hierarchy.
 */
class RemoveMembershipController extends PageController
{
    /**
     * Handle the incoming request to remove a membership.
     */
    public function __invoke(RemoveMembershipRequest $request, Trooper $trooper, Organization $organization): RedirectResponse
    {
        RemoveTrooperMembership::call(trooper: $trooper, organization: $organization);

        FlashType::success("Removed {$trooper->display_name} from {$organization->name}");

        return redirect()->route('admin.troopers.profile', compact('trooper'));
    }
}
