<?php

namespace Database\Factories\Base;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use App\Models\Notice;

class NoticeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            Notice::ORGANIZATION_ID => \App\Models\Organization::factory(),
            Notice::STARTS_AT => $this->faker->unixTime(),
            Notice::TITLE => $this->faker->title(),
            Notice::TYPE => $this->faker->word(),
            Notice::MESSAGE => $this->faker->text(),
        ];
    }
}
