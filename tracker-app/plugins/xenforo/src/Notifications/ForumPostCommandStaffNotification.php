<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo\Notifications;

use App\Enums\AdministrativeNotifications;
use App\Enums\TrooperNotifications;
use App\Models\Event;
use App\Models\Trooper;
use App\Notifications\BaseNotification;
use TroopTracker\Plugins\Xenforo\Mail\ForumPostCommandStaffMail;

/**
 * A comment asks for command staff's attention. The event's coordinator gets it too, worded
 * for them ($to_coordinator).
 */
class ForumPostCommandStaffNotification extends BaseNotification
{
    public const CATEGORY = 'forum_post_command_staff';

    protected AdministrativeNotifications|TrooperNotifications|string|null $notification_category = self::CATEGORY;

    public function __construct(
        private readonly Event $event,
        private readonly Trooper $poster,
        private readonly bool $to_coordinator = false,
    ) {}

    public function toMail(Trooper $notifiable): ForumPostCommandStaffMail
    {
        return (new ForumPostCommandStaffMail($this->event, $this->poster, $this->to_coordinator))
            ->to($notifiable->email);
    }

    public function toArray(Trooper $notifiable): array
    {
        return [
            'title' => __('xenforo::notifications.Command Staff Alert: :event', ['event' => $this->event->name]),
            'body' => $this->to_coordinator
                ? __(
                    "xenforo::notifications.:name has posted a comment requesting command staff attention. You're getting this as the event's coordinator.",
                    ['name' => $this->poster->display_name]
                )
                : __(
                    'xenforo::notifications.:name has posted a comment requesting command staff attention.',
                    ['name' => $this->poster->display_name]
                ),
            'url' => '/events/details/'.$this->event->id,
        ];
    }

    public function toFcm(Trooper $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
