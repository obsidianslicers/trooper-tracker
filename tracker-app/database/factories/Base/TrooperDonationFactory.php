<?php

namespace Database\Factories\Base;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use App\Models\TrooperDonation;

class TrooperDonationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            TrooperDonation::TROOPER_ID => \App\Models\Trooper::factory(),
            TrooperDonation::AMOUNT => $this->faker->randomFloat(2),
            TrooperDonation::TXN_ID => $this->faker->unique()->randomDigitNotNull(),
            TrooperDonation::TXN_TYPE => $this->faker->word(),
        ];
    }
}
