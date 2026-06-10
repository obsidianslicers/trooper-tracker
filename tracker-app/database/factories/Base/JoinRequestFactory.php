<?php

namespace Database\Factories\Base;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use App\Models\JoinRequest;

class JoinRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            JoinRequest::TROOPER_ID => \App\Models\Trooper::factory(),
            JoinRequest::ORGANIZATION_ID => $this->faker->randomDigitNotNull(),
            JoinRequest::PRIMARY_ORGANIZATION_ID => \App\Models\Organization::factory(),
            JoinRequest::STATUS => $this->faker->word(),
        ];
    }
}
