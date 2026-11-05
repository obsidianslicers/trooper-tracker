<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands;

use App\Models\Trooper;
use App\Notifications\Troopers\VisitorAccessExpiredNotification;
use Hyperdrive\Message;

/**
 * @method static void call(Trooper $trooper)
 */
final class NotifyExpiredVisitor extends Message
{
    public function __construct(
        private readonly Trooper $trooper
    ) {}

    public function handle(): void
    {
        $this->trooper->visitor_notified_at = now();
        $this->trooper->save();

        $this->trooper->notify(new VisitorAccessExpiredNotification);
    }
}
