<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands\Membership;

use App\Enums\MembershipStatus;
use App\Jobs\SendTrooperRegisteredNotificationsJob;
use App\Models\Trooper;
use Hyperdrive\Message;

/**
 * Handler for renewing a visitor trooper's membership.
 *
 * @method static void call(Trooper $trooper)
 */
final class RenewVisitorMembership extends Message
{
    public function __construct(
        private readonly Trooper $trooper,
    ) {}

    public function handle(): void
    {
        $this->trooper->membership_status = MembershipStatus::PENDING;
        $this->trooper->save();

        dispatch(new SendTrooperRegisteredNotificationsJob($this->trooper));
    }
}
