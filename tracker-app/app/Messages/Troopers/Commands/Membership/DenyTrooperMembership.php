<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands\Membership;

use App\Enums\MembershipStatus;
use App\Models\Trooper;
use App\Models\TrooperRequest;
use App\Notifications\Troopers\TrooperDeniedNotification;
use Hyperdrive\Message;

/**
 * Handler for denying a trooper's membership.
 *
 * @method static void call(Trooper $trooper)
 */
final class DenyTrooperMembership extends Message
{
    public function __construct(
        private readonly Trooper $trooper,
        private readonly ?string $denial_reason = null,
    ) {}

    public function handle(): void
    {
        $this->trooper->membership_status = MembershipStatus::DENIED;
        $this->trooper->save();

        TrooperRequest::where(TrooperRequest::TROOPER_ID, $this->trooper->id)
            ->pending()
            ->get()
            ->each(function (TrooperRequest $trooper_request): void {
                DenyTrooperRequest::call(
                    trooper_request: $trooper_request,
                    denial_reason: $this->denial_reason,
                    suppress_notification: true
                );
            });

        $this->trooper->notify(new TrooperDeniedNotification($this->denial_reason));
    }
}
