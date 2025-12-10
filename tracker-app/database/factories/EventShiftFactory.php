<?php

namespace Database\Factories;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use Database\Factories\Base\EventShiftFactory as BaseEventShiftFactory;

class EventShiftFactory extends BaseEventShiftFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return array_merge(parent::definition(), [
            EventShift::STATUS => EventStatus::OPEN,
            EventShift::SHIFT_STARTS_AT => $this->faker->dateTimeBetween('now', '+1 hour'),
            EventShift::SHIFT_ENDS_AT => $this->faker->dateTimeBetween('+2 hours', '+3 hours'),
        ]);
    }

    public function withAssignment(Trooper $trooper, OrganizationCostume $costume): static
    {
        return $this->afterCreating(function (Event $event) use ($trooper, $costume)
        {
            $event->event_troopers()->create([
                EventTrooper::TROOPER_ID => $trooper->id,
                EventTrooper::COSTUME_ID => $costume->id,
            ]);
        });
    }

    public function withEvent(Event $event): static
    {
        return $this->state(fn(array $attributes) => [
            EventShift::EVENT_ID => $event->id,
        ]);
    }

    /**
     * Indicate that the event is closed (historical).
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function closed(): static
    {
        return $this->state(function (array $attributes)
        {
            return [
                EventShift::STATUS => EventStatus::CLOSED,
                EventShift::SHIFT_STARTS_AT => $this->faker->dateTimeBetween('-2 years', '-1 month'),
                EventShift::SHIFT_ENDS_AT => $this->faker->dateTimeBetween('-1 month', '-1 day'),
            ];
        });
    }

    /**
     * Indicate that the event is open (future).
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function open(): static
    {
        return $this->state(function (array $attributes)
        {
            return [
                EventShift::STATUS => EventStatus::OPEN,
                EventShift::SHIFT_STARTS_AT => $this->faker->dateTimeBetween('now', '+1 month'),
                EventShift::SHIFT_ENDS_AT => $this->faker->dateTimeBetween('+1 month', '+1 year'),
            ];
        });
    }
}