<?php

declare(strict_types=1);

namespace App\Notifications\Events;

use App\Mail\Events\EventForumPostMail;
use App\Models\Event;
use App\Models\Trooper;
use App\Enums\AdministrativeNotifications;
use App\Enums\TrooperNotifications;
use App\Notifications\QueueableNotification;

class EventForumPostNotification extends QueueableNotification
{
    protected AdministrativeNotifications|TrooperNotifications|string|null $notification_category = 'event_forum_post';

    public function __construct(
        private readonly Event $event,
        private readonly int $post_id,
        private readonly string $username,
        private readonly string $post_body,
    ) {
    }

    public function toMail(Trooper $notifiable): EventForumPostMail
    {
        $base_url = config('services.xenforo.base_url');
        $post_url = $base_url !== '' ? $base_url . '/posts/' . $this->post_id . '/' : null;

        return (new EventForumPostMail($this->event, $this->username, $this->post_body, $post_url))
            ->to($notifiable->email);
    }

    public function toArray(Trooper $notifiable): array
    {
        return [
            'title' => 'New Forum Reply: ' . $this->event->name,
            'body' => $this->username . ' posted a reply in the event thread.',
            'url' => '/events/details/' . $this->event->id,
        ];
    }

    public function toFcm(Trooper $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
