<?php

namespace Database\Factories;

use Database\Factories\Base\TrooperOrganizationFactory as BaseTrooperOrganizationFactory;

class TrooperOrganizationFactory extends BaseTrooperOrganizationFactory
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
