<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
use App\Notifications\Events\EventUpdatedNotification;

/**
 * @implements CommandHandlerInterface<SendEventUpdatedNotificationCommand>
 */
readonly class SendEventUpdatedNotificationCommandHandler implements CommandHandlerInterface
{
    public function __invoke(object $message): mixed
    {
        $message->trooper->notify(new EventUpdatedNotification($message->event, $message->changed_fields));

        return null;
    }
}
