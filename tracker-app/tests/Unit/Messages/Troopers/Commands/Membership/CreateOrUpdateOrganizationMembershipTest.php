<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands\Membership;

use App\Enums\MembershipStatus;
use App\Messages\Troopers\Commands\Membership\CreateOrUpdateOrganizationMembership;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateOrUpdateOrganizationMembershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_creates_membership_and_assignment(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $primary_organization = Organization::factory()->create();
        $organization = Organization::factory()->create();

        $subject = new CreateOrUpdateOrganizationMembership(
            trooper_id: $trooper->{Trooper::ID},
            primary_organization_id: $primary_organization->{Organization::ID},
            organization_id: $organization->{Organization::ID},
            identifier: 'TK-12345',
        );

        $subject->handle();

        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->{Trooper::ID},
            TrooperAssignment::ORGANIZATION_ID => $organization->{Organization::ID},
            TrooperAssignment::IS_MEMBER => true,
        ]);

        $this->assertDatabaseHas('tt_trooper_organizations', [
            TrooperOrganization::TROOPER_ID => $trooper->{Trooper::ID},
            TrooperOrganization::ORGANIZATION_ID => $primary_organization->{Organization::ID},
            TrooperOrganization::IDENTIFIER => 'TK-12345',
            TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE->value,
        ]);
    }

    public function test_handle_updates_existing_membership_and_clears_empty_identifier(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $primary_organization = Organization::factory()->create();
        $organization = Organization::factory()->create();

        TrooperOrganization::factory()
            ->forTrooper($trooper)
            ->forOrganization($primary_organization)
            ->withIdentifier('OLD-ID')
            ->withMembershipStatus(MembershipStatus::PENDING)
            ->create();

        $subject = new CreateOrUpdateOrganizationMembership(
            trooper_id: $trooper->{Trooper::ID},
            primary_organization_id: $primary_organization->{Organization::ID},
            organization_id: $organization->{Organization::ID},
            identifier: '',
        );

        $subject->handle();

        $this->assertDatabaseHas('tt_trooper_organizations', [
            TrooperOrganization::TROOPER_ID => $trooper->{Trooper::ID},
            TrooperOrganization::ORGANIZATION_ID => $primary_organization->{Organization::ID},
            TrooperOrganization::IDENTIFIER => null,
            TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE->value,
        ]);
    }

    public function test_handle_restores_trashed_membership(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $primary_organization = Organization::factory()->create();
        $organization = Organization::factory()->create();

        $trooper_organization = TrooperOrganization::factory()
            ->forTrooper($trooper)
            ->forOrganization($primary_organization)
            ->withIdentifier('TK-12345')
            ->create();
        $trooper_organization->delete();

        $subject = new CreateOrUpdateOrganizationMembership(
            trooper_id: $trooper->{Trooper::ID},
            primary_organization_id: $primary_organization->{Organization::ID},
            organization_id: $organization->{Organization::ID},
            identifier: 'TK-54321',
        );

        $subject->handle();

        $this->assertDatabaseHas('tt_trooper_organizations', [
            TrooperOrganization::TROOPER_ID => $trooper->{Trooper::ID},
            TrooperOrganization::ORGANIZATION_ID => $primary_organization->{Organization::ID},
            TrooperOrganization::IDENTIFIER => 'TK-54321',
            TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE->value,
            TrooperOrganization::DELETED_AT => null,
        ]);

        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->{Trooper::ID},
            TrooperAssignment::ORGANIZATION_ID => $organization->{Organization::ID},
            TrooperAssignment::IS_MEMBER => true,
        ]);
    }
}
