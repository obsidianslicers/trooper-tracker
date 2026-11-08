<?php

declare(strict_types=1);

namespace App\Notifications\Admin;

use App\Enums\AdministrativeNotifications;
use App\Enums\TrooperNotifications;
use App\Mail\Admin\Troopers\StaleVerifications;
use App\Models\Trooper;
use App\Notifications\BaseNotification;

/**
 * Tells administrators that troop verifications have waited too long for a moderator.
 */
class StaleVerificationsNotification extends BaseNotification
{
    protected AdministrativeNotifications|TrooperNotifications|string|null $notification_category =
        AdministrativeNotifications::STALE_VERIFICATIONS;

    public function __construct(private readonly int $count) {}

    public function toMail(Trooper $notifiable): StaleVerifications
    {
        return (new StaleVerifications($this->count))->to($notifiable->email);
    }

    /**
     * @return array<string, string>
     */
    public function toArray(Trooper $notifiable): array
    {
        return [
            'title' => 'Troop Verifications Waiting',
            'body' => "{$this->count} troop verification(s) have waited more than 30 days.",
            'url' => '/admin/troopers/troop-verifications',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function toFcm(Trooper $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
