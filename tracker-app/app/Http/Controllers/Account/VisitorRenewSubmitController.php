<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Enums\MembershipStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Handles visitor access renewal submissions.
 *
 * Sets the trooper's membership status back to PENDING so they appear in the
 * admin approvals queue for a new 6-month access window to be granted.
 */
class VisitorRenewSubmitController
{
    public function __invoke(Request $request): RedirectResponse
    {
        $trooper = $request->user();

        $trooper->membership_status = MembershipStatus::PENDING;
        $trooper->save();

        return redirect()->route('auth.thank-you');
    }
}
