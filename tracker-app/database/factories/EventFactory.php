<?php

namespace Database\Factories;

use App\Enums\EventStatus;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $starts_at = $this->faker->dateTimeBetween('-1 month', '+1 month');
        $ends_at = (clone $starts_at)->modify('+4 hours');

        return [
            Event::NAME => $this->faker->sentence(4),
            Event::ORGANIZATION_ID => Organization::factory(),
            Event::STARTS_AT => $starts_at,
            Event::ENDS_AT => $ends_at,
            Event::LIMIT_ORGANIZATIONS => $this->faker->boolean(),
            Event::TROOPERS_ALLOWED => $this->faker->numberBetween(10, 50),
            Event::HANDLERS_ALLOWED => $this->faker->numberBetween(2, 5),
            Event::STATUS => EventStatus::OPEN,
        ];
    }

    public function withAssignment(Trooper $trooper, Costume $costume): static
    {
        return $this->afterCreating(function (Event $event) use ($trooper, $costume)
        {
            $event->event_troopers()->create([
                EventTrooper::TROOPER_ID => $trooper->id,
                EventTrooper::COSTUME_ID => $costume->id,
            ]);
        });
    }

    public function withOrganization(Organization $organization): static
    {
        return $this->state(fn(array $attributes) => [
            Event::ORGANIZATION_ID => $organization,
        ]);
    }

    /**
     * Indicate that the event is closed (historical).
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function closed(): Factory
    {
        return $this->state(function (array $attributes)
        {
            return [
                Event::STATUS => EventStatus::CLOSED,
                Event::STARTS_AT => $this->faker->dateTimeBetween('-2 years', '-1 month'),
                Event::ENDS_AT => $this->faker->dateTimeBetween('-1 month', '-1 day'),
            ];
        });
    }

    /**
     * Indicate that the event is open (future).
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function open(): Factory
    {
        return $this->state(function (array $attributes)
        {
            return [
                Event::STATUS => EventStatus::OPEN,
                Event::STARTS_AT => $this->faker->dateTimeBetween('now', '+1 month'),
                Event::ENDS_AT => $this->faker->dateTimeBetween('+1 month', '+1 year'),
            ];
        });
    }
}