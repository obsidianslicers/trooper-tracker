<?php

declare(strict_types=1);

namespace App\Notifications\Admin;

use App\Channels\FcmChannel;
use App\Mail\Admin\Troopers\TrooperMilestoneMail;
use App\Models\Trooper;
use App\Models\TrooperAchievement;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class TrooperMilestoneNotification extends Notification
{
    /** @param Collection<int, TrooperAchievement> $achievements */
    public function __construct(private readonly Collection $achievements) {}

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
        return (new TrooperMilestoneMail($this->achievements))->to($notifiable->email);
    }

    public function toArray(Trooper $notifiable): array
    {
        $troopers = $this->achievements->pluck('trooper')->unique('id')->values();
        $milestone_count = $this->achievements->count();
        $trooper_count = $troopers->count();

        return [
            'title' => 'Daily Trooper Milestones',
            'body' => $this->summaryBody($troopers, $milestone_count),
            'url' => '/service-records/achievements',
            'trooper_count' => $trooper_count,
            'milestone_count' => $milestone_count,
            'trooper_ids' => $troopers->pluck('id')->all(),
            'achievement_ids' => $this->achievements->pluck('id')->all(),
        ];
    }

    public function toFcm(Trooper $notifiable): array
    {
        return $this->toArray($notifiable);
    }

    private function summaryBody(Collection $troopers, int $milestone_count): string
    {
        $names = $troopers->pluck('display_name');
        $shown_names = $names->take(3)->implode(', ');
        $remaining = $names->count() - 3;
        $name_summary = $remaining > 0 ? $shown_names.' and '.$remaining.' more' : $shown_names;
        $milestone_word = $milestone_count === 1 ? 'milestone' : 'milestones';

        return $name_summary.' achieved '.$milestone_count.' '.$milestone_word.'.';
    }
}
