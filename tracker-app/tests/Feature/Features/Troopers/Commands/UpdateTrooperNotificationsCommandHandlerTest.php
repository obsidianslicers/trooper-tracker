<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Commands;

use App\Features\Troopers\Commands\UpdateTrooperNotificationsCommand;
use App\Features\Troopers\Commands\UpdateTrooperNotificationsCommandHandler;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see UpdateTrooperNotificationsCommandHandler
 */
class UpdateTrooperNotificationsCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_resets_all_notification_preferences_to_false(): void
    {
        $trooper = Trooper::factory()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($org1)
            ->withShouldNotify()
            ->create();
        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($org2)
            ->withShouldNotify()
            ->create();

        $command = new UpdateTrooperNotificationsCommand(
            trooper: $trooper,
            valid_data: []
        );
        $handler = app(UpdateTrooperNotificationsCommandHandler::class);

        $handler($command);

        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $org1->id,
            TrooperAssignment::SHOULD_NOTIFY => false,
        ]);
        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $org2->id,
            TrooperAssignment::SHOULD_NOTIFY => false,
        ]);
    }

    public function test_invoke_creates_new_notification_preference(): void
    {
        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();

        $valid_data = [
            $organization->id => ['should_notify' => true],
        ];

        $command = new UpdateTrooperNotificationsCommand(
            trooper: $trooper,
            valid_data: $valid_data
        );
        $handler = app(UpdateTrooperNotificationsCommandHandler::class);

        $handler($command);

        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::SHOULD_NOTIFY => true,
        ]);
    }

    public function test_invoke_updates_existing_notification_preference(): void
    {
        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();

        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->create([TrooperAssignment::SHOULD_NOTIFY => false]);

        $valid_data = [
            $organization->id => ['should_notify' => true],
        ];

        $command = new UpdateTrooperNotificationsCommand(
            trooper: $trooper,
            valid_data: $valid_data
        );
        $handler = app(UpdateTrooperNotificationsCommandHandler::class);

        $handler($command);

        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::SHOULD_NOTIFY => true,
        ]);
    }

    public function test_invoke_handles_multiple_organizations(): void
    {
        $trooper = Trooper::factory()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();
        $org3 = Organization::factory()->create();

        $valid_data = [
            $org1->id => ['should_notify' => true],
            $org2->id => ['should_notify' => false],
            $org3->id => ['should_notify' => true],
        ];

        $command = new UpdateTrooperNotificationsCommand(
            trooper: $trooper,
            valid_data: $valid_data
        );
        $handler = app(UpdateTrooperNotificationsCommandHandler::class);

        $handler($command);

        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $org1->id,
            TrooperAssignment::SHOULD_NOTIFY => true,
        ]);
        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $org2->id,
            TrooperAssignment::SHOULD_NOTIFY => false,
        ]);
        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $org3->id,
            TrooperAssignment::SHOULD_NOTIFY => true,
        ]);
    }
}
