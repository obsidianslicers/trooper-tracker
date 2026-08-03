<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Event;
use App\Models\Trooper;
use App\Services\EventImageUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * @see EventImageUploadService
 */
class EventImageUploadServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_stores_original_and_thumbnail_for_a_standard_image(): void
    {
        Storage::fake('public');

        $event = Event::factory()->create();
        $trooper = Trooper::factory()->asMember()->create();
        $file = UploadedFile::fake()->image('mission-review.jpg', 800, 600);

        $subject = new EventImageUploadService;
        $result = $subject->upload($event, $trooper->id, [$file], false);

        $this->assertSame(1, $result->uploads);
        $this->assertSame(0, $result->failures);
        $this->assertDatabaseHas('tt_event_uploads', [
            'event_id' => $event->id,
            'trooper_id' => $trooper->id,
            'is_administrative' => false,
        ]);
    }

    public function test_upload_reports_failure_for_undecodable_heic_data(): void
    {
        Storage::fake('public');

        $event = Event::factory()->create();
        $file = UploadedFile::fake()->create('phone-photo.heic', 2048, 'image/heic');

        $subject = new EventImageUploadService;
        $result = $subject->upload($event, 1, [$file], false);

        // The fake file's bytes are not a decodable HEIC image, so the upload
        // fails after the extension-based HEIC path is taken, regardless of
        // whether the Imagick extension is available in this environment.
        $this->assertSame(0, $result->uploads);
        $this->assertSame(1, $result->failures);
        $this->assertDatabaseMissing('tt_event_uploads', ['event_id' => $event->id]);
    }

    public function test_upload_leaves_no_orphaned_original_when_processing_fails(): void
    {
        Storage::fake('public');

        $event = Event::factory()->create();
        $file = UploadedFile::fake()->create('phone-photo.heic', 2048, 'image/heic');

        $subject = new EventImageUploadService;
        $subject->upload($event, 1, [$file], false);

        Storage::disk('public')->assertDirectoryEmpty("uploads/events/{$event->id}/originals");
    }
}
