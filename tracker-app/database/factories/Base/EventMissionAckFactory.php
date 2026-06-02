<?php

namespace Database\Factories\Base;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use App\Models\EventMissionAck;

class EventMissionAckFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            EventMissionAck::EVENT_ID => \App\Models\Event::factory(),
            EventMissionAck::TROOPER_ID => \App\Models\Trooper::factory(),
            EventMissionAck::ACKNOWLEDGED_AT => $this->faker->dateTime(),
        ];
    }
}
