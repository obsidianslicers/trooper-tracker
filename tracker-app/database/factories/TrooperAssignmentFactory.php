<?php

namespace Database\Factories;

use App\Models\TrooperAssignment;
use Database\Factories\Base\TrooperAssignmentFactory as BaseTrooperAssignmentFactory;

class TrooperAssignmentFactory extends BaseTrooperAssignmentFactory
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

    public function member(): static
    {
        return $this->state(fn(array $attributes) => [
            TrooperAssignment::IS_MEMBER => true,
        ]);
    }
}
