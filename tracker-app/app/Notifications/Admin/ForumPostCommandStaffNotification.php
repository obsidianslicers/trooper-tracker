<?php

declare(strict_types=1);

namespace App\Notifications\Admin;

use App\Channels\FcmChannel;
use App\Mail\Admin\Events\ForumPostCommandStaffMail;
use App\Models\Event;
use App\Models\Trooper;
use Illuminate\Notifications\Notification;

class ForumPostCommandStaffNotification extends Notification
{
    public function __construct(
        private readonly Event $event,
        private readonly Trooper $poster,
    ) {}

    public function via(Trooper $notifiable): array
    {
        $channels = [];

        if ($notifiable->wantsNotification('forum_post_command_staff', 'database'))
        {
            $channels[] = 'database';
        }

        if ($notifiable->push_notifications_enabled
            && $notifiable->wantsNotification('forum_post_command_staff', 'fcm'))
        {
            $channels[] = FcmChannel::class;
        }

        if ($notifiable->emailAppearsValid()
            && $notifiable->wantsNotification('forum_post_command_staff', 'mail'))
        {
            $channels[] = 'mail';
        }

        return $channels;
    }

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
