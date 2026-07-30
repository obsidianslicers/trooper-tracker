<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\MagicBusController;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class FriendsController extends MagicBusController
{
    public function __invoke(Request $request): View
    {
        $friends = $request->user()->trooper_friends()->with('friend')->get();

        return view('pages.account.friends', compact('friends'));
    }
}
