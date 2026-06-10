<?php

namespace Database\Factories\Base;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use App\Models\TrooperRequest;

class TrooperRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            TrooperRequest::TROOPER_ID => \App\Models\Trooper::factory(),
            TrooperRequest::ORGANIZATION_ID => $this->faker->randomDigitNotNull(),
            TrooperRequest::PRIMARY_ORGANIZATION_ID => \App\Models\Organization::factory(),
            TrooperRequest::STATUS => $this->faker->word(),
        ];
    }
}
