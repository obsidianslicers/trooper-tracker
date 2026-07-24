<?php

declare(strict_types=1);

namespace Tests\Feature\Messages\Troopers\Commands\Merge;

use App\Messages\Troopers\Commands\Merge\MergeTrooperAssignments;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MergeTrooperAssignmentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_call_transfers_active_and_trashed_assignments_to_target_trooper(): void
    {
        $target_trooper = Trooper::factory()->asActive()->create();
        $source_trooper = Trooper::factory()->asActive()->create();

        $active_organization = Organization::factory()->asOrganization()->create();
        $trashed_organization = Organization::factory()->asOrganization()->create();

        $active_assignment = TrooperAssignment::factory()
            ->forTrooper($source_trooper)
            ->forOrganization($active_organization)
            ->asMember()
            ->withShouldNotify(true)
            ->create();

        $trashed_assignment = TrooperAssignment::factory()
            ->forTrooper($source_trooper)
            ->forOrganization($trashed_organization)
            ->asModerator()
            ->create();
        $trashed_assignment->delete();

        MergeTrooperAssignments::call([
            'target_trooper' => $target_trooper,
            'source_trooper' => $source_trooper,
        ]);

        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::ID => $active_assignment->id,
            TrooperAssignment::TROOPER_ID => $target_trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $active_organization->id,
            TrooperAssignment::DELETED_AT => null,
        ]);

        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::ID => $trashed_assignment->id,
            TrooperAssignment::TROOPER_ID => $target_trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $trashed_organization->id,
        ]);

        $this->assertSoftDeleted('tt_trooper_assignments', [
            TrooperAssignment::ID => $trashed_assignment->id,
            TrooperAssignment::TROOPER_ID => $target_trooper->id,
        ]);

        $this->assertDatabaseMissing('tt_trooper_assignments', [
            TrooperAssignment::ID => $active_assignment->id,
            TrooperAssignment::TROOPER_ID => $source_trooper->id,
        ]);

        $this->assertDatabaseMissing('tt_trooper_assignments', [
            TrooperAssignment::ID => $trashed_assignment->id,
            TrooperAssignment::TROOPER_ID => $source_trooper->id,
        ]);
    }

    public function test_call_restores_target_assignment_and_merges_flags_for_same_organization(): void
    {
        $target_trooper = Trooper::factory()->asActive()->create();
        $source_trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->asOrganization()->create();

        $target_assignment = TrooperAssignment::factory()
            ->forTrooper($target_trooper)
            ->forOrganization($organization)
            ->create();
        $target_assignment->delete();

        $source_assignment = TrooperAssignment::factory()
            ->forTrooper($source_trooper)
            ->forOrganization($organization)
            ->asMember()
            ->asModerator()
            ->withShouldNotify(true)
            ->create();

        MergeTrooperAssignments::call([
            'target_trooper' => $target_trooper,
            'source_trooper' => $source_trooper,
        ]);

        $this->assertDatabaseHas('tt_trooper_assignments', [
            TrooperAssignment::ID => $target_assignment->id,
            TrooperAssignment::TROOPER_ID => $target_trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::DELETED_AT => null,
            TrooperAssignment::SHOULD_NOTIFY => true,
            TrooperAssignment::IS_MEMBER => true,
            TrooperAssignment::IS_MODERATOR => true,
        ]);

        $this->assertDatabaseMissing('tt_trooper_assignments', [
            TrooperAssignment::ID => $source_assignment->id,
            TrooperAssignment::TROOPER_ID => $source_trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
        ]);
    }
}
