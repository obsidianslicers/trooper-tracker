<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Events\Commands;

use App\Features\Events\Commands\DeleteEventUploadCommand;
use App\Features\Events\Commands\DeleteEventUploadCommandHandler;
use App\Models\Event;
use App\Models\EventUpload;
use App\Models\EventUploadTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * @see DeleteEventUploadCommandHandler
 */
class DeleteEventUploadCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private DeleteEventUploadCommandHandler $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = app(DeleteEventUploadCommandHandler::class);
    }

    public function test_invoke_deletes_the_upload(): void
    {
        $upload = EventUpload::factory()->create();

        ($this->subject)(new DeleteEventUploadCommand($upload));

        $this->assertDatabaseMissing('tt_event_uploads', [EventUpload::ID => $upload->id]);
    }

    public function test_invoke_deletes_pivot_rows_for_tagged_troopers(): void
    {
        $upload = EventUpload::factory()->create();
        $trooper = Trooper::factory()->create();
        $pivot = EventUploadTrooper::factory()->forEventUpload($upload)->forTrooper($trooper)->create();

        ($this->subject)(new DeleteEventUploadCommand($upload));

        $this->assertDatabaseMissing('tt_event_upload_troopers', [EventUploadTrooper::ID => $pivot->id]);
    }

    public function test_invoke_deletes_files_from_storage_when_paths_contain_slash(): void
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

        ($this->subject)(new DeleteEventUploadCommand($upload));

        Storage::disk('public')->assertMissing($lg_path);
        Storage::disk('public')->assertMissing($sm_path);
    }

    public function test_invoke_skips_storage_delete_when_paths_have_no_slash(): void
    {
        Storage::fake('public');

        $upload = EventUpload::factory()->create([
            EventUpload::IMAGE_PATH_LG => 'plainfilename.jpg',
            EventUpload::IMAGE_PATH_SM => 'plainfilename.png',
        ]);

        ($this->subject)(new DeleteEventUploadCommand($upload));

        $this->assertDatabaseMissing('tt_event_uploads', [EventUpload::ID => $upload->id]);
    }
}
