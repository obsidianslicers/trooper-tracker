<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\MembershipRole;
use App\Models\Event;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Notifications\Admin\ForumPostCommandStaffNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

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
            ->where(Trooper::MEMBERSHIP_ROLE, MembershipRole::ADMINISTRATOR->value)
            ->get();

        $notification = new ForumPostCommandStaffNotification($this->event, $this->poster);

        $moderators->merge($admins)
            ->unique('id')
            ->reject(fn ($trooper) => $trooper->id === $this->poster->id)
            ->each(fn ($trooper) => $trooper->notify($notification));
    }
}
