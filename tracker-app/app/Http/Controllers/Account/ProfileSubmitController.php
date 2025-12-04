<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\ProfileRequest;
use App\Services\FlashMessageService;
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
    public function __invoke(ProfileRequest $request): RedirectResponse
    {
        $trooper = $request->user();

        $trooper->name = $request->validated('name');
        $trooper->email = $request->validated('email');
        $trooper->phone = $request->validated('phone');
        $trooper->theme = $request->validated('theme');

        $trooper->save();

        $this->flash->updated($trooper);

        return redirect()->route('account.profile');
    }
}
