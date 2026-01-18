<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Bus\Contracts\CommandHandlerInterface;


readonly class UpdateTrooperCommandHandler implements CommandHandlerInterface
{
    public function __invoke(object $message): mixed
    {
        /** @var UpdateTrooperCommand $message */
        $message->trooper->fill($message->valid_data);

        if ($message->complete_setup)
        {
            $message->trooper->setup_completed_at = now();
        }

        $message->trooper->save();

        return null;
    }
}

