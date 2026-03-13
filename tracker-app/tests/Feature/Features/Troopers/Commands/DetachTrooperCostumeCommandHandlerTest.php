<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Commands;

use App\Features\Troopers\Commands\DetachTrooperCostumeCommand;
use App\Features\Troopers\Commands\DetachTrooperCostumeCommandHandler;
use App\Models\Costume;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperCostume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see DetachTrooperCostumeCommandHandler
 */
class DetachTrooperCostumeCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_soft_deletes_trooper_costume_when_found(): void
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

        $this->assertDatabaseHas('tt_trooper_costumes', [
            TrooperCostume::ID => $trooper_costume->id,
            TrooperCostume::DELETED_AT => null,
        ]);

        $command = new DetachTrooperCostumeCommand(
            trooper: $trooper,
            costume_id: $costume->id
        );
        $handler = app(DetachTrooperCostumeCommandHandler::class);

        $handler($command);

        $this->assertSoftDeleted('tt_trooper_costumes', [
            TrooperCostume::ID => $trooper_costume->id,
        ]);
    }

    public function test_invoke_succeeds_when_no_matching_costume(): void
    {
        $trooper = Trooper::factory()->create();
        $non_existent_costume_id = 999;

        $this->assertDatabaseCount('tt_trooper_costumes', 0);

        $command = new DetachTrooperCostumeCommand(
            trooper: $trooper,
            costume_id: $non_existent_costume_id
        );
        $handler = app(DetachTrooperCostumeCommandHandler::class);

        $handler($command);

        $this->assertDatabaseCount('tt_trooper_costumes', 0);
    }

    public function test_invoke_handles_multiple_trooper_costumes_for_same_costume(): void
    {
        $trooper = Trooper::factory()->create();
        $organization1 = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($organization1)->asMember()->create();
        $organization2 = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($organization2)->asMember()->create();

        $costume = Costume::factory()->create();

        $org_costume1 = OrganizationCostume::factory()
            ->forOrganization($organization1)
            ->forCostume($costume)
            ->create();
        $org_costume2 = OrganizationCostume::factory()
            ->forOrganization($organization2)
            ->forCostume($costume)
            ->create();

        $trooper_costume1 = TrooperCostume::factory()
            ->forTrooper($trooper)
            ->forOrganizationCostume($org_costume1)
            ->create();
        $trooper_costume2 = TrooperCostume::factory()
            ->forTrooper($trooper)
            ->forOrganizationCostume($org_costume2)
            ->create();

        $this->assertDatabaseCount('tt_trooper_costumes', 2);

        $command = new DetachTrooperCostumeCommand(
            trooper: $trooper,
            costume_id: $costume->id
        );
        $handler = app(DetachTrooperCostumeCommandHandler::class);

        $handler($command);

        $this->assertSoftDeleted('tt_trooper_costumes', [
            TrooperCostume::ID => $trooper_costume1->id,
        ]);
        $this->assertSoftDeleted('tt_trooper_costumes', [
            TrooperCostume::ID => $trooper_costume2->id,
        ]);
    }
}
