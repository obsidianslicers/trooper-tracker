<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers;

use App\Enums\FlashType;
use App\Http\Controllers\PageController;
use App\Http\Requests\Admin\Troopers\ProfileRequest;
use App\Messages\Troopers\Commands\UpdateTrooper;
use App\Models\Trooper;
use Illuminate\Http\RedirectResponse;

/**
 * Class ProfileSubmitController
 *
 * Handles the form submission for updating a trooper's profile.
 */
class ProfileSubmitController extends PageController
{
    /**
     * Handle the incoming request to update a trooper's profile information
     *
     * This method validates the incoming data using the ProfileRequest, updates the
     * specified trooper's attributes, flashes a success message to the session,
     * and redirects the user back to the main trooper list.
     *
     * @param  ProfileRequest  $request  The validated form request
     * @param  Trooper  $trooper  The trooper to be updated
     * @return RedirectResponse A redirect response to the trooper list page
     */
    public function __invoke(ProfileRequest $request, Trooper $trooper): RedirectResponse
    {
        UpdateTrooper::call(trooper: $trooper, valid_data: $request->validated());

        FlashType::updated($trooper);

        return redirect()->route('admin.troopers.list');
    }
}
