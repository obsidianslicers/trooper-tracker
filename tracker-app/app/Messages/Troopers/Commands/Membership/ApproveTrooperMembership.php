<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands\Membership;

use App\Enums\MembershipStatus;
use App\Models\Trooper;
use App\Models\TrooperRequest;
use App\Notifications\Troopers\MembershipApprovedNotification;
use Hyperdrive\Message;
use Illuminate\Support\Facades\DB;

/**
 * Handler for approving a trooper's membership.
 *
 * @method static void call(Trooper $trooper)
 */
final class ApproveTrooperMembership extends Message
{
    public function __construct(
        private readonly Trooper $trooper,
    ) {
    }

    public function handle(): void
    {
        DB::transaction(function (): void
        {
            $this->trooper->membership_status = MembershipStatus::ACTIVE;

            if ($this->trooper->is_visitor)
            {
                $this->trooper->visitor_expires_at = now()->addMonths(6);
                $this->trooper->visitor_notified_at = null;
            }

            $this->trooper->save();

            $this->trooper->trooper_requests()
                ->pending()
                ->get()
                ->each(function (TrooperRequest $trooper_request): void
                {
                    // $this->bus->send(new ApproveTrooperRequestCommanD($trooper_request, suppress_notification: true));
                });
        });

        $this->trooper->notify(new MembershipApprovedNotification());
    }
}
