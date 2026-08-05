<?php

declare(strict_types=1);

namespace App\Notifications\Events;

use App\Mail\Events\TrooperManualSelectionStandBy;
use App\Models\EventTrooper;
use App\Models\Trooper;
use App\Enums\AdministrativeNotifications;
use App\Enums\TrooperNotifications;
use App\Notifications\BaseNotification;

class ManualSelectionStandByNotification extends BaseNotification
{
    protected AdministrativeNotifications|TrooperNotifications|string|null $notification_category = 'manual_selection_stand_by';

    public function __construct(
        private readonly EventTrooper $event_trooper,
        private readonly Trooper $changed_by,
    ) {
    }

    public function toMail(Trooper $notifiable): TrooperManualSelectionStandBy
    {
        return (new TrooperManualSelectionStandBy($this->event_trooper, $this->changed_by))->to($notifiable->email);
    }

    public function toArray(Trooper $notifiable): array
    {
        $event = $this->event_trooper->event_shift->event;

        return [
            'title' => 'Moved to Stand By',
            'body' => $event->name,
            'url' => '/events/details/' . $event->id,
        ];
    }

    public function toFcm(Trooper $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
