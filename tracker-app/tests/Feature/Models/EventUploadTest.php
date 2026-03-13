<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\EventUpload;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}