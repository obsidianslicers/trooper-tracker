<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\MagicBusController;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PendingController extends MagicBusController
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        $trooper = $request->user();

        if (!$trooper->is_pending)
        {
            return redirect()->route('account.index');
        }

        return view('pages.account.pending', ['trooper' => $trooper]);
    }
}
