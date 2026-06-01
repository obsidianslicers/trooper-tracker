<?php

namespace Database\Factories\Base;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use App\Models\FaqSection;

class FaqSectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            FaqSection::LABEL => $this->faker->text(),
            FaqSection::ICON => $this->faker->word(),
            FaqSection::SORT_ORDER => $this->faker->word(),
        ];
    }
}
