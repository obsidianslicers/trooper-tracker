<?php

declare(strict_types=1);

namespace App\Notifications\Events;

use App\Channels\FcmChannel;
use App\Mail\Events\EventForumPostMail;
use App\Models\Event;
use App\Models\Trooper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class EventForumPostNotification extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        private readonly Event $event,
        private readonly int $post_id,
        private readonly string $username,
        private readonly string $message,
    ) {}

    public function via(Trooper $notifiable): array
    {
        $channels = [];

        if ($notifiable->wantsNotification('event_forum_post', 'database'))
        {
            $channels[] = 'database';
        }

        if ($notifiable->push_notifications_enabled
            && $notifiable->wantsNotification('event_forum_post', 'fcm'))
        {
            $channels[] = FcmChannel::class;
        }

        if ($notifiable->emailAppearsValid()
            && $notifiable->wantsNotification('event_forum_post', 'mail'))
        {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(Trooper $notifiable): EventForumPostMail
    {
        $base_url = rtrim((string) config('services.xenforo.base_url', ''), '/');
        $post_url = $base_url !== '' ? $base_url.'/posts/'.$this->post_id.'/' : null;

        return (new EventForumPostMail($this->event, $this->username, $this->message, $post_url))
            ->to($notifiable->email);
    }

    public function toArray(Trooper $notifiable): array
    {
        return [
            'title' => 'New Forum Reply: '.$this->event->name,
            'body'  => $this->username.' posted a reply in the event thread.',
            'url'   => '/events/details/'.$this->event->id,
        ];
    }

    public function toFcm(Trooper $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
