<?php

declare(strict_types=1);

namespace App\Notifications\Troopers;

use App\Mail\Admin\Troopers\TrooperRequestApproved;
use App\Models\TrooperRequest;
use App\Models\Trooper;
use App\Notifications\BaseNotification;
use App\Enums\AdministrativeNotifications;
use App\Enums\TrooperNotifications;

/**
 * Notifies a trooper their club join request was approved.
 */
class TrooperRequestApprovedNotification extends BaseNotification
{
    protected AdministrativeNotifications|TrooperNotifications|string|null $notification_category = 'join_request_approved';

    public function __construct(private readonly TrooperRequest $trooper_request)
    {
    }

    public function toMail(Trooper $notifiable): TrooperRequestApproved
    {
        return (new TrooperRequestApproved($this->trooper_request))->to($notifiable->email);
    }

    public function toArray(Trooper $notifiable): array
    {
        return [
            'title' => 'Join Request Approved',
            'body' => "You've been added to {$this->trooper_request->organization->name}!",
            'url' => '/account/club-memberships',
        ];
    }

    public function toFcm(Trooper $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
