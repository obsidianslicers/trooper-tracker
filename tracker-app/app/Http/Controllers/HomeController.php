<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * Displays the application home page.
 *
 * This controller renders the public landing page. For authenticated troopers,
 * it redirects to the events list. For guests, it renders the Inertia home page.
 */
class HomeController extends MagicBusController
{
    /**
     * Handle the incoming request to display the home page.
     *
     * Redirects authenticated troopers to the events list, or renders
     * the Inertia home page for guests.
     *
     * @param  Request  $request  The incoming HTTP request
     * @return InertiaResponse|RedirectResponse
     */
    public function __invoke(Request $request): View|InertiaResponse|RedirectResponse
    {
        if (Auth::check())
        {
            return redirect()->route('events.list');
        }

        if (config('app.debug'))
        {
            return Inertia::render('Home');
        }

        $data = [];

        return view('pages.home', $data);
    }
}
