<?php

declare(strict_types=1);

namespace App\Notifications\Admin;

use App\Enums\AdministrativeNotifications;
use App\Enums\TrooperNotifications;
use App\Mail\Admin\Events\ForumPostCommandStaffMail;
use App\Models\Event;
use App\Models\Trooper;
use App\Notifications\BaseNotification;

class ForumPostCommandStaffNotification extends BaseNotification
{
    protected AdministrativeNotifications|TrooperNotifications|string|null $notification_category = 'forum_post_command_staff';

    public function __construct(
        private readonly Event $event,
        private readonly Trooper $poster,
    ) {}

    public function toMail(Trooper $notifiable): ForumPostCommandStaffMail
    {
        return (new ForumPostCommandStaffMail($this->event, $this->poster))
            ->to($notifiable->email);
    }

    public function toArray(Trooper $notifiable): array
    {
        return [
            'title' => 'Command Staff Alert: '.$this->event->name,
            'body' => $this->poster->display_name.' has posted a comment requesting command staff attention.',
            'url' => '/events/details/'.$this->event->id,
        ];
    }

    public function toFcm(Trooper $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
