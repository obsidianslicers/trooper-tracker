<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Facades\TroopTracker;
use App\Http\Controllers\MagicBusController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

/**
 * Initiates the email-based registration flow.
 *
 * This controller creates a temporary registration session for troopers
 * who choose to sign up with email instead of OAuth. It sets up the
 * registration authentication context and redirects to the registration form.
 */
class SignUpEmailController extends MagicBusController
{
    private TroopTracker $troop_tracker;

    protected function initialized(): void
    {
        $this->troop_tracker = app(TroopTracker::class);
    }

    /**
     * Handle the incoming request to initiate email registration.
     *
     * Creates a short-lived registration session (20 minutes) that identifies
     * this as an email-based registration flow. The email field is null and
     * will be collected on the registration form.
     *
     * @param  Request  $request  The incoming HTTP request
     * @return RedirectResponse A redirect to the registration form
     */
    public function __invoke(Request $request): RedirectResponse
    {
        if ($this->troop_tracker->isXenforoOAuthRequired())
        {
            Session::forget('registration_auth');

            $this->flash->warning('Email sign up is disabled. Please sign up with XenForo.');

            return redirect()->route('auth.signup');
        }

        $registration_auth = [
            'method' => 'email',
            'email' => null, // will be filled in on the form
            'expires_at' => now()->addMinutes(20),
        ];

        Session::put('registration_auth', $registration_auth);

        return redirect()->route('auth.register');
    }
}
