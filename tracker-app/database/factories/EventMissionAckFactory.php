<?php

declare(strict_types=1);

namespace Database\Factories;

use Database\Factories\Base\EventMissionAckFactory as BaseEventMissionAckFactory;

class EventMissionAckFactory extends BaseEventMissionAckFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return array_merge(parent::definition(), [
        ]);
    }
}
