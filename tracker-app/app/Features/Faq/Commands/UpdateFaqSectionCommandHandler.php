<?php

declare(strict_types=1);

namespace App\Features\Faq\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
use App\Models\FaqSection;

readonly class UpdateFaqSectionCommandHandler implements CommandHandlerInterface
{
    public function __invoke(object $message): FaqSection
    {
        $message->section->label = $message->label;
        $message->section->icon = $message->icon;

        $message->section->save();

        return $message->section;
    }
}
