<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Facades\TroopTracker;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Messages\Auth\Commands\Login;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Handles the submission of the login form, authenticates troopers, and manages sessions.
 *
 * This controller processes login attempts by:
 * - Validating credentials against the database
 * - Checking trooper membership status (active, pending, retired)
 * - Creating authenticated sessions with optional "remember me" functionality
 * - Providing appropriate error messages for various failure scenarios
 */
class LoginSubmitController extends Controller
{
    public function __construct(
        private readonly TroopTracker $troop_tracker
    ) {}

    /**
     * Handles the incoming login request and authenticates the trooper.
     *
     * This method performs the following checks in order:
     * 1. Validates email and password (via LoginRequest)
     * 2. Checks if the trooper's membership status is PENDING
     * 3. Checks if the trooper's membership status is not ACTIVE (e.g., RETIRED)
     * 4. Verifies the password against the stored hash
     * 5. Logs in the trooper and redirects to the intended page
     *
     * @param  LoginRequest  $request  The validated login form request containing email and password
     * @return InertiaResponse|SymfonyResponse A redirect to the events list on success, or back with errors on failure
     */
    public function __invoke(LoginRequest $request): InertiaResponse|SymfonyResponse
    {
        if ($this->troop_tracker->isXenforoOAuthRequired())
        {
            return back()->withDanger('Email/Password login is disabled. Please log in with XenForo.');
        }

        $trooper = Login::call($request);

        if ($trooper)
        {
            $intended_url = redirect()->intended(route('events.list'))->getTargetUrl();

            return Inertia::location($intended_url);
        }

        return back()->withDanger('Invalid email or password.');
    }
}
