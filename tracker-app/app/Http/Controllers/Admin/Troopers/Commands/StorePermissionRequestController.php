<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers\Commands;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Troopers\Commands\StorePermissionRequestRequest;
use App\Messages\Troopers\Commands\Permissions\RequestTrooperPermissions;
use App\Models\Trooper;
use Illuminate\Http\RedirectResponse;

/**
 * Submits a moderator's permission request for administrator review.
 */
class StorePermissionRequestController extends Controller
{
    public function __invoke(StorePermissionRequestRequest $request): RedirectResponse
    {
        RequestTrooperPermissions::call(
            trooper: Trooper::findOrFail($request->validated('trooper_id')),
            membership_role: $request->validated('membership_role') ?: null,
            moderated_organization_ids: $request->validated('moderated_organization_ids', []),
            note: $request->validated('note'),
            extensions: $request->validated('extensions', []),
        );

        FlashType::success(__('admin.Permission request sent to the administrators.'));

        return redirect()->route('admin.troopers.permissions.create');
    }
}
