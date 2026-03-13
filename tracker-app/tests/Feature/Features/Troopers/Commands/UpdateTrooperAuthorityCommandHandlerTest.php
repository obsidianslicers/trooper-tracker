<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Commands;

use App\Enums\MembershipRole;
use App\Features\Troopers\Commands\UpdateTrooperAuthorityCommand;
use App\Features\Troopers\Commands\UpdateTrooperAuthorityCommandHandler;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see UpdateTrooperAuthorityCommandHandler
 */
class UpdateTrooperAuthorityCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_updates_trooper_membership_role(): void
    {
        $trooper = Trooper::factory()->asMember()->create();

        $this->assertEquals(MembershipRole::MEMBER, $trooper->membership_role);

        $command = new UpdateTrooperAuthorityCommand(
            trooper: $trooper,
            membership_role: MembershipRole::MODERATOR,
            valid_data: []
        );
        $handler = app(UpdateTrooperAuthorityCommandHandler::class);

        $handler($command);

        $trooper->refresh();
        $this->assertEquals(MembershipRole::MODERATOR, $trooper->membership_role);
    }

    public function test_invoke_resets_all_moderator_flags_to_false(): void
    {
        $trooper = Trooper::factory()->asModerator()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($org1)
            ->asModerator()
            ->create();
        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($org2)
            ->asModerator()
            ->create();

        $command = new UpdateTrooperAuthorityCommand(
            trooper: $trooper,
            membership_role: MembershipRole::MODERATOR,
            valid_data: []
        );
        $handler = app(UpdateTrooperAuthorityCommandHandler::class);

        $handler($command);

        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $org1->id,
            TrooperAssignment::IS_MODERATOR => false,
        ]);
        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $org2->id,
            TrooperAssignment::IS_MODERATOR => false,
        ]);
    }

    public function test_invoke_sets_moderator_flags_for_specified_organizations(): void
    {
        $trooper = Trooper::factory()->asModerator()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();
        $org3 = Organization::factory()->create();

        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($org1)
            ->asMember()
            ->create();
        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($org2)
            ->asMember()
            ->create();

        $valid_data = [
            $org1->id => ['is_moderator' => true],
            $org2->id => ['is_moderator' => false],
            $org3->id => ['is_moderator' => true],
        ];

        $command = new UpdateTrooperAuthorityCommand(
            trooper: $trooper,
            membership_role: MembershipRole::MODERATOR,
            valid_data: $valid_data
        );
        $handler = app(UpdateTrooperAuthorityCommandHandler::class);

        $handler($command);

        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $org1->id,
            TrooperAssignment::IS_MODERATOR => true,
        ]);
        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $org2->id,
            TrooperAssignment::IS_MODERATOR => false,
        ]);
        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $org3->id,
            TrooperAssignment::IS_MODERATOR => true,
        ]);
    }

    public function test_invoke_skips_moderator_assignment_when_trooper_is_not_moderator(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $org = Organization::factory()->create();

        $valid_data = [
            $org->id => ['is_moderator' => true],
        ];

        $command = new UpdateTrooperAuthorityCommand(
            trooper: $trooper,
            membership_role: MembershipRole::MEMBER,
            valid_data: $valid_data
        );
        $handler = app(UpdateTrooperAuthorityCommandHandler::class);

        $handler($command);

        $trooper->refresh();
        $this->assertEquals(MembershipRole::MEMBER, $trooper->membership_role);
        $this->assertDatabaseMissing('tt_trooper_assignments', [
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $org->id,
            TrooperAssignment::IS_MODERATOR => true,
        ]);
    }
}
