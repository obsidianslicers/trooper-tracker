<?php

namespace Database\Factories;

use Database\Factories\Base\OrganizationCostumeFactory as BaseOrganizationCostumeFactory;

class OrganizationCostumeFactory extends BaseOrganizationCostumeFactory
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
