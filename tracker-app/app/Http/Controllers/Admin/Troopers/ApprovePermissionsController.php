<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers;

use App\Enums\FlashType;
use App\Exceptions\ForumGroupUpdateException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Troopers\ApprovePermissionsRequest;
use App\Messages\Troopers\Commands\Permissions\ApproveTrooperPermissions;
use App\Models\PermissionRequest;
use Illuminate\Http\RedirectResponse;

/**
 * Approves a permission request and applies it to the trooper and their forum account.
 */
class ApprovePermissionsController extends Controller
{
    public function __invoke(
        ApprovePermissionsRequest $request,
        PermissionRequest $permission_request,
    ): RedirectResponse {
        try
        {
            ApproveTrooperPermissions::call(permission_request: $permission_request);

            FlashType::success('Permission request approved.');
        }
        catch (ForumGroupUpdateException $e)
        {
            FlashType::danger("Nothing was changed: {$e->getMessage()}");
        }

        return redirect()->route('admin.troopers.permissions.approvals');
    }
}
