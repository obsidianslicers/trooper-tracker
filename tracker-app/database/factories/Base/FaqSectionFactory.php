<?php

namespace Database\Factories\Base;

use App\Models\FaqSection;
use Illuminate\Database\Eloquent\Factories\Factory;

class FaqSectionFactory extends Factory
{
    protected $model = FaqSection::class;

    public function definition(): array
    {
        return [
            FaqSection::LABEL      => $this->faker->unique()->words(2, true),
            FaqSection::ICON       => 'fa-solid fa-' . $this->faker->word(),
            FaqSection::SORT_ORDER => $this->faker->numberBetween(0, 100),
        ];
    }
}
