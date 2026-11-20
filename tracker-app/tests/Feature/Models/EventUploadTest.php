<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\EventUpload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EventUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_can_create_event_upload(): void
    {
        $subject = EventUpload::factory()->create();

        $this->assertInstanceOf(EventUpload::class, $subject);
        $this->assertDatabaseHas($subject->getTable(), [
            'id' => $subject->getKey(),
        ]);
    }

    public function test_get_small_url_attribute_returns_storage_url_when_path_contains_slash(): void
    {
        $subject = EventUpload::factory()
            ->state([EventUpload::IMAGE_PATH_SM => 'event-photos/small/photo.jpg'])
            ->create();

        $result = $subject->small_url;

        $this->assertStringContainsString('event-photos/small/photo.jpg', $result);
    }

    public function test_get_small_url_attribute_returns_legacy_url_when_path_no_slash(): void
    {
        $subject = EventUpload::factory()
            ->state([EventUpload::IMAGE_PATH_SM => 'photo.jpg'])
            ->create();

        $result = $subject->small_url;

        $this->assertStringContainsString('images/uploads/photo.jpg', $result);
    }

    public function test_get_large_url_attribute_returns_storage_url_when_path_contains_slash(): void
    {
        $subject = EventUpload::factory()
            ->state([EventUpload::IMAGE_PATH_LG => 'event-photos/large/photo.jpg'])
            ->create();

        $result = $subject->large_url;

        $this->assertStringContainsString('event-photos/large/photo.jpg', $result);
    }

    public function test_get_large_url_attribute_returns_legacy_url_when_path_no_slash(): void
    {
        $subject = EventUpload::factory()
            ->state([EventUpload::IMAGE_PATH_LG => 'photo.jpg'])
            ->create();

        $result = $subject->large_url;

        $this->assertStringContainsString('images/uploads/photo.jpg', $result);
    }

    public function test_has_original_file_checks_the_public_disk_for_paths(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('uploads/events/1/originals/photo.jpg', 'x');

        $kept = EventUpload::factory()->create([EventUpload::IMAGE_PATH_LG => 'uploads/events/1/originals/photo.jpg']);
        $gone = EventUpload::factory()->create([EventUpload::IMAGE_PATH_LG => 'uploads/events/1/originals/gone.jpg']);

        $this->assertTrue($kept->hasOriginalFile());
        $this->assertFalse($gone->hasOriginalFile());
    }

    public function test_has_original_file_checks_the_legacy_folder_for_bare_filenames(): void
    {
        Storage::fake('legacy_uploads');
        Storage::disk('legacy_uploads')->put('photo.jpg', 'x');

        $kept = EventUpload::factory()->create([EventUpload::IMAGE_PATH_LG => 'photo.jpg']);
        $gone = EventUpload::factory()->create([EventUpload::IMAGE_PATH_LG => 'gone.jpg']);

        $this->assertTrue($kept->hasOriginalFile());
        $this->assertFalse($gone->hasOriginalFile());
    }

    public function test_display_url_falls_back_to_the_large_url_without_a_legacy_resized_copy(): void
    {
        Storage::fake('legacy_uploads');

        $subject = EventUpload::factory()->create([EventUpload::IMAGE_PATH_LG => 'photo.jpeg']);

        $this->assertSame($subject->large_url, $subject->display_url);
    }
}
