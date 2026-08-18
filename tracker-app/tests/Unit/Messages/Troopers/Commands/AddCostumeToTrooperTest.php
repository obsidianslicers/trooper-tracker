<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands;

use App\Messages\Troopers\Commands\AddCostumeToTrooper;
use App\Models\Costume;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperCostume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddCostumeToTrooperTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_creates_missing_trooper_costume_for_active_organization_membership(): void
    {
        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($organization)->asMember()->create();

        $costume = Costume::factory()->create();
        $organization_costume = OrganizationCostume::factory()
            ->forOrganization($organization)
            ->forCostume($costume)
            ->create();

        $subject = new AddCostumeToTrooper($trooper, $costume->id, [$organization->id]);

        $subject->handle();

        $this->assertDatabaseCount('tt_trooper_costumes', 1);
        $this->assertDatabaseHas('tt_trooper_costumes', [
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $organization_costume->id,
            TrooperCostume::DELETED_AT => null,
        ]);
    }

    public function test_handle_restores_soft_deleted_trooper_costume_for_matching_costume(): void
    {
        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($organization)->asMember()->create();

        $costume = Costume::factory()->create();
        $organization_costume = OrganizationCostume::factory()
            ->forOrganization($organization)
            ->forCostume($costume)
            ->create();

        $trooper_costume = TrooperCostume::factory()
            ->forTrooper($trooper)
            ->forOrganizationCostume($organization_costume)
            ->create();
        $trooper_costume->delete();

        $subject = new AddCostumeToTrooper($trooper, $costume->id, [$organization->id]);

        $subject->handle();

        $this->assertDatabaseCount('tt_trooper_costumes', 1);
        $this->assertDatabaseHas('tt_trooper_costumes', [
            TrooperCostume::ID => $trooper_costume->id,
            TrooperCostume::DELETED_AT => null,
        ]);
    }
}
