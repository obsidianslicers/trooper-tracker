<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Plugins\PluginLoginProviders;
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
class HomeController extends PageController
{
    /**
     * Handle the incoming request to display the home page.
     *
     * Redirects authenticated troopers to the events list, or renders
     * the public home page for guests. When a login provider is required,
     * the login button goes straight to it instead of the login page.
     *
     * @param  Request  $request  The incoming HTTP request
     * @param  PluginLoginProviders  $providers  The registered login providers
     * @return View|RedirectResponse The rendered home page view or redirect to events list
     */
    public function __invoke(Request $request, PluginLoginProviders $providers): View|RedirectResponse
    {
        if (Auth::check())
        {
            return redirect()->route('events.list');
        }

        $required = $providers->required();

        $data = [
            'login_url' => $required === null
                ? route('auth.login')
                : route('auth.oauth-redirect', ['provider' => $required->key()]),
            'login_label' => $required === null
                ? __('layout.Login')
                : __('auth.Login with :provider', ['provider' => $required->name()]),
        ];

        return view('pages.home', $data);
    }
}
