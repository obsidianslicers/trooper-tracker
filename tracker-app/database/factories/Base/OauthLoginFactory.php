<?php

namespace Database\Factories\Base;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use App\Models\OauthLogin;

class OauthLoginFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            OauthLogin::TROOPER_ID => \App\Models\Trooper::factory(),
            OauthLogin::PROVIDER => $this->faker->word(),
            OauthLogin::PROVIDER_ID => $this->faker->randomDigitNotNull(),
        ];
    }
}
