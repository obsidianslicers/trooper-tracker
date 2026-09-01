<?php

declare(strict_types=1);

namespace App\Messages\Faq\Commands;

use App\Models\FaqSection;
use Hyperdrive\Message;

/**
 * @method static FaqSection call(string $label, string $icon)
 */
final class CreateFaqSection extends Message
{
    public function __construct(
        private readonly string $label,
        private readonly string $icon,
    ) {
    }

    public function handle(): FaqSection
    {
        $max_order = FaqSection::max(FaqSection::SORT_ORDER) ?? 0;

        $section = new FaqSection;
        $section->label = $this->label;
        $section->icon = $this->icon;
        $section->sort_order = $max_order + 1;

        $section->save();

        return $section;
    }
}
