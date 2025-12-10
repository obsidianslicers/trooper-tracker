<?php

namespace Database\Factories;

use Database\Factories\Base\TrooperCostumeFactory as BaseTrooperCostumeFactory;

class TrooperCostumeFactory extends BaseTrooperCostumeFactory
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
