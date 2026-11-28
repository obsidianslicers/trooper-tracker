<?php

declare(strict_types=1);

namespace App\Notifications\Events;

use App\Enums\AdministrativeNotifications;
use App\Enums\TroopCorrectionStatus;
use App\Enums\TrooperNotifications;
use App\Mail\Events\TroopCorrectionDecided;
use App\Models\TroopCorrection;
use App\Models\Trooper;
use App\Notifications\BaseNotification;

/**
 * Tells a trooper whether their request for troop credit was approved or denied.
 */
class TroopCorrectionDecidedNotification extends BaseNotification
{
    protected AdministrativeNotifications|TrooperNotifications|string|null $notification_category =
        TrooperNotifications::TROOP_CREDIT_REQUESTS;

    public function __construct(public readonly TroopCorrection $troop_correction) {}

    public function toMail(Trooper $notifiable): TroopCorrectionDecided
    {
        return (new TroopCorrectionDecided($this->troop_correction))->to($notifiable->email);
    }

    /**
     * @return array<string, string>
     */
    public function toArray(Trooper $notifiable): array
    {
        $event = $this->troop_correction->event;
        $approved = $this->troop_correction->status === TroopCorrectionStatus::APPROVED;

        return [
            'title' => $approved
                ? __('notifications.Troop credit approved')
                : __('notifications.Troop credit denied'),
            'body' => $approved
                ? __('notifications.Your troop at :event now counts.', ['event' => $event->name])
                : __('notifications.Your request for credit for :event was denied.', ['event' => $event->name]),
            'url' => route('events.display', $event, absolute: false),
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
