<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Costume;
use App\Models\Organization;
use App\Models\OrganizationCostume;
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

    public function forOrganization(Organization $organization): static
    {
        return $this->state(fn(array $attributes): array => [
            OrganizationCostume::ORGANIZATION_ID => $organization->{Organization::ID},
        ]);
    }

    public function forCostume(Costume $costume): static
    {
        return $this->state(fn(array $attributes): array => [
            OrganizationCostume::COSTUME_ID => $costume->{Costume::ID},
        ]);
    }

    public function withPrefix(string $prefix): static
    {
        return $this->state(fn(array $attributes): array => [
            OrganizationCostume::PREFIX => $prefix,
        ]);
    }
}
