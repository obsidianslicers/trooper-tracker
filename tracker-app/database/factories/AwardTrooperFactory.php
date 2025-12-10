<?php

namespace Database\Factories;

use Database\Factories\Base\AwardTrooperFactory as BaseAwardTrooperFactory;

class AwardTrooperFactory extends BaseAwardTrooperFactory
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
