<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth\Pages;

use App\Enums\FlashType;
use App\Http\Controllers\PageController;
use App\Messages\Auth\Commands\StartRegistrationWithEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Initiates the email-based registration flow.
 *
 * This controller creates a temporary registration session for troopers
 * who choose to sign up with email instead of OAuth. It sets up the
 * registration authentication context and redirects to the registration form.
 */
class StartEmailSignUpController extends PageController
{
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
        $required = StartRegistrationWithEmail::call();

        if ($required !== null)
        {
            FlashType::warning(__(
                'auth.Email sign up is disabled. Please sign up with :provider.',
                ['provider' => $required->name()]
            ));

            return redirect()->route('auth.signup');
        }

        return redirect()->route('auth.register');
    }
}
