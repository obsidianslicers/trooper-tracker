<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
use App\Notifications\Events\EventCancelledNotification;

/**
 * @implements CommandHandlerInterface<SendEventCancelledNotificationCommand>
 */
readonly class SendEventCancelledNotificationCommandHandler implements CommandHandlerInterface
{
    public function __invoke(object $message): mixed
    {
        $message->trooper->notify(new EventCancelledNotification($message->event));

        return null;
    }
}
