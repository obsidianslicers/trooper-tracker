<?php

declare(strict_types=1);

namespace App\Notifications\Troopers;

use App\Mail\Admin\Troopers\TrooperDenied;
use App\Models\Trooper;
use App\Notifications\BaseNotification;
use App\Enums\AdministrativeNotifications;
use App\Enums\TrooperNotifications;

/**
 * Notifies a trooper their tracker registration was denied.
 */
class TrooperDeniedNotification extends BaseNotification
{
    protected AdministrativeNotifications|TrooperNotifications|string|null $notification_category = 'trooper_denied';

    public function __construct(private readonly ?string $denial_reason = null)
    {
    }

    public function toMail(Trooper $notifiable): TrooperDenied
    {
        return (new TrooperDenied($notifiable, $this->denial_reason))->to($notifiable->email);
    }

    public function toArray(Trooper $notifiable): array
    {
        $body = 'Your registration was not approved.';

        if ($this->denial_reason)
        {
            $body .= " Reason: {$this->denial_reason}";
        }

        return [
            'title' => 'Registration Not Approved',
            'body' => $body,
            'url' => '/account',
        ];
    }

    public function toFcm(Trooper $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
