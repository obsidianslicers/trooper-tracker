<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account\Commands;

use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Account\SetupRequest;
use App\Messages\Troopers\Commands\SetupTrooper;
use Illuminate\Http\RedirectResponse;

/**
 * Handles form submission for trooper organization membership setup.
 *
 * This controller validates organization selections, marks setup as completed,
 * persists organization memberships, and redirects to the costume setup page.
 * This is the first step in trooper onboarding after account approval.
 */
class SetupSubmitController extends MagicBusController
{
    /**
     * Handle the incoming request to process organization setup submission.
     *
     * @param  SetupRequest  $request  The validated setup request
     * @return RedirectResponse Redirect to the costume setup page
     */
    public function __invoke(SetupRequest $request): RedirectResponse
    {
        $trooper = $request->user();

        SetupTrooper::call(
            trooper: $trooper,
            legal_name: $request->validated('legal_name'),
            theme: $request->validated('theme'),
            email: $request->validated('email'),
            notification_frequency: $request->validated('notification_frequency')
        );

        return redirect()->route('account.index');
    }
}
