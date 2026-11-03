<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\Commands;

use App\Messages\Events\Commands\DeleteEventUpload;
use App\Models\EventUpload;
use App\Models\EventUploadTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DeleteEventUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_deletes_the_upload(): void
    {
        $upload = EventUpload::factory()->create();

        (new DeleteEventUpload($upload))->handle();

        $this->assertDatabaseMissing('tt_event_uploads', [EventUpload::ID => $upload->id]);
    }

    public function test_handle_deletes_pivot_rows_for_tagged_troopers(): void
    {
        $upload = EventUpload::factory()->create();
        $trooper = Trooper::factory()->create();
        $pivot = EventUploadTrooper::factory()->forEventUpload($upload)->forTrooper($trooper)->create();

        (new DeleteEventUpload($upload))->handle();

        $this->assertDatabaseMissing('tt_event_upload_troopers', [EventUploadTrooper::ID => $pivot->id]);
    }

    public function test_handle_deletes_files_from_storage_when_paths_contain_slash(): void
    {
        Storage::fake('public');

        $lg_path = 'uploads/events/1/originals/photo.jpg';
        $sm_path = 'uploads/events/1/thumbnails/photo.png';

        Storage::disk('public')->put($lg_path, 'fake-content');
        Storage::disk('public')->put($sm_path, 'fake-content');

        $upload = EventUpload::factory()->create([
            EventUpload::IMAGE_PATH_LG => $lg_path,
            EventUpload::IMAGE_PATH_SM => $sm_path,
        ]);

        (new DeleteEventUpload($upload))->handle();

        Storage::disk('public')->assertMissing($lg_path);
        Storage::disk('public')->assertMissing($sm_path);
    }

    public function test_handle_skips_storage_delete_when_paths_have_no_slash(): void
    {
        Storage::fake('public');

        $upload = EventUpload::factory()->create([
            EventUpload::IMAGE_PATH_LG => 'plainfilename.jpg',
            EventUpload::IMAGE_PATH_SM => 'plainfilename.png',
        ]);

        (new DeleteEventUpload($upload))->handle();

        $this->assertDatabaseMissing('tt_event_uploads', [EventUpload::ID => $upload->id]);
    }
}
