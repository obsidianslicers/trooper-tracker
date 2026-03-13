<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Commands;

use App\Features\Troopers\Commands\UpdateTrooperMembershipsCommand;
use App\Features\Troopers\Commands\UpdateTrooperMembershipsCommandHandler;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see UpdateTrooperMembershipsCommandHandler
 */
class UpdateTrooperMembershipsCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_creates_new_membership_assignment(): void
    {
        $trooper = Trooper::factory()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        $valid_data = [
            $org1->id => ['assignment' => $org2->id],
        ];

        $command = new UpdateTrooperMembershipsCommand(
            trooper: $trooper,
            valid_data: $valid_data
        );
        $handler = app(UpdateTrooperMembershipsCommandHandler::class);

        $handler($command);

        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $org2->id,
            TrooperAssignment::IS_MEMBER => true,
        ]);
    }

    public function test_invoke_updates_existing_membership_assignment(): void
    {
        $trooper = Trooper::factory()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($org2)
            ->create([TrooperAssignment::IS_MEMBER => false]);

        $valid_data = [
            $org1->id => ['assignment' => $org2->id],
        ];

        $command = new UpdateTrooperMembershipsCommand(
            trooper: $trooper,
            valid_data: $valid_data
        );
        $handler = app(UpdateTrooperMembershipsCommandHandler::class);

        $handler($command);

        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $org2->id,
            TrooperAssignment::IS_MEMBER => true,
        ]);
    }

    public function test_invoke_skips_null_assignments(): void
    {
        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();

        $valid_data = [
            $organization->id => ['assignment' => null],
        ];

        $command = new UpdateTrooperMembershipsCommand(
            trooper: $trooper,
            valid_data: $valid_data
        );
        $handler = app(UpdateTrooperMembershipsCommandHandler::class);

        $handler($command);

        $this->assertDatabaseMissing('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
        ]);
    }

    public function test_invoke_handles_multiple_assignments(): void
    {
        $trooper = Trooper::factory()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();
        $org3 = Organization::factory()->create();

        $valid_data = [
            $org1->id => ['assignment' => $org2->id],
            $org2->id => ['assignment' => $org3->id],
        ];

        $command = new UpdateTrooperMembershipsCommand(
            trooper: $trooper,
            valid_data: $valid_data
        );
        $handler = app(UpdateTrooperMembershipsCommandHandler::class);

        $handler($command);

        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $org2->id,
            TrooperAssignment::IS_MEMBER => true,
        ]);
        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $org3->id,
            TrooperAssignment::IS_MEMBER => true,
        ]);
    }
}
