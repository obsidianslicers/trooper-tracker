<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Commands;

use App\Enums\MembershipStatus;
use App\Features\Troopers\Commands\UpdateTrooperMembershipsCommand;
use App\Features\Troopers\Commands\UpdateTrooperMembershipsCommandHandler;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see UpdateTrooperMembershipsCommandHandler
 */
class UpdateTrooperMembershipsCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_ensures_pending_record_exists_for_assignment(): void
    {
        $trooper = Trooper::factory()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        // In production, UpdateTrooperIdentifiersCommandHandler runs first and creates
        // the TrooperOrganization record with an identifier. The memberships handler
        // then finds that record via firstOrCreate.
        TrooperOrganization::factory()
            ->forTrooper($trooper)
            ->forOrganization($org2)
            ->create([TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::PENDING]);

        $handler = app(UpdateTrooperMembershipsCommandHandler::class);
        $handler(new UpdateTrooperMembershipsCommand(trooper: $trooper, valid_data: [
            $org1->id => ['assignment' => $org2->id],
        ]));

        $this->assertDatabaseHas('tt_trooper_organizations', [
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $org2->id,
            TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::PENDING,
        ]);
    }

    public function test_invoke_skips_null_assignments(): void
    {
        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();

        $handler = app(UpdateTrooperMembershipsCommandHandler::class);
        $handler(new UpdateTrooperMembershipsCommand(trooper: $trooper, valid_data: [
            $organization->id => ['assignment' => null],
        ]));

        $this->assertDatabaseMissing('tt_trooper_organizations', [
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $organization->id,
        ]);
    }

    public function test_invoke_handles_multiple_assignments(): void
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

        $handler = app(UpdateTrooperMembershipsCommandHandler::class);
        $handler(new UpdateTrooperMembershipsCommand(trooper: $trooper, valid_data: [
            $org1->id => ['assignment' => $org2->id],
            $org2->id => ['assignment' => $org3->id],
        ]));

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
}
