<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers;

use App\Features\Troopers\Commands\UpdateTrooperCommand;
use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Admin\Troopers\ProfileRequest;
use App\Models\Trooper;
use Illuminate\Http\RedirectResponse;

/**
 * Class ProfileSubmitController
 *
 * Handles the form submission for updating a trooper's profile.
 */
class ProfileSubmitController extends MagicBusController
{
    /**
     * Handle the incoming request to update a trooper's profile information.
     *
     * This method validates the incoming data using the ProfileRequest, updates the
     * specified trooper's attributes, flashes a success message to the session,
     * and redirects the user back to the main trooper list.
     *
     * @param  ProfileRequest  $request  The validated form request.
     * @param  Trooper  $trooper  The trooper to be updated.
     * @return RedirectResponse A redirect response to the trooper list page.
     */
    public function __invoke(ProfileRequest $request, Trooper $trooper): RedirectResponse
    {
        $profile_cmd = new UpdateTrooperCommand($trooper, $request->validated());

        $this->bus->send($profile_cmd);

        $this->flash->updated($trooper);

        return redirect()->route('admin.troopers.list');
    }
}
