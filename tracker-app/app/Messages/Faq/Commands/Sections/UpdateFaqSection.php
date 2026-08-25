<?php

declare(strict_types=1);

namespace App\Messages\Faq\Commands\Sections;

use App\Models\FaqSection;
use Hyperdrive\Message;

/**
 * @method static FaqSection call(...$args)
 */
final class UpdateFaqSection extends Message
{
    public function __construct(
        public readonly FaqSection $section,
        public readonly string $label,
        public readonly string $icon,
    ) {}

    public function handle(): FaqSection
    {
        $this->section->label = $this->label;
        $this->section->icon = $this->icon;

        $this->section->save();

        return $this->section;
    }
}
