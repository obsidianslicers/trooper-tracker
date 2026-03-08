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
            TrooperAssignment::SHOULD_NOTIFY => false,
            TrooperAssignment::IS_MEMBER => false,
            TrooperAssignment::IS_MODERATOR => false,
        ]);
    }
}
