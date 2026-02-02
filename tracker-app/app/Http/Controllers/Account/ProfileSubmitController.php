<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Features\Troopers\Commands\UpdateTrooperCommand;
use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Account\ProfileRequest;
use Illuminate\Http\RedirectResponse;

/**
 * Handles form submission for updating the authenticated trooper's profile.
 *
 * This controller validates profile data via ProfileRequest, dispatches
 * UpdateTrooperCommand to persist changes, and redirects back to the profile page.
 */
class ProfileSubmitController extends MagicBusController
{
    /**
     * Handle the incoming request to update the trooper's profile.
     */
    public function __invoke(ProfileRequest $request): RedirectResponse
    {
        $trooper = $request->user();

        $update_cmd = new UpdateTrooperCommand($trooper, $request->validated());

        $this->bus->send($update_cmd);

        $this->flash->updated($trooper);

        return redirect()->route('account.profile');
    }
}
