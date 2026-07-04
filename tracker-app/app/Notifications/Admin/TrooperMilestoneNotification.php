<?php

declare(strict_types=1);

namespace App\Notifications\Admin;

use App\Channels\FcmChannel;
use App\Mail\Admin\Troopers\TrooperMilestoneMail;
use App\Models\Trooper;
use App\Models\TrooperAchievement;
use Illuminate\Notifications\Notification;

class TrooperMilestoneNotification extends Notification
{
    public function __construct(private readonly TrooperAchievement $achievement) {}

    public function via(Trooper $notifiable): array
    {
        $channels = [];

        if ($notifiable->wantsNotification('trooper_milestones', 'database'))
        {
            $channels[] = 'database';
        }

        if ($notifiable->push_notifications_enabled
            && $notifiable->wantsNotification('trooper_milestones', 'fcm'))
        {
            $channels[] = FcmChannel::class;
        }

        if ($notifiable->emailAppearsValid()
            && $notifiable->wantsNotification('trooper_milestones', 'mail'))
        {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(Trooper $notifiable): TrooperMilestoneMail
    {
        return (new TrooperMilestoneMail($this->achievement))->to($notifiable->email);
    }

    public function toArray(Trooper $notifiable): array
    {
        $trooper = $this->achievement->trooper;

        return [
            'title' => 'Trooper Milestone Achieved',
            'body' => $trooper->display_name.' has earned: '.$this->achievement->display_description.'.',
            'url' => route('admin.troopers.profile', $trooper),
        ];
    }

    public function toFcm(Trooper $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
