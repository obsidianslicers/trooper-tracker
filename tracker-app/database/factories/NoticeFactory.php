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

    public function withOrganization(Organization $organization): static
    {
        return $this->state(fn(array $attributes): array => [
            Notice::ORGANIZATION_ID => $organization->{Organization::ID},
        ]);
    }
}
