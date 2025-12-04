<?php

namespace Database\Factories;

use App\Models\Costume;
use App\Models\Event;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EventTrooper>
 */
class EventTrooperFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            EventTrooper::EVENT_ID => Event::factory(),
            EventTrooper::TROOPER_ID => Trooper::factory(),
            EventTrooper::COSTUME_ID => Costume::factory(),
        ];
    }

    public function withEvent(Event $event): static
    {
        return $this->state(fn(array $attributes) => [
            EventTrooper::EVENT_ID => $event,
        ]);
    }
}
