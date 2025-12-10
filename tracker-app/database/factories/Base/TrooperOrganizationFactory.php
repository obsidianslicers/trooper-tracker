<?php

namespace Database\Factories\Base;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use App\Models\TrooperOrganization;

class TrooperOrganizationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            TrooperOrganization::TROOPER_ID => \App\Models\Trooper::factory(),
            TrooperOrganization::ORGANIZATION_ID => \App\Models\Organization::factory(),
            TrooperOrganization::IDENTIFIER => $this->faker->word(),
            TrooperOrganization::MEMBERSHIP_STATUS => $this->faker->word(),
        ];
    }
}
