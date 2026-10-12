<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers;

use App\Enums\FlashType;
use App\Enums\MembershipRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Troopers\RequestPermissionsRequest;
use App\Messages\Troopers\Commands\Permissions\RequestTrooperPermissions;
use App\Models\Trooper;
use Illuminate\Http\RedirectResponse;

/**
 * Submits a moderator's permission request for administrator review.
 */
class RequestPermissionsSubmitController extends Controller
{
    public function __invoke(RequestPermissionsRequest $request): RedirectResponse
    {
        $membership_role = $request->validated('membership_role');

        RequestTrooperPermissions::call(
            trooper: Trooper::findOrFail($request->validated('trooper_id')),
            membership_role: MembershipRole::tryFrom((string) $membership_role),
            moderated_organization_ids: $request->validated('moderated_organization_ids', []),
            forum_group_ids_to_add: $request->validated('forum_group_ids_to_add', []),
            forum_group_ids_to_remove: $request->validated('forum_group_ids_to_remove', []),
            note: $request->validated('note'),
        );

        FlashType::success('Permission request sent to the administrators.');

        return redirect()->route('admin.troopers.permissions.request');
    }
}
