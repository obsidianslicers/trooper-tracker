<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Queries\TrooperMembership;

use App\Enums\MembershipStatus;
use App\Messages\Troopers\Queries\TrooperMembership\GetTrooperOrganizations;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetTrooperOrganizationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_returns_only_active_organization_memberships_for_trooper(): void
    {
        $trooper = Trooper::factory()->create();
        $active_org_1 = Organization::factory()->create();
        $active_org_2 = Organization::factory()->create();
        $inactive_org = Organization::factory()->create();
        $other_trooper = Trooper::factory()->create();

        TrooperOrganization::factory()
            ->forTrooper($trooper)
            ->forOrganization($active_org_1)
            ->create();

        TrooperOrganization::factory()
            ->forTrooper($trooper)
            ->forOrganization($active_org_2)
            ->create();

        TrooperOrganization::factory()
            ->forTrooper($trooper)
            ->forOrganization($inactive_org)
            ->create([
                TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::PENDING,
            ]);

        TrooperOrganization::factory()
            ->forTrooper($other_trooper)
            ->forOrganization($active_org_1)
            ->create();

        $subject = new GetTrooperOrganizations($trooper);

        $result = $subject->handle();

        $this->assertCount(2, $result);
        $this->assertEqualsCanonicalizing(
            [$active_org_1->{Organization::ID}, $active_org_2->{Organization::ID}],
            $result->pluck(TrooperOrganization::ORGANIZATION_ID)->all(),
        );
        $this->assertFalse(
            $result->contains(fn(TrooperOrganization $membership): bool => $membership->{TrooperOrganization::ORGANIZATION_ID} === $inactive_org->{Organization::ID}),
        );
    }
}
