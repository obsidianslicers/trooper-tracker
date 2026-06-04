<?php

namespace Database\Factories\Base;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use App\Models\TrooperNotification;

class TrooperNotificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            TrooperNotification::TYPE => $this->faker->word(),
            TrooperNotification::NOTIFIABLE_TYPE => $this->faker->word(),
            TrooperNotification::NOTIFIABLE_ID => $this->faker->randomDigitNotNull(),
            TrooperNotification::DATA => $this->faker->text(),
        ];
    }
}
