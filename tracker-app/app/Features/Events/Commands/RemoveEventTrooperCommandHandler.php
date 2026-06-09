<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Bus\Contracts\CommandHandlerInterface;

readonly class RemoveEventTrooperCommandHandler implements CommandHandlerInterface
{
    /**
     * @param  RemoveEventTrooperCommand  $message
     */
    public function __invoke(object $message): mixed
    {
        $message->event_trooper->delete();

        return null;
    }
}
