<?php

declare(strict_types=1);

namespace App\Notifications\Admin;

use App\Enums\AdministrativeNotifications;
use App\Enums\TrooperNotifications;
use App\Mail\Admin\Troopers\CostumeRequestSubmitted;
use App\Models\Trooper;
use App\Notifications\BaseNotification;

/**
 * Tells an officer that a trooper added a costume waiting for their approval.
 */
class CostumeRequestSubmittedNotification extends BaseNotification
{
    protected AdministrativeNotifications|TrooperNotifications|string|null $notification_category =
        AdministrativeNotifications::COSTUME_REQUESTS;

    /**
     * @param  array<int, string>  $club_names
     */
    public function __construct(
        public readonly string $trooper_name,
        public readonly string $costume_name,
        public readonly array $club_names,
    ) {}

    public function toMail(Trooper $notifiable): CostumeRequestSubmitted
    {
        return (new CostumeRequestSubmitted(
            $this->trooper_name,
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
            'title' => 'Costume Approval Request',
            'body' => "{$this->trooper_name} added {$this->costume_name} for {$clubs}.",
            'url' => '/admin/troopers/costume-approvals',
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
