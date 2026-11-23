<?php

declare(strict_types=1);

namespace App\Http\Controllers\Home\Pages;

use App\Http\Controllers\PageController;
use App\Messages\Home\PageData\HomePageData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Displays the application home page.
 *
 * This controller renders the public landing page. For authenticated troopers,
 * it redirects to the events list. For guests, it displays the home page with
 * a large login button.
 */
class ShowHomeController extends PageController
{
    /**
     * Redirects authenticated troopers to the events list, or renders
     * the public home page for guests.
     *
     * @param  Request  $request  The incoming HTTP request
     * @return View|RedirectResponse The rendered home page view or redirect to events list
     */
    public function __invoke(Request $request): View|RedirectResponse
    {
        if (Auth::check())
        {
            return redirect()->route('events.list');
        }

        $data = HomePageData::call();

        return view('pages.home', $data);
    }
}
