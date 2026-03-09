<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Commands;

use App\Features\Troopers\Commands\AttachTrooperCostumeCommand;
use App\Features\Troopers\Commands\AttachTrooperCostumeCommandHandler;
use App\Models\Costume;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperCostume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see AttachTrooperCostumeCommandHandler
 */
class AttachTrooperCostumeCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_creates_new_trooper_costume_when_none_exists(): void
    {
        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($organization)->asMember()->create();
        $costume = Costume::factory()->create();
        $organization_costume = OrganizationCostume::factory()
            ->forOrganization($organization)
            ->forCostume($costume)
            ->create();

        $this->assertDatabaseCount('tt_trooper_costumes', 0);

        $command = new AttachTrooperCostumeCommand(
            trooper: $trooper,
            organization_ids: [$organization_costume->id]
        );
        $handler = app(AttachTrooperCostumeCommandHandler::class);

        $handler($command);

        $this->assertDatabaseCount('tt_trooper_costumes', 1);
        $this->assertDatabaseHas('tt_trooper_costumes', [
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $organization_costume->id,
            TrooperCostume::DELETED_AT => null,
        ]);
    }

    public function test_invoke_restores_soft_deleted_trooper_costume(): void
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

        $this->assertSoftDeleted('tt_trooper_costumes', [
            TrooperCostume::ID => $trooper_costume->id,
        ]);

        $command = new AttachTrooperCostumeCommand(
            trooper: $trooper,
            organization_ids: [$organization_costume->id]
        );
        $handler = app(AttachTrooperCostumeCommandHandler::class);

        $handler($command);

        $this->assertDatabaseCount('tt_trooper_costumes', 1);
        $this->assertDatabaseHas('tt_trooper_costumes', [
            TrooperCostume::ID => $trooper_costume->id,
            TrooperCostume::DELETED_AT => null,
        ]);
    }

    public function test_invoke_only_attaches_costumes_from_organizations_trooper_belongs_to(): void
    {
        $trooper = Trooper::factory()->create();
        $trooper_organization = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($trooper_organization)->asMember()->create();
        $other_organization = Organization::factory()->create();

        $costume = Costume::factory()->create();
        $trooper_org_costume = OrganizationCostume::factory()
            ->forOrganization($trooper_organization)
            ->forCostume($costume)
            ->create();
        $other_org_costume = OrganizationCostume::factory()
            ->forOrganization($other_organization)
            ->forCostume($costume)
            ->create();

        $command = new AttachTrooperCostumeCommand(
            trooper: $trooper,
            organization_ids: [$trooper_org_costume->id, $other_org_costume->id]
        );
        $handler = app(AttachTrooperCostumeCommandHandler::class);

        $handler($command);

        $this->assertDatabaseCount('tt_trooper_costumes', 1);
        $this->assertDatabaseHas('tt_trooper_costumes', [
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $trooper_org_costume->id,
        ]);
        $this->assertDatabaseMissing('tt_trooper_costumes', [
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $other_org_costume->id,
        ]);
    }

    public function test_invoke_handles_multiple_organization_costume_ids(): void
    {
        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($organization)->asMember()->create();

        $costume1 = Costume::factory()->create();
        $costume2 = Costume::factory()->create();
        $org_costume1 = OrganizationCostume::factory()
            ->forOrganization($organization)
            ->forCostume($costume1)
            ->create();
        $org_costume2 = OrganizationCostume::factory()
            ->forOrganization($organization)
            ->forCostume($costume2)
            ->create();

        $command = new AttachTrooperCostumeCommand(
            trooper: $trooper,
            organization_ids: [$org_costume1->id, $org_costume2->id]
        );
        $handler = app(AttachTrooperCostumeCommandHandler::class);

        $handler($command);

        $this->assertDatabaseCount('tt_trooper_costumes', 2);
        $this->assertDatabaseHas('tt_trooper_costumes', [
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $org_costume1->id,
        ]);
        $this->assertDatabaseHas('tt_trooper_costumes', [
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $org_costume2->id,
        ]);
    }
}
