<?php

namespace Database\Factories\Base;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use App\Models\Faq;

class FaqFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            Faq::SECTION_ID => \App\Models\FaqSection::factory(),
            Faq::TITLE => $this->faker->title(),
            Faq::SORT_ORDER => $this->faker->word(),
        ];
    }
}
