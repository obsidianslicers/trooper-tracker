<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Features\Troopers\Commands\UpdateTrooperCommand;
use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Account\ProfileRequest;
use App\Services\Troopers\UpdateTrooperProfileCommand;
use Illuminate\Http\RedirectResponse;

/**
 * Handles form submission for updating the authenticated trooper's profile.
 *
 * This controller follows the ADR pattern as an Action that:
 * - Validates profile data via ProfileRequest
 * - Dispatches UpdateTrooperCommand to persist changes
 * - Flashes success message to session
 * - Redirects back to profile page
 *
 * Profile updates include personal information such as name, email,
 * phone number, and other trooper details.
 */
class ProfileSubmitController extends MagicBusController
{
    /**
     * Handle the incoming request to update the trooper's profile.
     *
     * Workflow:
     * 1. Retrieves the authenticated trooper from the request
     * 2. Creates UpdateTrooperCommand with validated data
     * 3. Dispatches command via MagicBus
     * 4. Flashes success message via FlashMessageService
     * 5. Redirects to account.profile route
     *
     * @param ProfileRequest $request The validated profile form request containing trooper data
     * @return RedirectResponse Redirect to account.profile route with success message
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
