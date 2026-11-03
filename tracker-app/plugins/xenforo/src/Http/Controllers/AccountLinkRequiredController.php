<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Asks a trooper to link their forum account when the site requires one.
 */
class AccountLinkRequiredController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('xenforo::account.required', ['user' => $request->user()]);
    }
}
