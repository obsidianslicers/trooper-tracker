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
            Organization::TYPE => OrganizationType::ORGANIZATION
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

    public function withRelatedForum(string $related_forum): static
    {
        return $this->state(fn(array $attributes): array => [
            Organization::RELATED_FORUM => $related_forum,
        ]);
    }
}
