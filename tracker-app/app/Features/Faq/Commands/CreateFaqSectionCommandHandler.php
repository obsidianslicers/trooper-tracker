<?php

declare(strict_types=1);

namespace App\Features\Faq\Commands;

use App\Bus\Concerns\ShouldBeTransactional;
use App\Bus\Contracts\CommandHandlerInterface;
use App\Models\FaqSection;

readonly class CreateFaqSectionCommandHandler implements CommandHandlerInterface
{
    use ShouldBeTransactional;

    public function __invoke(object $message): FaqSection
    {
        $max_order = FaqSection::max(FaqSection::SORT_ORDER) ?? 0;

        $section = new FaqSection;
        $section->label = $message->label;
        $section->icon = $message->icon;
        $section->sort_order = $max_order + 1;

        $section->save();

        return $section;
    }
}
