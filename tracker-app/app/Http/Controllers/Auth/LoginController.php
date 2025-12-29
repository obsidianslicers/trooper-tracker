<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Displays the login page.
 */
class LoginController extends Controller
{
    /**
     * Handle the incoming request to display the login view.
     *
     * @param Request $request The incoming HTTP request.
     * @return View The rendered login page view.
     */
    public function __invoke(Request $request): View|RedirectResponse
    {
        if (Auth::check())
        {
            return redirect()->route('home');
        }

        return view('pages.auth.login');
    }
}
