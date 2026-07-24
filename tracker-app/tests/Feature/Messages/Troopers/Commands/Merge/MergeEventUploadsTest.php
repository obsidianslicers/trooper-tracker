<?php

declare(strict_types=1);

namespace Tests\Feature\Messages\Troopers\Commands\Merge;

use App\Messages\Troopers\Commands\Merge\MergeEventUploads;
use App\Models\EventUpload;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MergeEventUploadsTest extends TestCase
{
    use RefreshDatabase;

    public function test_call_transfers_active_and_trashed_uploads_to_target_trooper(): void
    {
        $target_trooper = Trooper::factory()->asActive()->create();
        $source_trooper = Trooper::factory()->asActive()->create();

        $active_upload = EventUpload::factory()->create([
            EventUpload::TROOPER_ID => $source_trooper->id,
        ]);

        $trashed_upload = EventUpload::factory()->create([
            EventUpload::TROOPER_ID => $source_trooper->id,
        ]);
        $trashed_upload->delete();

        MergeEventUploads::call([
            'target_trooper' => $target_trooper,
            'source_trooper' => $source_trooper,
        ]);

        $this->assertDatabaseHas('tt_event_uploads', [
            EventUpload::ID => $active_upload->id,
            EventUpload::TROOPER_ID => $target_trooper->id,
            EventUpload::DELETED_AT => null,
        ]);

        $this->assertSoftDeleted('tt_event_uploads', [
            EventUpload::ID => $trashed_upload->id,
            EventUpload::TROOPER_ID => $target_trooper->id,
        ]);

        $this->assertDatabaseMissing('tt_event_uploads', [
            EventUpload::ID => $active_upload->id,
            EventUpload::TROOPER_ID => $source_trooper->id,
        ]);

        $this->assertDatabaseMissing('tt_event_uploads', [
            EventUpload::ID => $trashed_upload->id,
            EventUpload::TROOPER_ID => $source_trooper->id,
        ]);
    }

    public function test_call_rewrites_trooper_stamps_that_reference_source_trooper(): void
    {
        $target_trooper = Trooper::factory()->asActive()->create();
        $source_trooper = Trooper::factory()->asActive()->create();
        $other_trooper = Trooper::factory()->asActive()->create();

        $upload = EventUpload::factory()->create([
            EventUpload::TROOPER_ID => $source_trooper->id,
        ]);

        $upload->created_id = $source_trooper->id;
        $upload->updated_id = $other_trooper->id;
        $upload->deleted_id = $source_trooper->id;
        $upload->save();

        MergeEventUploads::call([
            'target_trooper' => $target_trooper,
            'source_trooper' => $source_trooper,
        ]);

        $this->assertDatabaseHas('tt_event_uploads', [
            EventUpload::ID => $upload->id,
            EventUpload::TROOPER_ID => $target_trooper->id,
        ]);
    }
}