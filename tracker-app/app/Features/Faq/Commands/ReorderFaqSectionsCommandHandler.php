<?php

declare(strict_types=1);

namespace App\Features\Faq\Commands;

use App\Bus\Concerns\ShouldBeTransactional;
use App\Bus\Contracts\CommandHandlerInterface;
use App\Models\FaqSection;

readonly class ReorderFaqSectionsCommandHandler implements CommandHandlerInterface
{
    use ShouldBeTransactional;

    public function __invoke(object $message): mixed
    {
        foreach ($message->ordered_ids as $position => $id)
        {
            FaqSection::where(FaqSection::ID, (int) $id)->update([FaqSection::SORT_ORDER => $position + 1]);
        }

        return null;
    }
}
