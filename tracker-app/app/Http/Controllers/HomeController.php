<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Displays the application home page.
 *
 * This controller renders the public landing page. For authenticated troopers,
 * it redirects to the events list. For guests, it displays the home page.
 */
class HomeController extends MagicBusController
{
    /**
     * Handle the incoming request to display the home page.
     *
     * Redirects authenticated troopers to the events list, or renders
     * the public home page for guests.
     *
     * @return View|RedirectResponse The rendered home page view or redirect to events list.
     */
    public function __invoke(Request $request): View|RedirectResponse
    {
        if (Auth::check())
        {
            return redirect()->route('events.list');
        }

        $data = [];

        return view('pages.home', $data);
    }
}
