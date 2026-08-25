<?php

declare(strict_types=1);

namespace App\Messages\Faq\Commands\Sections;

use App\Models\FaqSection;
use Hyperdrive\Message;

/**
 * @method static FaqSection call(...$args)
 */
final class CreateFaqSection extends Message
{
    public function __construct(
        public readonly string $label,
        public readonly string $icon,
    ) {}

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
