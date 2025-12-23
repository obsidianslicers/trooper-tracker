<?php

namespace App\Http\Controllers\Auth;

use App\Enums\MembershipStatus;
use App\Http\Controllers\Controller;
use App\Models\OauthLogin;
use App\Models\SocialAccount;
use App\Models\Trooper;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Facades\Socialite;

class OauthCallbackController extends Controller
{
    public function __invoke(Request $request, string $provider): RedirectResponse
    {
        $provider_user = Socialite::driver($provider)->user();

        // Find existing social account
        $account = OauthLogin::where(OauthLogin::PROVIDER, $provider)
            ->where(OauthLogin::PROVIDER_ID, $provider_user->getId())
            ->first();

        if ($account)
        {
            //  TODO WHAT IF NOT ACTIVE?
            Auth::login($account->trooper);

            return redirect()->intended('/');
        }

        $email = $provider_user->getEmail();

        $trooper = Trooper::byEmail($email)->first();

        if ($trooper === null)
        {
            $registration_auth = [
                'method' => $provider,
                'provider_id' => $provider_user->getId(),
                'email' => $provider_user->getEmail(),
                'expires_at' => now()->addMinutes(value: 20),
            ];

            $oauth_pending = [
                'provider' => $provider,
                'provider_id' => $provider_user->getId(),
                'email' => $provider_user->getEmail(),
                'name' => $provider_user->getName(),
                'token' => $provider_user->token ?? null,
                'refresh_token' => $provider_user->refreshToken ?? null,
            ];

            // Session::put(self::FLASH_KEY, $messages);
            Session::put('registration_auth', $registration_auth);
            Session::put('oauth_pending', $oauth_pending);

            return redirect()->route('auth.register');
        }

        if ($trooper->membership_status !== MembershipStatus::ACTIVE)
        {


        }

        // Link provider
        OauthLogin::create([
            OauthLogin::TROOPER_ID => $trooper->id,
            OauthLogin::PROVIDER => $provider,
            OauthLogin::PROVIDER_ID => $provider_user->getId(),
            OauthLogin::TOKEN => $provider_user->token ?? null,
            OauthLogin::REFRESH_TOKEN => $provider_user->refreshToken ?? null,
        ]);

        Auth::login($trooper);

        return redirect()->intended('/');
    }
}