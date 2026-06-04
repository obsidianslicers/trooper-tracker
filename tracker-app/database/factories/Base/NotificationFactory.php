<?php

namespace Database\Factories\Base;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use App\Models\Notification;

class NotificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            Notification::TYPE => $this->faker->word(),
            Notification::NOTIFIABLE_TYPE => $this->faker->word(),
            Notification::NOTIFIABLE_ID => $this->faker->randomDigitNotNull(),
            Notification::DATA => $this->faker->text(),
        ];
    }
}
