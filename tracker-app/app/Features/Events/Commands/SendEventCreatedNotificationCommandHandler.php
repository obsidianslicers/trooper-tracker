<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
use App\Notifications\Events\EventCreatedNotification;

/**
 * @implements CommandHandlerInterface<SendEventCreatedNotificationCommand>
 */
readonly class SendEventCreatedNotificationCommandHandler implements CommandHandlerInterface
{
    public function __invoke(object $message): mixed
    {
        $message->trooper->notify(new EventCreatedNotification($message->event));

        return null;
    }
}
