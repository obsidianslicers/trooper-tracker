<?php

namespace Tests\Unit\Models;

use App\Models\EventUpload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see \App\Models\EventUpload
 */
class EventUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_url_attribute_returns_filename_when_it_contains_slash(): void
    {
        // Arrange
        $event_upload = EventUpload::factory()->make([
            'filename' => 'https://example.com/images/photo.jpg',
        ]);

        // Act
        $url = $event_upload->url;

        // Assert
        $this->assertSame('https://example.com/images/photo.jpg', $url);
    }

    public function test_url_attribute_prepends_path_when_filename_has_no_slash(): void
    {
        // Arrange
        $event_upload = EventUpload::factory()->make([
            'filename' => 'photo.jpg',
        ]);

        // Act
        $url = $event_upload->url;

        // Assert
        $this->assertStringEndsWith('images/uploads/photo.jpg', $url);
        $this->assertStringStartsWith('http', $url);
    }
}
