<?php

declare(strict_types=1);

namespace App\Notifications\Admin;

use App\Channels\FcmChannel;
use App\Mail\Admin\Troopers\TroopersMerged;
use App\Models\Trooper;
use Illuminate\Notifications\Notification;

class TroopersMergedNotification extends Notification
{
    public function __construct(
        private readonly Trooper $source_trooper,
        private readonly Trooper $target_trooper,
    ) {}

    public function via(Trooper $notifiable): array
    {
        $channels = [];

        if ($notifiable->wantsNotification('troopers_merged', 'database'))
        {
            $channels[] = 'database';
        }

        if ($notifiable->push_notifications_enabled
            && $notifiable->wantsNotification('troopers_merged', 'fcm'))
        {
            $channels[] = FcmChannel::class;
        }

        if ($notifiable->emailAppearsValid()
            && $notifiable->wantsNotification('troopers_merged', 'mail'))
        {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(Trooper $notifiable): TroopersMerged
    {
        return (new TroopersMerged($this->source_trooper, $this->target_trooper))->to($notifiable->email);
    }

    public function toArray(Trooper $notifiable): array
    {
        return [
            'title' => 'Troopers Merged',
            'body' => "{$this->source_trooper->legal_name} (#{$this->source_trooper->id}) was merged into {$this->target_trooper->legal_name} (#{$this->target_trooper->id}).",
            'url' => "/admin/troopers/{$this->target_trooper->id}",
        ];
    }

    public function toFcm(Trooper $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
