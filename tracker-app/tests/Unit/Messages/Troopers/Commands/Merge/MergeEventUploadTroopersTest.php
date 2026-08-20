<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands\Merge;

use App\Messages\Troopers\Commands\Merge\MergeEventUploadTroopers;
use App\Models\EventUpload;
use App\Models\EventUploadTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MergeEventUploadTroopersTest extends TestCase
{
    use RefreshDatabase;

    public function test_call_transfers_active_and_trashed_rows_to_target_trooper(): void
    {
        $target_trooper = Trooper::factory()->asActive()->create();
        $source_trooper = Trooper::factory()->asActive()->create();

        $active_event_upload = EventUpload::factory()->create();
        $trashed_event_upload = EventUpload::factory()->create();

        $active_row = EventUploadTrooper::factory()
            ->forEventUpload($active_event_upload)
            ->forTrooper($source_trooper)
            ->create();

        $trashed_row = EventUploadTrooper::factory()
            ->forEventUpload($trashed_event_upload)
            ->forTrooper($source_trooper)
            ->create();
        $trashed_row->delete();

        MergeEventUploadTroopers::call([
            'target_trooper' => $target_trooper,
            'source_trooper' => $source_trooper,
        ]);

        $this->assertDatabaseHas('tt_event_upload_troopers', [
            EventUploadTrooper::ID => $active_row->id,
            EventUploadTrooper::EVENT_UPLOAD_ID => $active_event_upload->id,
            EventUploadTrooper::TROOPER_ID => $target_trooper->id,
            EventUploadTrooper::DELETED_AT => null,
        ]);

        $this->assertSoftDeleted('tt_event_upload_troopers', [
            EventUploadTrooper::ID => $trashed_row->id,
            EventUploadTrooper::TROOPER_ID => $target_trooper->id,
        ]);

        $this->assertDatabaseMissing('tt_event_upload_troopers', [
            EventUploadTrooper::ID => $active_row->id,
            EventUploadTrooper::TROOPER_ID => $source_trooper->id,
        ]);

        $this->assertDatabaseMissing('tt_event_upload_troopers', [
            EventUploadTrooper::ID => $trashed_row->id,
            EventUploadTrooper::TROOPER_ID => $source_trooper->id,
        ]);
    }

    public function test_call_restores_target_row_when_source_has_matching_event_upload(): void
    {
        $target_trooper = Trooper::factory()->asActive()->create();
        $source_trooper = Trooper::factory()->asActive()->create();

        $event_upload = EventUpload::factory()->create();

        $target_row = EventUploadTrooper::factory()
            ->forEventUpload($event_upload)
            ->forTrooper($target_trooper)
            ->create();
        $target_row->delete();

        $source_row = EventUploadTrooper::factory()
            ->forEventUpload($event_upload)
            ->forTrooper($source_trooper)
            ->create();

        MergeEventUploadTroopers::call([
            'target_trooper' => $target_trooper,
            'source_trooper' => $source_trooper,
        ]);

        $this->assertDatabaseHas('tt_event_upload_troopers', [
            EventUploadTrooper::ID => $target_row->id,
            EventUploadTrooper::EVENT_UPLOAD_ID => $event_upload->id,
            EventUploadTrooper::TROOPER_ID => $target_trooper->id,
            EventUploadTrooper::DELETED_AT => null,
        ]);

        $this->assertDatabaseMissing('tt_event_upload_troopers', [
            EventUploadTrooper::ID => $source_row->id,
            EventUploadTrooper::EVENT_UPLOAD_ID => $event_upload->id,
            EventUploadTrooper::TROOPER_ID => $source_trooper->id,
        ]);

        $this->assertSame(
            1,
            EventUploadTrooper::query()
                ->where(EventUploadTrooper::EVENT_UPLOAD_ID, $event_upload->id)
                ->where(EventUploadTrooper::TROOPER_ID, $target_trooper->id)
                ->count(),
        );
    }
}
