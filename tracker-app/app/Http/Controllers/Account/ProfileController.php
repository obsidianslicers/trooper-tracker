<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Handles the display of the main account management page.
 *
 * This controller is responsible for fetching the authenticated user's
 * data and rendering the primary account view where they can manage
 * their profile, settings, and other account-related information.
 */
class ProfileController extends Controller
{
    /**
     * Handle the incoming request to display the account page.
     *
     * This method retrieves the currently authenticated trooper and renders
     * the main account management view, passing the trooper's data to it.
     *
     * @param Request $request The incoming HTTP request.
     * @return View The rendered account page view.
     */
    public function __invoke(Request $request): View
    {
        $trooper = $request->user();

        $data = compact('trooper');

        return view('pages.account.profile', $data);
    }
}
