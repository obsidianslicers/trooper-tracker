<?php

namespace Database\Factories\Base;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use App\Models\EventWatch;

class EventWatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            EventWatch::EVENT_ID => \App\Models\Event::factory(),
            EventWatch::TROOPER_ID => \App\Models\Trooper::factory(),
        ];
    }
}
