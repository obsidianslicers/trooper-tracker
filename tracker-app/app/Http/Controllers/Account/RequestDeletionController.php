<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Messages\Troopers\Commands\RequestTrooperDeletion;
use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Account\RequestDeletionRequest;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Handles account deletion requests from authenticated troopers.
 *
 * Initiates the 30-day grace period, sends a confirmation email, and logs out
 * the trooper. The account remains accessible during the grace period so the
 * trooper can cancel. After 30 days the scheduler permanently anonymizes the data.
 */
class RequestDeletionController extends MagicBusController
{
    public function __invoke(RequestDeletionRequest $request): InertiaResponse|SymfonyResponse
    {
        $trooper = $request->user();

        RequestTrooperDeletion::call(trooper: $trooper);

        $this->flash->warning(
            'Your account has been scheduled for permanent deletion. ' .
            'You may log back in within 30 days to cancel.'
        );

        Auth::logout();

        $url = route('auth.login');

        return Inertia::location($url);
    }
}
