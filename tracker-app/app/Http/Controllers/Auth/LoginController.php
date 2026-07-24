<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Messages\Auth\PageData\LoginPageData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Displays the login page for unauthenticated troopers.
 *
 * This controller handles the login page request, automatically redirecting
 * already authenticated troopers to the home page to prevent redundant logins.
 */
class LoginController extends Controller
{
    /**
     * Handle the incoming request to display the login view.
     *
     * If the trooper is already authenticated, redirects them to the home page.
     * Otherwise, displays the login form with available authentication methods.
     *
     * @param  Request  $request  The incoming HTTP request
     * @return InertiaResponse|SymfonyResponse The login page view or redirect to home if authenticated
     */
    public function __invoke(Request $request): InertiaResponse|SymfonyResponse
    {
        if (Auth::check())
        {
            return redirect()->route('events.list');
        }

        $data = LoginPageData::call($request);

        return Inertia::render('auth/Login', $data);
    }
}
