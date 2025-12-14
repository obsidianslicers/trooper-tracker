<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Scopes;

use App\Models\Event;
use App\Models\EventUpload;
use App\Models\EventUploadTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HasEventUploadScopesTest extends TestCase
{
    use RefreshDatabase;

    public function test_scope_by_event_filters_uploads_for_specific_event(): void
    {
        // Arrange
        $event = Event::factory()->create();
        $other_event = Event::factory()->create();

        $upload_for_event = EventUpload::factory()->for($event)->create();
        $upload_for_other_event = EventUpload::factory()->for($other_event)->create();

        // Act
        $result = EventUpload::byEvent($event->id)->get();

        // Assert
        $this->assertCount(1, $result);
        $this->assertTrue($result->contains($upload_for_event));
        $this->assertFalse($result->contains($upload_for_other_event));
    }

    public function test_scope_by_event_eager_loads_troopers(): void
    {
        // Arrange
        $event = Event::factory()->create();
        $upload = EventUpload::factory()->for($event)->create();
        $trooper = Trooper::factory()->create();

        EventUploadTrooper::factory()->for($upload, 'event_upload')->for($trooper)->create();

        // Act
        $result = EventUpload::byEvent($event->id)->first();

        // Assert
        $this->assertTrue($result->relationLoaded('troopers'));
    }

    public function test_scope_by_trooper_filters_uploads_tagged_with_trooper(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $other_trooper = Trooper::factory()->create();

        $upload_with_trooper = EventUpload::factory()->create();
        EventUploadTrooper::factory()->for($upload_with_trooper, 'event_upload')->for($trooper)->create();

        $upload_with_other_trooper = EventUpload::factory()->create();
        EventUploadTrooper::factory()->for($upload_with_other_trooper, 'event_upload')->for($other_trooper)->create();

        $upload_without_trooper = EventUpload::factory()->create();

        // Act
        $result = EventUpload::byTrooper($trooper->id)->get();

        // Assert
        $this->assertCount(1, $result);
        $this->assertTrue($result->contains($upload_with_trooper));
        $this->assertFalse($result->contains($upload_with_other_trooper));
        $this->assertFalse($result->contains($upload_without_trooper));
    }

    public function test_scope_by_trooper_eager_loads_troopers(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $upload = EventUpload::factory()->create();
        EventUploadTrooper::factory()->for($upload, 'event_upload')->for($trooper)->create();

        // Act
        $result = EventUpload::byTrooper($trooper->id)->first();

        // Assert
        $this->assertTrue($result->relationLoaded('troopers'));
    }

    public function test_scope_by_trooper_returns_empty_collection_when_no_uploads(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();

        // Act
        $result = EventUpload::byTrooper($trooper->id)->get();

        // Assert
        $this->assertCount(0, $result);
    }
}
