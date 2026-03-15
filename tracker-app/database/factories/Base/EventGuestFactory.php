<?php

namespace Database\Factories\Base;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use App\Models\EventGuest;

class EventGuestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            EventGuest::EVENT_SHIFT_ID => \App\Models\EventShift::factory(),
            EventGuest::ADDED_BY_TROOPER_ID => \App\Models\Trooper::factory(),
            EventGuest::NAME => $this->faker->name(),
            EventGuest::IS_HANDLER => $this->faker->randomNumber(1),
            EventGuest::STATUS => $this->faker->word(),
            EventGuest::SIGNED_UP_AT => $this->faker->dateTime(),
        ];
    }
}
