<?php

declare(strict_types=1);

namespace App\Notifications\Events;

use App\Enums\AdministrativeNotifications;
use App\Enums\TrooperNotifications;
use App\Mail\Events\TroopCorrectionRequested;
use App\Models\TroopCorrection;
use App\Models\Trooper;
use App\Notifications\BaseNotification;

/**
 * Asks an event coordinator to confirm that a trooper who never signed up was at their
 * event. Sent again once as a reminder when they haven't answered in a week.
 */
class TroopCorrectionRequestedNotification extends BaseNotification
{
    protected AdministrativeNotifications|TrooperNotifications|string|null $notification_category =
        TrooperNotifications::TROOP_CREDIT_REQUESTS;

    public function __construct(
        public readonly TroopCorrection $troop_correction,
        public readonly bool $is_reminder = false,
    ) {}

    public function toMail(Trooper $notifiable): TroopCorrectionRequested
    {
        return (new TroopCorrectionRequested($this->troop_correction, $this->is_reminder))
            ->to($notifiable->email);
    }

    /**
     * @return array<string, string>
     */
    public function toArray(Trooper $notifiable): array
    {
        return [
            'title' => __('notifications.Were they at your troop?'),
            'body' => __('notifications.:trooper asked for credit for :event. Please confirm or deny.', [
                'trooper' => $this->troop_correction->trooper->display_name,
                'event' => $this->troop_correction->event->name,
            ]),
            'url' => route('events.troop-correction', $this->troop_correction, absolute: false),
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
