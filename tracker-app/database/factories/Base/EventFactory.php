<?php

namespace Database\Factories\Base;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use App\Models\Event;

class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            Event::ORGANIZATION_ID => \App\Models\Organization::factory(),
            Event::PRIMARY_ORGANIZATION_ID => \App\Models\Organization::factory(),
        ];
    }
}
