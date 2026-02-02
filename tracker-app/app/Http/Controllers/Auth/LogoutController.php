<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\MagicBusController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Handles trooper logout and session termination.
 *
 * This controller logs out the authenticated trooper, destroys their session,
 * displays a success message, and redirects them to the login page.
 */
class LogoutController extends MagicBusController
{
    /**
     * Handle the incoming request to log out the trooper.
     *
     * Terminates the trooper's authenticated session, flashes a success message,
     * and redirects to the login page with a logout confirmation flag.
     *
     * @param  Request  $request  The incoming HTTP request.
     * @return RedirectResponse A redirect to the login page with logged_out flag.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $this->flash->success('You have been logged out.');

        Auth::logout();

        return redirect()->route('auth.login', ['logged_out' => '1']);
    }
}
