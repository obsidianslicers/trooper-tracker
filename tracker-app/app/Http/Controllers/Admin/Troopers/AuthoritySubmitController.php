<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers;

use App\Features\Troopers\Commands\UpdateTrooperAuthorityCommand;
use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Admin\Troopers\AuthorityRequest;
use App\Models\Trooper;
use Illuminate\Http\RedirectResponse;

/**
 * Class AuthoritySubmitController
 *
 * Handles the form submission for a trooper's authority settings, including their
 * membership role and which organizations they are assigned to moderate.
 */
class AuthoritySubmitController extends MagicBusController
{
    /**
     * Handle the incoming request to update a trooper's authority settings
     *
     * This method validates the request, updates the trooper's membership role,
     * syncs their organization moderation assignments, and then redirects to the
     * main trooper list.
     *
     * @param  AuthorityRequest  $request  The validated form request
     * @param  Trooper  $trooper  The trooper whose authorities are being updated
     * @return RedirectResponse A redirect response to the trooper list page
     */
    public function __invoke(AuthorityRequest $request, Trooper $trooper): RedirectResponse
    {
        $membership_role = $request->validated('membership_role');

        $authority_cmd = new UpdateTrooperAuthorityCommand($trooper, $membership_role, $request->validated('organizations'));

        $this->bus->send($authority_cmd);

        $this->flash->updated($trooper);

        return redirect()->route('admin.troopers.list');
    }
}
