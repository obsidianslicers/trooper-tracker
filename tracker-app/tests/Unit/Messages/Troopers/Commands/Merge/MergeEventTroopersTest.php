<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands\Merge;

use App\Enums\EventTrooperStatus;
use App\Messages\Troopers\Commands\Merge\MergeEventTroopers;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MergeEventTroopersTest extends TestCase
{
    use RefreshDatabase;

    public function test_call_transfers_active_and_trashed_event_troopers_to_target_trooper(): void
    {
        $target_trooper = Trooper::factory()->asActive()->create();
        $source_trooper = Trooper::factory()->asActive()->create();

        $active_shift = EventShift::factory()->create();
        $trashed_shift = EventShift::factory()->create();

        $active_event_trooper = EventTrooper::factory()
            ->forEventShift($active_shift)
            ->forTrooper($source_trooper)
            ->asGoing()
            ->create();

        $trashed_event_trooper = EventTrooper::factory()
            ->forEventShift($trashed_shift)
            ->forTrooper($source_trooper)
            ->asTentative()
            ->create();
        $trashed_event_trooper->delete();

        MergeEventTroopers::call([
            'target_trooper' => $target_trooper,
            'source_trooper' => $source_trooper,
        ]);

        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::ID => $active_event_trooper->id,
            EventTrooper::TROOPER_ID => $target_trooper->id,
            EventTrooper::ADDED_BY_TROOPER_ID => $target_trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING->value,
            EventTrooper::DELETED_AT => null,
        ]);

        $this->assertSoftDeleted('tt_event_troopers', [
            EventTrooper::ID => $trashed_event_trooper->id,
            EventTrooper::TROOPER_ID => $target_trooper->id,
        ]);

        $this->assertDatabaseMissing('tt_event_troopers', [
            EventTrooper::ID => $active_event_trooper->id,
            EventTrooper::TROOPER_ID => $source_trooper->id,
        ]);

        $this->assertDatabaseMissing('tt_event_troopers', [
            EventTrooper::ID => $trashed_event_trooper->id,
            EventTrooper::TROOPER_ID => $source_trooper->id,
        ]);
    }

    public function test_call_merges_colliding_rows_for_same_shift_and_restores_target_row(): void
    {
        $target_trooper = Trooper::factory()->asActive()->create();
        $source_trooper = Trooper::factory()->asActive()->create();

        $event_shift = EventShift::factory()->create();
        $organization = Organization::factory()->asOrganization()->create();
        $target_costume_org = Organization::factory()->asOrganization()->create();
        $target_backup_costume_org = Organization::factory()->asOrganization()->create();

        $target_event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($target_trooper)
            ->create([
                EventTrooper::ORGANIZATION_ID => null,
                EventTrooper::COSTUME_ORGANIZATION_IDS => [$target_costume_org->id],
                EventTrooper::BACKUP_COSTUME_ORGANIZATION_IDS => [$target_backup_costume_org->id],
                EventTrooper::STATUS => EventTrooperStatus::NONE,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::SIGNED_UP_AT => Carbon::parse('2026-07-21 10:00:00'),
            ]);
        $target_event_trooper->delete();

        $source_event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($source_trooper)
            ->create([
                EventTrooper::ORGANIZATION_ID => $organization->id,
                EventTrooper::COSTUME_ORGANIZATION_IDS => [$organization->id],
                EventTrooper::BACKUP_COSTUME_ORGANIZATION_IDS => [$organization->id],
                EventTrooper::STATUS => EventTrooperStatus::GOING,
                EventTrooper::IS_HANDLER => true,
                EventTrooper::SIGNED_UP_AT => Carbon::parse('2026-07-20 10:00:00'),
            ]);

        MergeEventTroopers::call([
            'target_trooper' => $target_trooper,
            'source_trooper' => $source_trooper,
        ]);

        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::ID => $target_event_trooper->id,
            EventTrooper::EVENT_SHIFT_ID => $event_shift->id,
            EventTrooper::TROOPER_ID => $target_trooper->id,
            EventTrooper::ORGANIZATION_ID => $organization->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING->value,
            EventTrooper::IS_HANDLER => true,
            EventTrooper::ADDED_BY_TROOPER_ID => $target_trooper->id,
            EventTrooper::SIGNED_UP_AT => '2026-07-20 10:00:00',
            EventTrooper::DELETED_AT => null,
        ]);

        $this->assertDatabaseMissing('tt_event_troopers', [
            EventTrooper::ID => $source_event_trooper->id,
            EventTrooper::TROOPER_ID => $source_trooper->id,
        ]);

        $fresh_target_event_trooper = $target_event_trooper->fresh();

        $this->assertSame(
            [$organization->id, $target_costume_org->id],
            $fresh_target_event_trooper?->costume_organization_ids,
        );
        $this->assertSame(
            [$organization->id, $target_backup_costume_org->id],
            $fresh_target_event_trooper?->backup_costume_organization_ids,
        );

        $this->assertSame(
            1,
            EventTrooper::query()
                ->where(EventTrooper::EVENT_SHIFT_ID, $event_shift->id)
                ->where(EventTrooper::TROOPER_ID, $target_trooper->id)
                ->count(),
        );
    }
}
