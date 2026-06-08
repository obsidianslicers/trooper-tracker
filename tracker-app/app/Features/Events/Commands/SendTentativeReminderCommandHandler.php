<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
use App\Notifications\Events\TentativeStatusReminderNotification;

/**
 * @implements CommandHandlerInterface<SendTentativeReminderCommand>
 */
readonly class SendTentativeReminderCommandHandler implements CommandHandlerInterface
{
    public function __invoke(object $message): mixed
    {
        $message->event_trooper->trooper->notify(
            new TentativeStatusReminderNotification($message->event_trooper)
        );

        return null;
    }
}
