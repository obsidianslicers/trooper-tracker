<?php

declare(strict_types=1);

namespace App\Features\Faq\Commands;

use App\Bus\Concerns\ShouldBeTransactional;
use App\Bus\Contracts\CommandHandlerInterface;
use App\Models\Faq;

readonly class ReorderFaqItemsCommandHandler implements CommandHandlerInterface
{
    use ShouldBeTransactional;

    public function __invoke(object $message): mixed
    {
        foreach ($message->ordered_ids as $position => $id)
        {
            Faq::where(Faq::ID, (int) $id)->update([Faq::SORT_ORDER => $position + 1]);
        }

        return null;
    }
}
