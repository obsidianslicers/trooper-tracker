<?php

declare(strict_types=1);

namespace App\Messages\Faq\Commands;

use App\Models\Faq;
use Hyperdrive\Concerns\ShouldBeTransactional;
use Hyperdrive\Message;
use Illuminate\Support\Facades\DB;

/**
 * @method static void call(array $ordered_ids)
 */
final class ReorderFaqItems extends Message
{
    use ShouldBeTransactional;

    public function __construct(
        private readonly array $ordered_ids,
    ) {
    }

    public function handle(): void
    {
        foreach ($this->ordered_ids as $position => $id)
        {
            Faq::query()
                ->where(Faq::ID, (int) $id)
                ->update([Faq::SORT_ORDER => $position + 1]);
        }
    }
}
