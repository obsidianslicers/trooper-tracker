<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\MembershipStatus;
use App\Facades\TroopTracker;
use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Trooper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Handles the submission of the login form, authenticates troopers, and manages sessions.
 *
 * This controller processes login attempts by:
 * - Validating credentials against the database
 * - Checking trooper membership status (active, pending, retired)
 * - Creating authenticated sessions with optional "remember me" functionality
 * - Providing appropriate error messages for various failure scenarios
 */
class LoginSubmitController extends MagicBusController
{
    private TroopTracker $troop_tracker;

    protected function initialize(): void
    {
        $this->troop_tracker = app(TroopTracker::class);
    }

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
     * @return RedirectResponse A redirect to the events list on success, or back with errors on failure
     */
    public function __invoke(LoginRequest $request): RedirectResponse
    {
        if ($this->troop_tracker->isXenforoOAuthRequired())
        {
            $this->flash->warning('Email/password login is disabled. Please log in with XenForo.');

            return redirect()
                ->route('auth.login')
                ->withErrors(['oauth' => 'Email/password login is disabled. Please log in with XenForo.']);
        }

        $email = $request->validated('email');
        $password = $request->validated('password');

        //  trooper existance is checked via LoginRequest
        $trooper = Trooper::query()->byEmail($email)->first();

        if ($trooper->membership_status === MembershipStatus::PENDING)
        {
            $this->flash->warning('Your access has not been approved yet. Please refer to command staff for additional information.');

            return back()
                ->withInput(request()->except('password'))
                ->withErrors(['email' => 'Refer to command staff']);
        }

        if ($trooper->membership_status !== MembershipStatus::ACTIVE)
        {
            //  retired
            $this->flash->danger('You cannot access this account. Please refer to command staff for additional information (retired).');

            return back()
                ->withInput(request()->except('password'))
                ->withErrors(['email' => 'You cannot access this account.']);
        }

        if (Hash::check($password, $trooper->password))
        {
            Auth::login($trooper, $request->remember_me);

            return redirect()->intended(route('events.list'));
        }

        return back()
            ->withInput(request()->except('password'))
            ->withErrors(['email' => 'Invalid email and password.']);
    }
}
