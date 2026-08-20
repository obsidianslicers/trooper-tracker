<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands;

use App\Models\Trooper;
use Hyperdrive\Message;

/**
 * Command message for updating a trooper's push notifications setting.
 *
 * @method static void call(Trooper $trooper, bool $push_notifications_enabled)
 */
final class UpdateTrooperPushNotifications extends Message
{
    public function __construct(
        private readonly Trooper $trooper,
        private readonly bool $push_notifications_enabled,
    ) {}

    /**
     * Execute the command to update trooper push notifications setting.
     *
     * @return null
     */
    public function handle(): void
    {
        $this->trooper->push_notifications_enabled = $this->push_notifications_enabled;

        $this->trooper->save();
    }
}
