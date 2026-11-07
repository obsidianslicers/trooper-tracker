<?php

declare(strict_types=1);

namespace App\Notifications\Troopers;

use App\Enums\AdministrativeNotifications;
use App\Enums\TrooperNotifications;
use App\Mail\Admin\Troopers\CostumeApproved;
use App\Models\Trooper;
use App\Notifications\BaseNotification;

/**
 * Tells a trooper an officer approved a costume they added for a club.
 */
class CostumeApprovedNotification extends BaseNotification
{
    protected AdministrativeNotifications|TrooperNotifications|string|null $notification_category = 'costume_approved';

    public function __construct(
        public readonly string $costume_name,
        public readonly string $club_name,
    ) {}

    public function toMail(Trooper $notifiable): CostumeApproved
    {
        return (new CostumeApproved($notifiable, $this->costume_name, $this->club_name))
            ->to($notifiable->email);
    }

    /**
     * @return array<string, string>
     */
    public function toArray(Trooper $notifiable): array
    {
        return [
            'title' => 'Costume Approved',
            'body' => "{$this->costume_name} is approved for {$this->club_name}.",
            'url' => '/account',
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
