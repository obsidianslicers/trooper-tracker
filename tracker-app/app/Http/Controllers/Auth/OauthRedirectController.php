<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class OauthRedirectController extends Controller
{
    public function __invoke(Request $request, string $provider): RedirectResponse
    {
        return Socialite::driver($provider)->redirect();
    }
}