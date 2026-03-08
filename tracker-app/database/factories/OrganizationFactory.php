<?php

namespace Database\Factories;

use App\Enums\OrganizationType;
use App\Models\Organization;
use Database\Factories\Base\OrganizationFactory as BaseOrganizationFactory;
use Exception;

class OrganizationFactory extends BaseOrganizationFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return array_merge(parent::definition(), [
            Organization::TYPE => OrganizationType::ORGANIZATION
        ]);
    }
}
