<?php

declare(strict_types=1);

namespace App\Messages\Faq\Commands\Sections;

use App\Models\FaqSection;
use Hyperdrive\Message;
use Illuminate\Support\Facades\DB;

/**
 * @method static void call(...$args)
 */
final class ReorderFaqSections extends Message
{
    public function __construct(
        public readonly array $ordered_ids,
    ) {}

    public function handle(): void
    {
        DB::transaction(function () {
            foreach ($this->ordered_ids as $position => $id)
            {
                FaqSection::where(FaqSection::ID, (int) $id)->update([FaqSection::SORT_ORDER => $position + 1]);
            }
        });
    }
}
