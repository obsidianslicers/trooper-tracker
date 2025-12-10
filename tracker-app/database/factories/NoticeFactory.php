<?php

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

    public function active(): static
    {
        return $this->state(fn(array $attributes) => [
            Notice::STARTS_AT => Carbon::now()->subDay(),
            Notice::ENDS_AT => Carbon::now()->addDays(7),
        ]);
    }

    public function future(): static
    {
        return $this->state(fn(array $attributes) => [
            Notice::STARTS_AT => Carbon::now()->addDay(),
            Notice::ENDS_AT => Carbon::now()->addDays(7),
        ]);
    }

    public function past(): static
    {
        return $this->state(fn(array $attributes) => [
            Notice::STARTS_AT => Carbon::now()->subDays(8),
            Notice::ENDS_AT => Carbon::now()->subDays(7),
        ]);
    }

    public function withOrganization(Organization $organization): static
    {
        return $this->state(fn(array $attributes) => [
            Notice::ORGANIZATION_ID => $organization,
        ]);
    }
}
