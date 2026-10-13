<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Troopers\DenyPermissionsRequest;
use App\Messages\Troopers\Commands\Permissions\DenyTrooperPermissions;
use App\Models\PermissionRequest;
use Illuminate\Http\RedirectResponse;

/**
 * Denies a permission request without changing the trooper.
 */
class DenyPermissionsController extends Controller
{
    public function __invoke(
        DenyPermissionsRequest $request,
        PermissionRequest $permission_request,
    ): RedirectResponse {
        DenyTrooperPermissions::call(
            permission_request: $permission_request,
            denial_reason: $request->validated('denial_reason'),
        );

        FlashType::success('Permission request denied.');

        return redirect()->route('admin.troopers.permissions.approvals');
    }
}
