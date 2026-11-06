<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands;

use App\Enums\MembershipStatus;
use App\Messages\Troopers\Commands\UpdateTrooperMemberships;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTrooperMembershipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_ensures_pending_record_exists_for_assignment(): void
    {
        $trooper = Trooper::factory()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        // In production, UpdateTrooperIdentifiers runs first and creates
        // the TrooperOrganization record with an identifier. The memberships handler
        // then finds that record via firstOrCreate.
        TrooperOrganization::factory()
            ->forTrooper($trooper)
            ->forOrganization($org2)
            ->create([TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::PENDING]);

        (new UpdateTrooperMemberships(trooper: $trooper, valid_data: [
            $org1->id => ['assignment' => $org2->id],
        ]))->handle();

        $this->assertDatabaseHas('tt_trooper_organizations', [
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $org2->id,
            TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::PENDING,
        ]);
    }

    public function test_handle_skips_null_assignments(): void
    {
        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();

        (new UpdateTrooperMemberships(trooper: $trooper, valid_data: [
            $organization->id => ['assignment' => null],
        ]))->handle();

        $this->assertDatabaseMissing('tt_trooper_organizations', [
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $organization->id,
        ]);
    }

    public function test_handle_handles_multiple_assignments(): void
    {
        $trooper = Trooper::factory()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();
        $org3 = Organization::factory()->create();

        TrooperOrganization::factory()
            ->forTrooper($trooper)
            ->forOrganization($org2)
            ->create([TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::PENDING]);
        TrooperOrganization::factory()
            ->forTrooper($trooper)
            ->forOrganization($org3)
            ->create([TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::PENDING]);

        (new UpdateTrooperMemberships(trooper: $trooper, valid_data: [
            $org1->id => ['assignment' => $org2->id],
            $org2->id => ['assignment' => $org3->id],
        ]))->handle();

        $this->assertDatabaseHas('tt_trooper_organizations', [
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $org2->id,
            TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::PENDING,
        ]);
        $this->assertDatabaseHas('tt_trooper_organizations', [
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $org3->id,
            TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::PENDING,
        ]);
    }

    public function test_handle_skips_unknown_parent_organizations(): void
    {
        $trooper = Trooper::factory()->create();
        $unit = Organization::factory()->create();

        (new UpdateTrooperMemberships(trooper: $trooper, valid_data: [
            999999 => ['assignment' => $unit->id],
        ]))->handle();

        $this->assertDatabaseMissing('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->id,
        ]);
    }

    public function test_handle_restores_trashed_assignment_and_drops_conflicting_member_assignment(): void
    {
        $trooper = Trooper::factory()->create();
        $club = Organization::factory()->asOrganization()->create();
        $old_unit = Organization::factory()->asRegion()->withParent($club)->create();
        $new_unit = Organization::factory()->asRegion()->withParent($club)->create();

        $current = TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($old_unit)
            ->asMember()->create();
        $previous = TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($new_unit)
            ->create([TrooperAssignment::IS_MEMBER => false]);
        $previous->delete();

        (new UpdateTrooperMemberships(trooper: $trooper, valid_data: [
            $club->id => ['assignment' => $new_unit->id],
        ]))->handle();

        $this->assertSoftDeleted($current);
        $previous = TrooperAssignment::find($previous->id);
        $this->assertNotNull($previous);
        $this->assertTrue($previous->is_member);
    }
}
