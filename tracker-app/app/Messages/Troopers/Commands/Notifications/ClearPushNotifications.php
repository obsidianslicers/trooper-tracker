<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands\Notifications;

use App\Models\Trooper;
use Hyperdrive\Message;

/**
 * Command to clear all push notifications for a trooper.
 *
 * Deletes all notification records associated with the trooper.
 *
 * @method static void call(Trooper $trooper)
 */
final class ClearPushNotifications extends Message
{
    public function __construct(
        private readonly Trooper $trooper
    ) {}

    /**
     * Execute the command to delete all notifications for the trooper.
     */
    public function handle(): void
    {
        $this->trooper->notifications()->delete();
    }
}
