<?php

declare(strict_types=1);

namespace App\Notifications\Troopers;

use App\Enums\AdministrativeNotifications;
use App\Enums\TrooperNotifications;
use App\Mail\Admin\Troopers\TrooperRequestDenied;
use App\Models\Trooper;
use App\Models\TrooperRequest;
use App\Notifications\BaseNotification;

/**
 * Notifies a trooper their club join request was denied.
 */
class TrooperRequestDeniedNotification extends BaseNotification
{
    protected AdministrativeNotifications|TrooperNotifications|string|null $notification_category = 'join_request_denied';

    public function __construct(private readonly TrooperRequest $trooper_request) {}

    public function toMail(Trooper $notifiable): TrooperRequestDenied
    {
        return (new TrooperRequestDenied($this->trooper_request))->to($notifiable->email);
    }

    public function toArray(Trooper $notifiable): array
    {
        $body = "Your request to join {$this->trooper_request->organization->name} was not approved.";

        if ($this->trooper_request->denial_reason)
        {
            $body .= " Reason: {$this->trooper_request->denial_reason}";
        }

        return [
            'title' => 'Join Request Not Approved',
            'body' => $body,
            'url' => '/account/club-memberships',
        ];
    }

    public function toFcm(Trooper $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
