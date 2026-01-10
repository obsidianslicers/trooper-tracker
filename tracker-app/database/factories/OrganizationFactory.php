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

    public function region(): static
    {
        return $this->state(fn(array $attributes) => [
            Organization::PARENT_ID => Organization::factory(),
            Organization::TYPE => OrganizationType::REGION,
        ]);
    }

    public function unit(): static
    {
        return $this->state(fn(array $attributes) => [
            Organization::PARENT_ID => Organization::factory()->region(),
            Organization::TYPE => OrganizationType::UNIT,
        ]);
    }

    public function withCostume(string $name): static
    {
        return $this->afterCreating(function (Organization $organization) use ($name)
        {
            if ($organization->type != OrganizationType::ORGANIZATION)
            {
                throw new Exception('Invalid Organization Type for a costume: ' . $organization->type);
            }

            $organization->organization_costumes()->create([
                'name' => $name
            ]);
        });
    }

    /**
     * Set the organization to have identifier validation rules.
     *
     * @param string $validation_rules Validation rules for the identifier (default: 'integer|between:10000,99999')
     * @return static
     */
    public function withIdentifierValidation(string $validation_rules = 'integer|between:10000,99999'): static
    {
        return $this->state(fn(array $attributes) => [
            Organization::IDENTIFIER_VALIDATION => $validation_rules,
        ]);
    }

    /**
     * Alias for region() for better readability in tests.
     *
     * @return static
     */
    public function asRegion(): static
    {
        return $this->region();
    }
}
