<?php

declare(strict_types=1);

namespace App\Notifications\Tests;

use App\Channels\FcmChannel;
use App\Models\Trooper;
use Illuminate\Notifications\Notification;

class TestPushNotification extends Notification
{
    public function __construct(
        private readonly string $title,
        private readonly string $body,
        private readonly string $url,
    ) {}

    public function via(Trooper $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->push_notifications_enabled)
        {
            $channels[] = FcmChannel::class;
        }

        return $channels;
    }

    public function toArray(Trooper $notifiable): array
    {
        return [
            'title' => $this->title,
            'body'  => $this->body,
            'url'   => $this->url,
        ];
    }

    public function toFcm(Trooper $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
