<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Features\Troopers\Commands\RequestAccountDeletionCommand;
use App\Http\Controllers\MagicBusController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Handles account deletion requests from authenticated troopers.
 *
 * Initiates the 30-day grace period, sends a confirmation email, and logs out
 * the trooper. The account remains accessible during the grace period so the
 * trooper can cancel. After 30 days the scheduler permanently anonymizes the data.
 */
class DeletionRequestController extends MagicBusController
{
    public function __invoke(Request $request): RedirectResponse
    {
        $trooper = $request->user();

        $this->authorize('requestDeletion', $trooper);

        $this->bus->send(new RequestAccountDeletionCommand($trooper));

        $this->flash->warning(
            'Your account has been scheduled for permanent deletion. '.
            'You may log back in within 30 days to cancel.'
        );

        Auth::logout();

        return redirect()->route('auth.login');
    }
}
