<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\ProfileRequest;
use App\Services\FlashMessageService;
use App\Services\Troopers\UpdateTrooperProfileCommand;
use Illuminate\Http\RedirectResponse;

/**
 * Handles the form submission for updating the authenticated user's profile.
 */
class ProfileSubmitController extends Controller
{
    /**
     * ProfileSubmitController constructor.
     *
     * @param FlashMessageService $flash The service for creating flash messages.
     */
    public function __construct(private readonly FlashMessageService $flash)
    {
    }

    /**
     * Handle the incoming request to update the authenticated user's profile.
     *
     * This method validates the request data, updates the user's trooper record,
     * flashes a success message, and redirects back to the profile page.
     *
     * @param ProfileRequest $request The validated profile form request.
     * @return RedirectResponse A redirect response to the account profile page.
     */
    public function __invoke(
        ProfileRequest $request,
        UpdateTrooperProfileCommand $update_profile): RedirectResponse
    {
        $trooper = $request->user();

        $update_profile($trooper, $request->validated());

        $trooper->save();

        $this->flash->updated($trooper);

        return redirect()->route('account.profile');
    }
}
