<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\MagicBusController;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class MinorsController extends MagicBusController
{
    public function __invoke(Request $request): View
    {
        $minors = $request->user()->troopers()->get();

        return view('pages.account.minors', compact('minors'));
    }
}
