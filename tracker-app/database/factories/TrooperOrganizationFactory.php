<?php

namespace Database\Factories;

use App\Enums\MembershipStatus;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperOrganization;
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
            TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
        ]);
    }

    public function forOrganization(Organization $organization): static
    {
        return $this->state(fn(array $attributes): array => [
            TrooperOrganization::ORGANIZATION_ID => $organization->{Organization::ID},
        ]);
    }

    public function forTrooper(Trooper $trooper): static
    {
        return $this->state(fn(array $attributes): array => [
            TrooperOrganization::TROOPER_ID => $trooper->{Trooper::ID},
        ]);
    }

    public function withIdentifier(string $identifier): static
    {
        return $this->state(fn(array $attributes): array => [
            TrooperOrganization::IDENTIFIER => $identifier,
        ]);
    }
}
