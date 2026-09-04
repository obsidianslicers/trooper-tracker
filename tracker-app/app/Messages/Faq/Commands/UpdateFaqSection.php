<?php

declare(strict_types=1);

namespace App\Messages\Faq\Commands;

use App\Models\FaqSection;
use Hyperdrive\Message;

/**
 * @method static FaqSection call(FaqSection $section, string $label, string $icon)
 */
final class UpdateFaqSection extends Message
{
    public function __construct(
        private readonly FaqSection $section,
        private readonly string $label,
        private readonly string $icon,
    ) {
    }

    public function handle(): FaqSection
    {
        $this->section->label = $this->label;
        $this->section->icon = $this->icon;

        $this->section->save();

        return $this->section;
    }
}
