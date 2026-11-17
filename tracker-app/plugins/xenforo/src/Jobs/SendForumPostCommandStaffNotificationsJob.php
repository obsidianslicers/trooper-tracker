<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo\Jobs;

use App\Enums\MembershipRole;
use App\Models\Event;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use TroopTracker\Plugins\Xenforo\Notifications\ForumPostCommandStaffNotification;

/**
 * Notifies command staff (the hosting organization's moderators, staff officers and system
 * owners) and the event's coordinator when a comment asks for their attention. The poster
 * isn't notified.
 */
class SendForumPostCommandStaffNotificationsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Event $event,
        private readonly Trooper $poster,
    ) {}

    public function handle(): void
    {
        $moderators = Trooper::whereHas(
            'trooper_assignments',
            fn ($q) => $q->where(TrooperAssignment::ORGANIZATION_ID, $this->event->organization_id)
                ->where(TrooperAssignment::IS_MODERATOR, true)
        )->get();

        $admins = Trooper::active()
            ->whereIn(Trooper::MEMBERSHIP_ROLE, [
                MembershipRole::SYSTEM_OWNER->value,
                MembershipRole::STAFF_OFFICER->value,
            ])
            ->get();

        $command_staff = $moderators->merge($admins)
            ->unique('id')
            ->reject(fn ($trooper) => $trooper->id === $this->poster->id);

        $notification = new ForumPostCommandStaffNotification($this->event, $this->poster);

        $command_staff->each(fn ($trooper) => $trooper->notify($notification));

        //  the picked coordinator, else the creator; command staff already have the alert
        $coordinator = $this->event->eventCoordinator();

        if ($coordinator !== null
            && $coordinator->is_active
            && $coordinator->id !== $this->poster->id
            && !$command_staff->contains('id', $coordinator->id))
        {
            $coordinator->notify(new ForumPostCommandStaffNotification($this->event, $this->poster, to_coordinator: true));
        }
    }
}
