<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Trooper;
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

    public function forTrooper(Trooper $trooper): static
    {
        return $this->state(fn(array $attributes): array => [
            TrooperAssignment::TROOPER_ID => $trooper->{Trooper::ID},
        ]);
    }

    public function forOrganization(Organization $organization): static
    {
        return $this->state(fn(array $attributes): array => [
            TrooperAssignment::ORGANIZATION_ID => $organization->{Organization::ID},
        ]);
    }

    public function asMember(): static
    {
        return $this->state(fn(array $attributes): array => [
            TrooperAssignment::IS_MEMBER => true,
        ]);
    }

    public function asModerator(): static
    {
        return $this->state(fn(array $attributes): array => [
            TrooperAssignment::IS_MODERATOR => true,
        ]);
    }

    public function withShouldNotify(bool $should_notify = true): static
    {
        return $this->state(fn(array $attributes): array => [
            TrooperAssignment::SHOULD_NOTIFY => $should_notify,
        ]);
    }
}
