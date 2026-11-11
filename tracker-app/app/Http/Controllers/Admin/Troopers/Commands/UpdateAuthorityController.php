<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers\Commands;

use App\Enums\FlashType;
use App\Http\Controllers\PageController;
use App\Http\Requests\Admin\Troopers\UpdateAuthorityRequest;
use App\Messages\Troopers\Commands\UpdateTrooperAuthority;
use App\Models\Trooper;
use Illuminate\Http\RedirectResponse;

/**
 * class UpdateAuthorityController
 *
 * Handles the form submission for a trooper's authority settings, including their
 * membership role and which organizations they are assigned to moderate.
 */
class UpdateAuthorityController extends PageController
{
    /**
     * Handle the incoming request to update a trooper's authority settings
     *
     * This method validates the request, updates the trooper's membership role,
     * syncs their organization moderation assignments, and then redirects to the
     * main trooper list.
     *
     * @param  UpdateAuthorityRequest  $request  The validated form request
     * @param  Trooper  $trooper  The trooper whose authorities are being updated
     * @return RedirectResponse A redirect response to the trooper list page
     */
    public function __invoke(UpdateAuthorityRequest $request, Trooper $trooper): RedirectResponse
    {
        $membership_role = $request->validated('membership_role');
        $organizations = $request->validated('organizations', []);

        UpdateTrooperAuthority::call(
            trooper: $trooper,
            membership_role: $membership_role,
            valid_data: $organizations,
        );

        FlashType::updated($trooper);

        return redirect()->route('admin.troopers.profile', compact('trooper'));
    }
}
