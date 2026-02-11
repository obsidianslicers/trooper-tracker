<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events;

use App\Models\Event;
use App\Models\EventUpload;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadImageControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        // Arrange
        Storage::fake('public');
        $event = Event::factory()->create();

        // Act
        $response = $this->post(route('events.upload-image', $event));

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_validates_images_array_required(): void
    {
        // Arrange
        Storage::fake('public');
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();

        // Act
        $response = $this->actingAs($trooper)->post(route('events.upload-image', $event), [
            'images' => [],
        ]);

        // Assert
        $response->assertOk();
        $this->assertDatabaseCount(EventUpload::class, 0);
    }

    public function test_invoke_validates_image_is_image_file(): void
    {
        // Arrange
        Storage::fake('public');
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();
        $file = UploadedFile::fake()->create('document.pdf', 100);

        // Act
        $response = $this->actingAs($trooper)->post(route('events.upload-image', $event), [
            'images' => [$file],
        ]);

        // Assert
        $response->assertSessionHasErrors(['images.0']);
    }

    public function test_invoke_validates_image_max_size(): void
    {
        // Arrange
        Storage::fake('public');
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();
        $file = UploadedFile::fake()->image('large.jpg')->size(5000);

        // Act
        $response = $this->actingAs($trooper)->post(route('events.upload-image', $event), [
            'images' => [$file],
        ]);

        // Assert
        $response->assertSessionHasErrors(['images.0']);
    }

    public function test_invoke_validates_allowed_mime_types(): void
    {
        // Arrange
        Storage::fake('public');
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();
        $file = UploadedFile::fake()->create('image.bmp', 100, 'image/bmp');

        // Act
        $response = $this->actingAs($trooper)->post(route('events.upload-image', $event), [
            'images' => [$file],
        ]);

        // Assert
        $response->assertSessionHasErrors(['images.0']);
    }

    public function test_invoke_creates_event_upload_record(): void
    {
        // Arrange
        Storage::fake('public');
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();
        $file = UploadedFile::fake()->image('photo.jpg', 400, 300);

        // Act
        $response = $this->actingAs($trooper)->post(route('events.upload-image', $event), [
            'images' => [$file],
        ]);

        // Assert
        $response->assertOk();
        $this->assertDatabaseHas(EventUpload::class, [
            EventUpload::TROOPER_ID => $trooper->id,
            EventUpload::EVENT_ID => $event->id,
            EventUpload::IS_ADMINISTRATIVE => false,
        ]);
    }

    public function test_invoke_stores_original_image_in_storage(): void
    {
        // Arrange
        Storage::fake('public');
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();
        $file = UploadedFile::fake()->image('photo.jpg', 400, 300);

        // Act
        $response = $this->actingAs($trooper)->post(route('events.upload-image', $event), [
            'images' => [$file],
        ]);

        // Assert
        $response->assertOk();
        $upload = EventUpload::where(EventUpload::EVENT_ID, $event->id)->first();
        Storage::disk('public')->assertExists($upload->image_path_lg);
    }

    public function test_invoke_stores_thumbnail_in_storage(): void
    {
        // Arrange
        Storage::fake('public');
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();
        $file = UploadedFile::fake()->image('photo.jpg', 400, 300);

        // Act
        $response = $this->actingAs($trooper)->post(route('events.upload-image', $event), [
            'images' => [$file],
        ]);

        // Assert
        $response->assertOk();
        $upload = EventUpload::where(EventUpload::EVENT_ID, $event->id)->first();
        Storage::disk('public')->assertExists($upload->image_path_sm);
    }

    public function test_invoke_stores_images_in_event_specific_directory(): void
    {
        // Arrange
        Storage::fake('public');
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();
        $file = UploadedFile::fake()->image('photo.jpg', 400, 300);

        // Act
        $response = $this->actingAs($trooper)->post(route('events.upload-image', $event), [
            'images' => [$file],
        ]);

        // Assert
        $response->assertOk();
        $upload = EventUpload::where(EventUpload::EVENT_ID, $event->id)->first();
        $this->assertStringContainsString("uploads/events/{$event->id}/originals", $upload->image_path_lg);
        $this->assertStringContainsString("uploads/events/{$event->id}/thumbnails", $upload->image_path_sm);
    }

    public function test_invoke_accepts_png_format(): void
    {
        // Arrange
        Storage::fake('public');
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();
        $file = UploadedFile::fake()->image('photo.png', 400, 300);

        // Act
        $response = $this->actingAs($trooper)->post(route('events.upload-image', $event), [
            'images' => [$file],
        ]);

        // Assert
        $response->assertOk();
        $this->assertDatabaseHas(EventUpload::class, [
            EventUpload::EVENT_ID => $event->id,
        ]);
    }

    public function test_invoke_accepts_jpeg_format(): void
    {
        // Arrange
        Storage::fake('public');
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();
        $file = UploadedFile::fake()->image('photo.jpeg', 400, 300);

        // Act
        $response = $this->actingAs($trooper)->post(route('events.upload-image', $event), [
            'images' => [$file],
        ]);

        // Assert
        $response->assertOk();
        $this->assertDatabaseHas(EventUpload::class, [
            EventUpload::EVENT_ID => $event->id,
        ]);
    }

    public function test_invoke_accepts_webp_format(): void
    {
        // Arrange
        Storage::fake('public');
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();
        $file = UploadedFile::fake()->image('photo.webp', 400, 300);

        // Act
        $response = $this->actingAs($trooper)->post(route('events.upload-image', $event), [
            'images' => [$file],
        ]);

        // Assert
        $response->assertOk();
        $this->assertDatabaseHas(EventUpload::class, [
            EventUpload::EVENT_ID => $event->id,
        ]);
    }

    public function test_invoke_handles_multiple_images(): void
    {
        // Arrange
        Storage::fake('public');
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();
        $file1 = UploadedFile::fake()->image('photo1.jpg', 400, 300);
        $file2 = UploadedFile::fake()->image('photo2.jpg', 400, 300);
        $file3 = UploadedFile::fake()->image('photo3.jpg', 400, 300);

        // Act
        $response = $this->actingAs($trooper)->post(route('events.upload-image', $event), [
            'images' => [$file1, $file2, $file3],
        ]);

        // Assert
        $response->assertOk();
        $this->assertDatabaseCount(EventUpload::class, 3);
    }

    public function test_invoke_returns_view_with_event_data(): void
    {
        // Arrange
        Storage::fake('public');
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();
        $file = UploadedFile::fake()->image('photo.jpg', 400, 300);

        // Act
        $response = $this->actingAs($trooper)->post(route('events.upload-image', $event), [
            'images' => [$file],
        ]);

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.events.inc.upload-display');
        $response->assertViewHas('event');
        $response->assertViewHas('is_administrative', false);
    }

    public function test_invoke_returns_flash_message_header_for_single_upload(): void
    {
        // Arrange
        Storage::fake('public');
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();
        $file = UploadedFile::fake()->image('photo.jpg', 400, 300);

        // Act
        $response = $this->actingAs($trooper)->post(route('events.upload-image', $event), [
            'images' => [$file],
        ]);

        // Assert
        $response->assertOk();
        $this->assertTrue($response->headers->has('X-Flash-Message'));
        $flash = json_decode($response->headers->get('X-Flash-Message'), true);
        $this->assertEquals('Image Uploaded!', $flash['message']);
        $this->assertEquals('success', $flash['type']);
    }

    public function test_invoke_returns_flash_message_header_for_multiple_uploads(): void
    {
        // Arrange
        Storage::fake('public');
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();
        $file1 = UploadedFile::fake()->image('photo1.jpg', 400, 300);
        $file2 = UploadedFile::fake()->image('photo2.jpg', 400, 300);

        // Act
        $response = $this->actingAs($trooper)->post(route('events.upload-image', $event), [
            'images' => [$file1, $file2],
        ]);

        // Assert
        $response->assertOk();
        $this->assertTrue($response->headers->has('X-Flash-Message'));
        $flash = json_decode($response->headers->get('X-Flash-Message'), true);
        $this->assertEquals('2 Images Uploaded!', $flash['message']);
        $this->assertEquals('success', $flash['type']);
    }

    public function test_invoke_associates_upload_with_authenticated_trooper(): void
    {
        // Arrange
        Storage::fake('public');
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();
        $file = UploadedFile::fake()->image('photo.jpg', 400, 300);

        // Act
        $response = $this->actingAs($trooper)->post(route('events.upload-image', $event), [
            'images' => [$file],
        ]);

        // Assert
        $response->assertOk();
        $upload = EventUpload::where(EventUpload::EVENT_ID, $event->id)->first();
        $this->assertEquals($trooper->id, $upload->trooper_id);
    }

    public function test_invoke_sets_is_administrative_to_false(): void
    {
        // Arrange
        Storage::fake('public');
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();
        $file = UploadedFile::fake()->image('photo.jpg', 400, 300);

        // Act
        $response = $this->actingAs($trooper)->post(route('events.upload-image', $event), [
            'images' => [$file],
        ]);

        // Assert
        $response->assertOk();
        $upload = EventUpload::where(EventUpload::EVENT_ID, $event->id)->first();
        $this->assertFalse($upload->is_administrative);
    }

    public function test_invoke_stores_thumbnail_as_png(): void
    {
        // Arrange
        Storage::fake('public');
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();
        $file = UploadedFile::fake()->image('photo.jpg', 400, 300);

        // Act
        $response = $this->actingAs($trooper)->post(route('events.upload-image', $event), [
            'images' => [$file],
        ]);

        // Assert
        $response->assertOk();
        $upload = EventUpload::where(EventUpload::EVENT_ID, $event->id)->first();
        $this->assertStringEndsWith('.png', $upload->image_path_sm);
    }
}
