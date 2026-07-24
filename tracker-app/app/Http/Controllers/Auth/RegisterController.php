<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\MagicBusController;
use App\Messages\Auth\PageData\RegisterPageData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Displays the user registration page.
 *
 * This controller renders the registration form with a list of available
 * organizations and pre-fills data from the OAuth session if the trooper
 * is registering via an OAuth provider.
 */
class RegisterController extends MagicBusController
{
    /**
     * Handle the incoming request to display the registration form.
     *
     * Retrieves all organizations with their related data and the registration
     * authentication information from the session (if OAuth was used). The
     * registration data includes the email and method (OAuth provider or manual).
     *
     * @param  Request  $request  The incoming HTTP request
     * @return InertiaResponse|SymfonyResponse The rendered registration page view with organizations and registration data
     */
    public function __invoke(Request $request): InertiaResponse|SymfonyResponse
    {
        $data = RegisterPageData::call();

        return Inertia::render('auth/Register', $data);
    }
}
