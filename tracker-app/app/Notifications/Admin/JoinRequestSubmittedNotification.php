<?php

declare(strict_types=1);

namespace App\Notifications\Admin;

use App\Channels\FcmChannel;
use App\Mail\Admin\Troopers\TrooperJoinRequestSubmitted;
use App\Models\Trooper;
use App\Models\TrooperOrganization;
use Illuminate\Notifications\Notification;

class JoinRequestSubmittedNotification extends Notification
{
    public function __construct(private readonly TrooperOrganization $join_request) {}

    public function via(Trooper $notifiable): array
    {
        $channels = [];

        if ($notifiable->wantsNotification('join_requests', 'database'))
        {
            $channels[] = 'database';
        }

        if ($notifiable->push_notifications_enabled
            && $notifiable->wantsNotification('join_requests', 'fcm'))
        {
            $channels[] = FcmChannel::class;
        }

        if ($notifiable->emailAppearsValid()
            && $notifiable->wantsNotification('join_requests', 'mail'))
        {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(Trooper $notifiable): TrooperJoinRequestSubmitted
    {
        return (new TrooperJoinRequestSubmitted($this->join_request))->to($notifiable->email);
    }

    public function toArray(Trooper $notifiable): array
    {
        $trooper = $this->join_request->trooper;
        $org = $this->join_request->organization;

        return [
            'title' => 'Club Join Request',
            'body'  => "{$trooper->display_name} has requested to join {$org->name}.",
            'url'   => '/admin/troopers/approvals',
        ];
    }

    public function toFcm(Trooper $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
