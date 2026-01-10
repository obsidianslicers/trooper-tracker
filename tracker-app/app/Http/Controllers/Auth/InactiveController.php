<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Handles the inactive account page.
 */
class InactiveController extends Controller
{
    /**
     * Handle the incoming request to display the inactive account page.
     *
     * @param Request $request The incoming HTTP request.
     * @return RedirectResponse A redirect response to the login page.
     */
    public function __invoke(Request $request): View
    {
        return view('pages.auth.inactive');
    }
}
