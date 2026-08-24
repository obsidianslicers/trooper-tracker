<?php

declare(strict_types=1);

namespace App\Features\Faq\Commands;

use App\Bus\Contracts\CommandHandlerInterface;

readonly class DeleteFaqCommandHandler implements CommandHandlerInterface
{
    public function __invoke(object $message): mixed
    {
        $message->faq->delete();

        return null;
    }
}
