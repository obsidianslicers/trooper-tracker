<?php

namespace Database\Factories\Base;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use App\Models\EventOrganization;

class EventOrganizationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            EventOrganization::EVENT_ID => \App\Models\Event::factory(),
            EventOrganization::ORGANIZATION_ID => \App\Models\Organization::factory(),
            EventOrganization::CAN_ATTEND => $this->faker->randomNumber(1),
        ];
    }
}
