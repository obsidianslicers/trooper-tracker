<?php

namespace Database\Factories\Base;

use App\Models\Faq;
use App\Models\FaqSection;
use Illuminate\Database\Eloquent\Factories\Factory;

class FaqFactory extends Factory
{
    protected $model = Faq::class;

    public function definition(): array
    {
        return [
            Faq::SECTION_ID  => FaqSection::factory(),
            Faq::TITLE       => $this->faker->sentence(),
            Faq::DESCRIPTION => $this->faker->optional()->paragraph(),
            Faq::VIDEO_URL   => null,
            Faq::SORT_ORDER  => $this->faker->numberBetween(0, 100),
        ];
    }
}
