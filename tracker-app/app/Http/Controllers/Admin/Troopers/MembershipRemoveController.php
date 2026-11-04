<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers;

use App\Enums\FlashType;
use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Admin\Troopers\MembershipRemoveRequest;
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
class MembershipRemoveController extends MagicBusController
{
    /**
     * Handle the incoming request to remove a membership.
     */
    public function __invoke(MembershipRemoveRequest $request, Trooper $trooper, Organization $organization): RedirectResponse
    {
        RemoveTrooperMembership::call(trooper: $trooper, organization: $organization);

        FlashType::success("Removed {$trooper->display_name} from {$organization->name}");

        return redirect()->route('admin.troopers.membership', compact('trooper'));
    }
}
