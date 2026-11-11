<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers\Commands;

use App\Enums\FlashType;
use App\Http\Requests\Admin\Troopers\UpdateProfileRequest;
use App\Messages\Troopers\Commands\UpdateTrooper;
use App\Models\Trooper;
use Inertia\Response as InertiaResponse;

/**
 * class UpdateProfileController
 *
 * Handles the form submission for updating a trooper's profile.
 */
class UpdateProfileController extends ProfileCommandController
{
    /**
     * Handle the incoming request to update a trooper's profile information
     *
     * This method validates the incoming data using the UpdateProfileRequest, updates the
     * specified trooper's attributes, flashes a success message to the session,
     * and redirects the user back to the main trooper list.
     *
     * @param  UpdateProfileRequest  $request  The validated form request
     * @param  Trooper  $trooper  The trooper to be updated
     * @return InertiaResponse The refreshed profile page data as results
     */
    public function __invoke(UpdateProfileRequest $request, Trooper $trooper): InertiaResponse
    {
        UpdateTrooper::call(trooper: $trooper, valid_data: $request->validated());

        FlashType::updated($trooper);

        return $this->respondWithResults($request);
    }
}
