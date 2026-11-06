<?php

declare(strict_types=1);

namespace App\Notifications\Admin;

use App\Enums\AdministrativeNotifications;
use App\Enums\TrooperNotifications;
use App\Mail\Admin\Troopers\TroopVerificationRequested;
use App\Models\Trooper;
use App\Notifications\BaseNotification;

/**
 * Tells an officer that a flagged trooper's troop is waiting for them to verify it.
 */
class TroopVerificationRequestedNotification extends BaseNotification
{
    protected AdministrativeNotifications|TrooperNotifications|string|null $notification_category =
        AdministrativeNotifications::TROOP_VERIFICATIONS;

    /**
     * @param  array<int, string>  $club_names
     */
    public function __construct(
        public readonly string $trooper_name,
        public readonly string $event_name,
        public readonly ?string $costume_name,
        public readonly array $club_names,
    ) {}

    public function toMail(Trooper $notifiable): TroopVerificationRequested
    {
        return (new TroopVerificationRequested(
            $this->trooper_name,
            $this->event_name,
            $this->costume_name,
            $this->club_names,
        ))->to($notifiable->email);
    }

    /**
     * @return array<string, string>
     */
    public function toArray(Trooper $notifiable): array
    {
        $clubs = implode(', ', $this->club_names);

        return [
            'title' => 'Troop Verification Request',
            'body' => "{$this->trooper_name} confirmed {$this->event_name} for {$clubs}.",
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
