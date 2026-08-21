<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands;

use App\Enums\NotificationFrequency;
use App\Models\Trooper;
use Hyperdrive\Message;

/**
 * Command message for updating a trooper's notification frequency.
 *
 * @method static void call(Trooper $trooper, NotificationFrequency $notification_frequency)
 */
final class UpdateTrooperNotificationFrequency extends Message
{
    public function __construct(
        private readonly Trooper $trooper,
        private readonly NotificationFrequency $notification_frequency,
    ) {}

    /**
     * Execute the command to update trooper notification frequency.
     *
     * @return null
     */
    public function handle(): void
    {
        $this->trooper->notification_frequency = $this->notification_frequency;

        $this->trooper->save();
    }
}
