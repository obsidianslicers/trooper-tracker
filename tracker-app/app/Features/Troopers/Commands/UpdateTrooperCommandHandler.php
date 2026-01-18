<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;


readonly class UpdateTrooperCommandHandler
{
    public function __invoke(object $message): void
    {
        /** @var UpdateTrooperCommand $message */
        $message->trooper->fill($message->valid_data);

        if ($message->complete_setup)
        {
            $message->trooper->setup_completed_at = now();
        }

        $message->trooper->save();
    }
}

