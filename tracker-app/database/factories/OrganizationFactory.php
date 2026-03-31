<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OrganizationType;
use App\Models\Organization;
use Database\Factories\Base\OrganizationFactory as BaseOrganizationFactory;

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
            Organization::TYPE => OrganizationType::ORGANIZATION,
            Organization::CAN_ATTEND_DEFAULT => true,
            Organization::REQUIRES_GUARDIAN => false,
        ]);
    }

    public function withName(string $name): static
    {
        return $this->state(fn(array $attributes): array => [
            Organization::NAME => $name,
        ]);
    }

    public function withNodePath(string $node_path): static
    {
        return $this->state(fn(array $attributes): array => [
            Organization::NODE_PATH => $node_path,
        ]);
    }

    public function withParent(Organization $parent): static
    {
        return $this->state(fn(array $attributes): array => [
            Organization::PARENT_ID => $parent->{Organization::ID},
            Organization::DEPTH => $parent->{Organization::DEPTH} + 1,
        ]);
    }

    public function withIdentifierDisplay(string $identifier_display): static
    {
        return $this->state(fn(array $attributes): array => [
            Organization::IDENTIFIER_DISPLAY => $identifier_display,
        ]);
    }

    public function withSequence(int $sequence): static
    {
        return $this->state(fn(array $attributes): array => [
            Organization::SEQUENCE => $sequence,
        ]);
    }

    public function asOrganization(): static
    {
        return $this->state(fn(array $attributes): array => [
            Organization::TYPE => OrganizationType::ORGANIZATION,
            Organization::DEPTH => 0,
        ]);
    }

    public function asRegion(): static
    {
        return $this->state(fn(array $attributes): array => [
            Organization::TYPE => OrganizationType::REGION,
            Organization::DEPTH => 1,
        ]);
    }

    public function asUnit(): static
    {
        return $this->state(fn(array $attributes): array => [
            Organization::TYPE => OrganizationType::UNIT,
            Organization::DEPTH => 2,
        ]);
    }

    public function withRelatedForum(string $related_forum): static
    {
        return $this->state(fn(array $attributes): array => [
            Organization::RELATED_FORUM => $related_forum,
        ]);
    }
}
