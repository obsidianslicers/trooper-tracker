<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
use App\Notifications\Troopers\VisitorAccessExpiredNotification;

/**
 * @implements CommandHandlerInterface<NotifyExpiredVisitorCommand>
 */
readonly class NotifyExpiredVisitorCommandHandler implements CommandHandlerInterface
{
    public function __invoke(object $message): void
    {
        $message->trooper->visitor_notified_at = now();
        $message->trooper->save();

        $message->trooper->notify(new VisitorAccessExpiredNotification);
    }
}
