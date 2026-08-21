<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Features\Troopers\Commands\UpdateTrooperCommand;
use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Account\SetupRequest;
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

        $this->bus->send(new UpdateTrooperCommand($trooper, $request->validated(), true));

        return redirect()->route('account.index');
    }
}
