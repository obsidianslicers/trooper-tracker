<?php

namespace Database\Factories;

use App\Models\FaqSection;
use Database\Factories\Base\FaqSectionFactory as BaseFaqSectionFactory;

class FaqSectionFactory extends BaseFaqSectionFactory
{
    public function definition(): array
    {
        return array_merge(parent::definition(), [
            FaqSection::SORT_ORDER => $this->faker->numberBetween(1, 999),
        ]);
    }

    public function withLabel(string $label): static
    {
        return $this->state(fn(array $attributes) => [
            FaqSection::LABEL => $label,
        ]);
    }
}
