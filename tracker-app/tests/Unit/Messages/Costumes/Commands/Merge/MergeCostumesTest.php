<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Costumes\Commands\Merge;

use App\Messages\Costumes\Commands\Merge\MergeCostumes;
use App\Models\Costume;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class MergeCostumesTest extends TestCase
{
    use RefreshDatabase;

    public function test_call_moves_every_reference_and_deletes_source(): void
    {
        $organization = Organization::factory()->asOrganization()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $target = Costume::factory()->create();
        $source = Costume::factory()->create();

        $source_organization_costume = OrganizationCostume::factory()
            ->forOrganization($organization)
            ->forCostume($source)
            ->create();
        $trooper_costume = TrooperCostume::factory()
            ->forTrooper($trooper)
            ->forOrganizationCostume($source_organization_costume)
            ->create();
        $event_trooper = EventTrooper::factory()->forTrooper($trooper)->withCostume($source)->create();

        MergeCostumes::call(target_costume: $target, source_costume: $source);

        $this->assertDatabaseMissing('tt_costumes', [Costume::ID => $source->id]);
        $this->assertDatabaseHas('tt_organization_costumes', [
            OrganizationCostume::ID => $source_organization_costume->id,
            OrganizationCostume::COSTUME_ID => $target->id,
        ]);
        $this->assertDatabaseHas('tt_trooper_costumes', [
            TrooperCostume::ID => $trooper_costume->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $source_organization_costume->id,
            TrooperCostume::DELETED_AT => null,
        ]);
        $this->assertSame($target->id, $event_trooper->fresh()->costume_id);
    }

    public function test_call_merges_a_soft_deleted_source(): void
    {
        $target = Costume::factory()->create();
        $source = Costume::factory()->create();
        $source->delete();

        MergeCostumes::call(target_costume: $target, source_costume: $source);

        $this->assertDatabaseMissing('tt_costumes', [Costume::ID => $source->id]);
        $this->assertDatabaseHas('tt_costumes', [Costume::ID => $target->id]);
    }

    public function test_call_refuses_to_merge_a_costume_into_itself(): void
    {
        $costume = Costume::factory()->create();

        $this->expectException(InvalidArgumentException::class);

        MergeCostumes::call(target_costume: $costume, source_costume: $costume);
    }
}
