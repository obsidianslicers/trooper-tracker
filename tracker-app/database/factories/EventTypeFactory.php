<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\EventType;
use Database\Factories\Base\EventTypeFactory as BaseEventTypeFactory;

class EventTypeFactory extends BaseEventTypeFactory
{
    public function definition(): array
    {
        $name = ucwords($this->faker->unique()->words(2, true));

        return array_merge(parent::definition(), [
            EventType::SLUG => EventType::uniqueSlugFor($name),
            EventType::NAME => $name,
            EventType::DESCRIPTION => $this->faker->sentence(),
            EventType::SORT_ORDER => $this->faker->numberBetween(100, 999),
            EventType::IS_COUNTED => true,
            EventType::IS_ACTIVE => true,
        ]);
    }

    public function uncounted(): static
    {
        return $this->state(fn (array $attributes) => [
            EventType::IS_COUNTED => false,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            EventType::IS_ACTIVE => false,
        ]);
    }
}
