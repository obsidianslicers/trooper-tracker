<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events;

use App\Models\Event;
use App\Models\EventUpload;
use App\Models\Trooper;
use App\Services\EventImageUploadResult;
use App\Services\EventImageUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadImageControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_mission_review_upload_stores_image_and_returns_flash_header(): void
    {
        Storage::fake('public');

        $trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();
        $event = Event::factory()->create();
        $file = UploadedFile::fake()->image('mission-review.jpg', 800, 600)->size(2048);

        $response = $this->actingAs($trooper)
            ->withSession(['_token' => 'test-token'])
            ->withHeader('HX-Request', 'true')
            ->withHeader('X-CSRF-TOKEN', 'test-token')
            ->post(route('events.upload-image', $event), [
                'images' => [$file],
            ]);

        $response->assertOk();
        $response->assertHeader('X-Flash-Message');
        $response->assertSee('event-uploads', false);

        $upload = EventUpload::query()->firstOrFail();

        $this->assertFalse($upload->is_administrative);
        $this->assertSame($event->id, $upload->event_id);
        $this->assertSame($trooper->id, $upload->trooper_id);
        Storage::disk('public')->assertExists($upload->image_path_lg);
        Storage::disk('public')->assertExists($upload->image_path_sm);
    }

    public function test_oversized_image_returns_visible_htmx_error(): void
    {
        Storage::fake('public');

        $trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();
        $event = Event::factory()->create();
        $file = UploadedFile::fake()->image('too-large.jpg')->size(12289);

        $response = $this->actingAs($trooper)
            ->withSession(['_token' => 'test-token'])
            ->withHeader('HX-Request', 'true')
            ->withHeader('X-CSRF-TOKEN', 'test-token')
            ->post(route('events.upload-image', $event), [
                'images' => [$file],
            ]);

        $response->assertUnprocessable();
        $response->assertHeader('X-Flash-Message');
        $this->assertStringContainsString('12MB', (string) $response->headers->get('X-Flash-Message'));
        $this->assertSame(0, EventUpload::query()->count());
    }

    public function test_post_too_large_request_returns_visible_htmx_error(): void
    {
        $trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($trooper)
            ->withSession(['_token' => 'test-token'])
            ->withHeader('HX-Request', 'true')
            ->withHeader('X-CSRF-TOKEN', 'test-token')
            ->withServerVariables([
                'CONTENT_LENGTH' => (string) (13 * 1024 * 1024),
            ])
            ->post(route('events.upload-image', $event));

        $response->assertStatus(413);
        $response->assertHeader('X-Flash-Message');
        $this->assertStringContainsString('too large for the server', (string) $response->headers->get('X-Flash-Message'));
        $this->assertSame(0, EventUpload::query()->count());
    }

    public function test_heic_upload_returns_visible_htmx_error(): void
    {
        Storage::fake('public');

        $trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();
        $event = Event::factory()->create();
        $file = UploadedFile::fake()->create('phone-photo.heic', 2048, 'image/heic');

        $response = $this->actingAs($trooper)
            ->withSession(['_token' => 'test-token'])
            ->withHeader('HX-Request', 'true')
            ->withHeader('X-CSRF-TOKEN', 'test-token')
            ->post(route('events.upload-image', $event), [
                'images' => [$file],
            ]);

        $response->assertUnprocessable();
        $response->assertHeader('X-Flash-Message');
        $this->assertStringContainsString('HEIC', (string) $response->headers->get('X-Flash-Message'));
        $this->assertSame(0, EventUpload::query()->count());
    }

    public function test_partial_processing_failure_returns_warning_flash(): void
    {
        $trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();
        $event = Event::factory()->create();
        $files = [
            UploadedFile::fake()->image('good.jpg')->size(1024),
            UploadedFile::fake()->image('also-valid.png')->size(1024),
        ];

        $this->mock(EventImageUploadService::class, function ($mock): void {
            $mock->shouldReceive('upload')
                ->once()
                ->andReturn(new EventImageUploadResult(1, 1));
        });

        $response = $this->actingAs($trooper)
            ->withSession(['_token' => 'test-token'])
            ->withHeader('HX-Request', 'true')
            ->withHeader('X-CSRF-TOKEN', 'test-token')
            ->post(route('events.upload-image', $event), [
                'images' => $files,
            ]);

        $response->assertOk();
        $response->assertHeader('X-Flash-Message');
        $flash = (string) $response->headers->get('X-Flash-Message');
        $this->assertStringContainsString('1 image uploaded', $flash);
        $this->assertStringContainsString('1 image failed', $flash);
        $this->assertStringContainsString('warning', $flash);
    }

    public function test_invoke_requires_authentication(): void
    {
        $event = Event::factory()->create();
        $file = UploadedFile::fake()->image('troop.png');

        $response = $this->withSession(['_token' => 'test-token'])
            ->withHeader('X-CSRF-TOKEN', 'test-token')
            ->post(route('events.upload-image', ['event' => $event->id]), [
                'images' => [$file],
            ]);

        $response->assertRedirect(route('auth.login'));
    }
}
