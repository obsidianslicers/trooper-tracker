<?php

declare(strict_types=1);

namespace App\Notifications\Events;

use App\Enums\AdministrativeNotifications;
use App\Enums\TrooperNotifications;
use App\Mail\Events\TrooperManualSelectionApproved;
use App\Models\EventTrooper;
use App\Models\Trooper;
use App\Notifications\BaseNotification;

class ManualSelectionApprovedNotification extends BaseNotification
{
    protected AdministrativeNotifications|TrooperNotifications|string|null $notification_category = 'manual_selection_approved';

    public function __construct(
        private readonly EventTrooper $event_trooper,
        private readonly Trooper $approved_by,
    ) {}

    public function toMail(Trooper $notifiable): TrooperManualSelectionApproved
    {
        return (new TrooperManualSelectionApproved($this->event_trooper, $this->approved_by))->to($notifiable->email);
    }

    public function toArray(Trooper $notifiable): array
    {
        $event = $this->event_trooper->event_shift->event;

        return [
            'title' => "You're Now Going!",
            'body' => $event->name,
            'url' => '/events/details/'.$event->id,
        ];
    }

    public function toFcm(Trooper $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
