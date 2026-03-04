<?php

namespace Database\Factories\Base;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use App\Models\EventShare;

class EventShareFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            EventShare::EVENT_ID => \App\Models\Event::factory(),
            EventShare::TROOPER_ID => \App\Models\Trooper::factory(),
            EventShare::SHARE_TOKEN => $this->faker->unique()->sha1(),
            EventShare::RECIPIENT_EMAIL => $this->faker->word(),
            EventShare::VIEW_COUNT => $this->faker->randomNumber(),
            EventShare::EXPIRES_AT => $this->faker->unixTime(),
            EventShare::IS_REVOKED => $this->faker->randomNumber(1),
        ];
    }
}
