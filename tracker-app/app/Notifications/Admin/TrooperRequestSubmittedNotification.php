<?php

declare(strict_types=1);

namespace App\Notifications\Admin;

use App\Mail\Admin\Troopers\TrooperRequestSubmitted;
use App\Models\TrooperRequest;
use App\Models\Trooper;
use App\Enums\AdministrativeNotifications;
use App\Enums\TrooperNotifications;
use App\Notifications\BaseNotification;

class TrooperRequestSubmittedNotification extends BaseNotification
{
    protected AdministrativeNotifications|TrooperNotifications|string|null $notification_category = 'trooper_requests';

    public function __construct(private readonly TrooperRequest $trooper_request)
    {
    }

    public function toMail(Trooper $notifiable): TrooperRequestSubmitted
    {
        return (new TrooperRequestSubmitted($this->trooper_request))->to($notifiable->email);
    }

    public function toArray(Trooper $notifiable): array
    {
        $trooper = $this->trooper_request->trooper;
        $org = $this->trooper_request->organization;

        return [
            'title' => 'Club Join Request',
            'body' => "{$trooper->display_name} has requested to join {$org->name}.",
            'url' => '/admin/troopers/approvals',
        ];
    }

    public function toFcm(Trooper $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
