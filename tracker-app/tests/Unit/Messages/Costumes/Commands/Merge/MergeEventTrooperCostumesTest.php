<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Costumes\Commands\Merge;

use App\Messages\Costumes\Commands\Merge\MergeEventTrooperCostumes;
use App\Models\Costume;
use App\Models\EventTrooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MergeEventTrooperCostumesTest extends TestCase
{
    use RefreshDatabase;

    public function test_call_repoints_primary_and_backup_costumes(): void
    {
        $target = Costume::factory()->create();
        $source = Costume::factory()->create();
        $other = Costume::factory()->create();

        $primary = EventTrooper::factory()->withCostume($source)->withBackupCostume($other)->create();
        $backup = EventTrooper::factory()->withCostume($other)->withBackupCostume($source)->create();
        $trashed = EventTrooper::factory()->withCostume($source)->create();
        $trashed->delete();

        MergeEventTrooperCostumes::call(target_costume: $target, source_costume: $source);

        $this->assertSame($target->id, $primary->fresh()->costume_id);
        $this->assertSame($other->id, $primary->fresh()->backup_costume_id);
        $this->assertSame($other->id, $backup->fresh()->costume_id);
        $this->assertSame($target->id, $backup->fresh()->backup_costume_id);
        $this->assertSame($target->id, EventTrooper::withTrashed()->find($trashed->id)->costume_id);
    }

    public function test_call_clears_a_backup_that_would_match_the_primary(): void
    {
        $target = Costume::factory()->create();
        $source = Costume::factory()->create();

        $event_trooper = EventTrooper::factory()
            ->withCostume($target)
            ->withBackupCostume($source)
            ->withBackupCostumeOrganizationIds([1])
            ->create();

        MergeEventTrooperCostumes::call(target_costume: $target, source_costume: $source);

        $event_trooper->refresh();
        $this->assertSame($target->id, $event_trooper->costume_id);
        $this->assertNull($event_trooper->backup_costume_id);
        $this->assertNull($event_trooper->backup_costume_organization_ids);
    }

    public function test_call_leaves_unrelated_sign_ups_alone(): void
    {
        $target = Costume::factory()->create();
        $source = Costume::factory()->create();

        $event_trooper = EventTrooper::factory()->withCostume($target)->withBackupCostume($target)->create();

        MergeEventTrooperCostumes::call(target_costume: $target, source_costume: $source);

        $this->assertSame($target->id, $event_trooper->fresh()->backup_costume_id);
    }
}
