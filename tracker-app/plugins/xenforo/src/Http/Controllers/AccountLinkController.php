<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\OauthLogin;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use TroopTracker\Plugins\Xenforo\Auth\XenforoLoginProvider;

/**
 * Shows whether the signed-in trooper has linked their forum account.
 */
class AccountLinkController extends Controller
{
    public function __invoke(Request $request): View
    {
        $xenforo_login = OauthLogin::where(OauthLogin::TROOPER_ID, $request->user()->id)
            ->where(OauthLogin::PROVIDER, XenforoLoginProvider::KEY)
            ->first();

        return view('xenforo::account.index', [
            'user' => $request->user(),
            'xenforo_login' => $xenforo_login,
        ]);
    }
}
