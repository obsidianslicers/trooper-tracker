<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account\Commands;

use App\Http\Requests\Account\RenewVisitorMembershipRequest;
use App\Messages\Troopers\Commands\Membership\RenewVisitorMembership;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Handles visitor access renewal submissions.
 *
 * Sets the trooper's membership status back to PENDING so they appear in the
 * admin approvals queue for a new 6-month access window to be granted.
 */
class RenewVisitorMembershipController
{
    public function __invoke(RenewVisitorMembershipRequest $request): InertiaResponse|SymfonyResponse
    {
        RenewVisitorMembership::call($request);

        $url = route('auth.thank-you');

        return Inertia::location($url);
    }
}
