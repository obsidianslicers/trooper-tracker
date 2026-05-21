<?php

namespace Database\Factories;

use App\Models\FaqSection;
use Database\Factories\Base\FaqSectionFactory as BaseFaqSectionFactory;

class FaqSectionFactory extends BaseFaqSectionFactory
{
    public function definition(): array
    {
        return parent::definition();
    }

    public function withLabel(string $label): static
    {
        return $this->state(fn(array $attributes) => [
            FaqSection::LABEL => $label,
        ]);
    }
}
