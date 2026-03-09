<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\NoticeType;
use App\Models\Notice;
use App\Models\Organization;
use Carbon\Carbon;
use Database\Factories\Base\NoticeFactory as BaseNoticeFactory;

class NoticeFactory extends BaseNoticeFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return array_merge(parent::definition(), [
            Notice::TYPE => NoticeType::INFO
        ]);
    }

    public function withOrganization(Organization $organization): static
    {
        return $this->state(fn(array $attributes): array => [
            Notice::ORGANIZATION_ID => $organization->{Organization::ID},
        ]);
    }

    public function forOrganization(Organization $organization): static
    {
        return $this->withOrganization($organization);
    }

    public function asGlobal(): static
    {
        return $this->state(fn(array $attributes): array => [
            Notice::ORGANIZATION_ID => null,
        ]);
    }

    public function asActive(): static
    {
        return $this->state(fn(array $attributes): array => [
            Notice::STARTS_AT => Carbon::now()->subDay(),
            Notice::ENDS_AT => Carbon::now()->addWeek(),
        ]);
    }

    public function asPast(): static
    {
        return $this->state(fn(array $attributes): array => [
            Notice::STARTS_AT => Carbon::now()->subWeek(),
            Notice::ENDS_AT => Carbon::now()->subDay(),
        ]);
    }

    public function asFuture(): static
    {
        return $this->state(fn(array $attributes): array => [
            Notice::STARTS_AT => Carbon::now()->addDay(),
            Notice::ENDS_AT => Carbon::now()->addWeek(),
        ]);
    }

    public function withTitle(string $title): static
    {
        return $this->state(fn(array $attributes): array => [
            Notice::TITLE => $title,
        ]);
    }
}
