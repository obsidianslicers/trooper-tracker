<?php

declare(strict_types=1);

namespace App\Messages\Events\Commands\Verification;

use App\Enums\MembershipRole;
use App\Messages\Troopers\Queries\GetTroopersByRole;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Notifications\Admin\TroopVerificationRequestedNotification;
use Hyperdrive\Message;

/**
 * Tells administrators and in-scope moderators that a flagged trooper's troop is
 * waiting for them. Each officer only hears about the clubs they can verify, and
 * nobody is told once every club has been handled.
 *
 * @method static void call(EventTrooper $event_trooper)
 */
final class SendTroopVerificationNotifications extends Message
{
    public function __construct(private readonly EventTrooper $event_trooper) {}

    public function handle(): void
    {
        $event_trooper = $this->event_trooper->fresh(['trooper', 'costume', 'event_shift.event']);

        if ($event_trooper === null || !$event_trooper->isAwaitingVerification())
        {
            return;
        }

        $officers = GetTroopersByRole::call(membership_role: MembershipRole::ADMINISTRATOR)
            ->merge(GetTroopersByRole::call(membership_role: MembershipRole::MODERATOR))
            ->unique('id');

        foreach ($officers as $officer)
        {
            $organization_ids = $event_trooper->verifiableOrganizationIdsFor($officer);

            if ($organization_ids === [])
            {
                continue;
            }

            $officer->notify(new TroopVerificationRequestedNotification(
                trooper_name: $event_trooper->trooper->display_name,
                event_name: $event_trooper->event_shift->event->name,
                costume_name: $event_trooper->costume?->name,
                club_names: Organization::whereIn(Organization::ID, $organization_ids)
                    ->orderBy(Organization::NAME)
                    ->pluck(Organization::NAME)
                    ->all(),
            ));
        }
    }
}
