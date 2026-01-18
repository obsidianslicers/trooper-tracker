<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Features\Troopers\Commands\UpdateTrooperCommand;
use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Account\ProfileRequest;
use App\Services\Troopers\UpdateTrooperProfileCommand;
use Illuminate\Http\RedirectResponse;

/**
 * Handles the form submission for updating the authenticated user's profile.
 */
class ProfileSubmitController extends MagicBusController
{
    /**
     * Handle the incoming request to update the authenticated user's profile.
     *
     * This method validates the request data, updates the user's trooper record,
     * flashes a success message, and redirects back to the profile page.
     *
     * @param ProfileRequest $request The validated profile form request.
     * @return RedirectResponse A redirect response to the account profile page.
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
