<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use App\Models\Costume;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperCostume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackfillHandlerCostumesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_organization_costume_records_for_all_orgs(): void
    {
        $org_a = Organization::factory()->asOrganization()->create();
        $org_b = Organization::factory()->asOrganization()->create();
        // Costumes don't exist yet, so OrganizationObserver created nothing

        $handler = Costume::factory()->withName(Costume::HANDLER)->create();
        $command_staff = Costume::factory()->withName(Costume::COMMAND_STAFF)->create();

        $this->artisan('tracker:backfill-handler-costumes')->assertSuccessful();

        foreach ([$org_a, $org_b] as $org)
        {
            $this->assertDatabaseHas('tt_organization_costumes', [
                OrganizationCostume::ORGANIZATION_ID => $org->id,
                OrganizationCostume::COSTUME_ID => $handler->id,
            ]);
            $this->assertDatabaseHas('tt_organization_costumes', [
                OrganizationCostume::ORGANIZATION_ID => $org->id,
                OrganizationCostume::COSTUME_ID => $command_staff->id,
            ]);
        }
    }

    public function test_creates_trooper_costume_records_for_member_troopers(): void
    {
        $org = Organization::factory()->asOrganization()->create();

        $member = Trooper::factory()->create();
        $non_member = Trooper::factory()->create();

        TrooperAssignment::factory()->forTrooper($member)->forOrganization($org)->asMember()->create();
        TrooperAssignment::factory()->forTrooper($non_member)->forOrganization($org)->create();

        $handler = Costume::factory()->withName(Costume::HANDLER)->create();
        $command_staff = Costume::factory()->withName(Costume::COMMAND_STAFF)->create();

        $this->artisan('tracker:backfill-handler-costumes')->assertSuccessful();

        $handler_org_costume = OrganizationCostume::where(OrganizationCostume::COSTUME_ID, $handler->id)
            ->where(OrganizationCostume::ORGANIZATION_ID, $org->id)
            ->first();

        $command_staff_org_costume = OrganizationCostume::where(OrganizationCostume::COSTUME_ID, $command_staff->id)
            ->where(OrganizationCostume::ORGANIZATION_ID, $org->id)
            ->first();

        // Member gets TrooperCostume records
        $this->assertDatabaseHas('tt_trooper_costumes', [
            TrooperCostume::TROOPER_ID => $member->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $handler_org_costume->id,
        ]);
        $this->assertDatabaseHas('tt_trooper_costumes', [
            TrooperCostume::TROOPER_ID => $member->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $command_staff_org_costume->id,
        ]);

        // Non-member gets nothing
        $this->assertDatabaseMissing('tt_trooper_costumes', [
            TrooperCostume::TROOPER_ID => $non_member->id,
        ]);
    }

    public function test_is_idempotent(): void
    {
        $org = Organization::factory()->asOrganization()->create();
        $trooper = Trooper::factory()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org)->asMember()->create();

        Costume::factory()->withName(Costume::HANDLER)->create();
        Costume::factory()->withName(Costume::COMMAND_STAFF)->create();

        $this->artisan('tracker:backfill-handler-costumes')->assertSuccessful();
        $this->artisan('tracker:backfill-handler-costumes')->assertSuccessful();

        $this->assertSame(2, OrganizationCostume::where(OrganizationCostume::ORGANIZATION_ID, $org->id)->count());
        $this->assertSame(2, TrooperCostume::where(TrooperCostume::TROOPER_ID, $trooper->id)->count());
    }
}
