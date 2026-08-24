<?php

declare(strict_types=1);

namespace App\Features\Faq\Commands;

use App\Bus\Contracts\CommandHandlerInterface;

readonly class DeleteFaqSectionCommandHandler implements CommandHandlerInterface
{
    public function __invoke(object $message): mixed
    {
        $message->section->delete();

        return null;
    }
}
