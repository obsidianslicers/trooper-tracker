<?php

declare(strict_types=1);

namespace App\Messages\Faq\Commands;

use App\Models\FaqSection;
use Hyperdrive\Message;

/**
 * Deletes a FAQ section, but only once it's empty.
 *
 * @method static int call(FaqSection $section)
 */
final class DeleteFaqSection extends Message
{
    public function __construct(
        private readonly FaqSection $section,
    ) {}

    /**
     * @return int The FAQ items still in the section; it's deleted only when that's 0
     */
    public function handle(): int
    {
        $faq_count = $this->section->faqs()->count();

        if ($faq_count === 0)
        {
            $this->section->delete();
        }

        return $faq_count;
    }
}
