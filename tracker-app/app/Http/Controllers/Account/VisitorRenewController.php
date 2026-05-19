<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays the visitor access renewal page.
 *
 * Shown when an authenticated visitor trooper's 6-month access window has elapsed.
 * The trooper must explicitly submit a renewal request to re-enter the approvals queue.
 */
class VisitorRenewController
{
    public function __invoke(Request $request): View
    {
        return view('pages.account.visitor-renew', [
            'trooper' => $request->user(),
        ]);
    }
}
