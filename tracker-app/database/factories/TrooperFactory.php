<?php

namespace Database\Factories;

use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Enums\TrooperTheme;
use App\Models\Notice;
use App\Models\NoticeTrooper;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperCostume;
use App\Models\TrooperOrganization;
use Database\Factories\Base\TrooperFactory as BaseTrooperFactory;
use Illuminate\Support\Facades\Hash;

class TrooperFactory extends BaseTrooperFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return array_merge(parent::definition(), [
            Trooper::EMAIL => $this->faker->safeEmail(),
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
            Trooper::MEMBERSHIP_ROLE => MembershipRole::MEMBER,
            Trooper::THEME => TrooperTheme::STORMTROOPER,
            Trooper::SETUP_COMPLETED_AT => now(),
        ]);
    }

    public function asActive(): static
    {
        return $this->withMembershipStatus(MembershipStatus::ACTIVE);
    }

    public function asRetired(): static
    {
        return $this->withMembershipStatus(MembershipStatus::RETIRED);
    }

    public function asPending(): static
    {
        return $this->withMembershipStatus(MembershipStatus::PENDING);
    }

    private function withMemberShipStatus(MembershipStatus $status = MembershipStatus::ACTIVE): static
    {
        return $this->state(fn(array $attributes) => [
            Trooper::MEMBERSHIP_STATUS => $status,
        ]);
    }

    public function asAdministrator(): static
    {
        return $this->withMemberShipRole(MembershipRole::ADMINISTRATOR);
    }

    public function asModerator(): static
    {
        return $this->withMemberShipRole(MembershipRole::MODERATOR);
    }

    public function asMember(): static
    {
        return $this->withMemberShipRole(MembershipRole::MEMBER);
    }

    private function withMemberShipRole(MembershipRole $role = MembershipRole::MEMBER): static
    {
        return $this->state(fn(array $attributes) => [
            Trooper::MEMBERSHIP_ROLE => $role,
        ]);
    }

    public function withOrganization(Organization $organization, string $identifier = 'TK9999'): static
    {
        return $this->afterCreating(function (Trooper $trooper) use ($organization, $identifier)
        {
            $trooper->organizations()->attach($organization->id, [
                TrooperOrganization::IDENTIFIER => $identifier,
            ]);
        });
    }

    public function withAssignment(Organization $organization, bool $moderator = false, bool $member = false, bool $notify = false): static
    {
        return $this->afterCreating(function (Trooper $trooper) use ($organization, $moderator, $member, $notify)
        {
            $trooper->trooper_assignments()->create([
                TrooperAssignment::ORGANIZATION_ID => $organization->id,
                TrooperAssignment::IS_MEMBER => $member,
                TrooperAssignment::CAN_NOTIFY => $notify,
                TrooperAssignment::IS_MODERATOR => $moderator,
            ]);
        });
    }

    public function withCostume(OrganizationCostume $costume): static
    {
        return $this->afterCreating(function (Trooper $trooper) use ($costume)
        {
            $trooper->trooper_costumes()->create([
                TrooperCostume::COSTUME_ID => $costume->id,
            ]);
        });
    }

    public function markAsRead(Notice $notice): static
    {
        return $this->afterCreating(function (Trooper $trooper) use ($notice)
        {
            NoticeTrooper::firstOrCreate(
                [
                    NoticeTrooper::TROOPER_ID => $trooper->id,
                    NoticeTrooper::NOTICE_ID => $notice->id,
                ],
                [
                    NoticeTrooper::IS_READ => true
                ]
            );
        });
    }
}
