<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\FlashType;
use App\Http\Controllers\MagicBusController;
use App\Plugins\PluginLoginProviders;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

/**
 * Handles OAuth provider redirect initiation.
 *
 * This controller redirects troopers to the OAuth provider's
 * authorization page where they can grant permission for the
 * application to access their account information.
 */
class OauthRedirectController extends MagicBusController
{
    /**
     * Redirects the trooper to the provider's authorization page. After
     * authorization, the provider redirects back to the callback URL.
     *
     * @param  Request  $request  The incoming HTTP request
     * @param  string  $provider  The login provider's key, such as google
     * @return RedirectResponse A redirect to the provider's authorization page
     */
    public function __invoke(Request $request, string $provider): RedirectResponse
    {
        $refusal = app(PluginLoginProviders::class)->unavailableReason($provider);

        if ($refusal !== null)
        {
            FlashType::warning($refusal);

            return redirect()->route('auth.login');
        }

        return Socialite::driver($provider)->redirect();
    }
}
