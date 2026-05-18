<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers;

use App\Enums\MembershipRole;
use App\Features\Troopers\Commands\RemoveTrooperMembershipCommand;
use App\Http\Controllers\MagicBusController;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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

        $this->bus->send(new RemoveTrooperMembershipCommand($trooper, $organization));

        $this->flash->success("Removed {$trooper->display_name} from {$organization->name}");

        return redirect()->route('admin.troopers.membership', compact('trooper'));
    }
}
