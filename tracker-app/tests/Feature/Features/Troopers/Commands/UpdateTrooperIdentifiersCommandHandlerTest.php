<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Commands;

use App\Features\Troopers\Commands\UpdateTrooperIdentifiersCommand;
use App\Features\Troopers\Commands\UpdateTrooperIdentifiersCommandHandler;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see UpdateTrooperIdentifiersCommandHandler
 */
class UpdateTrooperIdentifiersCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_updates_existing_identifier(): void
    {
        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();
        TrooperOrganization::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->withIdentifier('TK-OLD')
            ->create();

        $valid_data = [
            $organization->id => ['identifier' => 'TK-NEW'],
        ];

        $command = new UpdateTrooperIdentifiersCommand(
            trooper: $trooper,
            valid_data: $valid_data
        );
        $handler = app(UpdateTrooperIdentifiersCommandHandler::class);

        $handler($command);

        $this->assertDatabaseHas('tt_trooper_organizations', [
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $organization->id,
            TrooperOrganization::IDENTIFIER => 'TK-NEW',
        ]);
    }

    public function test_invoke_creates_new_identifier_when_no_relationship_exists(): void
    {
        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();

        $valid_data = [
            $organization->id => ['identifier' => 'TK-123'],
        ];

        $command = new UpdateTrooperIdentifiersCommand(
            trooper: $trooper,
            valid_data: $valid_data
        );
        $handler = app(UpdateTrooperIdentifiersCommandHandler::class);

        $handler($command);

        $this->assertDatabaseHas('tt_trooper_organizations', [
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $organization->id,
            TrooperOrganization::IDENTIFIER => 'TK-123',
        ]);
    }

    public function test_invoke_trims_whitespace_from_identifier(): void
    {
        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();

        $valid_data = [
            $organization->id => ['identifier' => '  TK-777  '],
        ];

        $command = new UpdateTrooperIdentifiersCommand(
            trooper: $trooper,
            valid_data: $valid_data
        );
        $handler = app(UpdateTrooperIdentifiersCommandHandler::class);

        $handler($command);

        $this->assertDatabaseHas('tt_trooper_organizations', [
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $organization->id,
            TrooperOrganization::IDENTIFIER => 'TK-777',
        ]);
    }

    public function test_invoke_skips_null_identifiers(): void
    {
        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();

        $valid_data = [
            $organization->id => ['identifier' => null],
        ];

        $command = new UpdateTrooperIdentifiersCommand(
            trooper: $trooper,
            valid_data: $valid_data
        );
        $handler = app(UpdateTrooperIdentifiersCommandHandler::class);

        $handler($command);

        $this->assertDatabaseMissing('tt_trooper_organizations', [
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $organization->id,
        ]);
    }

    public function test_invoke_handles_multiple_organizations(): void
    {
        $trooper = Trooper::factory()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        TrooperOrganization::factory()
            ->forTrooper($trooper)
            ->forOrganization($org1)
            ->create();

        $valid_data = [
            $org1->id => ['identifier' => 'TK-111'],
            $org2->id => ['identifier' => 'TK-222'],
        ];

        $command = new UpdateTrooperIdentifiersCommand(
            trooper: $trooper,
            valid_data: $valid_data
        );
        $handler = app(UpdateTrooperIdentifiersCommandHandler::class);

        $handler($command);

        $this->assertDatabaseHas('tt_trooper_organizations', [
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $org1->id,
            TrooperOrganization::IDENTIFIER => 'TK-111',
        ]);
        $this->assertDatabaseHas('tt_trooper_organizations', [
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $org2->id,
            TrooperOrganization::IDENTIFIER => 'TK-222',
        ]);
    }
}
